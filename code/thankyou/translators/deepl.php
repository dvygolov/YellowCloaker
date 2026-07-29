<?php
include_once __DIR__ . '/../../requestfunc.php';
include_once __DIR__ . '/../../logging.php';
class Deepl
{
    private $apiKey;
    private $languages = ["bg", "cs", "da", "de", "el", "en", "es", "et", "fi", "fr", "hu", "it", "ja", "lt", "lv", "nl", "pl", "pt", "ro", "ru", "sk", "sl", "sv", "zh"];

    public function __construct()
    {
        $cloSettings = json_decode(file_get_contents(__DIR__ . '/../../settings.json'), true);
        $this->apiKey = $cloSettings["deeplApiKey"];
    }

    public function checkLanguages($src, $target)
    {
        if (empty($this->apiKey))
            return false;
        return (in_array($src, $this->languages) && in_array($target, $this->languages));
    }
    public function translate($text, $sourceLang, $targetLang)
    {
        $addressPrefix = strpos($this->apiKey, ":fx") !== -1 ? "api-free" : "api";
        $translateAddress = "https://{$addressPrefix}.deepl.com/v2/translate?auth_key={$this->apiKey}";
        $params = array(
        "auth_key" => $this->apiKey,
        "text" => $text,
        "source_lang" => strtoupper($sourceLang),
        "target_lang" => strtoupper($targetLang)
        );
        $res = post($translateAddress, $params);
        if ($res['info']['http_code'] !== 200) {
            add_log(
            "thankyou",
            "Can't translate text '$text' from $sourceLang to $targetLang using Deepl. Error {$res['error']}"
            );
            return 'error';
        }
        $json = json_decode($res['content']);
        if (!isset($json))
            return 'error';
        else
            return $json->translations[0]->text;
    }
}