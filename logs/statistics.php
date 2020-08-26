<?php
//Включение отладочной информации
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
//Конец включения отладочной информации

include '../settings.php';
//Verify the password (if set)
if ($_GET["password"] !== $log_password && $log_password !== "") {
    exit();
}
    
//------------------------------------------------
//Configuration
//
$startdate=isset($_GET['startdate'])?DateTime::createFromFormat('d.m.y', $_GET['startdate']):new DateTime();
$enddate=isset($_GET['enddate'])?DateTime::createFromFormat('d.m.y', $_GET['enddate']):new DateTime();

$delimiter = ","; //CSV delimiter character: , ; /t
$enclosure = '"'; //CSV enclosure character: " '
//------------------------------------------------
 
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
$tableOutput.="</TR></thead><tbody>";

$lpctr_array = array();
$lpdest_array=array();
$landclicks_array=array();
$landconv_array=array();
$creatives_array=array();
 
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

$date = $startdate;
while ($date<=$enddate) {
    $formatteddate = $date->format('d.m.y');
    $traf_fn=$formatteddate.'.csv';
    $ctr_fn=$formatteddate.'.lpctr.csv';
    $leads_fn=$formatteddate.'.leads.csv';
     
    // Reads lines of all files to array
    $traf_file = file_exists($traf_fn)?file($traf_fn, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES):array();
    array_shift($traf_file);
    $ctr_file = file_exists($ctr_fn)?file($ctr_fn, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES):array();
    array_shift($ctr_file);
    $leads_file = file_exists($leads_fn)?file($leads_fn, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES):array();
    array_shift($leads_file);
    $leads_count = ($leads_file===array())?0:count($leads_file);
    $total_leads+=$leads_count;
    
    
    //count unique clicks
    $sub_land_dest = array();
    $unique_clicks = array();
    foreach ($traf_file as $traf_line) {
        $traf_line_fields = array_map('trim', str_getcsv($traf_line, $delimiter, $enclosure));
        $land_name=$traf_line_fields[count($traf_line_fields)-1];
        $lp_name=$traf_line_fields[count($traf_line_fields)-2];
        $sub_land_dest[$traf_line_fields[0]]= $land_name;
        if (!in_array($traf_line_fields[0], $unique_clicks)) { //subid в словаре? значит не уник
            array_push($unique_clicks, $traf_line_fields[0]);
        }
        if (array_key_exists($lp_name, $lpdest_array)) {
            $lpdest_array[$lp_name]++;
        } else {
            $lpdest_array[$lp_name]=1;
        }
        
        $cur_creo='Unknown';
        $cur_query = explode('&', $traf_line_fields[count($traf_line_fields)-3]);
        foreach ($cur_query as $query_item) {
            if (substr($query_item, 0, strlen($creative_sub_name))===$creative_sub_name) {
                $qsplit = explode('=', $query_item);
                $cur_creo = $qsplit[1];
                break;
            }
        }
        if (array_key_exists($cur_creo, $creatives_array)) {
            $creatives_array[$cur_creo]++;
        } else {
            $creatives_array[$cur_creo]=1;
        }
    }

    //count lp ctrs
    foreach ($ctr_file as $ctr_line) {
        $ctr_line_fields = array_map('trim', str_getcsv($ctr_line, $delimiter, $enclosure));
        $lp_name=$ctr_line_fields[count($ctr_line_fields)-1];
        if ($lp_name=='') {
            continue;
        }
        if (array_key_exists($lp_name, $lpctr_array)) {
            $lpctr_array[$lp_name]++;
        } else {
            $lpctr_array[$lp_name]=1;
        }
        //count landing clicks
        $subid_lp = $ctr_line_fields[0];
        $dest_land = $sub_land_dest[$subid_lp];
        if (array_key_exists($dest_land, $landclicks_array)) {
            $landclicks_array[$dest_land]++;
        } else {
            $landclicks_array[$dest_land]=1;
        }
    }
    
    //count leads
    $purchase_count=0;
    $hold_count=0;
    $reject_count=0;
    $trash_count=0;
    foreach ($leads_file as $lead_line) {
        $lead_line_fields = array_map('trim', str_getcsv($lead_line, $delimiter, $enclosure));
        $lead_status = $lead_line_fields[count($lead_line_fields)-1];
        switch ($lead_status) {
            case 'Lead':
                $total_holds++;
                $hold_count++;
                break;
            case 'Purchase':
                $total_purchases++;
                $purchase_count++;
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
        $subid_lead = $lead_line_fields[0];
        $conv_land= $sub_land_dest[$subid_lead];
        if (array_key_exists($conv_land, $landconv_array)) {
            $landconv_array[$conv_land]++;
        } else {
            $landconv_array[$conv_land]=1;
        }
    }
    
    //Add all data to main table
    $tableOutput.="<TR>";
    $tableOutput.="<TD scope='col'>".$date->format('d.m.y')."</TD>";
    $clicks = count($traf_file)==0?0:count($traf_file);
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
    array_push($total_cr_all, $cr_all);
    $tableOutput.="<TD scope='col'>".number_format($cr_all, 2, '.', '')."</TD>";
    $cr_sales = $unique_clicks_count==0?0:$purchase_count/$unique_clicks_count*100;
    array_push($total_cr_sales, $cr_sales);
    $tableOutput.="<TD scope='col'>".number_format($cr_sales, 2, '.', '')."</TD>";
    $approve_wo_trash = ($leads_count-$trash_count==0)?0:$purchase_count*100/($leads_count-$trash_count);
    array_push($total_app_wo_trash, $approve_wo_trash);
    $tableOutput.="<TD scope='col'>".number_format($approve_wo_trash, 2, '.', '')."</TD>";
    $approve = $leads_count==0?0:$purchase_count*100/$leads_count;
    array_push($total_app, $approve);
    $tableOutput.="<TD scope='col'>".number_format($approve, 2, '.', '')."</TD>";
    $tableOutput.="</TR>";
    $date->add(new DateInterval('P1D'));
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
$tcr_all=array_sum($total_cr_all)/count($total_cr_all);
$tableOutput.="<TD scope='col'>".number_format($tcr_all, 2, '.', '')."</TD>";
$tcr_sales=array_sum($total_cr_sales)/count($total_cr_sales);
$tableOutput.="<TD scope='col'>".number_format($tcr_sales, 2, '.', '')."</TD>";
$tapprove_wo_trash=array_sum($total_app_wo_trash)/count($total_app_wo_trash);
$tableOutput.="<TD scope='col'>".number_format($tapprove_wo_trash, 2, '.', '')."</TD>";
$tapprove=array_sum($total_app)/count($total_app);
$tableOutput.="<TD scope='col'>".number_format($tapprove, 2, '.', '')."</TD>";
$tableOutput.="</TR>";
//Close the table tag
$tableOutput.="</tbody></TABLE>";


//Open the lpctr table tag
$lpctrTableOutput="<TABLE class='table w-auto table-striped'>";
$lpctrTableOutput.="<thead class='thead-dark'>";
$lpctrTableOutput.="<TR>";
$lpctrTableOutput.="<TH scope='col'>Prelanding</TH>";
$lpctrTableOutput.="<TH scope='col'>LP Clicks</TH>";
$lpctrTableOutput.="<TH scope='col'>LP CTR</TH>";
$lpctrTableOutput.="</TR></thead><tbody>";
//Add all data to LP CTR Table
foreach ($lpctr_array as $lp_name => $lp_count) {
    $lpctrTableOutput.="<TR>";
    $lpctrTableOutput.="<TD scope='col'>".$lp_name."</TD>";
    $lpctrTableOutput.="<TD scope='col'>".$lp_count."</TD>";
    $cur_ctr = $lp_count*100/$lpdest_array[$lp_name];
    $lpctrTableOutput.="<TD scope='col'>".number_format($cur_ctr, 2, '.', '')."%</TD>";
    $lpctrTableOutput.="</TR>";
}
$lpctrTableOutput.="</tbody></TABLE>";

//Open the landcr table tag
$landcrTableOutput="<TABLE class='table w-auto table-striped'>";
$landcrTableOutput.="<thead class='thead-dark'>";
$landcrTableOutput.="<TR>";
$landcrTableOutput.="<TH scope='col'>Landing</TH>";
$landcrTableOutput.="<TH scope='col'>Clicks</TH>";
$landcrTableOutput.="<TH scope='col'>Conversions</TH>";
$landcrTableOutput.="<TH scope='col'>CR%</TH>";
$landcrTableOutput.="</TR></thead><tbody>";
//Add all data to landcr table
foreach ($landclicks_array as $land_name => $land_clicks) {
    $landcrTableOutput.="<TR>";
    $landcrTableOutput.="<TD scope='col'>".$land_name."</TD>";
    $landcrTableOutput.="<TD scope='col'>".$land_clicks."</TD>";
    $cur_conv=array_key_exists($land_name, $landconv_array)?$landconv_array[$land_name]:0;
    $landcrTableOutput.="<TD scope='col'>".$cur_conv."</TD>";
    $cur_cr = $cur_conv*100/$land_clicks;
    $landcrTableOutput.="<TD scope='col'>".number_format($cur_cr, 2, '.', '')."%</TD>";
    $landcrTableOutput.="</TR>";
}
$landcrTableOutput.="</tbody></TABLE>";

//Open the creatives table tag
$creoTableOutput="<TABLE class='table w-auto table-striped'>";
$creoTableOutput.="<thead class='thead-dark'>";
$creoTableOutput.="<TR>";
$creoTableOutput.="<TH scope='col'>Creative</TH>";
$creoTableOutput.="<TH scope='col'>Clicks</TH>";
$creoTableOutput.="</TR></thead><tbody>";
//Add all data to creatives table
foreach ($creatives_array as $creo_name => $creo_clicks) {
    $creoTableOutput.="<TR>";
    $creoTableOutput.="<TD scope='col'>".$creo_name."</TD>";
    $creoTableOutput.="<TD scope='col'>".$creo_clicks."</TD>";
    $creoTableOutput.="</TR>";
}
$creoTableOutput.="</tbody></TABLE>";
?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8"> 
    <title>Binomo Cloaker Statistics</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/litepicker/dist/js/main.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
	<link rel="shortcut icon" type="image/png" href="favicon.png"/>
    </head>
    <body>
    <a href="index.php?password=<?=$_GET['password']?>">
        <img src="binomocloaker.png" width="300px"/>
    </a>
    <?php
        $date_str='';
        if (isset($_GET['startdate'])&& isset($_GET['enddate'])) {
            $startstr = $_GET['startdate'];
            $endstr = $_GET['enddate'];
            $date_str="&startdate={$startstr}&enddate={$endstr}";
        }
    ?>
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
	  <div class="collapse navbar-collapse" id="navbarNav">
		<ul class="navbar-nav">
		  <li class="nav-item">
			<a class="nav-link" href="#" id='litepicker'>Date:</a>
          </li>
          <li class="nav-item">
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
		  <li class="nav-item">
			<a class="nav-link" href="index.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">Allowed</a>
		  </li>
		  <li class="nav-item">
			<a class="nav-link" href="index.php?filter=leads&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">Leads</a>
		  </li>
		  <li class="nav-item">
			<a class="nav-link" href="index.php?filter=blocked&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">Blocked</a>
		  </li>
  		  <li class="nav-item">
			<a class="nav-link" href="index.php?filter=emails&password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">Emails</a>
		  </li>
		  <li class="divider"></li>
		  <li class="nav-item">
			<a class="nav-link" href="" onClick="location.reload()">Refresh</a>
		  </li>
		</ul>
	  </div>
	</nav>
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
    <?=$tableOutput ?>
	<?=$lpctrTableOutput ?>
	<?=$landcrTableOutput ?>
	<?=$creoTableOutput ?>
    <a name="bottom"></a>
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    </body>
    </html>