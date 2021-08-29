<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require_once 'settings.php';
require_once 'db.php';
require_once 'cookies.php';
require_once 'redirect.php';
require_once 'requestfunc.php';

$name = '';
if (isset($_POST['name']))
    $name=$_POST['name'];
else if (isset($_POST['fio']))
    $name=$_POST['fio'];
else if (isset($_POST['first_name'])&&isset($_POST['last_name']))
    $name = $_POST['first_name'].' '.$_POST['last_name'];
else if (isset($_POST['firstname'])&&isset($_POST['lastname']))
    $name = $_POST['firstname'].' '.$_POST['lastname'];

$phone='';
if (isset($_POST['phone']))
    $phone=$_POST['phone'];
else if (isset($_POST['tel']))
    $phone=$_POST['tel'];

$subid = get_subid();
if ($subid==='' && isset($_POST['subid']))
    $subid=$_POST['subid'];

//если юзверь каким-то чудом отправил пустые поля в форме
if ($name===''||$phone===''){
    redirect('thankyou.php?nopixel=1');
    return;
}

$date = new DateTime();
$ts = $date->getTimestamp();

$is_duplicate=has_conversion_cookies($name,$phone);
//устанавливаем пользователю в куки его имя и телефон, чтобы показать их на стр Спасибо
//также ставим куки даты конверсии
ywbsetcookie('name',$name,'/');
ywbsetcookie('phone',$phone,'/');
ywbsetcookie('ctime',$ts,'/');

//шлём в ПП только если это не дубль
if (!$is_duplicate){
    $fullpath='';
    //если у формы прописан в action адрес, а не локальный скрипт, то шлём все данные формы на этот адрес
    if (substr($black_land_conversion_script, 0, 4 ) === "http"){
        $fullpath=$black_land_conversion_script;
    }
    //иначе составляем полный адрес до скрипта отправки ПП
    else{
        $url= get_cookie('landing').'/'.$black_land_conversion_script;
        $fullpath = get_abs_from_rel($url);
    }

    //на всякий случай, перед отправкой чекаем, установлен ли subid
    $sub_rewrites=array_column($sub_ids,'rewrite','name');
    if (array_key_exists('subid',$sub_rewrites)){
        if (!isset($_POST[$sub_rewrites['subid']])||
            $_POST[$sub_rewrites['subid']]!==$subid)
            $_POST[$sub_rewrites['subid']]=$subid;
    }

    $res=post($fullpath,http_build_query($_POST));

    //в ответе должен быть редирект, если его нет - грузим обычную страницу Спасибо кло
    switch($res["info"]["http_code"]){
        case 302:
            add_lead($subid,$name,$phone);
            if ($black_land_use_custom_thankyou_page ){
                redirect("thankyou/thankyou.php?".http_build_query($_GET),302,false);
            }
            else{
                redirect($res["info"]["redirect_url"]);
            }
            break;
        case 200:
            add_lead($subid,$name,$phone);
            if ($black_land_use_custom_thankyou_page ){
                jsredirect("thankyou/thankyou.php?".http_build_query($_GET));
            }
            else{
                echo $res["html"];
            }
            break;
        default:
            var_dump($res["error"]);
            var_dump($res["info"]);
            exit();
            break;
    }
}
else
{
    redirect('thankyou/thankyou.php?nopixel=1');
}

?>