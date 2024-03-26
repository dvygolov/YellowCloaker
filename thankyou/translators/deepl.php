<?php
include_once __DIR__.'/../requestfunc.php';
class Deepl
{
    private $apiKey = ""; //enter your Deepl API KEY here
    private $languages=["bg","cs","da","de","el","en","es","et","fi","fr","hu","it","ja","lt","lv","nl","pl","pt","ro","ru","sk","sl","sv","zh"];

    public function checkLanguages($src,$target){
        if (empty($this->apiKey)) return false;
        return (in_array($src, $this->languages) && in_array($target, $this->languages));
    }
    public function translate($text, $sourceLang, $targetLang)
    {
        $address_start = strpos($this->apiKey, ":fx") !== -1 ? "api-free" : "api";
        $translateAddress = "https://$address_start.deepl.com/v2/translate?auth_key=$this->apiKey";
        $params = array(
            "auth_key" => $this->apiKey,
            "text" => $text,
            "source_lang" => strtoupper($sourceLang),
            "target_lang" => strtoupper($targetLang)
        );
        $res = post($translateAddress, $params);
        $json = json_decode($res);
        if (!isset($json))
            return 'error';
        else
            return $json->translations[0]->text;
    }
}