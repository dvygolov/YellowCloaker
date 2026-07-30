<?php

require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/macros.php';
require_once __DIR__ . '/requestfunc.php';

final class ConversionService
{
    public function __construct(private readonly Db $db)
    {
    }

    /** @return array{accepted:bool,code:string,message:string,status?:string,click?:array,conversion_id?:int} */
    public function record(
        Campaign $campaign,
        string $clickid,
        string $rawStatus,
        string $source,
        mixed $payoutRaw = null,
        string $currency = 'USD',
        ?string $tid = null,
        ?string $tidParameter = null
    ): array {
        $clickid = trim($clickid);
        $rawStatus = trim($rawStatus);
        $source = trim($source);
        $tid = ($tid === null || trim($tid) === '') ? null : trim($tid);
        $tidParameter = $tid === null ? null : trim($tidParameter ?? 'tid');

        if ($clickid === '') {
            return $this->reject('missing_clickid', 'No clickid found.');
        }
        if (!in_array($source, ['postback', 'form_submit', 'site_script'], true)) {
            return $this->reject('invalid_source', 'Unknown conversion source.');
        }
        if (
            $tid !== null
            && (
                strlen($tid) > ConversionSettings::MAX_TRANSACTION_ID_VALUE_BYTES
                || preg_match('/[\x00-\x1F\x7F]/', $tid) === 1
                || $tidParameter === ''
                || !in_array($tidParameter, $campaign->conversions->transactionIdParameters, true)
            )
        ) {
            return $this->reject('invalid_tid', 'Invalid transaction ID or parameter.');
        }
        $status = $campaign->conversions->resolveStatus($rawStatus);
        if ($status === null) {
            add_log('warning', "Unknown conversion status '{$rawStatus}' for campaign {$campaign->campaignId}");
            return $this->reject('unknown_status', "Status {$rawStatus} is unknown.");
        }

        $payoutProvided = $payoutRaw !== null && trim((string)$payoutRaw) !== '';
        if ($payoutProvided && !is_numeric((string)$payoutRaw)) {
            return $this->reject('invalid_payout', 'Payout must be numeric.');
        }
        $originalPayout = $payoutProvided ? (float)$payoutRaw : 0.0;
        if (!is_finite($originalPayout) || $originalPayout < 0) {
            return $this->reject('invalid_payout', 'Payout must be a finite non-negative number.');
        }
        $currency = strtoupper(trim($currency)) ?: 'USD';
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            return $this->reject('invalid_currency', 'Currency must be a three-letter ISO code.');
        }

        try {
            $payout = $originalPayout == 0.0 ? 0.0 : CurrencyConverter::convert($originalPayout, $currency);
        } catch (Throwable $e) {
            add_log('warning', 'Conversion currency error: ' . $e->getMessage());
            return $this->reject('currency_error', 'Failed to convert payout currency.');
        }

        $result = $this->db->record_conversion(
            $clickid,
            $campaign->campaignId,
            $status,
            $rawStatus,
            $source,
            $tid,
            $tidParameter,
            (float)$payout,
            $currency,
            $payoutProvided,
            $campaign->conversions->tidDeduplicationEnabled,
            $campaign->conversions->paidRepeatWithoutTid
        );

        if (!($result['accepted'] ?? false)) {
            add_log('warning', sprintf(
                'Conversion rejected: campaign=%d clickid=%s status=%s code=%s',
                $campaign->campaignId,
                $clickid,
                $status,
                (string)($result['code'] ?? 'rejected')
            ));
            return $result;
        }

        $click = is_array($result['click'] ?? null) ? $result['click'] : [];
        process_conversion_s2s_postbacks($campaign->postback->s2sPostbacks, $status, $click);
        add_log('postback', sprintf(
            'Conversion accepted: campaign=%d clickid=%s status=%s payout=%s currency=%s source=%s',
            $campaign->campaignId,
            $clickid,
            $status,
            $payout,
            $currency,
            $source
        ));
        return $result + ['status' => $status];
    }

    private function reject(string $code, string $message): array
    {
        return ['accepted' => false, 'code' => $code, 'message' => $message];
    }
}

final class ConversionCapEvaluator
{
    public static function matches(Db $db, Campaign $campaign, FlowSettings $flow, string $kind, array $filter): bool
    {
        if (!in_array($kind, ['conversion_cap_campaign', 'conversion_cap_flow'], true)) {
            return true;
        }
        $value = is_array($filter['value'] ?? null) ? $filter['value'] : [];
        $statuses = is_array($value['statuses'] ?? null) ? $value['statuses'] : [];
        $limit = (int)($value['limit'] ?? 0);
        if ($statuses === [] || $limit < 0) {
            return false;
        }

        try {
            $timezone = new DateTimeZone($campaign->statistics->timezone);
        } catch (Throwable) {
            $timezone = new DateTimeZone('UTC');
        }
        $start = (new DateTimeImmutable('now', $timezone))->setTime(0, 0, 0);
        $end = $start->modify('+1 day');
        $stopAfter = $limit === PHP_INT_MAX ? PHP_INT_MAX : $limit + 1;
        $count = $db->count_conversions_for_cap(
            $campaign->campaignId,
            $kind === 'conversion_cap_flow' ? $flow->name : null,
            $statuses,
            $start->getTimestamp(),
            $end->getTimestamp(),
            $stopAfter
        );
        return match ($filter['operator'] ?? '') {
            'less' => $count < $limit,
            'less_or_equal' => $count <= $limit,
            'equal' => $count === $limit,
            'not_equal' => $count !== $limit,
            'greater_or_equal' => $count >= $limit,
            'greater' => $count > $limit,
            default => false,
        };
    }
}

function process_conversion_s2s_postbacks(array $s2sPostbacks, string $status, array $click): void
{
    if ($click === []) {
        return;
    }
    $clickid = (string)($click['clickid'] ?? '');
    $userid = (string)($click['userid'] ?? '');
    $macros = new MacrosProcessor(null, $click, $clickid, $userid);

    foreach ($s2sPostbacks as $index => $s2s) {
        if (empty($s2s->url) || !in_array($status, $s2s->statuses, true)) {
            continue;
        }
        $method = strtoupper((string)$s2s->method);
        $baseContext = [
            'rule_number' => $index + 1,
            'trigger_type' => 'conversion',
            'method' => $method,
            'campaign_id' => isset($click['campaign_id']) ? (int)$click['campaign_id'] : null,
            'clickid' => $clickid,
            'status' => $status,
        ];

        try {
            $finalUrl = $macros->replace_url_macros(str_replace('{status}', $status, $s2s->url));
            $response = match ($method) {
                'GET' => get($finalUrl),
                'POST' => conversion_s2s_post($finalUrl),
                default => null,
            };
        } catch (Throwable $exception) {
            ytds_log_postback(
                'outgoing',
                'failed',
                'Outgoing conversion S2S postback failed',
                $baseContext + ['error' => $exception->getMessage()]
            );
            continue;
        }

        if (!is_array($response)) {
            ytds_log_postback(
                'outgoing',
                'failed',
                'Outgoing conversion S2S postback failed',
                $baseContext + ['url' => $finalUrl, 'error' => 'Unsupported S2S method']
            );
            continue;
        }

        $httpCode = (int)($response['info']['http_code'] ?? 0);
        $transportError = trim((string)($response['error'] ?? ''));
        $delivered = $transportError === '' && $httpCode >= 200 && $httpCode < 300;
        $context = $baseContext + [
            'url' => $finalUrl,
            'http_code' => $httpCode,
            'response_body' => conversion_s2s_response_excerpt($response['content'] ?? ''),
        ];
        if ($transportError !== '') {
            $context['error'] = $transportError;
        }
        ytds_log_postback(
            'outgoing',
            $delivered ? 'delivered' : 'failed',
            $delivered ? 'Outgoing conversion S2S postback delivered' : 'Outgoing conversion S2S postback failed',
            array_filter($context, static fn(mixed $value): bool => $value !== null && $value !== '')
        );
    }
}

function conversion_s2s_post(string $url): array
{
    $parts = explode('?', $url, 2);
    $params = [];
    if (isset($parts[1])) {
        parse_str($parts[1], $params);
    }
    return post($parts[0], $params);
}

function conversion_s2s_response_excerpt(mixed $content, int $limit = 2048): string
{
    if (!is_string($content) || $content === '') {
        return '';
    }
    if (strlen($content) <= $limit) {
        return $content;
    }
    return substr($content, 0, $limit) . '…';
}
