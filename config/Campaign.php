<?php
require_once __DIR__ . '/ConfigInterface.php';
require_once __DIR__ . '/AbstractConfig.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Parser/ParserInterface.php';
require_once __DIR__ . '/Parser/Json.php';
require_once __DIR__ . '/Writer/AbstractWriter.php';
require_once __DIR__ . '/Writer/WriterInterface.php';
require_once __DIR__ . '/Writer/Json.php';
require_once __DIR__ . '/ErrorException.php';
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/Exception/ParseException.php';
require_once __DIR__ . '/Exception/FileNotFoundException.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../logging.php';

use Noodlehaus\Config;
use Noodlehaus\Exception\EmptyDirectoryException;
use Noodlehaus\Writer\Json;

class Campaign
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
    private Config $conf;

    public function __construct(int $campId, string $settings)
    {
        $conf = Config::load($settings, null, true);
        $this->campaignId = $campId;
        $this->conf = $conf;
        $this->domains = $conf->get('domains');
        $this->filters = $conf['tds.filters'];
        $this->saveUserFlow = $conf['tds.saveuserflow'];
        $this->apiKey = $conf['tds.apikey'];
        $this->subIds = $conf['subids'];

        $ws = new WhiteSettings();
        $ws->action = $conf->get('white.action', 'folder');
        $ws->folderNames = $conf->get('white.folder.names', ['white']);
        $ws->redirectUrls = $conf->get('white.redirect.urls', []);
        $ws->redirectType = $conf->get('white.redirect.type', 302);
        $ws->curlUrls = $conf->get('white.curl.urls', []);
        $ws->errorCodes = $conf->get('white.error.codes', [404]);
        $ws->domainFilterEnabled = $conf->get('white.domainfilter.use', false);
        $ws->domainSpecific = $conf['white.domainfilter.domains'];

        $jsc = new JsChecks();
        $jsc->enabled = $conf['white.jschecks.enabled'];
        $jsc->events = $conf['white.jschecks.events'];
        $jsc->timeout = $conf['white.jschecks.timeout'];
        $jsc->obfuscate = $conf['white.jschecks.obfuscate'];
        $jsc->tzStart = $conf['white.jschecks.tzstart'];
        $jsc->tzEnd = $conf['white.jschecks.tzend'];
        $ws->jsChecks = $jsc;

        $this->white = $ws;

        $pls = new PrelandSettings();
        $pls->action = $conf['black.prelanding.action'];
        $pls->folder_names = $conf['black.prelanding.folders'];

        $ls = new LandingSettings();
        $ls->action = $conf['black.landing.action'];
        $ls->folderNames = $conf['black.landing.folder.names'];
        $ls->redirectUrls = $conf['black.landing.redirect.urls'];
        $ls->redirectType = $conf['black.landing.redirect.type'];
        $ls->useCustomThankyou = $conf['black.landing.folder.customthankyoupage.use'];

        $bs = new BlackSettings();
        $bs->preland = $pls;
        $bs->land = $ls;
        $bs->jsconnectAction = $conf['black.jsconnect'];
        if ($bs->preland->action === 'none' && $bs->land->action === 'redirect')
            $bs->jsconnectAction = 'redirect';
        else if ($bs->jsconnectAction === 'redirect')
            $bs->jsconnectAction = 'replace';
        $this->black = $bs;

        $ss = new ScriptsSettings();
        $ss->backfix = $conf['scripts.backfix.use'];
        $ss->backfixAddress = $conf['scripts.backfix.url']; //TODO: implement backfix
        $ss->replacePrelanding = $conf['scripts.prelandingreplace.use'];
        $ss->replacePrelandingAddress = $conf['scripts.prelandingreplace.url'];
        $ss->replaceLanding = $conf['scripts.landingreplace.use'];
        $ss->replaceLandingAddress = $conf['scripts.landingreplace.url'];
        $ss->imagesLazyLoad = $conf['scripts.imageslazyload'];
        $this->scripts = $ss;

        $ps = PostbackSettings();
        $ps->s2sPostbacks = $conf['postback.s2s'];
        $ps->leadStatusName = $conf['postback.lead'];
        $ps->purchaseStatusName = $conf['postback.purchase'];
        $ps->rejectStatusName = $conf['postback.reject'];
        $ps->trashStatusName = $conf['postback.trash'];
        $this->postback = $ps;

        $sts = new StatisticsSettings();
    }

    public function to_json_string(array $settings): string
    {
        try {
            foreach ($settings as $key => $value) {
                $confkey = str_replace('_', '.', $key);
                if (is_string($value) && is_array($this->conf[$confkey])) {
                    if (str_starts_with($value, '{') || str_starts_with($value, '[')) {
                        $value = json_decode($value, true);
                    } else if ($value === '')
                        $value = [];
                    else
                        $value = explode(',', $value);
                } else if ($value === 'false' || $value === 'true') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                $this->conf[$confkey] = $value;
            }
            return $this->conf->toString(new Json());
        } catch (Exception $e) {
            add_log(
            "config",
            "Couldn't save settings to JSON: " . $e->getMessage() . " " . implode(',', $settings)
            );
            return null;
        }
    }
}

class WhiteSettings
{
    public JsChecks $jsChecks;
    public string $action;
    public array $folderNames;
    public array $redirectUrls;
    public int $redirectType;
    public array $curlUrls;
    public array $errorCodes;
    public bool $domainFilterEnabled;
    public array $domainSpecific;
}

class BlackSettings
{
    public string $jsconnectAction;
    public PrelandSettings $preland;
    public LandingSettings $land;
}

class PrelandSettings
{
    public string $action;
    public array $folderNames;
}

class LandingSettings
{
    public string $action;
    public array $folderNames;
    public array $redirectUrls;
    public int $redirectType;
    public bool $useCustomThankyou;
}

class JsChecks
{
    public bool $enabled;
    public array $events;
    public int $timeout;
    public bool $obfuscate;
    public int $tzStart;
    public int $tzEnd;
}

class ScriptsSettings
{
    public bool $backfix;
    public string $backfixAddress;
    public bool $replacePrelanding;
    public string $replacePrelanding_address;
    public bool $replaceLanding;
    public string $replaceLandingAddress;
    public bool $imagesLazyLoad;
}

class PostbackSettings
{
    public array $s2sPostbacks;
    public string $leadStatusName;
    public string $purchaseStatusName;
    public string $rejectStatusName;
    public string $trashStatusName;
}

class StatisticsSettings
{
    public array $allowed;
    public array $blocked;
    public array $tables;
}
