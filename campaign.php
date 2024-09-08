<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logging.php';

class Campaign implements JsonSerializable
{
    public int $campaignId;
    public array $domains;
    public array $filters;
    public bool $saveUserFlow;
    public string $apiKey;
    public array $subIds;

    public WhiteSettings $white;
    public BlackSettings $black;
    public ScriptsSettings $scripts;
    public PostbackSettings $postback;
    public StatisticsSettings $statistics;

    public function __construct(int $campId, array $s)
    {
        $this->campaignId = $campId;
        $this->domains = $s['domains'];
        $this->filters = $s['filters'];
        $this->saveUserFlow = $s['saveuserflow'];
        $this->apiKey = $s['apikey'];

        $this->white = WhiteSettings::fromArray($s['white']);
        $this->black = BlackSettings::fromArray($s['black']);

        $this->subIds = [];
        foreach ($s['subids'] as $sr){
            $this->subIds[] = new SubIdRewrite($sr['name'], $sr['rewrite']);
        }

        $this->scripts = ScriptsSettings::fromArray($s['scripts']);
        $this->postback = PostbackSettings::fromArray($s['postback']);

        $sts = new StatisticsSettings();
    }

    function jsonSerialize()
    {
        return [
        "campaignId" => $this->campaignId,
        "domains" => $this->domains,
        "filters" => $this->filters,
        "saveuserflow" => $this->saveUserFlow,
        "apikey" => $this->apiKey,
        "white" => $this->white,
        "black" => $this->black,
        "subids" => $this->subIds,
        "statistics" => $this->statistics,
        "postback" => $this->postback,
        "scripts" => $this->scripts
        ];
    }
}

class WhiteSettings implements JsonSerializable
{
    public string $action;
    public array $folderNames;
    public array $redirectUrls;
    public int $redirectType;
    public array $curlUrls;
    public array $errorCodes;
    public bool $domainFilterEnabled;
    public array $domainSpecific;
    public JsChecks $jsChecks;

    public static function fromArray(array $s): WhiteSettings
    {
        $ws = new WhiteSettings();
        $ws->action = $s['action'];
        $ws->folderNames = $s['folders'];
        $ws->redirectUrls = $s['redirect']['urls'];
        $ws->redirectType = $s['redirect']['type'];
        $ws->curlUrls = $s['curls'];
        $ws->errorCodes = $s['errorcodes'];
        $ws->domainFilterEnabled = $s['domainfilter']['use'];

        $ws->domainSpecific = [];
        foreach ($s['domainfilter']['domains'] as $df) {
            $ws->domainSpecific[] = DomainSpecificWhite::fromArray($df);
        }

        $ws->jsChecks = JsChecks::fromArray($s['jschecks']);
        return $ws;
    }

    function jsonSerialize()
    {
        return [
        "action" => $this->action,
        "folders" => $this->folderNames,
        "redirect" => [
        "urls" => $this->redirectUrls,
        "type" => $this->redirectType
        ],
        "curls" => $this->curlUrls,
        "errorcodes" => $this->errorCodes,
        "jschecks" => $this->jsChecks,
        "domainfilter" => [
        "use" => $this->domainFilterEnabled,
        "domains" => $this->domainSpecific
        ]
        ];
    }
}

class DomainSpecificWhite implements JsonSerializable
{
    public string $name;
    public string $action;

    public function __construct($name, $action)
    {
        $this->name = $name;
        $this->action = $action;
    }

    public static function fromArray($arr): DomainSpecificWhite
    {
        return new DomainSpecificWhite($arr['name'], $arr['action']);
    }

    function jsonSerialize()
    {
        return [
        "name" => $this->name,
        "action" => $this->action
        ];
    }
}

class BlackSettings implements JsonSerializable
{
    public string $jsconnectAction;
    public PrelandSettings $preland;
    public LandingSettings $land;

    public static function fromArray($arr): BlackSettings
    {
        $bs = new BlackSettings();
        $bs->preland = PrelandSettings::fromArray($arr['prelanding']);
        $bs->land = LandingSettings::fromArray($arr['landing']);
        $bs->jsconnectAction = $arr['jsconnect'];

        if ($bs->preland->action === 'none' && $bs->land->action === 'redirect')
            $bs->jsconnectAction = 'redirect';
        else if ($bs->jsconnectAction === 'redirect')
            $bs->jsconnectAction = 'replace';
        return $bs;
    }
    function jsonSerialize()
    {
        return [
        "prelanding" => $this->preland,
        "landing" => $this->land,
        "jsconnect" => $this->jsconnectAction
        ];
    }
}

class PrelandSettings implements JsonSerializable
{
    public string $action;
    public array $folderNames;

    public static function fromArray($arr): PrelandSettings
    {
        $pls = new PrelandSettings();
        $pls->action = $arr['action'];
        $pls->folderNames = $arr['folders'];
        return $pls;
    }

    function jsonSerialize()
    {
        return [
        "action" => $this->action,
        "folders" => $this->folderNames
        ];
    }
}

class LandingSettings implements JsonSerializable
{
    public string $action;
    public array $folderNames;
    public array $redirectUrls;
    public int $redirectType;
    public bool $useCustomThankyou;

    public static function fromArray($arr): LandingSettings
    {
        $ls = new LandingSettings();
        $ls->action = $arr['action'];
        $ls->folderNames = $arr['folders'];
        $ls->redirectUrls = $arr['redirect']['urls'];
        $ls->redirectType = $arr['redirect']['type'];
        $ls->useCustomThankyou = $arr['customthankyou'];
        return $ls;
    }
    function jsonSerialize()
    {
        return [
        "action" => $this->action,
        "folders" => $this->folderNames,
        "redirect" => [
        "urls" => $this->redirectUrls,
        "type" => $this->redirectType
        ],
        "customthankyou" => $this->useCustomThankyou
        ];
    }
}

class JsChecks implements JsonSerializable
{
    public bool $enabled;
    public array $events;
    public int $timeout;
    public int $tzMin;
    public int $tzMax;

    public static function fromArray($arr): JsChecks
    {
        $jsc = new JsChecks();
        $jsc->enabled = $arr['enabled'];
        $jsc->events = $arr['events'];
        $jsc->timeout = $arr['timeout'];
        $jsc->tzMin = $arr['timezone']['min'];
        $jsc->tzMax = $arr['timezone']['max'];
        return $jsc;
    }
    function jsonSerialize()
    {
        return [
        "jschecks" => [
        "enabled" => $this->enabled,
        "events" => $this->events,
        "timeout" => $this->timeout,
        "timezone" => [
        "min" => $this->tzMin,
        "max" => $this->tzMax
        ]
        ]
        ];

    }
}

class ScriptsSettings implements JsonSerializable
{
    public bool $backfix;
    public string $backfixAddress;
    public bool $replacePrelanding;
    public string $replacePrelandingAddress;
    public bool $replaceLanding;
    public string $replaceLandingAddress;
    public bool $imagesLazyLoad;

    public static function fromArray($arr): ScriptsSettings
    {
        $ss = new ScriptsSettings();
        $ss->backfix = $arr['backfix']['use'];
        $ss->backfixAddress = $arr['backfix']['url'];
        $ss->replacePrelanding = $arr['prelandingreplace']['use'];
        $ss->replacePrelandingAddress = $arr['prelandingreplace']['url'];
        $ss->replaceLanding = $arr['landingreplace']['use'];
        $ss->replaceLandingAddress = $arr['landingreplace']['url'];
        $ss->imagesLazyLoad = $arr['imageslazyload'];
        return $ss;
    }

    function jsonSerialize()
    {
        return [
        "scripts" => [
        "backfix" => [
        "use" => $this->backfix,
        "address" => $this->backfixAddress
        ],
        "replacePrelanding" => [
        "use" => $this->replacePrelanding,
        "address" => $this->replacePrelandingAddress
        ],
        "replaceLanding" => [
        "use" => $this->replaceLanding,
        "address" => $this->replaceLandingAddress
        ],
        "imagesLazyLoad" => $this->imagesLazyLoad
        ]
        ];
    }
}

class SubIdRewrite implements JsonSerializable
{
    public string $name;
    public string $rewrite;

    public function __construct($name, $rewrite){
        $this->name = $name;
        $this->rewrite = $rewrite;
    }

    function jsonSerialize()
    {
        return [
        "name" => $this->name,
        "rewrite" => $this->rewrite
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
        $ps = PostbackSettings();

        $ps->s2sPostbacks = [];
        foreach ($arr['s2s'] as $s2s){
            $ps->s2sPostbacks[] = S2sPostback::fromArray($s2s);
        }
        
        $ps->leadStatusName = $arr['events']['lead'];
        $ps->purchaseStatusName = $arr['events']['purchase'];
        $ps->rejectStatusName = $arr['events']['reject'];
        $ps->trashStatusName = $arr['events']['trash'];
        return $ps;
    }

    function jsonSerialize()
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

    function jsonSerialize()
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
    public array $blocked;
    public array $tables;

    function jsonSerialize()
    {
        return [
        "statistics" => [
        "timezone" => $this->timezone,
        "allowed" => $this->allowed,
        "blocked" => $this->blocked,
        "tables" => $this->tables
        ]
        ];
    }
}
