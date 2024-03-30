<?php
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/requestfunc.php';

$name = '';
if (isset($_POST['name']))
    $name = $_POST['name'];
else if (isset($_POST['fio']))
    $name = $_POST['fio'];
else if (isset($_POST['first_name']) && isset($_POST['last_name']))
    $name = $_POST['first_name'] . ' ' . $_POST['last_name'];
else if (isset($_POST['firstname']) && isset($_POST['lastname']))
    $name = $_POST['firstname'] . ' ' . $_POST['lastname'];

$phone = '';
if (isset($_POST['phone']))
    $phone = $_POST['phone'];
else if (isset($_POST['tel']))
    $phone = $_POST['tel'];

$subid = get_cookie('subid');
if ($subid === '' && isset($_POST['subid']))
    $subid = $_POST['subid'];

//если юзверь каким-то чудом отправил пустые поля в форме
if ($name === '' || $phone === '') {
    redirect('thankyou/thankyou.php?nopixel=1');
    return;
}

$is_duplicate = has_conversion_cookies($name, $phone);
//устанавливаем пользователю в куки его имя и телефон, чтобы показать их на стр Спасибо
//также ставим куки даты конверсии
ywbsetcookie('name', $name, '/');
ywbsetcookie('phone', $phone, '/');
ywbsetcookie('ctime', (new DateTime())->getTimestamp(), '/');

//шлём в ПП только если это не дубль
if ($is_duplicate) {
    redirect('thankyou/thankyou.php?nopixel=1');
    return;
}

$fullpath = '';
$original_action = $_GET['original_action'];
//если у формы прописан в action адрес, а не локальный скрипт, то шлём все данные формы на этот адрес
if (str_starts_with($original_action, "http")) {
    $fullpath = $original_action;
} //иначе составляем полный адрес до скрипта отправки ПП
else {
    $url = get_cookie('landing') . '/' . $original_action;
    $fullpath = get_abs_from_rel($url);
}

$post_data = http_build_query($_POST);
$res = post($fullpath, $post_data);

$db = new Db();
//в ответе должен быть редирект, если его нет - грузим обычную страницу Спасибо кло
switch ($res["info"]["http_code"]) {
    case 302:
        $db->add_lead($subid, $name, $phone);
        if ($black_land_use_custom_thankyou_page) {
            redirect("thankyou/thankyou.php?" . http_build_query($_GET));
        } else {
            redirect($res["info"]["redirect_url"]);
        }
        break;
    case 200:
        $db->add_lead($subid, $name, $phone);
        if ($black_land_use_custom_thankyou_page) {
            jsredirect("thankyou/thankyou.php?" . http_build_query($_GET));
        } else {
            echo $res["html"];
        }
        break;
    default:
        echo $fullpath."<br/>";
        var_dump($res["html"]);
        echo '<br/>';
        var_dump($res["error"]);
        echo '<br/>';
        var_dump($res["info"]);
        echo '<br/>';
        var_dump($post_data);
        exit();
        break;
}