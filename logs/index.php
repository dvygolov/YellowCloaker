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
     
    if ($_GET["password"] !== $log_password && $log_password !== "") {
        exit();
    }

    $startdate=isset($_GET['startdate'])?DateTime::createFromFormat('d.m.y', $_GET['startdate']):new DateTime();
    $enddate=isset($_GET['enddate'])?DateTime::createFromFormat('d.m.y', $_GET['enddate']):new DateTime();

    $filter=isset($_GET['filter'])?$_GET['filter']:'';
    $fileName='';
    
    $date = $startdate;
    $formatteddate = $date->format('d.m.y');
    switch ($filter) {
        case '':
            $fileName = $formatteddate.".csv";
            break;
        case 'leads':
            $fileName = $formatteddate.".leads.csv";
        break;
        case 'blocked':
            $fileName = $formatteddate.".blocked.csv";
        break;
        case 'emails':
            $fileName = $formatteddate.".emails.csv";
        break;
    }
    
    if (file_exists($fileName)) { // File exists
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
    }
    
    $countLines = 0;
    while ($date<=$enddate) {
        $formatteddate = $date->format('d.m.y');
        switch ($filter) {
            case '':
                $fileName = $formatteddate.".csv";
                break;
            case 'leads':
                $fileName = $formatteddate.".leads.csv";
            break;
            case 'blocked':
                $fileName = $formatteddate.".blocked.csv";
            break;
            case 'emails':
                $fileName = $formatteddate.".emails.csv";
            break;
        }

        //Variable initialization
        $logLines = array();
     
        //Verify the password (if set)
        if (file_exists($fileName)) { // File exists
     
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
                            $tableOutput.="<TR><TD style='background-color: lightgray;'>".$countLines."</TD>";
                            $i=0;
                            foreach ($arrayFields as $field) {
                                $i++;
                                if ($i==1 && $filter=='leads') {
                                    $tableOutput.="<TD><a href='index.php?password=".$_GET['password']."#".$field."'>".$field."</a></TD>";
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
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8"> 
    <title>Binomo Cloaker Log</title>
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
			<a class="nav-link" href="statistics.php?password=<?=$_GET['password']?><?=$date_str!==''?$date_str:''?>">Statistics</a>
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
		  <li class="nav-item">
			<a class="nav-link" href="#bottom">Go to bottom</a>
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
    <?=isset($tableOutput)?$tableOutput:'' ?>
    <a name="bottom"></a>
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    </body>
    </html>