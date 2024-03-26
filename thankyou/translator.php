<?php
include __DIR__ . '/translators/google.php';
include __DIR__ . '/translators/deepl.php';
include __DIR__ . '/translators/libretranslate.php';

function translate($text_content, $templates_lang, $lang)
{
    $ggl = new GoogleTranslator();
    if ($ggl->checkLanguages($templates_lang, $lang))
        return $ggl->translate($text_content, $templates_lang, $lang);

    $dpl = new Deepl();
    if ($dpl->checkLanguages($templates_lang, $lang))
        return $dpl->translate($text_content, $templates_lang, $lang);

    $lt = new LibreTranslate();
    if ($lt->checkLanguages($templates_lang, $lang))
        return $dpl->translate($text_content, $templates_lang, $lang);

    return 'error';
}