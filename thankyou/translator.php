<?php
include 'google.php';

function translate(string $text_content, string $templates_lang, string $lang): string
{
    $ggllanguages = ["af", "sq", "ar", "az", "eu", "bn", "be", "bg", "ca", "zh-CN", "zh-TW", "hr", "cs", "da", "nl", "en", "eo", "et", "tl", "fi", "fr", "gl", "ka", "de", "el", "gu", "ht", "iw", "hi", "hu", "is", "id", "ga", "it", "ja", "kn", "ko", "la", "lv", "lt", "mk", "ms", "mt", "no", "fa", "pl", "pt", "ro", "ru", "sr", "sk", "sl", "es", "sw", "sv", "ta", "te", "th", "tr", "uk", "ur", "vi", "cy", "yi"];

    if (!in_array($templates_lang, $ggllanguages) || !in_array($lang, $ggllanguages))
        return 'error';
    $gt = new GoogleTranslate();
    $response = $gt->translate($text_content, $templates_lang, $lang);
    return $response ?? 'error';
}