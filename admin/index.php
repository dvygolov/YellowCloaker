<?php
    //Включение отладочной информации
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', 1);
    //Конец включения отладочной информации
    include '../settings.php';
      
    //------------------------------------------------
    //Configuration
    //
    $delimiter = ","; //CSV delimiter character: , ; /t
    $enclosure = '"'; //CSV enclosure character: " '
    $ignorePreHeader = 0; //Number of characters to ignore before the table header. Windows UTF-8 BOM has 3 characters.
     
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

    $filter=isset($_GET['filter'])?$_GET['filter']:'';
    $fileName='';
    
    $date = $startdate;
    $formatteddate = $date->format('d.m.y');
    switch ($filter) {
        case '':
            $fileName = "../logs/".$formatteddate.".csv";
            break;
        case 'leads':
            $fileName = "../logs/".$formatteddate.".leads.csv";
        break;
        case 'blocked':
            $fileName = "../logs/".$formatteddate.".blocked.csv";
        break;
        case 'emails':
            $fileName = "../logs/".$formatteddate.".emails.csv";
        break;
    }
	$tableOutput='';

    $logOriginalHeader = array();
	$headerSet=false;
    $countLines = 0;
    while ($date<=$enddate) {
        $formatteddate = $date->format('d.m.y');
        switch ($filter) {
            case '':
                $fileName = "../logs/".$formatteddate.".csv";
                break;
            case 'leads':
                $fileName = "../logs/".$formatteddate.".leads.csv";
            break;
            case 'blocked':
                $fileName = "../logs/".$formatteddate.".blocked.csv";
            break;
            case 'emails':
                $fileName = "../logs/".$formatteddate.".emails.csv";
            break;
        }

        //Variable initialization
        $logLines = array();
     
        if (file_exists($fileName)) { // File exists
			if (!$headerSet){
		        $fileLines = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				//Open the table tag
				$tableOutput="<TABLE class='table w-auto table-striped'>";

				//Print the table header
				$tableOutput.="<thead class='thead-dark'>";
				$tableOutput.="<TR>";
				$tableOutput.="<TH scope='col'>Row</TH>";
				//Extract the existing header from the file
				$lineHeader = array_shift($fileLines);
				$logOriginalHeader = array_map('trim', str_getcsv(substr($lineHeader, $ignorePreHeader), $delimiter, $enclosure));
			
				foreach ($logOriginalHeader as $field) {
					$tableOutput.="<TH scope='col'>".$field."</TH>";
				} //Add the columns
				$tableOutput.="</TR></thead><tbody>";
				$headerSet=true;
			}
     
            // Reads lines of file to array
            $fileLines = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
     
            //Not Empty file
            if ($fileLines !== array()) {
     
                //Process the file only if the system could find a valid header
                if (count($logOriginalHeader) > 0) {
     
                    //Get each line of the array and print the table files
                    array_shift($fileLines);
                    $fileLines = array_reverse($fileLines);
                    foreach ($fileLines as $line) {
                        if (trim($line) !== '') { //Remove blank lines
                            $countLines++;
                            $arrayFields = array_map('trim', str_getcsv($line, $delimiter, $enclosure)); //Convert line to array
                            $tableOutput.="<TR><TD>".$countLines."</TD>";
                            $i=0;
                            foreach ($arrayFields as $field) {
                                $i++;
                                if ($i==1 && $filter=='leads') {
                                    $tableOutput.="<TD><a href='index.php?password=".$_GET['password'].($date_str!==''?$date_str:'')."#".$field."'>".$field."</a></TD>";
                                    continue;
                                }
                                if ($i==1 && $filter=='') {
                                    $tableOutput.="<TD><a name='".$field."'>".$field."</a></TD>";
                                    continue;
                                }
                                $tableOutput.="<TD>".$field."</TD>"; //Add the columns
                            }
                            $tableOutput.="</TR>";
                        }
                    }
                }
            }
        }
        $date->add(new DateInterval('P1D'));
    }
    //Close the table tag
    if (isset($tableOutput)) {
        $tableOutput.="</tbody></TABLE>";
    }
    ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Binomo Cloaker - Dashboard V.1</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/js/main.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
	
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- favicon
		============================================ -->
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
    <!-- Google Fonts
		============================================ -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet">
    <!-- Bootstrap CSS
		============================================ -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Bootstrap CSS
		============================================ -->
    <link rel="stylesheet" href="css/font-awesome.min.css">
	<!-- nalika Icon CSS
		============================================ -->
    <link rel="stylesheet" href="css/nalika-icon.css">
    <!-- owl.carousel CSS
		============================================ -->
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/owl.theme.css">
    <link rel="stylesheet" href="css/owl.transitions.css">
    <!-- animate CSS
		============================================ -->
    <link rel="stylesheet" href="css/animate.css">
    <!-- normalize CSS
		============================================ -->
    <link rel="stylesheet" href="css/normalize.css">
    <!-- meanmenu icon CSS
		============================================ -->
    <link rel="stylesheet" href="css/meanmenu.min.css">
    <!-- main CSS
		============================================ -->
    <link rel="stylesheet" href="css/main.css">
    <!-- morrisjs CSS
		============================================ -->
    <link rel="stylesheet" href="css/morrisjs/morris.css">
    <!-- mCustomScrollbar CSS
		============================================ -->
    <link rel="stylesheet" href="css/scrollbar/jquery.mCustomScrollbar.min.css">
    <!-- metisMenu CSS
		============================================ -->
    <link rel="stylesheet" href="css/metisMenu/metisMenu.min.css">
    <link rel="stylesheet" href="css/metisMenu/metisMenu-vertical.css">
    <!-- calendar CSS
		============================================ -->
    <link rel="stylesheet" href="css/calendar/fullcalendar.min.css">
    <link rel="stylesheet" href="css/calendar/fullcalendar.print.min.css">
    <!-- style CSS
		============================================ -->
    <link rel="stylesheet" href="css/style.css">
    <!-- responsive CSS
		============================================ -->
    <link rel="stylesheet" href="css/responsive.css">
    <!-- modernizr JS
		============================================ -->
    <script src="js/vendor/modernizr-2.8.3.min.js"></script>
</head>

<body>
    <div class="left-sidebar-pro">
        <nav id="sidebar" class="">
            <div class="sidebar-header">
                <a href="/panel/index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><img class="main-logo" src="img/logo/logo.png" alt="" /></a>
                <strong><img src="img/favicon.png" alt="" style="width:50px"/></strong>
            </div>
			<div class="nalika-profile">
				<div class="profile-dtl">
					<a href="https://t.me/yellow_web"><img src="img/notification/4.jpg" alt="" /></a>
					<h2><a href="https://yellowweb.top" target="_blank">Yellow <span class="min-dtn">Web</span></a></h2>
				</div>
			</div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                  <ul class="metismenu" id="menu1">
                        <li class="active">
                            
                            <a class="has-arrow" href="index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>" aria-expanded="false"><i class="icon nalika-bar-chart icon-wrap"></i> <span class="mini-click-non">Statistics</span></a>
                            <ul class="submenu-angle" aria-expanded="false">
                                <li><a title="Bar Charts" href="index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><span class="mini-sub-pro">Allowed</span></a></li>
                                <li><a title="Line Charts" href="index.php?filter=leads&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><span class="mini-sub-pro">Leads</span></a></li>
                                <li><a title="Area Charts" href="index.php?filter=blocked&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><span class="mini-sub-pro">Blocked</span></a></li>
                                <li><a title="Rounded Charts" href="index.php?filter=emails&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>"><span class="mini-sub-pro">Emails</span></a></li>
                                
                                <li><a title="Peity Charts" href="#bottom"><span class="mini-sub-pro">Go to bottom</span></a></li>
                            </ul>
                        </li>
                        <li>
                            <a class="has-arrow" href="settings.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>" aria-expanded="false"><i class="icon nalika-table icon-wrap"></i> <span class="mini-click-non">Settings</span></a>
                        </li><li>
                            <a class="has-arrow" href="pixelfb.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>" aria-expanded="false"><i class="icon nalika-alarm icon-wrap"></i> <span class="mini-click-non">Pixel FB</span></a>
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
                            <a class="nav-link" href="#" id='litepicker'>Date:</a>
                            <a class="nav-link">
                            <?php
                            $calendsd = isset($_GET['startdate'])?$_GET['startdate']:'';
                            $calended = isset($_GET['enddate'])?$_GET['enddate']:'';
                            if ($calendsd!==''&&$calended!=='') {
                            if ($calendsd===$calended) {
                            echo $calendsd;
                            } else {
                            echo "{$calendsd} - {$calended}";
                            }
                            } else {
                            echo $formatteddate;
                            }
                            ?>
                            </a>
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

	<script>
		var picker = new Litepicker({ 
			element: document.getElementById('litepicker'), 
			format: 'DD.MM.YY', 
			autoApply:false, 
			singleMode:false, 
			onSelect: function(date1, date2) { 
				var searchParams = new URLSearchParams(window.location.search);
				searchParams.set('startdate', moment(date1).format('DD.MM.YY'));
				searchParams.set('enddate', moment(date2).format('DD.MM.YY'));
				window.location.search = searchParams.toString();
			}
		});
    </script>
    
    <a name="top"></a>
    <?=isset($tableOutput)?$tableOutput:'' ?>
    <a name="bottom"></a>
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    </div>
    <!-- jquery
		============================================ -->
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <!-- bootstrap JS
		============================================ -->
    <script src="js/bootstrap.min.js"></script>
    <!-- wow JS
		============================================ -->
    <script src="js/wow.min.js"></script>
    <!-- price-slider JS
		============================================ -->
    <script src="js/jquery-price-slider.js"></script>
    <!-- meanmenu JS
		============================================ -->
    <script src="js/jquery.meanmenu.js"></script>
    <!-- owl.carousel JS
		============================================ -->
    <script src="js/owl.carousel.min.js"></script>
    <!-- sticky JS
		============================================ -->
    <script src="js/jquery.sticky.js"></script>
    <!-- scrollUp JS
		============================================ -->
    <script src="js/jquery.scrollUp.min.js"></script>
    <!-- mCustomScrollbar JS
		============================================ -->
    <script src="js/scrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="js/scrollbar/mCustomScrollbar-active.js"></script>
    <!-- metisMenu JS
		============================================ -->
    <script src="js/metisMenu/metisMenu.min.js"></script>
    <script src="js/metisMenu/metisMenu-active.js"></script>
    <!-- sparkline JS
		============================================ -->
    <script src="js/sparkline/jquery.sparkline.min.js"></script>
    <script src="js/sparkline/jquery.charts-sparkline.js"></script>
    <!-- calendar JS
		============================================ -->
    <script src="js/calendar/moment.min.js"></script>
    <script src="js/calendar/fullcalendar.min.js"></script>
    <script src="js/calendar/fullcalendar-active.js"></script>
	<!-- float JS
		============================================ -->
    <script src="js/flot/jquery.flot.js"></script>
    <script src="js/flot/jquery.flot.resize.js"></script>
    <script src="js/flot/curvedLines.js"></script>
    <script src="js/flot/flot-active.js"></script>
    <!-- plugins JS
		============================================ -->
    <script src="js/plugins.js"></script>
    <!-- main JS
		============================================ -->
    <script src="js/main.js"></script>
</body>

</html>