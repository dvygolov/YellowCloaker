<?php
include_once 'countryfuncs.php';
class ThankyouTemplate
{
    private $cache_dir = 'cache';
    private $dir = 'templates';
    private $srcLang = 'en';
    private $lang = 'en';
    private $template = 'random';
    private $pageContent;
    private $cached_thankyou_path;

    public function __construct()
    {
        if (isset($_REQUEST['lang']))
            $this->lang = strtolower($_REQUEST['lang']);
        elseif (isset($_REQUEST['country']))
            $this->lang = CountryFuncs::get_language($_REQUEST['country']);

        if (isset($_REQUEST['template'])) {
            $this->template = $_REQUEST['template'];
            if (!is_dir(__DIR__ . "/$this->dir/$this->template"))
                $this->template = 'random';
        }

        //selecting a random template
        if ($this->template === 'random') {
            $directories = glob("$this->dir/*", GLOB_ONLYDIR);
            $r = rand(0, count($directories) - 1);
            $this->template = substr($directories[$r], strlen($this->dir) + 1);
        }

        if (!is_dir(__DIR__ . '/' . $this->cache_dir))
            mkdir(__DIR__ . '/' . $this->cache_dir);

        if (!is_dir(__DIR__ . '/' . $this->cache_dir . '/' . $this->template))
            mkdir(__DIR__ . '/' . $this->cache_dir . '/' . $this->template);
    }

    public function processTemplate()
    {
        $this->cached_thankyou_path = __DIR__ . "/$this->cache_dir/$this->template/$this->lang.html";
        if (file_exists($this->cached_thankyou_path)) {
            $this->pageContent = file_get_contents($this->cached_thankyou_path);
            return;
        }

        $text_path = __DIR__ . "/$this->dir/$this->template/text.txt";
        $text_content = file_get_contents($text_path);
        $translation = $this->getTranslation($text_content);

        $template_path = __DIR__ . "/$this->dir/$this->template/t.html";
        $template_content = file_get_contents($template_path);
        for ($i = 0; $i < count($translation); $i++) {
            $template_content = str_replace('{T' . ($i + 1) . '}', $translation[$i], $template_content);
        }
        $this->cacheTemplate($template_content);
        $this->pageContent = $template_content;
    }

    private function getTranslation($text_content)
    {
        include __DIR__ . '/translator.php';
        $translation = array();
        $translated_text = translate($text_content, $this->srcLang, $this->lang);
        if ($translated_text === 'error' || !isset($translated_text)) {
            $this->cached_thankyou_path = __DIR__ . "/$this->cache_dir/$this->template/en.html";
            $translation = explode("\n", $text_content);
        } else {
            $translation = explode("\n", $translated_text);
        }
        return $translation;
    }

    public function processMacros()
    {
        $thankyou = $this->pageContent;
        if (isset($_REQUEST['subid']))
            $thankyou = str_replace('{SUBID}', $_REQUEST['subid'], $thankyou);
        if (isset($_GET['name']))
            $thankyou = str_replace('{NAME}', $_GET['name'], $thankyou);
        if (isset($_GET['phone']))
            $thankyou = str_replace('{PHONE}', $_GET['phone'], $thankyou);
        $this->pageContent = $thankyou;
    }

    private function cacheTemplate($template_content)
    {
        if (!isset($_GET['nocache']))
            file_put_contents($this->cached_thankyou_path, $template_content);
    }

    public function addPixelCode()
    {
        if (!isset($pixel_sub))
            $pixel_sub = 'px'; //The GET/POST parameter that will have a Facebook's pixel ID as it's value
        if (!isset($pixel_event)) {
            $pixel_event = 'Lead';
            if (isset($_REQUEST['pixelevent']))
                $pixel_event = $_REQUEST['pixelevent'];
        }
        if (isset($_REQUEST[$pixel_sub]))
            $pixel_code = '<img height="1" width="1" src="https://www.facebook.com/tr?id=' . $_REQUEST[$pixel_sub] . '&ev=' . $pixel_event . '&noscript=1">';
        if (isset($pixel_code)) {
            include_once __DIR__ . '/htmlinject.php';
            $this->pageContent = insert_after_tag($this->pageContent, '<body', $pixel_code);
        }
    }

    public function getPage(){
        return $this->pageContent;
    }
}