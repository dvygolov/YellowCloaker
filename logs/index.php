<?php
	include '../settings.php';
      
    //------------------------------------------------
    //Configuration
    //
	$filter=isset($_GET['filter'])?$_GET['filter']:'';
	$fileName='';
	switch($filter){
		case '':
			$fileName = date("d.m.y").".csv";
			break;
		case 'leads':
			$fileName = date("d.m.y").".leads.csv";
		break;
		case 'blocked':
			$fileName = date("d.m.y").".blocked.csv";
		break;
	}

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
    				$tableOutput="<TABLE  style='min-width: 50%;'>";
     
    				//Print the table header
    				$tableOutput.="<TR style='background-color: lightgray;text-align:center;'>";
    				$tableOutput.="<TD><B>Row</B></TD>"; 
    				foreach ($logOriginalHeader as $field)
    					$tableOutput.="<TD><B>".$field."</B></TD>"; //Add the columns
    				$tableOutput.="</TR>";
     
    				//Get each line of the array and print the table files
    				$countLines = 0;
    				foreach ($fileLines as $line) {
    					if(trim($line) !== ''){ //Remove blank lines
    							$countLines++;
    							$arrayFields = array_map('trim', str_getcsv($line, $delimiter, $enclosure)); //Convert line to array
    							$tableOutput.="<TR><TD style='background-color: lightgray;'>".$countLines."</TD>";
    							foreach ($arrayFields as $field)
    								$tableOutput.="<TD>".$field."</TD>"; //Add the columns
    							$tableOutput.="</TR>";
    						}
    				}
     
    				//Print the table footer
    				$tableOutput.="<TR style='background-color: lightgray;text-align:center;'>";
    				$tableOutput.="<TD><B>Row</B></TD>";
    				foreach ($logOriginalHeader as $field)
    					$tableOutput.="<TD><B>".$field."</B></TD>"; //Add the columns
    				$tableOutput.="</TR>";
     
    				//Close the table tag
    				$tableOutput.="</TABLE>";
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
    <title>Binomo Cloacker Log</title>
    </head>
    <body>
    <h1>Binomo Cloacker Log</h1> 
	<div id="navigation">
		<a href="index.php?password=<?=$_GET['password']?>">Allowed</a> | <a href="index.php?filter=leads&password=<?=$_GET['password']?>">Leads</a> | <a href="index.php?filter=blocked&password=<?=$_GET['password']?>">Blocked</a>
	</div>
    <a name="top"></a>
    <hr>
    <table style="width:50%">
    	<tr>
    		<td><a href="" onClick="location.reload()">Refresh</a></td>
    		<td><a href="<?=$fileName ?>">Download</a></td>
    		<td><a href="#bottom">End</a></td>
    	</tr>
    </table>
    <hr>
    <?=$tableOutput ?>
    <a name="bottom"></a> 
    <hr>
    <table style="width:50%">
    	<tr>
    		<td><a href="" onClick="location.reload()">Refresh</a></td>
    		<td><a href="<?=$fileName ?>">Download</a></td>
    		<td><a href="#top">Top</a></td>
    	</tr>
    </table>
    <hr>
    </body>
    </html>