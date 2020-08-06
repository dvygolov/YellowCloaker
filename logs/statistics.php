<?php
//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

include '../settings.php';

    //------------------------------------------------
    //Configuration
    //
	$date=isset($_GET['date'])?$_GET['date']:date("d.m.y");

	$traf_fn=$date.'.csv';
	$ctr_fn=$date.'.lpctr.csv';
	$leads_fn=$date.'.leads.csv';

    $delimiter = ","; //CSV delimiter character: , ; /t
    $enclosure = '"'; //CSV enclosure character: " ' 
    $ignorePreHeader = 0; //Number of characters to ignore before the table header. Windows UTF-8 BOM has 3 characters.
    //------------------------------------------------
     
    //Variable initialization
    $logLines = array();
    $tableOutput = "<b>No data loaded</b>";
     
    //Verify the password (if set)
    if($_GET["password"] === $log_password || $log_password === ""){
     
    		if(file_exists($fileName)){ // File exists
     
    		// Reads lines of file to array
    		$fileLines = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
     
    		//Not Empty file
    		if($fileLines !== array()){
     
    			//Extract the existing header from the file
    			$lineHeader = array_shift($fileLines);
    			$logOriginalHeader = array_map('trim', str_getcsv(substr($lineHeader,$ignorePreHeader), $delimiter, $enclosure));
     
    			//Process the file only if the system could find a valid header
    			if(count($logOriginalHeader) > 0) {			
    				//Open the table tag
    				$tableOutput="<TABLE class='table w-auto table-striped'>";
     
    				//Print the table header
					$tableOutput.="<thead class='thead-dark'>";
    				$tableOutput.="<TR>";
    				$tableOutput.="<TH scope='col'>Row</TH>"; 
    				$tableOutput.="<TH scope='col'>Clicks</TH>"; 
    				$tableOutput.="<TH scope='col'>Unique</TH>"; 
    				$tableOutput.="<TH scope='col'>CR% all</TH>"; 
    				$tableOutput.="<TH scope='col'>CR% sales</TH>"; 
    				$tableOutput.="<TH scope='col'>App% (without trash)</TH>"; 
    				$tableOutput.="<TH scope='col'>App% (total)</TH>"; 
    				$tableOutput.="</TR></thead><tbody>";
     
    				//Get each line of the array and print the table files
    				$countLines = 0;
    				foreach ($fileLines as $line) {
    					if(trim($line) !== ''){ //Remove blank lines
    							$countLines++;
    							$arrayFields = array_map('trim', str_getcsv($line, $delimiter, $enclosure)); //Convert line to array
    							$tableOutput.="<TR><TD style='background-color: lightgray;'>".$countLines."</TD>";
    							$i=0;
								foreach ($arrayFields as $field)
								{
									$i++;
									if ($i==1 && $filter=='leads'){
										$tableOutput.="<TD><a href='index.php?password=".$_GET['password']."#".$field."'>".$field."</a></TD>"; 
										continue;
									}
									if ($i==1 && $filter==''){
										$tableOutput.="<TD><a name='".$field."'>".$field."</a></TD>"; 
										continue;
									}
									$tableOutput.="<TD>".$field."</TD>"; //Add the columns
								}
    							$tableOutput.="</TR>";
    						}
    				}
     
    				//Close the table tag
    				$tableOutput.="</tbody></TABLE>";
    			}
    			else $tableOutput = "<b>Invalid data format</b>";
    		}
    		else $tableOutput = "<b>Empty file</b>";
    	}
    	else $tableOutput = "<b>File not found</b>";
    }
    else $tableOutput = "<b>Invalid password.</b> Enter the password using this URL format: ".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."?Password=<b>your_password</b>";
?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8"> 
    <title>Binomo Cloaker Statistics</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    </head>
    <body>
    <h1>Binomo Cloaker Statistics</h1> 
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
	  <div class="collapse navbar-collapse" id="navbarNav">
		<ul class="navbar-nav">
		  <li class="nav-item">
			<a class="nav-link" href="index.php?password=<?=$_GET['password']?>">Allowed</a>
		  </li>
		  <li class="nav-item">
			<a class="nav-link" href="index.php?filter=leads&password=<?=$_GET['password']?>">Leads</a>
		  </li>
		  <li class="nav-item">
			<a class="nav-link" href="index.php?filter=blocked&password=<?=$_GET['password']?>">Blocked</a>
		  </li>
		  <li class="divider"></li>
		  <li class="nav-item">
			<a class="nav-link" href="" onClick="location.reload()">Refresh</a>
		  </li>
		</ul>
	  </div>
	</nav>

    <a name="top"></a>
    <?=$tableOutput ?>
    <a name="bottom"></a>
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    </body>
    </html>