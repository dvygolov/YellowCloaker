<?php
use Stichoza\GoogleTranslate\GoogleTranslate;
include_once __DIR__.'/../vendor/autoload.php';
include_once __DIR__.'/../requestfunc.php';
class GoogleTranslator
{
    private $languages = ["af","sq","ar","az","eu","bn","be","bg","ca","zh-CN","zh-TW","hr","cs","da","nl","en","eo","et","tl","fi","fr","gl","ka","de","el","gu","ht","iw","hi","hu","is","id","ga","it","ja","kn","ko","la","lv","lt","mk","ms","mt","no","fa","pl","pt","ro","ru","sr","sk","sl","es","sw","sv","ta","te","th","tr","uk","ur","vi","cy","yi"];

    public function checkLanguages($src,$target){
        return (in_array($src, $this->languages) && in_array($target, $this->languages));
    }

    public function translate($text, $sourceLang, $targetLang)
    {
        $tr = new GoogleTranslate($targetLang,$sourceLang,["verify"=>false]);
        $translation = $tr->translate($text);

        if (is_null($translation))
            return 'error';
        else
            return $translation;
    }
}
