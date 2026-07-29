<?php

require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/uniqueness.php';

class Campaign implements JsonSerializable
{
    public int $campaignId;
    public array $domains;
    public bool $saveUserFlow;
    public string $apiKey;

    public WhiteSettings $white;
    public BlackSettings $black;
    public ScriptsSettings $scripts;
    public EventSettings $events;
    public ConversionSettings $conversions;
    public PostbackSettings $postback;
    public StatisticsSettings $statistics;
    public UniquenessSettings $uniqueness;

    public function __construct(int $campId, array $s)
    {
        $this->campaignId = $campId;

        $this->domains = $s['domains'];
        $this->saveUserFlow = $s['saveuserflow'];
        $this->apiKey = $s['apikey'];

        $this->white = WhiteSettings::fromArray($s['white']);
        $this->black = BlackSettings::fromArray($s['black']);

        $this->scripts = ScriptsSettings::fromArray($s['scripts']);
        $this->events = EventSettings::fromArray($s['events'] ?? []);
        $this->conversions = ConversionSettings::fromArray($s['conversions'] ?? []);
        $this->postback = PostbackSettings::fromArray($s['postback']);
        $this->statistics = StatisticsSettings::fromArray($s['statistics']);
        $this->uniqueness = UniquenessSettings::fromArray($s['uniqueness'] ?? []);
    }

    function jsonSerialize(): array
    {
        return [
            "domains" => $this->domains,
            "saveuserflow" => $this->saveUserFlow,
            "apikey" => $this->apiKey,
            "white" => $this->white,
            "black" => $this->black,
            "statistics" => $this->statistics,
            "conversions" => $this->conversions,
            "postback" => $this->postback,
            "scripts" => $this->scripts,
            "events" => $this->events,
            "uniqueness" => $this->uniqueness
        ];
    }
}

class WhiteSettings implements JsonSerializable
{
    public array $filters;
    public string $action;
    public array $folderNames;
    public array $redirectUrls;
    public int $redirectType;
    public array $curlUrls;
    public array $errorCodes;
    public bool $domainFilterEnabled;
    public array $domainSpecific;
    public array $loadMode;

    public static function fromArray(array $s): WhiteSettings
    {
        $ws = new WhiteSettings();
        $ws->filters = $s['filters'] ?? [];
        $ws->action = $s['action'];
        $ws->folderNames = $s['folders'];
        $ws->redirectUrls = $s['redirect']['urls'];
        $ws->redirectType = $s['redirect']['type'];
        $ws->curlUrls = $s['curls'];
        $ws->errorCodes = $s['errorcodes'];
        $ws->domainFilterEnabled = $s['domainfilter']['use'];

        $ws->domainSpecific = [];
        foreach ($s['domainfilter']['domains'] as $df) {
            $ws->domainSpecific[] = DomainWhiteSettings::fromArray($df);
        }

        $ws->loadMode = $s['loadmode'] ?? [];
        return $ws;
    }

    public function isDirectLoad(string $name): bool
    {
        return ($this->loadMode[$name] ?? '') === 'direct';
    }

    public function getLoadMode(string $name): string
    {
        return $this->loadMode[$name] ?? 'base';
    }

    public function jsonSerialize(): array
    {
        return [
            "filters" => $this->filters,
            "action" => $this->action,
            "folders" => $this->folderNames,
            "redirect" => [
                "urls" => $this->redirectUrls,
                "type" => $this->redirectType
            ],
            "curls" => $this->curlUrls,
            "errorcodes" => $this->errorCodes,
            "domainfilter" => [
                "use" => $this->domainFilterEnabled,
                "domains" => $this->domainSpecific
            ],
            "loadmode" => $this->loadMode
        ];
    }
}

class DomainWhiteSettings implements JsonSerializable
{
    public string $domain;
    public string $action;
    public array $folderNames;
    public array $redirectUrls;
    public int $redirectType;
    public array $curlUrls;
    public array $errorCodes;
    public array $loadMode;

    public static function fromArray(array $arr): DomainWhiteSettings
    {
        $dw = new DomainWhiteSettings();
        $dw->domain = $arr['domain'] ?? $arr['name'] ?? '';
        $dw->action = $arr['action'] ?? 'folder';
        $dw->folderNames = $arr['folders'] ?? [];
        $dw->redirectUrls = $arr['redirect']['urls'] ?? [];
        $dw->redirectType = $arr['redirect']['type'] ?? 302;
        $dw->curlUrls = $arr['curls'] ?? [];
        $dw->errorCodes = $arr['errorcodes'] ?? [];
        $dw->loadMode = $arr['loadmode'] ?? [];
        return $dw;
    }

    public function getLoadMode(string $name): string
    {
        return $this->loadMode[$name] ?? 'base';
    }

    public function jsonSerialize(): array
    {
        return [
            "domain" => $this->domain,
            "action" => $this->action,
            "folders" => $this->folderNames,
            "redirect" => [
                "urls" => $this->redirectUrls,
                "type" => $this->redirectType
            ],
            "curls" => $this->curlUrls,
            "errorcodes" => $this->errorCodes,
            "loadmode" => $this->loadMode
        ];
    }
}

class BlackSettings implements JsonSerializable
{
    public string $jsconnectAction;
    public JsBotDetection $jsBotDetection;
    /** @var FlowSettings[] */
    public array $flows;

    public static function fromArray($arr): BlackSettings
    {
        $bs = new BlackSettings();
        $bs->jsconnectAction = $arr['jsconnect'];
        $bs->jsBotDetection = JsBotDetection::fromArray($arr['jsbotdetection']);
        $bs->flows = [];
        foreach ($arr['flows'] as $f) {
            $bs->flows[] = FlowSettings::fromArray($f);
        }
        return $bs;
    }

    public function jsonSerialize(): array
    {
        return [
            "jsconnect" => $this->jsconnectAction,
            "jsbotdetection" => $this->jsBotDetection,
            "flows" => $this->flows
        ];
    }
}

class FlowSettings implements JsonSerializable
{
    public string $name;
    public array $filters;
    /** @var StepSettings[] */
    public array $steps;
    public string $distribution;
    public string $optimize_for;
    public string $optimize_mode;

    public static function fromArray($arr): FlowSettings
    {
        $fs = new FlowSettings();
        $fs->name = $arr['name'] ?? 'Flow';
        $fs->filters = $arr['filters'] ?? [];
        $fs->steps = [];
        foreach (($arr['steps'] ?? []) as $s) {
            $fs->steps[] = StepSettings::fromArray($s);
        }
        $fs->distribution = $arr['distribution'] ?? 'equal';
        $fs->optimize_for = $arr['optimize_for'] ?? 'Lead';
        $fs->optimize_mode = $arr['optimize_mode'] ?? 'funnels';
        return $fs;
    }

    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "filters" => $this->filters,
            "steps" => $this->steps,
            "distribution" => $this->distribution,
            "optimize_for" => $this->optimize_for,
            "optimize_mode" => $this->optimize_mode
        ];
    }

    public function hasMultipleSteps(): bool
    {
        return count($this->steps) > 1;
    }
}

final class MvtValueSettings implements JsonSerializable
{
    public bool $active;
    public string $value;

    public static function fromArray(array $arr): self
    {
        $settings = new self();
        $settings->active = (bool)($arr['active'] ?? true);
        $settings->value = (string)($arr['value'] ?? '');
        return $settings;
    }

    public function jsonSerialize(): array
    {
        return [
            'active' => $this->active,
            'value' => $this->value,
        ];
    }
}

final class MvtTestSettings implements JsonSerializable
{
    public bool $active;
    /** @var MvtValueSettings[] */
    public array $values;

    public static function fromArray(array $arr): self
    {
        $settings = new self();
        $settings->active = (bool)($arr['active'] ?? true);
        $settings->values = [];
        foreach (($arr['values'] ?? []) as $value) {
            if (is_array($value)) {
                $settings->values[] = MvtValueSettings::fromArray($value);
            }
        }
        return $settings;
    }

    public function jsonSerialize(): array
    {
        return [
            'active' => $this->active,
            'values' => $this->values,
        ];
    }
}

final class MvtSettings implements JsonSerializable
{
    public bool $enabled;
    /** @var MvtTestSettings[] */
    public array $tests;

    public static function fromArray(array $arr): self
    {
        $settings = new self();
        $settings->enabled = (bool)($arr['enabled'] ?? false);
        $settings->tests = [];
        foreach (($arr['tests'] ?? []) as $test) {
            if (is_array($test)) {
                $settings->tests[] = MvtTestSettings::fromArray($test);
            }
        }
        return $settings;
    }

    public function jsonSerialize(): array
    {
        return [
            'enabled' => $this->enabled,
            'tests' => $this->tests,
        ];
    }

    public static function valueCode(int $index): string
    {
        if ($index < 0) {
            return '';
        }
        $code = '';
        do {
            $code = chr(65 + ($index % 26)) . $code;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);
        return $code;
    }

    public static function valueIndex(string $code): ?int
    {
        $code = strtoupper(trim($code));
        if ($code === '' || preg_match('/^[A-Z]+$/', $code) !== 1) {
            return null;
        }
        $number = 0;
        foreach (str_split($code) as $char) {
            $number = $number * 26 + (ord($char) - 64);
        }
        return $number - 1;
    }
}

final class StepFolderSettings implements JsonSerializable
{
    public string $name;
    public string $loadType;
    public int $weight;
    public MvtSettings $mvt;

    public static function fromArray(array $arr): self
    {
        $settings = new self();
        $settings->name = trim((string)($arr['name'] ?? ''));
        $loadType = (string)($arr['loadtype'] ?? 'base');
        $settings->loadType = in_array($loadType, ['base', 'direct'], true)
            ? $loadType
            : 'base';
        $settings->weight = max(0, (int)($arr['weight'] ?? 0));
        $settings->mvt = MvtSettings::fromArray(
            is_array($arr['mvt'] ?? null) ? $arr['mvt'] : []
        );
        return $settings;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'loadtype' => $this->loadType,
            'weight' => $this->weight,
            'mvt' => $this->mvt,
        ];
    }
}

class StepSettings implements JsonSerializable
{
    public string $action;
    /** @var StepFolderSettings[] */
    public array $folders;
    /** @var array<array{url: string, label: string, weight: int}> */
    public array $redirectUrls;
    public int $redirectType;

    public static function fromArray($arr): StepSettings
    {
        $ss = new StepSettings();
        $ss->action = $arr['action'] ?? 'folder';
        $ss->folders = [];
        foreach (($arr['folders'] ?? []) as $folder) {
            if (!is_array($folder)) {
                continue;
            }
            $parsed = StepFolderSettings::fromArray($folder);
            if ($parsed->name !== '') {
                $ss->folders[] = $parsed;
            }
        }
        $ss->redirectUrls = [];
        foreach (($arr['redirect']['urls'] ?? []) as $redirect) {
            if (!is_array($redirect)) {
                continue;
            }
            $url = trim((string)($redirect['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $ss->redirectUrls[] = [
                'url' => $url,
                'label' => trim((string)($redirect['label'] ?? $url)),
                'weight' => max(0, (int)($redirect['weight'] ?? 0)),
            ];
        }
        $ss->redirectType = $arr['redirect']['type'] ?? 302;
        return $ss;
    }

    public function isDirectLoad(string $folderName): bool
    {
        return $this->getLoadMode($folderName) === 'direct';
    }

    public function getLoadMode(string $folderName): string
    {
        return $this->getFolder($folderName)?->loadType ?? 'base';
    }

    public function getFolder(string $folderName): ?StepFolderSettings
    {
        foreach ($this->folders as $folder) {
            if ($folder->name === $folderName) {
                return $folder;
            }
        }
        return null;
    }

    public function getMvt(string $folderName): ?MvtSettings
    {
        return $this->getFolder($folderName)?->mvt;
    }

    public function getWeights(): array
    {
        if ($this->isRedirect()) {
            return array_map(
                static fn(array $redirect): int => (int)($redirect['weight'] ?? 0),
                $this->redirectUrls
            );
        }
        return array_map(
            static fn(StepFolderSettings $folder): int => $folder->weight,
            $this->folders
        );
    }

    public function isRedirect(): bool
    {
        return $this->action === 'redirect';
    }

    public function isFolder(): bool
    {
        return $this->action === 'folder';
    }

    public function getItems(): array
    {
        if ($this->isRedirect()) {
            return array_map(fn($r) => $r['label'] ?? $r['url'] ?? '', $this->redirectUrls);
        }
        return array_map(
            static fn(StepFolderSettings $folder): string => $folder->name,
            $this->folders
        );
    }

    public function getRedirectUrlByLabel(string $label): string
    {
        foreach ($this->redirectUrls as $r) {
            if (($r['label'] ?? '') === $label) {
                return $r['url'];
            }
        }
        return !empty($this->redirectUrls) ? $this->redirectUrls[0]['url'] : '';
    }

    public function jsonSerialize(): array
    {
        return [
            "action" => $this->action,
            "folders" => $this->folders,
            "redirect" => [
                "urls" => $this->redirectUrls,
                "type" => $this->redirectType
            ]
        ];
    }
}

class JsBotDetection implements JsonSerializable
{
    public bool $enabled;
    public array $events;
    public int $timeout;
    public int $tzMin;
    public int $tzMax;

    public static function fromArray($arr): JsBotDetection
    {
        $jsc = new JsBotDetection();
        $jsc->enabled = $arr['enabled'];
        $jsc->events = $arr['events'];
        $jsc->timeout = $arr['timeout'];
        $jsc->tzMin = $arr['timezone']['min'];
        $jsc->tzMax = $arr['timezone']['max'];
        return $jsc;
    }

    public static function defaults(): JsBotDetection
    {
        $jsc = new JsBotDetection();
        $jsc->enabled = false;
        $jsc->events = [];
        $jsc->timeout = 3;
        $jsc->tzMin = -12;
        $jsc->tzMax = 12;
        return $jsc;
    }

    public function jsonSerialize(): array
    {
        return [
            "enabled" => $this->enabled,
            "events" => $this->events,
            "timeout" => $this->timeout,
            "timezone" => [
                "min" => $this->tzMin,
                "max" => $this->tzMax
            ]
        ];
    }
}

class EventSettings implements JsonSerializable
{
    public const EVENT_NAME_PATTERN = '/^[a-z][a-z0-9_]{0,63}$/';
    public const MAX_THRESHOLDS = 32;
    public const MAX_CUSTOM_EVENTS = 64;

    public bool $scrollTrackingUse;
    /** @var list<int> */
    public array $scrollTrackingThresholds;
    public bool $timeTrackingUse;
    /** @var list<int> */
    public array $timeTrackingThresholds;
    public bool $performanceTrackingUse;
    /** @var list<string> */
    public array $customEventNames;

    public static function fromArray(mixed $settings): EventSettings
    {
        $settings = is_array($settings) ? $settings : [];
        $scroll = is_array($settings['scroll'] ?? null) ? $settings['scroll'] : [];
        $time = is_array($settings['time'] ?? null) ? $settings['time'] : [];
        $performance = is_array($settings['performance'] ?? null) ? $settings['performance'] : [];
        $events = new EventSettings();
        $events->scrollTrackingUse = self::toBool($scroll['use'] ?? false);
        $events->scrollTrackingThresholds = self::normalizeThresholds(
            $scroll['thresholds'] ?? [50],
            100
        );
        $events->timeTrackingUse = self::toBool($time['use'] ?? false);
        $events->timeTrackingThresholds = self::normalizeThresholds(
            $time['thresholds'] ?? [60],
            86400
        );
        $events->performanceTrackingUse = self::toBool($performance['use'] ?? false);
        $events->customEventNames = self::normalizeCustomEventNames($settings['custom'] ?? []);
        return $events;
    }

    /** @return list<string> */
    public function getConfiguredEventNames(): array
    {
        $names = [];
        if ($this->scrollTrackingUse) {
            foreach ($this->scrollTrackingThresholds as $threshold) {
                $names['scroll_' . $threshold] = true;
            }
        }
        if ($this->timeTrackingUse) {
            foreach ($this->timeTrackingThresholds as $threshold) {
                $names['stay_' . $threshold . 's'] = true;
            }
        }
        foreach ($this->customEventNames as $name) {
            $names[$name] = true;
        }
        return array_keys($names);
    }

    public function accepts(string $name): bool
    {
        return in_array($name, $this->getConfiguredEventNames(), true);
    }

    public static function isReservedEventName(string $name): bool
    {
        return $name === 'performance'
            || str_starts_with($name, 'performance_')
            || preg_match('/^scroll_\d+$/', $name) === 1
            || preg_match('/^stay_\d+s$/', $name) === 1;
    }

    public function jsonSerialize(): array
    {
        return [
            'scroll' => [
                'use' => $this->scrollTrackingUse,
                'thresholds' => $this->scrollTrackingThresholds,
            ],
            'time' => [
                'use' => $this->timeTrackingUse,
                'thresholds' => $this->timeTrackingThresholds,
            ],
            'performance' => [
                'use' => $this->performanceTrackingUse,
            ],
            'custom' => $this->customEventNames,
        ];
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        return (bool)$value;
    }

    /** @return list<int> */
    private static function normalizeThresholds(mixed $thresholds, int $maximum): array
    {
        if (is_string($thresholds)) {
            $thresholds = array_map('trim', explode(',', $thresholds));
        }
        if (!is_array($thresholds)) {
            return [];
        }

        $normalized = [];
        foreach ($thresholds as $threshold) {
            if (!is_numeric($threshold)) {
                continue;
            }
            $threshold = (int)$threshold;
            if ($threshold <= 0 || $threshold > $maximum) {
                continue;
            }
            $normalized[$threshold] = $threshold;
            if (count($normalized) >= self::MAX_THRESHOLDS) {
                break;
            }
        }
        ksort($normalized);
        return array_values($normalized);
    }

    /** @return list<string> */
    private static function normalizeCustomEventNames(mixed $names): array
    {
        if (is_string($names)) {
            $names = preg_split('/[\s,]+/', $names) ?: [];
        }
        if (!is_array($names)) {
            return [];
        }

        $normalized = [];
        foreach ($names as $name) {
            if (!is_string($name)) {
                continue;
            }
            $name = trim($name);
            if (
                preg_match(self::EVENT_NAME_PATTERN, $name) !== 1
                || self::isReservedEventName($name)
            ) {
                continue;
            }
            $normalized[$name] = $name;
            if (count($normalized) >= self::MAX_CUSTOM_EVENTS) {
                break;
            }
        }
        return array_values($normalized);
    }
}

class ScriptsSettings implements JsonSerializable
{
    public bool $backfix;
    public array $backfixUrls;
    public bool $nextRedirectUse;
    public array $nextRedirectRules;
    public bool $submitRedirectUse;
    public array $submitRedirectRules;
    public bool $imagesLazyLoad;

    public static function fromArray($arr): ScriptsSettings
    {
        $ss = new ScriptsSettings();
        $ss->backfix = self::toBool($arr['backfix']['use'] ?? false);
        $ss->backfixUrls = $arr['backfix']['urls'] ?? [];
        $ss->nextRedirectUse = self::toBool($arr['nextredirect']['use'] ?? false);
        $ss->nextRedirectRules = self::normalizeRedirectRules($arr['nextredirect']['rules'] ?? []);
        $ss->submitRedirectUse = self::toBool($arr['submitredirect']['use'] ?? false);
        $ss->submitRedirectRules = self::normalizeRedirectRules($arr['submitredirect']['rules'] ?? []);
        $ss->imagesLazyLoad = self::toBool($arr['imageslazyload'] ?? false);
        return $ss;
    }

    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        return (bool)$value;
    }

    private static function normalizeRedirectRules($rules): array
    {
        if (!is_array($rules)) {
            return [];
        }

        $normalized = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $url = trim((string)($rule['url'] ?? ''));
            if ($url === '') {
                continue;
            }

            $flow = trim((string)($rule['flow'] ?? '*'));
            if ($flow === '') {
                $flow = '*';
            }

            $steps = self::normalizeRuleSteps($rule['steps'] ?? '*');
            if ($steps === []) {
                continue;
            }

            $normalized[] = [
                'flow' => $flow,
                'steps' => $steps,
                'url' => $url,
            ];
        }

        return $normalized;
    }

    private static function normalizeRuleSteps($steps): array|string
    {
        if ($steps === '*' || $steps === null) {
            return '*';
        }

        if (is_string($steps)) {
            $steps = trim($steps);
            if ($steps === '' || $steps === '*') {
                return '*';
            }
            $steps = array_map('trim', explode(',', $steps));
        }

        if (!is_array($steps)) {
            return [];
        }

        $normalized = [];
        foreach ($steps as $step) {
            if ($step === '*' || $step === '') {
                return '*';
            }
            if (!is_numeric($step)) {
                continue;
            }
            $step = (int)$step;
            if ($step < 0) {
                continue;
            }
            $normalized[$step] = $step;
        }

        if (empty($normalized)) {
            return [];
        }

        ksort($normalized);
        return array_values($normalized);
    }

    public function getNextRedirectRule(string $flowName, int $stepIndex): ?array
    {
        return $this->findRedirectRule($this->nextRedirectUse, $this->nextRedirectRules, $flowName, $stepIndex);
    }

    public function getSubmitRedirectRule(string $flowName, int $stepIndex): ?array
    {
        return $this->findRedirectRule($this->submitRedirectUse, $this->submitRedirectRules, $flowName, $stepIndex);
    }

    private function findRedirectRule(bool $enabled, array $rules, string $flowName, int $stepIndex): ?array
    {
        if (!$enabled) {
            return null;
        }

        foreach ($rules as $rule) {
            $ruleFlow = (string)($rule['flow'] ?? '*');
            $ruleSteps = $rule['steps'] ?? '*';

            $flowMatches = $ruleFlow === '*' || $ruleFlow === $flowName;
            $stepMatches = $ruleSteps === '*' || (is_array($ruleSteps) && in_array($stepIndex, $ruleSteps, true));
            if ($flowMatches && $stepMatches) {
                return $rule;
            }
        }

        return null;
    }

    public function jsonSerialize(): array
    {
        return [
            "scripts" => [
                "backfix" => [
                    "use" => $this->backfix,
                    "urls" => $this->backfixUrls
                ],
                "nextredirect" => [
                    "use" => $this->nextRedirectUse,
                    "rules" => $this->nextRedirectRules
                ],
                "submitredirect" => [
                    "use" => $this->submitRedirectUse,
                    "rules" => $this->submitRedirectRules
                ],
                "imageslazyload" => $this->imagesLazyLoad
            ]
        ];
    }
}

class ConversionStatus implements JsonSerializable
{
    public const BUILT_INS = ['Lead', 'Purchase', 'Reject', 'Trash'];

    public function __construct(public string $name, public array $aliases = [])
    {
        $this->name = trim($name);
        $this->aliases = array_values(array_unique(array_filter(array_map(
            static fn($alias): string => trim((string)$alias),
            $aliases
        ), static fn(string $alias): bool => $alias !== '')));
    }

    public function isBuiltIn(): bool
    {
        return in_array($this->name, self::BUILT_INS, true);
    }

    public function jsonSerialize(): array
    {
        return ['name' => $this->name, 'aliases' => $this->aliases];
    }
}

class ConversionSettings implements JsonSerializable
{
    public const MAX_TRANSACTION_ID_PARAMETERS = 32;
    public const MAX_TRANSACTION_ID_PARAMETER_INPUT_LENGTH = 2110;
    public const MAX_TRANSACTION_ID_VALUE_BYTES = 255;
    public const TRANSACTION_ID_PARAMETER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_-]{0,63}$/';
    private const RESERVED_POSTBACK_PARAMETERS = ['clickid', 'status', 'payout', 'currency', 'pbkey'];

    /** @var ConversionStatus[] */
    public array $statuses = [];
    public bool $tidDeduplicationEnabled = true;
    /** @var string[] */
    public array $transactionIdParameters = ['tid'];
    public string $paidRepeatWithoutTid = 'reject';
    public bool $formEnabled = false;
    public string $formStatus = 'Lead';
    public bool $siteEnabled = false;

    public static function fromArray(array $arr): ConversionSettings
    {
        $settings = new ConversionSettings();
        $rawStatuses = is_array($arr['statuses'] ?? null) ? $arr['statuses'] : [];
        foreach ($rawStatuses as $rawStatus) {
            if (!is_array($rawStatus)) {
                continue;
            }
            $name = trim((string)($rawStatus['name'] ?? ''));
            $aliases = $rawStatus['aliases'] ?? [];
            if (is_string($aliases)) {
                $aliases = explode(',', $aliases);
            }
            if ($name !== '' && is_array($aliases)) {
                $settings->statuses[] = new ConversionStatus($name, $aliases);
            }
        }

        if ($settings->statuses === []) {
            $settings->statuses = self::defaultStatuses();
        }

        $dedup = is_array($arr['deduplication'] ?? null) ? $arr['deduplication'] : [];
        $settings->tidDeduplicationEnabled = self::toBool($dedup['enabled'] ?? true);
        if (array_key_exists('transaction_id_parameters', $dedup)) {
            $settings->transactionIdParameters = self::normalizeTransactionIdParameters(
                $dedup['transaction_id_parameters']
            );
        } else {
            $settings->transactionIdParameters = ['tid'];
        }
        $repeatMode = strtolower(trim((string)($dedup['paid_repeat_without_tid'] ?? 'reject')));
        $settings->paidRepeatWithoutTid = in_array($repeatMode, ['reject', 'upsell'], true) ? $repeatMode : 'reject';

        $form = is_array($arr['form'] ?? null) ? $arr['form'] : [];
        $settings->formEnabled = self::toBool($form['enabled'] ?? false);
        $settings->formStatus = trim((string)($form['status'] ?? 'Lead')) ?: 'Lead';

        $site = is_array($arr['site'] ?? null) ? $arr['site'] : [];
        $settings->siteEnabled = self::toBool($site['enabled'] ?? false);
        return $settings;
    }

    /** @return ConversionStatus[] */
    public static function defaultStatuses(): array
    {
        return [
            new ConversionStatus('Lead', ['lead', 'pending', 'hold']),
            new ConversionStatus('Purchase', ['purchase', 'approved', 'sale']),
            new ConversionStatus('Reject', ['reject', 'declined']),
            new ConversionStatus('Trash', ['trash', 'invalid']),
        ];
    }

    /** @return array{statuses:array<int,array{name:string,aliases:array}>,owners:array<string,string>} */
    public static function normalizeStatusCatalog(array $rawStatuses): array
    {
        $normalized = [];
        $seenNames = [];
        $builtIns = array_fill_keys(ConversionStatus::BUILT_INS, false);
        foreach ($rawStatuses as $rawStatus) {
            if (!is_array($rawStatus)) {
                throw new InvalidArgumentException('Invalid conversion status row.');
            }
            $name = trim((string)($rawStatus['name'] ?? ''));
            if (preg_match('/^[A-Za-z][A-Za-z0-9 _-]{0,63}$/', $name) !== 1) {
                throw new InvalidArgumentException('Status names must start with a letter and use up to 64 letters, numbers, spaces, underscores or hyphens.');
            }
            foreach (ConversionStatus::BUILT_INS as $builtIn) {
                if (strcasecmp($name, $builtIn) === 0) {
                    $name = $builtIn;
                    $builtIns[$builtIn] = true;
                    break;
                }
            }
            $nameKey = strtolower($name);
            if (isset($seenNames[$nameKey])) {
                throw new InvalidArgumentException("Duplicate conversion status: {$name}.");
            }
            $seenNames[$nameKey] = true;

            $aliases = $rawStatus['aliases'] ?? [];
            if (is_string($aliases)) {
                $aliases = explode(',', $aliases);
            }
            if (!is_array($aliases)) {
                throw new InvalidArgumentException("Invalid aliases for {$name}.");
            }
            $normalized[] = [
                'name' => $name,
                'aliases' => array_values(array_unique(array_filter(array_map(
                    static fn($alias): string => trim((string)$alias),
                    $aliases
                ), static fn(string $alias): bool => $alias !== ''))),
            ];
        }

        foreach ($builtIns as $builtIn => $present) {
            if (!$present) {
                throw new InvalidArgumentException("Built-in conversion status {$builtIn} cannot be removed.");
            }
        }

        $owners = [];
        foreach ($normalized as $status) {
            foreach (array_merge([$status['name']], $status['aliases']) as $token) {
                $key = strtolower(trim((string)$token));
                if ($key === '') continue;
                if (isset($owners[$key]) && $owners[$key] !== $status['name']) {
                    throw new InvalidArgumentException("Incoming value '{$token}' is already assigned to {$owners[$key]}.");
                }
                $owners[$key] = $status['name'];
            }
        }

        return ['statuses' => $normalized, 'owners' => $owners];
    }

    /** @return string[] */
    public static function normalizeTransactionIdParameters(mixed $rawParameters): array
    {
        if (is_string($rawParameters)) {
            $rawParameters = explode(',', $rawParameters);
        }
        if (!is_array($rawParameters)) {
            throw new InvalidArgumentException('Transaction ID postback parameters must be a comma-separated list.');
        }

        $parameters = [];
        foreach ($rawParameters as $rawParameter) {
            if (!is_string($rawParameter)) {
                throw new InvalidArgumentException('Transaction ID postback parameter names must be strings.');
            }
            $parameter = trim($rawParameter);
            if ($parameter === '') {
                continue;
            }
            if (preg_match(self::TRANSACTION_ID_PARAMETER_PATTERN, $parameter) !== 1) {
                throw new InvalidArgumentException(
                    'Transaction ID postback parameters may contain up to 64 letters, numbers, underscores or hyphens and must start with a letter or underscore.'
                );
            }
            if (in_array(strtolower($parameter), self::RESERVED_POSTBACK_PARAMETERS, true)) {
                throw new InvalidArgumentException("Postback parameter '{$parameter}' is reserved.");
            }
            $parameterKey = strtolower($parameter);
            if (!isset($parameters[$parameterKey])) {
                $parameters[$parameterKey] = $parameter;
            }
        }

        if ($parameters === []) {
            throw new InvalidArgumentException('Add at least one transaction ID postback parameter.');
        }
        if (count($parameters) > self::MAX_TRANSACTION_ID_PARAMETERS) {
            throw new InvalidArgumentException(
                'A campaign supports at most ' . self::MAX_TRANSACTION_ID_PARAMETERS
                . ' transaction ID postback parameters.'
            );
        }

        return array_values($parameters);
    }

    /**
     * @return array{ok:true,tid:?string,parameter:?string}
     *     | array{ok:false,code:string,message:string}
     */
    public function resolveTransactionIdFromRequest(array $query, array $body = []): array
    {
        $values = [];
        foreach ($this->transactionIdParameters as $parameter) {
            $resolved = PostbackInput::readString(
                $query,
                $body,
                $parameter,
                self::MAX_TRANSACTION_ID_VALUE_BYTES
            );
            if (!($resolved['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'code' => ($resolved['code'] ?? '') === 'ambiguous_parameter'
                        ? 'ambiguous_tid'
                        : 'invalid_tid',
                    'message' => (string)($resolved['message'] ?? 'Invalid transaction ID.'),
                ];
            }
            $value = $resolved['value'];
            if ($value === null || trim($value) === '') {
                continue;
            }
            $values[$parameter] = trim($value);
        }

        if ($values === []) {
            return ['ok' => true, 'tid' => null, 'parameter' => null];
        }
        if (count($values) > 1) {
            return [
                'ok' => false,
                'code' => 'ambiguous_tid',
                'message' => 'Send exactly one configured transaction ID parameter per postback.',
            ];
        }

        return [
            'ok' => true,
            'tid' => reset($values),
            'parameter' => array_key_first($values),
        ];
    }

    public function resolveStatus(string $incoming): ?string
    {
        $needle = strtolower(trim($incoming));
        if ($needle === '') {
            return null;
        }
        foreach ($this->statuses as $status) {
            if (strtolower($status->name) === $needle) {
                return $status->name;
            }
            foreach ($status->aliases as $alias) {
                if (strtolower($alias) === $needle) {
                    return $status->name;
                }
            }
        }
        return null;
    }

    public function statusNames(): array
    {
        return array_map(static fn(ConversionStatus $status): string => $status->name, $this->statuses);
    }

    public function jsonSerialize(): array
    {
        return [
            'statuses' => $this->statuses,
            'deduplication' => [
                'enabled' => $this->tidDeduplicationEnabled,
                'transaction_id_parameters' => $this->transactionIdParameters,
                'paid_repeat_without_tid' => $this->paidRepeatWithoutTid,
            ],
            'form' => ['enabled' => $this->formEnabled, 'status' => $this->formStatus],
            'site' => ['enabled' => $this->siteEnabled],
        ];
    }

    private static function toBool(mixed $value): bool
    {
        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

final class PostbackInput
{
    /**
     * @return array{ok:true,value:?string}
     *     | array{ok:false,code:string,message:string}
     */
    public static function readString(
        array $query,
        array $body,
        string $parameter,
        int $maximumBytes = 4096
    ): array {
        $inQuery = array_key_exists($parameter, $query);
        $inBody = array_key_exists($parameter, $body);
        if ($inQuery && $inBody) {
            return [
                'ok' => false,
                'code' => 'ambiguous_parameter',
                'message' => "Parameter '{$parameter}' must not be sent in both the query string and request body.",
            ];
        }
        if (!$inQuery && !$inBody) {
            return ['ok' => true, 'value' => null];
        }

        $value = $inQuery ? $query[$parameter] : $body[$parameter];
        if ($value === null) {
            return ['ok' => true, 'value' => null];
        }
        if (!is_string($value)) {
            return [
                'ok' => false,
                'code' => 'invalid_parameter',
                'message' => "Parameter '{$parameter}' must contain one string value.",
            ];
        }
        if (strlen($value) > $maximumBytes || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            return [
                'ok' => false,
                'code' => 'invalid_parameter',
                'message' => "Parameter '{$parameter}' contains an invalid value.",
            ];
        }

        return ['ok' => true, 'value' => $value];
    }
}

class PostbackSettings implements JsonSerializable
{
    public const MAX_S2S_POSTBACKS = 5;
    public const MAX_S2S_URL_LENGTH = 8192;

    /** @var list<S2sPostback> */
    public array $s2sPostbacks;
    public bool $pbkeyEnabled = false;
    /** @var list<string> */
    public array $pbkeys = [];

    public static function fromArray(mixed $arr): PostbackSettings
    {
        $ps = new PostbackSettings();
        $arr = is_array($arr) ? $arr : [];

        $ps->s2sPostbacks = [];
        foreach (is_array($arr['s2s'] ?? null) ? $arr['s2s'] : [] as $s2s) {
            $ps->s2sPostbacks[] = S2sPostback::fromArray($s2s);
        }
        $pbkey = is_array($arr['pbkey'] ?? null) ? $arr['pbkey'] : [];
        $ps->pbkeyEnabled = is_bool($pbkey['enabled'] ?? null)
            ? $pbkey['enabled']
            : filter_var($pbkey['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $keys = $pbkey['keys'] ?? [];
        if (is_string($keys)) {
            $keys = explode(',', $keys);
        }
        $ps->pbkeys = array_values(array_unique(array_filter(array_map(
            static fn($key): string => trim((string)$key),
            is_array($keys) ? $keys : []
        ), static fn(string $key): bool => $key !== '')));
        return $ps;
    }

    public function jsonSerialize(): array
    {
        return [
            'pbkey' => ['enabled' => $this->pbkeyEnabled, 'keys' => $this->pbkeys],
            's2s' => $this->s2sPostbacks,
        ];
    }
}

class S2sPostback implements JsonSerializable
{
    public string $url;
    public string $method;
    /** @var list<string> */
    public array $statuses;
    /** @var list<string> */
    public array $events;

    /**
     * @param list<string> $statuses
     * @param list<string> $events
     */
    public function __construct(string $url, string $method, array $statuses, array $events)
    {
        $this->url = $url;
        $this->method = $method;
        $this->statuses = $statuses;
        $this->events = $events;
    }

    public static function fromArray(mixed $arr): S2sPostback
    {
        $arr = is_array($arr) ? $arr : [];
        return new S2sPostback(
            trim((string)($arr['url'] ?? '')),
            strtoupper(trim((string)($arr['method'] ?? 'GET'))),
            self::normalizeStringList($arr['statuses'] ?? []),
            self::normalizeStringList($arr['events'] ?? [])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'statuses' => $this->statuses,
            'events' => $this->events,
        ];
    }

    /** @return list<string> */
    private static function normalizeStringList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $normalized = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }
            $value = trim($value);
            if ($value !== '' && !isset($normalized[$value])) {
                $normalized[$value] = $value;
            }
        }
        return array_values($normalized);
    }
}

class StatisticsSettings implements JsonSerializable
{
    public string $timezone;
    public array $allowed;
    public array $leads;
    public array $blocked;
    public array $tables;

    public static function fromArray($arr)
    {
        $ss = new StatisticsSettings();
        $ss->timezone = $arr['timezone'];
        $ss->allowed = $arr['allowed'];
        $ss->leads = $arr['leads'];
        $ss->blocked = $arr['blocked'];
        $ss->tables = [];
        foreach ($arr['tables'] as $st) {
            $ss->tables[] = StatisticsTable::fromArray($st);
        }

        return $ss;
    }
    public function jsonSerialize(): array
    {
        return [
            "statistics" => [
                "timezone" => $this->timezone,
                "allowed" => $this->allowed,
                "leads" => $this->leads,
                "blocked" => $this->blocked,
                "tables" => $this->tables
            ]
        ];
    }
}

class StatisticsTable implements JsonSerializable
{
    public string $name;
    public array $columns;
    public array $groupby;
    public array $filters;
    public array $orderby;
    public array $mvt;

    public function __construct($name, $columns, $groupby, $filters = [], $orderby = [], $mvt = [])
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->groupby = $groupby;
        $this->filters = $filters;
        $this->orderby = $orderby;
        $this->mvt = is_array($mvt) ? $mvt : [];
    }

    public static function fromArray($arr): StatisticsTable
    {
        return new StatisticsTable(
            $arr['name'],
            $arr['columns'],
            $arr['groupby'],
            $arr['filters'] ?? [],
            $arr['orderby'] ?? [],
            $arr['mvt'] ?? []
        );
    }

    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "columns" => $this->columns,
            "groupby" => $this->groupby,
            "filters" => $this->filters,
            "orderby" => $this->orderby,
            "mvt" => $this->mvt,
        ];
    }
}
