<?php
    define("DEEPL_KEY",""); //TODO:get from settings
    include 'requestfunc.php';
    include 'google.php';

	function translate($text_content,$templates_lang,$lang){
        $ltlanguages=["en","ar","zh","nl","fi","fr","de","hi","hu","id","ga","it","ja","ko","pl","pt","ru","es","sv","tr","uk","vi"];
		$dpllanguages=["bg","cs","da","de","el","en","es","et","fi","fr","hu","it","ja","lt","lv","nl","pl","pt","ro","ru","sk","sl","sv","zh"];
        $ggllanguages=["af","sq","ar","az","eu","bn","be","bg","ca","zh-CN","zh-TW","hr","cs","da","nl","en","eo","et","tl","fi","fr","gl","ka","de","el","gu","ht","iw","hi","hu","is","id","ga","it","ja","kn","ko","la","lv","lt","mk","ms","mt","no","fa","pl","pt","ro","ru","sr","sk","sl","es","sw","sv","ta","te","th","tr","uk","ur","vi","cy","yi"];

		//If libretranslae && deepl && google don't know input or target language return error
		if (!in_array($templates_lang, $ltlanguages)&&
            !in_array($templates_lang, $dpllanguages)&&
            !in_array($templates_lang, $ggllanguages))
			return 'error';
		if (!in_array($lang, $ltlanguages)&&
            !in_array($lang, $dpllanguages)&&
            !in_array($lang, $ggllanguages))
			return 'error';

		if (in_array($templates_lang,$ggllanguages)&&in_array($lang,$ggllanguages))
			$translator='google';
		elseif (in_array($templates_lang,$dpllanguages)&&in_array($lang,$dpllanguages))
			$translator='deepl';
		elseif (in_array($templates_lang,$ltlanguages)&&in_array($lang,$ltlanguages))
			$translator='libretranslate';

		switch($translator){
            case 'libretranslate':
                $translateAddress='https://libretranslate.de/translate';
                $params = array("q"=>$text_content,"source"=>$templates_lang,"target"=>$lang,"format"=>"text");
                $json= json_decode(post($translateAddress,$params));
                if (isset($json->error)) //this language is not supported so we show an english version
                    return 'error';
                else
                    return $json->translatedText;
            case 'deepl':
                $address_start=strpos(DEEPL_KEY,":fx")!==-1?"api-free":"api";
                $translateAddress='https://'.$address_start.'.deepl.com/v2/translate?auth_key='.DEEPL_KEY;
                $params = array("auth_key"=>DEEPL_KEY,"text"=>$text_content,"source_lang"=>strtoupper($templates_lang),"target_lang"=>strtoupper($lang));
                $res= post($translateAddress,$params);
                $json=json_decode($res);
                if (!isset($json))
                    return 'error';
                else
                    return $json->translations[0]->text;
            case 'google':
                $gt = new GoogleTranslate();
                $response = $gt->translate($text_content,$templates_lang,$lang);
                if (isset($response))
                    return $response;
                else
                    return 'error';
        }
	}
?>