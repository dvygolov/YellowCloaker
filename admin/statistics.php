<?php
//Включение отладочной информации
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
//Конец включения отладочной информации

require_once '../settings.php';
require_once 'password.php';
check_password();

require_once 'db.php';
require_once '../abtests/Calculator/SplitTestAnalyzer.php';
require_once '../abtests/Calculator/Variation.php';
use BenTools\SplitTestAnalyzer\SplitTestAnalyzer;
use BenTools\SplitTestAnalyzer\Variation;

date_default_timezone_set($stats_timezone);
$startdate=isset($_GET['startdate'])?
    DateTime::createFromFormat('d.m.y', $_GET['startdate'],new DateTimeZone($stats_timezone)):
    new DateTime(null,new DateTimeZone($stats_timezone));
$enddate=isset($_GET['enddate'])?
    DateTime::createFromFormat('d.m.y', $_GET['enddate'],new DateTimeZone($stats_timezone)):
    new DateTime(null,new DateTimeZone($stats_timezone));

$date_str='';
if (isset($_GET['startdate'])&& isset($_GET['enddate'])) {
    $startstr = $_GET['startdate'];
    $endstr = $_GET['enddate'];
    $date_str="&startdate={$startstr}&enddate={$endstr}";
}

//Open the table tag
$tableOutput="<TABLE class='table w-auto table-striped'>";

//Print the table header
$tableOutput.="<thead class='thead-dark'>";
$tableOutput.="<TR>";
$tableOutput.="<TH scope='col'>Date</TH>";
$tableOutput.="<TH scope='col'>Clicks</TH>";
$tableOutput.="<TH scope='col'>Unique</TH>";
$tableOutput.="<TH scope='col'>Conversions</TH>";
$tableOutput.="<TH scope='col'>Purchase</TH>";
$tableOutput.="<TH scope='col'>Hold</TH>";
$tableOutput.="<TH scope='col'>Reject</TH>";
$tableOutput.="<TH scope='col'>Trash</TH>";
$tableOutput.="<TH scope='col'>CR% all</TH>";
$tableOutput.="<TH scope='col'>CR% sales</TH>";
$tableOutput.="<TH scope='col'>App% (w/o trash)</TH>";
$tableOutput.="<TH scope='col'>App% (total)</TH>";
$tableOutput.="<TH scope='col'>EPuC</TH>";
$tableOutput.="<TH scope='col'>Revenue</TH>";
$tableOutput.="</TR></thead><tbody>";

$lpctr_array = array();
$lpdest_array=array();
$landclicks_array=array();
$landconv_array=array();
$subs_array=array();
foreach ($stats_sub_names as $ssn)
{
    $subs_array[$ssn["name"]]=[];
}
$subid_query=array();
$query_conversions=array();
foreach ($stats_sub_names as $ssn)
{
    $query_conversions[$ssn["name"]]=[];
}

$total_clicks=0;
$total_uniques=0;
$total_leads=0;
$total_holds=0;
$total_purchases=0;
$total_rejects=0;
$total_trash=0;
$total_cr_all=array();
$total_cr_sales=array();
$total_app_wo_trash=array();
$total_app=array();
$total_revenue=0;

$noprelanding= $black_preland_action==='none';

$date = $enddate;
while ($date>=$startdate) {

    $ts=$date->getTimestamp();
    $curstart=date_create("@$ts");
    $curstart->setTime(0,0,0);
    $curend=date_create("@$ts");
    $curend->setTime(23,59,59);
    $formatteddate = $date->format('d.m.y');
    $day_traf=get_black_clicks($curstart->getTimestamp(),$curend->getTimestamp());
    $day_ctr=get_lpctr($curstart->getTimestamp(),$curend->getTimestamp());
    $day_leads=get_leads($curstart->getTimestamp(),$curend->getTimestamp());

    $leads_count = ($day_leads===array())?0:count($day_leads);
    $total_leads+=$leads_count;

    //count unique clicks
    $sub_land_dest = array();
    $unique_clicks = array();
    foreach ($day_traf as $titem) {
        $cur_subid=$titem['subid'];
        $land_name=$titem['land'];
        $lp_name=$titem['preland'];
        $sub_land_dest[$cur_subid]= $land_name;
        if (!in_array($cur_subid, $unique_clicks)) { //subid в словаре? значит не уник
            $unique_clicks[]= $cur_subid;
        }
        if (array_key_exists($lp_name, $lpdest_array)) {
            $lpdest_array[$lp_name]++;
        } else {
            $lpdest_array[$lp_name]=1;
        }
		//if we don't have prelandings then we should count offer clicks here
		if ($noprelanding){
			if (array_key_exists($land_name, $landclicks_array)) {
				$landclicks_array[$land_name]++;
			} else {
				$landclicks_array[$land_name]=1;
			}
		}

        //count all subs
        $qs=$titem['subs'];
        if (empty($qs)) continue;
        foreach ($qs as $qkey=>$qvalue) {
            if (array_key_exists($qkey,$subs_array)){
                //для подсчёта лидов и продаж по меткам
                $subid_query[$cur_subid][$qkey]=$qvalue;

                if (array_key_exists($qvalue, $subs_array[$qkey])) {
                    $subs_array[$qkey][$qvalue]++;
                } else {
                    $subs_array[$qkey][$qvalue]=1;
                }
            }
        }
    }

	if (!$noprelanding) //count only if we have prelanders
	{
		//count lp ctrs
		foreach ($day_ctr as $ctritem) {
			$lp_name=$ctritem['preland'];
			if ($lp_name=='') continue;
			if (array_key_exists($lp_name, $lpctr_array)) {
				$lpctr_array[$lp_name]++;
			} else {
				$lpctr_array[$lp_name]=1;
			}
			//count landing clicks
			$subid_lp = $ctritem['subid'];
			$dest_land = $sub_land_dest[$subid_lp];
			if (array_key_exists($dest_land, $landclicks_array)) {
				$landclicks_array[$dest_land]++;
			} else {
				$landclicks_array[$dest_land]=1;
			}
		}
	}

    //count leads
    $purchase_count=0;
    $hold_count=0;
    $reject_count=0;
    $trash_count=0;
    $revenue=0;
    foreach ($day_leads as $leaditem) {
        $lead_status = $leaditem['status'];
        switch ($lead_status) {
            case 'Lead':
                $total_holds++;
                $hold_count++;
                break;
            case 'Purchase':
                $total_purchases++;
                $purchase_count++;
                $revenue+=$leaditem['payout'];
                break;
            case 'Reject':
                $total_rejects++;
                $reject_count++;
                break;
            case 'Trash':
                $total_trash++;
                $trash_count++;
                break;
        }
        $subid_lead = $leaditem['subid'];
		if (array_key_exists($subid_lead, $sub_land_dest)){
			$conv_land= $sub_land_dest[$subid_lead];
			if (!array_key_exists($conv_land, $landconv_array)) {
				$landconv_array[$conv_land]=[];
            }
			if (array_key_exists($lead_status, $landconv_array[$conv_land])) {
				$landconv_array[$conv_land][$lead_status]++;
			} else {
				$landconv_array[$conv_land][$lead_status]=1;
			}
		}

        if (array_key_exists($subid_lead,$subid_query)){
            foreach ($subid_query[$subid_lead] as $subkey=>$subvalue)
            {
                if (!array_key_exists($subvalue, $query_conversions[$subkey])) {
                    $query_conversions[$subkey][$subvalue]=[];
                }
                if (array_key_exists($lead_status,$query_conversions[$subkey][$subvalue])){
                    $query_conversions[$subkey][$subvalue][$lead_status]++;
                } else {
                    $query_conversions[$subkey][$subvalue][$lead_status]=1;
                }
            }
        }
    }

    //Add all data to main table
    $tableOutput.="<TR>";
    $tableOutput.="<TD scope='col'>".$date->format('d.m.y')."</TD>";
    $clicks = count($day_traf)==0?0:count($day_traf);
    $total_clicks+=$clicks;
    $tableOutput.="<TD scope='col'>".$clicks."</TD>";
    $unique_clicks_count = count($unique_clicks);
    $total_uniques+=$unique_clicks_count;
    $tableOutput.="<TD scope='col'>".$unique_clicks_count."</TD>";
    $tableOutput.="<TD scope='col'>".$leads_count."</TD>";
    $tableOutput.="<TD scope='col'>".$purchase_count."</TD>";
    $tableOutput.="<TD scope='col'>".$hold_count."</TD>";
    $tableOutput.="<TD scope='col'>".$reject_count."</TD>";
    $tableOutput.="<TD scope='col'>".$trash_count."</TD>";
    $cr_all = $unique_clicks_count==0?0:$leads_count/$unique_clicks_count*100;
    $total_cr_all[]= $cr_all;
    $tableOutput.="<TD scope='col'>".number_format($cr_all, 2, '.', '')."</TD>";
    $cr_sales = $unique_clicks_count==0?0:$purchase_count/$unique_clicks_count*100;
    $total_cr_sales[]= $cr_sales;
    $tableOutput.="<TD scope='col'>".number_format($cr_sales, 2, '.', '')."</TD>";
    $approve_wo_trash = ($leads_count-$trash_count==0)?0:$purchase_count*100/($leads_count-$trash_count);
    $total_app_wo_trash[]= $approve_wo_trash;
    $tableOutput.="<TD scope='col'>".number_format($approve_wo_trash, 2, '.', '')."</TD>";
    $approve = $leads_count==0?0:$purchase_count*100/$leads_count;
    $total_app[]= $approve;
    $tableOutput.="<TD scope='col'>".number_format($approve, 2, '.', '')."</TD>";
    $epc= $unique_clicks_count==0?0:$revenue/$unique_clicks_count;
    $tableOutput.="<TD scope='col'>".number_format($epc, 2, '.', '')."</TD>";
    $tableOutput.="<TD scope='col'>".$revenue."</TD>";
    $total_revenue+=$revenue;
    $tableOutput.="</TR>";
    $date->sub(new DateInterval('P1D'));
}
//Add total line
$tableOutput.="<TR class='table-dark'>";
$tableOutput.="<TD scope='col'>Total</TD>";
$tableOutput.="<TD scope='col'>".$total_clicks."</TD>";
$tableOutput.="<TD scope='col'>".$total_uniques."</TD>";
$tableOutput.="<TD scope='col'>".$total_leads."</TD>";
$tableOutput.="<TD scope='col'>".$total_purchases."</TD>";
$tableOutput.="<TD scope='col'>".$total_holds."</TD>";
$tableOutput.="<TD scope='col'>".$total_rejects."</TD>";
$tableOutput.="<TD scope='col'>".$total_trash."</TD>";
$tcr_all=($total_uniques===0?0:$total_leads/$total_uniques*100);
$tableOutput.="<TD scope='col'>".number_format($tcr_all, 2, '.', '')."</TD>";
$tcr_sales=($total_uniques===0?0:$total_purchases/$total_uniques*100);
$tableOutput.="<TD scope='col'>".number_format($tcr_sales, 2, '.', '')."</TD>";
$tapprove_wo_trash=($total_leads-$total_leads===0?0:$total_purchases*100/($total_leads-$total_trash));
$tableOutput.="<TD scope='col'>".number_format($tapprove_wo_trash, 2, '.', '')."</TD>";
$tapprove=($total_leads===0?0:$total_purchases*100/$total_leads);
$tableOutput.="<TD scope='col'>".number_format($tapprove, 2, '.', '')."</TD>";
$tepc=($total_uniques===0?0:$total_revenue/$total_uniques);
$tableOutput.="<TD scope='col'>".number_format($tepc, 2, '.', '')."</TD>";
$tableOutput.="<TD scope='col'>".$total_revenue."</TD>";
$tableOutput.="</TR>";
//Close the table tag
$tableOutput.="</tbody></TABLE>";

if (!$noprelanding){
    $preland_splittest=(count($lpctr_array)>1);
    $preland_split_probability=[];
    if ($preland_splittest){
        $variations = [];

        foreach ($lpctr_array as $lp_name => $lp_count) {
            $variations[]= new Variation($lp_name,$lpdest_array[$lp_name], $lp_count);
        }
        $predictor = SplitTestAnalyzer::create()->withVariations(...$variations);
        $preland_split_probability=$predictor->getResult();
    }

	//Open the lpctr table tag
	$lpctrTableOutput="<TABLE class='table w-auto table-striped'>";
	$lpctrTableOutput.="<thead class='thead-dark'>";
	$lpctrTableOutput.="<TR>";
	$lpctrTableOutput.="<TH scope='col'>Prelanding</TH>";
	$lpctrTableOutput.="<TH scope='col'>Traffic</TH>";
	$lpctrTableOutput.="<TH scope='col'>LP Clicks</TH>";
	$lpctrTableOutput.="<TH scope='col'>LP CTR</TH>";
    if ($preland_splittest)
        $lpctrTableOutput.="<TH scope='col'>Is Best%</TH>";
	$lpctrTableOutput.="</TR></thead><tbody>";
	//Add all data to LP CTR Table
	foreach ($lpctr_array as $lp_name => $lp_count) {
		$lpctrTableOutput.="<TR>";
		$lpctrTableOutput.="<TD scope='col'>".$lp_name."</TD>";
		$lpctrTableOutput.="<TD scope='col'>".$lpdest_array[$lp_name]."</TD>";
		$lpctrTableOutput.="<TD scope='col'>".$lp_count."</TD>";
		$cur_ctr = $lp_count*100/$lpdest_array[$lp_name];
		$lpctrTableOutput.="<TD scope='col'>".number_format($cur_ctr, 2, '.', '')."%</TD>";
        if ($preland_splittest)
            $lpctrTableOutput.="<TD scope='col'>".$preland_split_probability[$lp_name]."</TD>";
		$lpctrTableOutput.="</TR>";
	}
	$lpctrTableOutput.="</tbody></TABLE>";
}


$land_splittest=(count($landclicks_array)>1);
$land_split_probability=[];
if ($land_splittest){
    $variations = [];

    foreach ($landclicks_array as $land_name => $land_clicks) {
        $cur_land_arr=array_key_exists($land_name, $landconv_array)?$landconv_array[$land_name]:[];
        $land_conv=array_sum($cur_land_arr);
        $variations[]= new Variation($land_name, $land_clicks, $land_conv);
    }
    $predictor = SplitTestAnalyzer::create()->withVariations(...$variations);
    $land_split_probability=$predictor->getResult();
}

//Open the landcr table tag
$landcrTableOutput="<TABLE class='table w-auto table-striped'>";
$landcrTableOutput.="<thead class='thead-dark'>";
$landcrTableOutput.="<TR>";
$landcrTableOutput.="<TH scope='col'>Landing</TH>";
$landcrTableOutput.="<TH scope='col'>Clicks</TH>";
$landcrTableOutput.="<TH scope='col'>Conversions</TH>";
$landcrTableOutput.="<TH scope='col'>Hold</TH>";
$landcrTableOutput.="<TH scope='col'>Purchase</TH>";
$landcrTableOutput.="<TH scope='col'>Reject</TH>";
$landcrTableOutput.="<TH scope='col'>Trash</TH>";
$landcrTableOutput.="<TH scope='col'>CR%</TH>";
if ($land_splittest)
    $landcrTableOutput.="<TH scope='col'>Is Best%</TH>";
$landcrTableOutput.="</TR></thead><tbody>";
//Add all data to landcr table
foreach ($landclicks_array as $land_name => $land_clicks) {
    $cur_land_arr=array_key_exists($land_name, $landconv_array)?$landconv_array[$land_name]:[];
    $land_conv=array_sum($cur_land_arr);
    $land_lead = array_key_exists('Lead',$cur_land_arr)?$cur_land_arr['Lead']:0;
    $land_purchase = array_key_exists('Purchase',$cur_land_arr)?$cur_land_arr['Purchase']:0;
    $land_reject = array_key_exists('Reject',$cur_land_arr)?$cur_land_arr['Reject']:0;
    $land_trash = array_key_exists('Trash',$cur_land_arr)?$cur_land_arr['Trash']:0;
    $cur_cr = $land_conv*100/$land_clicks;

    $landcrTableOutput.="<TR>";
    $landcrTableOutput.="<TD scope='col'>".$land_name."</TD>";
    $landcrTableOutput.="<TD scope='col'>".$land_clicks."</TD>";
    $landcrTableOutput.="<TD scope='col'>".$land_conv."</TD>";
    $landcrTableOutput.="<TD scope='col'>".$land_lead."</TD>";
    $landcrTableOutput.="<TD scope='col'>".$land_purchase."</TD>";
    $landcrTableOutput.="<TD scope='col'>".$land_reject."</TD>";
    $landcrTableOutput.="<TD scope='col'>".$land_trash."</TD>";
    $landcrTableOutput.="<TD scope='col'>".number_format($cur_cr, 2, '.', '')."</TD>";
    if ($land_splittest)
        $landcrTableOutput.="<TD scope='col'>".$land_split_probability[$land_name]."</TD>";
    $landcrTableOutput.="</TR>";
}
$landcrTableOutput.="</tbody></TABLE>";

$subs_tables=[];
foreach ($subs_array as $sub_key=>$sub_values)
{
    if (count($sub_values)===0) continue;
    //Open the current sub table tag
    $subTableOutput="<TABLE class='table w-auto table-striped'>";
    $subTableOutput.="<thead class='thead-dark'>";
    $subTableOutput.="<TR>";
    $sub_clmn_name=$stats_sub_names[array_search($sub_key,array_column($stats_sub_names,'name'))]['value'];
    $subTableOutput.="<TH scope='col'>".$sub_clmn_name."</TH>";
    $subTableOutput.="<TH scope='col'>Clicks</TH>";
    $subTableOutput.="<TH scope='col'>Conversions</TH>";
    $subTableOutput.="<TH scope='col'>Hold</TH>";
    $subTableOutput.="<TH scope='col'>Purchase</TH>";
    $subTableOutput.="<TH scope='col'>Reject</TH>";
    $subTableOutput.="<TH scope='col'>Trash</TH>";
    $subTableOutput.="<TH scope='col'>CR%</TH>";
    $subTableOutput.="</TR></thead><tbody>";
    //Add all data to creatives table
    foreach ($sub_values as $sub_value_name => $sub_value_clicks) {
        $sub_conversions_arr=array_key_exists($sub_value_name,$query_conversions[$sub_key])?$query_conversions[$sub_key][$sub_value_name]:[];
        $sub_conversions_cnt= array_sum($sub_conversions_arr);
        $sub_cr = $sub_conversions_cnt*100/$sub_value_clicks;
        $sub_lead = array_key_exists('Lead',$sub_conversions_arr)?$sub_conversions_arr['Lead']:0;
        $sub_purchase = array_key_exists('Purchase',$sub_conversions_arr)?$sub_conversions_arr['Purchase']:0;
        $sub_reject = array_key_exists('Reject',$sub_conversions_arr)?$sub_conversions_arr['Reject']:0;
        $sub_trash = array_key_exists('Trash',$sub_conversions_arr)?$sub_conversions_arr['Trash']:0;

        $subTableOutput.="<TR>";
        $subTableOutput.="<TD scope='col'>".$sub_value_name."</TD>";
        $subTableOutput.="<TD scope='col'>".$sub_value_clicks."</TD>";
        $subTableOutput.="<TD scope='col'>".$sub_conversions_cnt."</TD>";
        $subTableOutput.="<TD scope='col'>".$sub_lead."</TD>";
        $subTableOutput.="<TD scope='col'>".$sub_purchase."</TD>";
        $subTableOutput.="<TD scope='col'>".$sub_reject."</TD>";
        $subTableOutput.="<TD scope='col'>".$sub_trash."</TD>";
        $subTableOutput.="<TD scope='col'>".number_format($sub_cr, 2, '.', '')."</TD>";
        $subTableOutput.="</TR>";
    }
    $subTableOutput.="</tbody></TABLE>";
    $subs_tables[]=$subTableOutput;
}
?>
<!doctype html>
<html lang="ru">
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
    <!-- Bootstrap CSS
		============================================ -->
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <!-- nalika Icon CSS
		============================================ -->
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
                                    <a title="Bottom" href="#bottom">
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
    <!-- start welcome area -->
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
                                            <button type="button" id="sidebarcollapse" class="btn bar-button-pro header-drl-controller-btn btn-info navbar-btn">
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
                                                                echo $formatteddate;
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

        <a name="top"></a>
        <?=$tableOutput ?>
        <?=($noprelanding?'':$lpctrTableOutput)?>
        <?=$landcrTableOutput ?>
        <?php 
        foreach($subs_tables as $subTableOutput){
            echo $subTableOutput;
        }
        ?>
        <a name="bottom"></a>
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
