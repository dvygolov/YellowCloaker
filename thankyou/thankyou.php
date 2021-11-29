<?php
//Для отображение отладочной информации
ini_set('display_errors','1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require_once '../settings.php';
require_once '../pixels.php';
require_once '../htmlinject.php';
require_once '../db.php';
require_once '../cookies.php';

function mb_basename($path) {
    if (preg_match('@^.*[\\\\/]([^\\\\/]+)$@s', $path, $matches)) {
        return $matches[1];
    } else if (preg_match('@^([^\\\\/]+)$@s', $path, $matches)) {
        return $matches[1];
    }
    return '';
}

$ispost=($_SERVER['REQUEST_METHOD']==='POST');
if ($ispost){
    $name = isset($_POST['name'])?$_POST['name']:'';
    $phone = isset($_POST['phone'])?$_POST['phone']:'';
    $subid = isset($_POST['subid'])?$_POST['subid']:'';
    $email = isset($_POST['email'])?$_POST['email']:'';
    $lang = isset($_POST['language'])?$_POST['language']:'';
    add_email($subid,$email);
}

$filepath = __DIR__.'/templates/'.$black_land_thankyou_page_language.'.html';
if (!file_exists($filepath))
	$filepath = __DIR__.'/templates/EN.html';

$html = file_get_contents($filepath);
//добавляем в страницу скрипт GTM
$html = insert_gtm_script($html);
//добавляем в страницу скрипт Yandex Metrika
$html = insert_yandex_script($html);
//отстукиваем пиксель только если это не дубль, если дубль - то нам придёт nopixel=1
if (empty($_GET['nopixel']))
{
    $html = insert_fbpixel_script($html,$fb_thankyou_event);
    $html = insert_ttpixel_script($html,$tt_thankyou_event);
}

$search='{NAME}';
$html = str_replace($search,get_cookie('name'),$html);
$search='{PHONE}';
$html = str_replace($search,get_cookie('phone'),$html);

//добавляем на стр Спасибо допродажи
if ($thankyou_upsell===true){
    //вставляем все нужные стили и скрипты
    $scripts_html=file_get_contents(__DIR__.'/upsell/upsell.js');
    $css_html=file_get_contents(__DIR__.'/upsell/upsell.css');
    $html=insert_after_tag($html,'<head',$scripts_html);
    $html=insert_after_tag($html,'<head',$css_html);

    //вставляем все картинки в витрину, заполняем заголовок и текст
    $dir = __DIR__.'/upsell/'.$thankyou_upsell_imgdir;
    if (is_dir($dir))
    {
        $upsell_template=file_get_contents(__DIR__.'/upsell/upsell.template.html');
        $upsell_template=str_replace('{HEADER}',$thankyou_upsell_header,$upsell_template);
        $upsell_template=str_replace('{TEXT}',$thankyou_upsell_text,$upsell_template);
        $upsell_template=str_replace('{URL}',$thankyou_upsell_url,$upsell_template);

        $istart='{ITEMSTART}';
        $istartpos = strpos($upsell_template,$istart);
        $istartlen = strlen($istart);
        $iend='{ITEMEND}';
        $iendpos=strpos($upsell_template,$iend);
        $iendlen = strlen($iend);

        $item_template = trim(substr($upsell_template,$istartpos+$istartlen,$iendpos-($istartpos+$istartlen)));
        $images=glob("{$dir}/*.{jpg,jpeg,png}", GLOB_BRACE);
        $all_images_html='';
        foreach ($images as $image)
        {
            $all_images_html.= str_replace('{URL}',$thankyou_upsell_url,
                str_replace('{IMG}','upsell/'.$thankyou_upsell_imgdir.'/'.mb_basename($image),$item_template));
        }
        $upsell_template = substr_replace($upsell_template,$all_images_html,$istartpos,$iendpos+$iendlen-$istartpos);

        $html = str_replace('{UPSELL}',$upsell_template,$html);
    }
    else
        $html = str_replace('{UPSELL}','',$html);
}
else{
    $html = str_replace('{UPSELL}','',$html);
}
//вставляем форму сбора мыла или сообщение об успешном сборе мыла
if($ispost){
        $emailtemplatepath = __DIR__.'/templates/email/'.$black_land_thankyou_page_language.'save.html';
        if (!file_exists($emailtemplatepath))
        {
            $html = str_replace('{EMAIL}','',$html);
        }
        else{
            $html = str_replace('{EMAIL}',file_get_contents($emailtemplatepath),$html);
        }
}
else{
    $subid=get_subid();
    if(empty($subid)||email_exists_for_subid($subid)){
        $html = str_replace('{EMAIL}','',$html);
    }
    else{
        $emailtemplatepath = __DIR__.'/templates/email/'.$black_land_thankyou_page_language.'fill.html';
        if (!file_exists($emailtemplatepath))
        {
            $html = str_replace('{EMAIL}','',$html);
        }
        else{
            $html = str_replace('{EMAIL}',file_get_contents($emailtemplatepath),$html);
        }
    }
}

$needle = '</form>';
$str_to_insert = '<input type="hidden" name="name" value="'.get_cookie('name').'"/>';
$html = insert_before_tag($html,$needle,$str_to_insert);
$str_to_insert = '<input type="hidden" name="phone" value="'.get_cookie('phone').'"/>';
$html = insert_before_tag($html,$needle,$str_to_insert);
$str_to_insert = '<input type="hidden" name="subid" value="'.get_subid().'"/>';
$html = insert_before_tag($html,$needle,$str_to_insert);
$str_to_insert = '<input type="hidden" name="language" value="'.$black_land_thankyou_page_language.'"/>';
$html = insert_before_tag($html,$needle,$str_to_insert);
echo $html;