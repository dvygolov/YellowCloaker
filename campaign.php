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
            "postback" => $this->postback,
            "scripts" => $this->scripts,
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

    public function lastStep(): ?StepSettings
    {
        return empty($this->steps) ? null : end($this->steps);
    }
}

class StepSettings implements JsonSerializable
{
    public string $action;
    public array $folderNames;
    /** @var array<array{url: string, label: string}> */
    public array $redirectUrls;
    public int $redirectType;
    public array $weights;
    public array $folderLoadTypes;

    public static function fromArray($arr): StepSettings
    {
        $ss = new StepSettings();
        $ss->action = $arr['action'] ?? 'folder';
        $ss->folderNames = $arr['folders'] ?? [];
        $ss->redirectUrls = $arr['redirect']['urls'] ?? [];
        $ss->redirectType = $arr['redirect']['type'] ?? 302;
        $ss->weights = $arr['weights'] ?? [];
        $ss->folderLoadTypes = $arr['folderloadtypes'] ?? [];
        return $ss;
    }

    public function isDirectLoad(string $folderName): bool
    {
        return ($this->folderLoadTypes[$folderName] ?? '') === 'direct';
    }

    public function getLoadMode(string $folderName): string
    {
        return $this->folderLoadTypes[$folderName] ?? 'base';
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
        return $this->folderNames;
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

    public static function generateRedirectLabel(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? preg_replace('/^www\./', '', $host) : 'redirect';
    }

    public function jsonSerialize(): array
    {
        return [
            "action" => $this->action,
            "folders" => $this->folderNames,
            "redirect" => [
                "urls" => $this->redirectUrls,
                "type" => $this->redirectType
            ],
            "weights" => $this->weights,
            "folderloadtypes" => $this->folderLoadTypes
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

class ScriptsSettings implements JsonSerializable
{
    public bool $backfix;
    public array $backfixUrls;
    public bool $nextRedirectUse;
    public array $nextRedirectRules;
    public bool $submitRedirectUse;
    public array $submitRedirectRules;
    public bool $scrollTrackingUse;
    public array $scrollTrackingThresholds;
    public bool $timeTrackingUse;
    public array $timeTrackingThresholds;
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
        $ss->scrollTrackingUse = self::toBool($arr['events']['scroll']['use'] ?? false);
        $ss->scrollTrackingThresholds = self::normalizeThresholds($arr['events']['scroll']['thresholds'] ?? [50]);
        $ss->timeTrackingUse = self::toBool($arr['events']['time']['use'] ?? false);
        $ss->timeTrackingThresholds = self::normalizeThresholds($arr['events']['time']['thresholds'] ?? [60]);
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

    private static function normalizeThresholds($thresholds): array
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
            if ($threshold <= 0) {
                continue;
            }
            $normalized[$threshold] = $threshold;
        }

        ksort($normalized);
        return array_values($normalized);
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

    public function getConfiguredEventMetricFields(): array
    {
        $fields = [];
        if ($this->scrollTrackingUse) {
            foreach ($this->scrollTrackingThresholds as $threshold) {
                $fields[] = 'event.scroll_' . $threshold;
            }
        }
        if ($this->timeTrackingUse) {
            foreach ($this->timeTrackingThresholds as $threshold) {
                $fields[] = 'event.stay_' . $threshold . 's';
            }
        }
        return $fields;
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
                "events" => [
                    "scroll" => [
                        "use" => $this->scrollTrackingUse,
                        "thresholds" => $this->scrollTrackingThresholds
                    ],
                    "time" => [
                        "use" => $this->timeTrackingUse,
                        "thresholds" => $this->timeTrackingThresholds
                    ]
                ],
                "imageslazyload" => $this->imagesLazyLoad
            ]
        ];
    }
}

class PostbackSettings implements JsonSerializable
{
    public array $s2sPostbacks;
    public string $leadStatusName;
    public string $purchaseStatusName;
    public string $rejectStatusName;
    public string $trashStatusName;

    public static function fromArray($arr): PostbackSettings
    {
        $ps = new PostbackSettings();

        $ps->s2sPostbacks = [];
        foreach ($arr['s2s'] as $s2s) {
            $ps->s2sPostbacks[] = S2sPostback::fromArray($s2s);
        }

        $ps->leadStatusName = $arr['events']['lead'];
        $ps->purchaseStatusName = $arr['events']['purchase'];
        $ps->rejectStatusName = $arr['events']['reject'];
        $ps->trashStatusName = $arr['events']['trash'];
        return $ps;
    }

    public function jsonSerialize(): array
    {
        return [
            "postback" => [
                "events" => [
                    "lead" => $this->leadStatusName,
                    "purchase" => $this->purchaseStatusName,
                    "reject" => $this->rejectStatusName,
                    "trash" => $this->trashStatusName
                ],
                "s2s" => $this->s2sPostbacks
            ]
        ];
    }
}

class S2sPostback implements JsonSerializable
{
    public string $url;
    public string $method;
    public array $events;

    public function __construct($url, $method, $events)
    {
        $this->url = $url;
        $this->method = $method;
        $this->events = $events;
    }

    public static function fromArray($arr): S2sPostback
    {
        return new S2sPostback($arr['url'], $arr['method'], $arr['events']);
    }

    public function jsonSerialize(): array
    {
        return [
            "url" => $this->url,
            "method" => $this->method,
            "events" => $this->events
        ];
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

    public function __construct($name, $columns, $groupby, $filters = [], $orderby = [])
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->groupby = $groupby;
        $this->filters = $filters;
        $this->orderby = $orderby;
    }

    public static function fromArray($arr): StatisticsTable
    {
        return new StatisticsTable($arr['name'], $arr['columns'], $arr['groupby'], $arr['filters'] ?? [], $arr['orderby'] ?? []);
    }

    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "columns" => $this->columns,
            "groupby" => $this->groupby,
            "filters" => $this->filters,
            "orderby" => $this->orderby,
        ];
    }
}
