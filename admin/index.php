<?php
//Включение отладочной информации
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
//Конец включения отладочной информации
if (version_compare(phpversion(), '7.2.0', '<')) {
    die("PHP version should be 7.2 or higher! Change your PHP version and return.");
}
require_once '../settings.php';
require_once 'password.php';
check_password();
require_once 'db.php';

date_default_timezone_set($stats_timezone);
$startdate=isset($_GET['startdate'])?
    DateTime::createFromFormat('d.m.y', $_GET['startdate'],new DateTimeZone($stats_timezone)):
    new DateTime("now",new DateTimeZone($stats_timezone));
$enddate=isset($_GET['enddate'])?
    DateTime::createFromFormat('d.m.y', $_GET['enddate'],new DateTimeZone($stats_timezone)):
    new DateTime("now",new DateTimeZone($stats_timezone));
$startdate->setTime(0,0,0);
$enddate->setTime(23,59,59);

$date_str='';
if (isset($_GET['startdate'])&& isset($_GET['enddate'])) {
    $startstr = $_GET['startdate'];
    $endstr = $_GET['enddate'];
    $date_str="&startdate={$startstr}&enddate={$endstr}";
}

$filter=isset($_GET['filter'])?$_GET['filter']:'';

$dataDir = __DIR__ . "/../logs";
switch ($filter) {
    case '':
        $header = ["Subid","IP","Country","ISP","Time","OS","UA","QueryString","Preland","Land"];
        $dataset=get_black_clicks($startdate->getTimestamp(),$enddate->getTimestamp());
        break;
    case 'leads':
        $header = ["Subid","Time","Name","Phone","Email","Status","Preland","Land","Fbp","Fbclid"];
        $dataset=get_leads($startdate->getTimestamp(),$enddate->getTimestamp());
        break;
    case 'blocked':
        $header = ["IP","Country","ISP","Time","Reason","OS","UA","QueryString"];
        $dataset=get_white_clicks($startdate->getTimestamp(),$enddate->getTimestamp());
        break;
}

//Open the table tag
$tableOutput="<TABLE class='table w-auto table-striped'>";
//Print the table header
$tableOutput.="<thead class='thead-dark'>";
$tableOutput.="<TR>";
$tableOutput.="<TH scope='col'>Row</TH>";
foreach ($header as $field) {
    $tableOutput.="<TH scope='col'>".$field."</TH>";
} //Add the columns
$tableOutput.="</TR></thead><tbody>";
$countLines=0;
foreach ($dataset as $line) {
    $countLines++;
    $tableOutput.="<TR><TD>".$countLines."</TD>";
    $i=0;
    switch($filter){
        case '':
            $tableOutput.="<TD><a name='".$line['subid']."'>".$line['subid']."</a></TD>";
            $tableOutput.="<TD>".$line['ip']."</TD>";
            $tableOutput.="<TD>".$line['country']."</TD>";
            $tableOutput.="<TD>".$line['isp']."</TD>";
            $tableOutput.="<TD>".date('Y-m-d H:i:s',$line['time'])."</TD>";
            $tableOutput.="<TD>".$line['os']."</TD>";
            $tableOutput.="<TD>".$line['ua']."</TD>";
            $tableOutput.="<TD>".http_build_query($line['subs'])."</TD>";
            $tableOutput.="<TD>".$line['preland']."</TD>";
            $tableOutput.="<TD>".$line['land']."</TD>";
            break;
        case 'blocked':
            $tableOutput.="<TD>".$line['ip']."</TD>";
            $tableOutput.="<TD>".$line['country']."</TD>";
            $tableOutput.="<TD>".$line['isp']."</TD>";
            $tableOutput.="<TD>".date('Y-m-d H:i:s',$line['time'])."</TD>";
            $tableOutput.="<TD>".implode(',',$line['reason'])."</TD>";
            $tableOutput.="<TD>".$line['os']."</TD>";
            $tableOutput.="<TD>".$line['ua']."</TD>";
            $tableOutput.="<TD>".http_build_query($line['subs'])."</TD>";
            break;
        case 'leads':
            $tableOutput.="<TD><a href='index.php?password=".$_GET['password'].($date_str!==''?$date_str:'')."#".$line['subid']."'>".$line['subid']."</a></TD>";
            $tableOutput.="<TD>".date('Y-m-d H:i:s',$line['time'])."</TD>";
            $tableOutput.="<TD>".$line['name']."</TD>";
            $tableOutput.="<TD>".$line['phone']."</TD>";
            $tableOutput.="<TD>".(empty($line['email'])?'no':$line['email'])."</TD>";
            $tableOutput.="<TD>".$line['status']."</TD>";
            $tableOutput.="<TD>".$line['preland']."</TD>";
            $tableOutput.="<TD>".$line['land']."</TD>";
            $tableOutput.="<TD>".$line['fbp']."</TD>";
            $tableOutput.="<TD>".$line['fbclid']."</TD>";
            break;
    }
    $tableOutput.="</TR>";
}

$tableOutput.="</tbody></TABLE>";
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>Binomo Cloaker by Yellow Web</title>
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>

    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
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
                <a href="/admin/index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">
                    <img class="main-logo" src="img/logo/logo.png" alt="" />
                </a>
                <strong>
                    <img src="img/favicon.png" alt="" style="width:50px" />
                </strong>
            </div>
            <div class="nalika-profile">
                <div class="profile-dtl">
                    <a href="https://t.me/yellow_web">
                        <img src="img/notification/4.jpg" alt="" />
                    </a>
                    <?php include "version.php" ?>
                </div>
            </div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li class="active">

                            <a class="has-arrow" href="index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>" aria-expanded="false">
                                <i class="icon nalika-bar-chart icon-wrap"></i>
                                <span class="mini-click-non">Traffic</span>
                            </a>
                            <ul class="submenu-angle" aria-expanded="false">
                                <li>
                                    <a title="Стата" href="statistics.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">
                                        <span class="mini-sub-pro">Statistics</span>
                                    </a>
                                </li>
                                <li>
                                    <a title="Разрешённый" href="index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">
                                        <span class="mini-sub-pro">Allowed</span>
                                    </a>
                                </li>
                                <li>
                                    <a title="Лиды" href="index.php?filter=leads&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">
                                        <span class="mini-sub-pro">Leads</span>
                                    </a>
                                </li>
                                <li>
                                    <a title="Заблокированный" href="index.php?filter=blocked&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">
                                        <span class="mini-sub-pro">Blocked</span>
                                    </a>
                                </li>
                                <li>
                                    <a title="Bottom button" href="#bottom">
                                        <span class="mini-sub-pro">Go to bottom</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a href="editsettings.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>" aria-expanded="false">
                                <i class="icon nalika-table icon-wrap"></i>
                                <span class="mini-click-non">Settings</span>
                            </a>
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
                        <a href="index.html">
                            <img class="main-logo" src="img/logo/logo.png" alt="" />
                        </a>
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
                                                <li class="nav-item">
                                                    <a class="nav-link" href="" onclick="location.reload()">Refresh</a>
                                                    <a class="nav-link" href="#" id='litepicker'>Date:</a>
                                                    <a class="nav-link">
                                                        <?php
                                                            $calendsd=isset($_GET['startdate'])?$_GET['startdate']:'';
                                                            $calended=isset($_GET['enddate'])?$_GET['enddate']:'';
                                                            if ($calendsd!==''&&$calended!=='') {
                                                            if ($calendsd===$calended) {
                                                            echo $calendsd;
                                                            } else {
                                                            echo "{$calendsd} - {$calended}";
                                                            }
                                                            } else {
                                                            echo $startdate->format('d.m.y');

                                                            } ?>
                                                    </a>
                                                </li>
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

        <script>
		var picker = new Litepicker({
			element: document.getElementById('litepicker'),
			format: 'DD.MM.YY',
			autoApply:false,
            lang:"ru-RU",
            buttonText: {"apply":"Выбрать","cancel":"Отмена"},
			singleMode:false,
            setup: (p) => {
                p.on('button:apply', (date1, date2) => {
                    var searchParams = new URLSearchParams(window.location.search);
                    var d1 = moment(date1.dateInstance).format('DD.MM.YY');
                    var d2 = moment(date2.dateInstance).format('DD.MM.YY');
                    searchParams.set('startdate',d1);
                    searchParams.set('enddate', d2);
                    window.location.search = searchParams.toString();
                });
            }
		});
        </script>

        <a name="top"></a>
        <?=isset($tableOutput)?$tableOutput:'' ?>
        <a name="bottom"></a>
    </div>
    <!-- jquery
		============================================ -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- bootstrap JS
		============================================ -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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