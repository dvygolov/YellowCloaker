<?php
//Включение отладочной информации
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
//Конец включения отладочной информации
include '../settings.php';

if ($log_password!==''&&(empty($_GET['password'])||$_GET['password'] !== $log_password)) {
    echo 'No Password Given!';
    exit();
}

$startdate=isset($_GET['startdate'])?DateTime::createFromFormat('d.m.y', $_GET['startdate']):new DateTime();
$enddate=isset($_GET['enddate'])?DateTime::createFromFormat('d.m.y', $_GET['enddate']):new DateTime();

$date_str='';
if (isset($_GET['startdate'])&& isset($_GET['enddate'])) {
    $startstr = $_GET['startdate'];
    $endstr = $_GET['enddate'];
    $date_str="&startdate={$startstr}&enddate={$endstr}";
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Binomo Cloaker - Dashboard v1.0.0</title>
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/js/main.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>

    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- favicon
		============================================ -->
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png" />
    <!-- Google Fonts
		============================================ -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet" />
    <!-- nalika Icon CSS
		============================================ -->
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/nalika-icon.css" />
    <!-- main CSS
		============================================ -->
    <link rel="stylesheet" href="css/main.css" />
    <!-- metisMenu CSS
		============================================ -->
    <link rel="stylesheet" href="css/metisMenu/metisMenu.min.css" />
    <link rel="stylesheet" href="css/metisMenu/metisMenu-vertical.css" />
    <!-- style CSS
		============================================ -->
    <link rel="stylesheet" href="css/style.css" />
</head>

<body>
    <div class="left-sidebar-pro">
        <nav id="sidebar" class="">
            <div class="sidebar-header">
                <a href="/admin/index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><img class="main-logo" src="img/logo/logo.png" alt="" /></a>
                <strong><img src="img/favicon.png" alt="" style="width:50px"/></strong>
            </div>
			<div class="nalika-profile">
				<div class="profile-dtl">
					<a href="https://t.me/yellow_web"><img src="img/notification/4.jpg" alt="" /></a>
					<h2><a href="https://yellowweb.top/donate" target="_blank">Помощь автору проекта! </a></h2>
				</div>
			</div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                  <ul class="metismenu" id="menu1">
                        <li class="active">

                            <a class="has-arrow" href="index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>" aria-expanded="false"><i class="icon nalika-bar-chart icon-wrap"></i> <span class="mini-click-non">Traffic</span></a>
                            <ul class="submenu-angle" aria-expanded="false">
                                <li><a title="Стата" href="statistics.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><span class="mini-sub-pro">Statistics</span></a></li>
                                <li><a title="Разрешённый" href="index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><span class="mini-sub-pro">Allowed</span></a></li>
                                <li><a title="Лиды" href="index.php?filter=leads&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><span class="mini-sub-pro">Leads</span></a></li>
                                <li><a title="Заблокированный" href="index.php?filter=blocked&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><span class="mini-sub-pro">Blocked</span></a></li>
                                <li><a title="Почты" href="index.php?filter=emails&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><span class="mini-sub-pro">Emails</span></a></li>

                                <li><a title="Peity Charts" href="#bottom"><span class="mini-sub-pro">Go to bottom</span></a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="settings.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>" aria-expanded="false"><i class="icon nalika-table icon-wrap"></i> <span class="mini-click-non">Settings</span></a>
                        </li>
                  </ul>
                </nav>
            </div>
        </nav>
    </div>
    <!-- Start Welcome area -->
    <div class="all-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="logo-pro">
                        <a href="index.html"><img class="main-logo" src="img/logo/logo.png" alt="" /></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="header-advance-area">
            <div class="header-top-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="header-top-wraper">
                                <div class="row">
                                    <div class="col-lg-1 col-md-0 col-sm-1 col-xs-12">
                                        <div class="menu-switcher-pro">
                                            <button type="button" id="sidebarCollapse" class="btn bar-button-pro header-drl-controller-btn btn-info navbar-btn">
													<i class="icon nalika-menu-task"></i>
												</button>
                                        </div>
                                    </div>

                                    <div class="col-lg-11 col-md-1 col-sm-12 col-xs-12">
                                        <div class="header-right-info">
                                            <ul class="nav navbar-nav mai-top-nav header-right-menu">
                                                <li class="nav-item dropdown">
                             <li class="nav-item">
                            <a class="nav-link" href="" onClick="location.reload()">Refresh</a>
                            </li>
                                                </i>
                                            </ul>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    <a name="top"></a>

    <form action="savesettings.php?password=<?=$log_password?>" method="post">
        <div class="basic-form-area mg-tb-15">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="sparkline12-list">
                            <div class="sparkline12-graph">
                                <div class="basic-login-form-ad">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="all-form-element-inner">

                                                    <hr>
                                                    <h4>#1 Настройка Javascript-проверок</h4>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Использовать JS проверку? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$use_js_checks===false?'checked="checked"':''?> value="false" name="white.jschecks.enabled" onclick="(document.getElementById('jscheckssettings').style.display = 'none')"> Нет, не использовать </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" value="true" <?=$use_js_checks===true?'checked="checked"':''?> name="white.jschecks.enabled" onclick="(document.getElementById('jscheckssettings').style.display = 'block')">  Использовать </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                <div id="jscheckssettings" style="display:<?=$use_js_checks===true?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Что проверять? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="checkbox" name="white.jschecks.events[]" value="mousemove" <?=in_array('mousemove',$js_checks)?'checked':''?>> Движения мыши </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="checkbox" name="white.jschecks.events[]" value="keydown" <?=in_array('keydown',$js_checks)?'checked':''?>> Нажатия клавиш </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="checkbox" name="white.jschecks.events[]" value="scroll" <?=in_array('scroll',$js_checks)?'checked':''?>> Скроллинг </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="checkbox" name="white.jschecks.events[]" value="wheel" <?=in_array('wheel',$js_checks)?'checked':''?>> Колесо мыши </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="checkbox" name="white.jschecks.events[]" value="devicemotion" <?=in_array('devicemotion',$js_checks)?'checked':''?>> Датчик движения (только для Android)</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="checkbox" name="white.jschecks.events[]" value="deviceorientation" <?=in_array('deviceorientation',$js_checks)?'checked':''?>> Датчик ориентации в пространстве (только для Android)</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Маскировать код JS-проверки? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" value="true" <?=$js_obfuscate===true?'checked="checked"':''?> name="white.jschecks.obfuscate">  Маскировать </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" value="false" <?=$js_obfuscate===false?'checked="checked"':''?> name="white.jschecks.obfuscate"> Нет, не маскировать </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Время теста в миллисекундах: </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="10000" name="white.jschecks.timeout" value="<?=$js_timeout?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <p>Если проверка по JS включена, то пользователь всегда попадает вначале на вайт, и только если проверки пройдены, тогда ему показывается блэк.
                                                </div>
                                            </div>
                                                    <br>
                                                    <hr>
                                                    <h4>#2 Настройка вайта</h4>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Выберите метод: </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_action==='folder'?'checked':''?> value="folder" name="white.action" onclick="(document.getElementById('b_2').style.display='block'); (document.getElementById('b_3').style.display='none'); (document.getElementById('b_4').style.display='none'); (document.getElementById('b_5').style.display='none')"> Локальный вайт-пейдж из папки </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_action==='redirect'?'checked':''?> value="redirect" name="white.action" onclick="(document.getElementById('b_2').style.display='none'); (document.getElementById('b_3').style.display='block'); (document.getElementById('b_4').style.display='none'); (document.getElementById('b_5').style.display='none')"> Редирект </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_action==='curl'?'checked':''?> value="curl" name="white.action" onclick="(document.getElementById('b_2').style.display='none'); (document.getElementById('b_3').style.display='none'); (document.getElementById('b_4').style.display='block'); (document.getElementById('b_5').style.display='none')">  Подгрузка внешнего сайта через curl </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_action==='error'?'checked':''?> value="error" name="white.action" onclick="(document.getElementById('b_2').style.display='none'); (document.getElementById('b_3').style.display='none'); (document.getElementById('b_4').style.display='none'); (document.getElementById('b_5').style.display='block')">  Возврат HTTP-кода <small>(например, ошибки 404 или просто 200)</small> </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <div id="b_2" style="display:<?=$white_action==='folder'?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Папка, где лежит вайт: </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="white" name="white.folder.name" value="<?=$white_folder_name?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_3" style="display:<?=$white_action==='redirect'?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Адрес для редиректа: </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="https://ya.ru" name="white.redirect.url" value="<?=$white_redirect_url?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Выберите код редиректа: </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_redirect_type===301?'checked':''?> value="301" name="white.redirect.type"> 301 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_redirect_type===302?'checked':''?> value="302" name="white.redirect.type"> 302 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_redirect_type===303?'checked':''?> value="303" name="white.redirect.type">  303 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_redirect_type===307?'checked':''?> value="307" name="white.redirect.type">  307 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="b_4" style="display:<?=$white_action==='curl'?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Адрес для подгрузки через curl: </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="https://ya.ru" name="white.curl.url" value="<?=$white_curl_url?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="b_5" style="display:<?=$white_action==='error'?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">HTTP-код для возврата вместо вайта: </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="404" name="white.error.code" value="<?=$white_error_code?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Показывать индивидуальный вайт под каждый домен? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_use_domain_specific===false?'checked':''?> value="false" name="white.domainfilter.use" onclick="(document.getElementById('b_6').style.display='none')"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$white_use_domain_specific===true?'checked':''?> value="true" name="white.domainfilter.use" onclick="(document.getElementById('b_6').style.display='block')"> Да, показывать </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                <div id="b_6" style="display:<?=$white_use_domain_specific===true?'block':'none'?>;">
                                                    <div id="white_domainspecific">
                                                    <?php for($j=0;$j<count($white_domain_specific);$j++){ ?>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                <label class="login2 pull-left pull-left-pro">Домен => Метод:Направление</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                 <div class="input-group">
                                                                    <input type="text" class="form-control" placeholder="xxx.yyy.com" value="<?=$white_domain_specific[$j]["name"]?>" name="white.domainfilter.domains[<?=$j?>][name]">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                 <p>=></p>  
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control" placeholder="site:white" value="<?=$white_domain_specific[$j]["action"]?>" name="white.domainfilter.domains[<?=$j?>][action]">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                <a href="javascript:void(0)" class="remove-domain-item btn btn-sm btn-primary">Удалить</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php } ?>
                                                    </div>
                                                    <a id="add-domain-item" class="btn btn-sm btn-primary" href="javascript:;">Добавить</a>
                                                </div>

                                                    <br>
                                                    <hr>
                                                    <h4>#3 Настройка блэка</h4>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Выберите метод загрузки прокладок: </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_preland_action==='none'?'checked':''?> value="none" name="black.prelanding.action" onclick="(document.getElementById('b_7').style.display='none'); (document.getElementById('b_8').style.display='none')"> Не использовать прелендинг </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_preland_action==='folder'?'checked':''?> value="folder" name="black.prelanding.action" onclick="(document.getElementById('b_7').style.display='none'); (document.getElementById('b_8').style.display='block')"> Локальный прелендинг из папки </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_preland_action==='redirect'?'checked':''?> value="redirect" name="black.prelanding.action" onclick="(document.getElementById('b_7').style.display='block'); (document.getElementById('b_8').style.display='none')"> Редирект </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                <div id="b_7" style="display:<?=$black_preland_action==='redirect'?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Адреса для редиректа: <small>(Можно НЕСКОЛЬКО через запятую)</small> </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="https://ya.ru,https://google.com" name="black.prelanding.redirect.urls" value="<?=implode(',',$black_preland_redirect_urls)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Выберите код редиректа: </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_preland_redirect_type===301?'checked':''?> value="301" name="black.prelanding.redirect.type"> 301 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_preland_redirect_type===302?'checked':''?> value="302" name="black.prelanding.redirect.type"> 302 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_preland_redirect_type===303?'checked':''?> value="303" name="black.prelanding.redirect.type">  303 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_preland_redirect_type===307?'checked':''?> value="307" name="black.prelanding.redirect.type">  307 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="b_8" style="display:<?=$black_preland_action==='folder'?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Папки, где лежат прелендинги </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="p1,p2" name="black.prelanding.folders" value="<?=implode(',',$black_preland_folder_names)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Выберите метод загрузки лендингов: </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                    <input type="radio" <?=$black_land_action==='folder'?'checked':''?> value="folder" name="black.landing.action" onclick="(document.getElementById('b_landings_redirect').style.display='none'); (document.getElementById('b_landings_folder').style.display='block')"> Локальный лендинг из папки </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                    <input type="radio" <?=$black_land_action==='redirect'?'checked':''?> value="redirect" name="black.landing.action" onclick="(document.getElementById('b_landings_redirect').style.display='block'); (document.getElementById('b_landings_folder').style.display='none')"> Редирект </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_landings_folder" style="display:<?=$black_land_action==='folder'?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Папки, где лежат лендинги </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="l1,l2" name="black.landing.folder.names" value="<?=implode(',',$black_land_folder_names)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Использовать страницу Спасибо: </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_use_custom_thankyou_page===true?'checked':''?> value="true" name="black.landing.folder.customthankyoupage.use" onclick="(document.getElementById('ctpage').style.display = 'block'); (document.getElementById('pppage').style.display = 'none')"> Кастомную, на стороне кло </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_use_custom_thankyou_page===false?'checked':''?> value="false" name="black.landing.folder.customthankyoupage.use" onclick="(document.getElementById('ctpage').style.display = 'none'); (document.getElementById('pppage').style.display = 'block')"> Обычную, на стороне ПП </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div id="ctpage" class="form-group-inner" style="display:<?=$black_land_use_custom_thankyou_page===true?'block':'none'?>">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Язык, на котором показывать страницу Спасибо Кло: </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="EN" name="black.landing.folder.customthankyoupage.language" value="<?=$black_land_thankyou_page_language?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro"> Путь от корня лендинга до скрипта отправки данных с формы:</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="order.php" name="black.landing.folder.conversions.script" value="<?=$black_land_conversion_script?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="pppage" class="form-group-inner" style="display:<?=$black_land_use_custom_thankyou_page===false?'block':'none'?>">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Добавить в обработчик кнопки на ленде подсчёт конверсий? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_log_conversions_on_button_click===false?'checked':''?> value="false" name="black.landing.folder.conversions.logonbuttonclick"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_log_conversions_on_button_click===true?'checked':''?> value="true" name="black.landing.folder.conversions.logonbuttonclick"> Да </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Откуда отстукивать конверсию? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$fb_add_button_pixel===false?'checked':''?> value="false" name="pixels.fb.conversion.fireonbutton"> Со страницы спасибо </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$fb_add_button_pixel===true?'checked':''?> value="true" name="pixels.fb.conversion.fireonbutton"> С кнопки на ленде </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_landings_redirect" style="display:<?=$black_land_action==='redirect'?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Адреса для редиректа: <small>(Можно НЕСКОЛЬКО через запятую)</small> </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="https://ya.ru,https://google.com" name="black.landing.redirect.urls" value="<?=implode(',',$black_land_redirect_urls)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Выберите код редиректа: </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_redirect_type===301?'checked':''?> value="301" name="black.landing.redirect.type"> 301 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_redirect_type===302?'checked':''?> value="302" name="black.landing.redirect.type"> 302 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_redirect_type===303?'checked':''?> value="303" name="black.landing.redirect.type">  303 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_redirect_type===307?'checked':''?> value="307" name="black.landing.redirect.type">  307 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                    <br>
                                                    <hr>
                                                    <h4>#4 Настройка метрик и пикселей</h4>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Идентификатор Google Tag Manager: </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder=" " name="pixels.gtm.id" value="<?=$gtm_id?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Идентификатор Яндекс.Метрики: </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="" name="pixels.ya.id" value="<?=$ya_id?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Имя параметра в котором лежит ID пикселя Facebook: </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="px" name="pixels.fb.subname" value="<?=$fbpixel_subname?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Добавлять ли на проклы-ленды событие PageView? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$fb_use_pageview===false?'checked':''?> value="false" name="pixels.fb.pageview"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$fb_use_pageview===true?'checked':''?> value="true" name="pixels.fb.pageview"> Да, добавлять </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Добавлять событие ViewContent после просмотра страницы в течении указанного ниже времени? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$fb_use_viewcontent===false?'checked':''?> value="false" name="pixels.fb.viewcontent.use" onclick="(document.getElementById('b_8-2').style.display='none')"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$fb_use_viewcontent===true?'checked':''?> value="true" name="pixels.fb.viewcontent.use" onclick="(document.getElementById('b_8-2').style.display='block')"> Да, добавлять </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div id="b_8-2" style="display:<?=$fb_use_viewcontent===true?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Время в сек после которго отправляется ViewContent:<br><small>если 0, то событие не будет вызвано</small> </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="30" name="pixels.fb.viewcontent.time" value="<?=$fb_view_content_time?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Процент проскролливания страницы, до вызова события ViewContent:<br><small>если 0, то событие не будет вызвано</small> </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="75" name="pixels.fb.viewcontent.percent" value="<?=$fb_view_content_percent?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Какое событие будем использовать для конверсии? <small>Например: Lead или Purchase</small></label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="Lead" name="pixels.fb.conversion.event" value="<?=$fb_thankyou_event?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Клоачить ли пиксель? <small>по методу <a href="https://tgraph.me/Kloachim-FB-Pixel-bez-iframe-02-15">из вот этой статьи</a></small></label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$use_cloaked_pixel===false?'checked':''?> value="false" name="pixels.fb.cloak"> Нет, не клоачить </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$use_cloaked_pixel===true?'checked':''?> value="true" name="pixels.fb.cloak"> Да, клоачить </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <br>
                                                    <hr>
                                                    <h4>#5 Настройка TDS</h4>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Посылать всех на Вайт? <br><small>например, при модерации</small></label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$full_cloak_on===false?'checked':''?> value="false" name="tds.fullcloak"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$full_cloak_on===true?'checked':''?> value="true" name="tds.fullcloak"> Да, посылать </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Посылать всех на Блэк? <br><small>например, при настройке flow</small></label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$disable_tds===false?'checked':''?> value="false" name="tds.disable"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$disable_tds===true?'checked':''?> value="true" name="tds.disable"> Да, посылать </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Посылать одного и того же юзера на одни и те же проклы-ленды?</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$save_user_flow===false?'checked':''?> value="false" name="tds.saveuserflow"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$save_user_flow===true?'checked':''?> value="true" name="tds.saveuserflow"> Да, посылать </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <br>
                                                    <hr>
                                                    <h4>#6 Настройка фильтров</h4>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Список разрешённых ОС:</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="tds.filters.allowed.os" class="form-control" placeholder="Android,iOS,Windows,OS X" value="<?=implode(',',$os_white)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Список разрешённых стран: <small>(WW или пустое значение для всего мира)</small></label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="tds.filters.allowed.countries" class="form-control" placeholder="RU,UA" value="<?=implode(',',$country_white)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">IP через запятую, которые будут отправлены на white page</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="tds.filters.blocked.ips" class="form-control" placeholder="0.0.0.1,192.161.0.1" value="<?=implode(',',$ip_black)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Слова через запятую, при наличии которых в адресе перехода (в ссылке, по которой перешли), юзер будет отправлен на whitepage</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Слова через запятую, которые обязательно должны быть в адресе. Если хотя бы чего-то нет - показывается вайт</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="tds.filters.allowed.inurl" class="form-control" placeholder="" value="<?=implode(',',$url_should_contain)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Слова через запятую, при наличии которых в UserAgent, юзер будет отправлен на whitepage</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control" placeholder="facebook,Facebot,curl,gce-spider,yandex.com/bots" name="tds.filters.blocked.useragents" value="<?=implode(',',$ua_black)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Блокировка по провадеру (ISP), например: facebook,google</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="tds.filters.blocked.isps" class="form-control" placeholder="facebook,google,yandex,amazon,azure,digitalocean" value="<?=implode(',',$isp_black)?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Посылать все запросы без referer на whitepage?</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$block_without_referer===false?'checked':''?> value="false" name="tds.filters.blocked.withoutreferer"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$block_without_referer===true?'checked':''?> value="true" name="tds.filters.blocked.withoutreferer"> Да </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Посылать всех, использующих VPN и Tor на вайт?</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$block_vpnandtor===false?'checked':''?> value="false" name="tds.filters.blocked.vpntor"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$block_vpnandtor===true?'checked':''?> value="true" name="tds.filters.blocked.vpntor"> Да </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <br>
                                                    <hr>
                                                    <h4>#7 Настройка дополнительных скриптов</h4>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Что делать с кнопкой Назад?</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$back_button_action==='off'?'checked':''?> value="off" name="scripts.back.action" onclick="(document.getElementById('b_9').style.display='none')"> Оставить по умолчанию </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$back_button_action==='disable'?'checked':''?> value="disable" name="scripts.back.action" onclick="(document.getElementById('b_9').style.display='none')"> Отключить (перестает нажиматься)</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$back_button_action==='replace'?'checked':''?> value="replace" name="scripts.back.action" onclick="(document.getElementById('b_9').style.display='block')"> Повесить на нее редирект на URL</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <div id="b_9" style="display:<?=$back_button_action==='replace'?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Куда направлять при нажатии Назад?</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="scripts.back.value" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?=$replace_back_address?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Запретить выделять и сохранять текст по Ctrl+S, убирать контекстное меню?</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$disable_text_copy===false?'checked':''?> value="false" name="scripts.disabletextcopy"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$disable_text_copy===true?'checked':''?> value="true" name="scripts.disabletextcopy"> Да, запретить </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Открывать ссылки на ленд в новом окне с подменой в старом окне проклы на URL указанный ниже?</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$replace_prelanding===false?'checked':''?> value="false" name="scripts.prelandingreplace.use" onclick="(document.getElementById('b_10').style.display='none')"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$replace_prelanding===true?'checked':''?> value="true" name="scripts.prelandingreplace.use" onclick="(document.getElementById('b_10').style.display='block')"> Да, открывать  </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                <div id="b_10" style="display:<?=$replace_prelanding===true?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">URL который откроется в старом окне:</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="scripts.prelandingreplace.url" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?=$replace_prelanding_address?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">К полю ввода телефона НА ЛЕНДИНГЕ будет добавлена маска указанная ниже</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_use_phone_mask===false?'checked':''?> value="false" name="scripts.phonemask.use" onclick="(document.getElementById('b_11').style.display='none')"> Нет </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
																						<input type="radio" <?=$black_land_use_phone_mask===true?'checked':''?> value="true" name="scripts.phonemask.use" onclick="(document.getElementById('b_11').style.display='block')"> Да, добавить маску </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                <div id="b_11" style="display:<?=$black_land_use_phone_mask===true?'block':'none'?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Укажите маску:</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="scripts.phonemask.mask" class="form-control" placeholder="+421 999 999 999" value="<?=$black_land_phone_mask?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                    <br>
                                                    <hr>
                                                    <h4>#8 Настройка суб-меток</h4>
                                                    <p>Кло берёт из адресной строки те субметки, что слева и:<br>
                                                       1. Если у вас локальный ленд, то кло записывает значения меток в каждую форму на ленде в поля с именами, которые справа<br>
                                                       2. Если у вас ленд в ПП, то кло дописывает значения меток к ссылке ПП с именами, которые справа<br>
                                                       Таким образом мы передаём значения субметок в ПП, чтобы в стате ПП отображалась нужная нам инфа <br>
                                                       Ну и плюс это нужно для того, чтобы передавать subid для постбэка<br>
                                                       Есть 3 "зашитые" метки: <br>
                                                       - subid - уникальный идентификатор пользователя, создаётся при заходе пользователя на блэк, хранится в куки<br>
                                                       - prelanding - название папки преленда<br>
                                                       - landing - название папки ленда<br><br />
                                                       Пример: <br>
                                                       у вас в адресной строке было http://xxx.com?cn=MyCampaign<br>
                                                       вы написали в настройке: cn => utm_campaign <br />
                                                       в форме на ленде добавится <pre>&lt;input type="hidden" name="utm_campaign" value="MyCampaign"/&gt;</pre>
                                                    </p>
                                                    <div id="subs_container">
                                                        <?php  for ($i=0;$i<count($sub_ids);$i++){?>
                                                            <div class="form-group-inner">
                                                                <div class="row">
                                                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                         <div class="input-group">
                                                                            <input type="text" class="form-control" placeholder="subid" value="<?=$sub_ids[$i]["name"]?>" name="subids[<?=$i?>][name]">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                        <p>=></p>
                                                                    </div>
                                                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                        <div class="input-group custom-go-button">
                                                                            <input type="text" class="form-control" placeholder="sub_id" value="<?=$sub_ids[$i]["rewrite"]?>" name="subids[<?=$i?>][rewrite]">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                        <a href="javascript:void(0)" class="remove-sub-item btn btn-sm btn-primary">Удалить</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php }?>
                                                        <a id="add-sub-item" class="btn btn-sm btn-primary" href="javascript:;">Добавить</a>
                                                    </div>

                                                    <br>
                                                    <hr>
                                                    <h4>#9 Настройка статистики и постбэка</h4>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Пароль от админ-панели: <br><small>добавлять как: /admin?password=xxxxx</small></label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="statistics.password" class="form-control" placeholder="12345" value="<?=$log_password?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Название метки в которой из источника трафика приходит название креатива</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="statistics.creativesubname" class="form-control" placeholder="an" value="<?=$creative_sub_name?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <br>
                                                    <p>Здесь необходимо прописать статусы лидов, в том виде, как их вам отправляет в постбэке ПП:</p>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Lead</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="statistics.postback.lead" class="form-control" placeholder="Lead" value="<?=$lead_status_name?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Purchase</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="statistics.postback.purchase" class="form-control" placeholder="Purchase" value="<?=$purchase_status_name?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Reject</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="statistics.postback.reject" class="form-control" placeholder="Reject" value="<?=$reject_status_name?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Trash</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="statistics.postback.trash" class="form-control" placeholder="Trash" value="<?=$trash_status_name?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <hr>
                                                    <div class="form-group-inner">
                                                        <div class="login-btn-inner">
                                                            <div class="row">
                                                                <div class="col-lg-3"></div>
                                                                <div class="col-lg-9">
                                                                    <div class="login-horizental cancel-wp pull-left">
                                                                        <button class="btn btn-sm btn-primary" type="submit"><strong>Сохранить настройки</strong></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <a name="bottom"></a>
    </div>
    <!-- jquery
		============================================ -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- bootstrap JS
		============================================ -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!--cloneData-->
    <script src="js/cloneData.js"></script>
    <script>
    $('#add-domain-item').cloneData({
        mainContainerId: 'white_domainspecific',
        cloneContainer: 'form-group-inner',
        removeButtonClass: 'remove-domain-item',
        maxLimit: 5,
        minLimit: 1,
        removeConfirm: false
    });

    $('#add-sub-item').cloneData({
        mainContainerId: 'subs_container',
        cloneContainer: 'form-group-inner',
        removeButtonClass: 'remove-sub-item',
        maxLimit: 10,
        minLimit: 1,
        removeConfirm: false
    });
    </script>
    <!-- meanmenu JS
		============================================ -->
    <script src="js/jquery.meanmenu.js"></script>
    <!-- sticky JS
		============================================ -->
    <script src="js/jquery.sticky.js"></script>
    <!-- metisMenu JS
		============================================ -->
    <script src="js/metisMenu/metisMenu.min.js"></script>
    <script src="js/metisMenu/metisMenu-active.js"></script>
    <!-- plugins JS
		============================================ -->
    <script src="js/plugins.js"></script>
    <!-- main JS
		============================================ -->
    <script src="js/main.js"></script>
</body>

</html>