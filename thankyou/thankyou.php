<?php

require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../pixels.php';
require_once __DIR__ . '/../htmlinject.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../cookies.php';

function mb_basename($path)
{
    if (preg_match('@^.*[\\\\/]([^\\\\/]+)$@s', $path, $matches)) {
        return $matches[1];
    } else if (preg_match('@^([^\\\\/]+)$@s', $path, $matches)) {
        return $matches[1];
    }
    return '';
}

$ispost = ($_SERVER['REQUEST_METHOD'] === 'POST');
if ($ispost) {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subid = $_POST['subid'] ?? '';
    $email = $_POST['email'] ?? '';
    $lang = $_POST['language'] ?? '';
    add_email($subid, $email);
}

$filepath = __DIR__ . '/templates/' . $black_land_thankyou_page_language . '.html';
if (!file_exists($filepath))
    $filepath = __DIR__ . '/templates/EN.html';

$html = file_get_contents($filepath);
//добавляем в страницу скрипт GTM
$html = insert_gtm_script($html);
//добавляем в страницу скрипт Yandex Metrika
$html = insert_yandex_script($html);
//отстукиваем пиксель только если это не дубль, если дубль - то нам придёт nopixel=1
if (empty($_GET['nopixel'])) {
    $html = insert_fbpixel_script($html, $fb_thankyou_event);
    $html = insert_ttpixel_script($html, $tt_thankyou_event);
}

$html = str_replace('{NAME}', get_cookie('name'), $html);
$html = str_replace('{PHONE}', get_cookie('phone'), $html);

//вставляем форму сбора мыла или сообщение об успешном сборе мыла
if ($ispost) {
    $emailtemplatepath = __DIR__ . '/templates/email/' . $black_land_thankyou_page_language . 'save.html';
    if (!file_exists($emailtemplatepath)) {
        $html = str_replace('{EMAIL}', '', $html);
    } else {
        $html = str_replace('{EMAIL}', file_get_contents($emailtemplatepath), $html);
    }
} else {
    $subid = get_subid();
    $db=new Db();
    if (empty($subid) || $db->email_exists_for_subid($subid)) {
        $html = str_replace('{EMAIL}', '', $html);
    } else {
        $emailtemplatepath = __DIR__ . '/templates/email/' . $black_land_thankyou_page_language . 'fill.html';
        if (!file_exists($emailtemplatepath)) {
            $html = str_replace('{EMAIL}', '', $html);
        } else {
            $html = str_replace('{EMAIL}', file_get_contents($emailtemplatepath), $html);
        }
    }
}

$str_to_insert = '<input type="hidden" name="name" value="' . get_cookie('name') . '"/>';
$str_to_insert .= '<input type="hidden" name="phone" value="' . get_cookie('phone') . '"/>';
$str_to_insert .= '<input type="hidden" name="subid" value="' . get_subid() . '"/>';
$str_to_insert .= '<input type="hidden" name="language" value="' . $black_land_thankyou_page_language . '"/>';
$html = insert_before_tag($html, '</form>', $str_to_insert);
echo $html;