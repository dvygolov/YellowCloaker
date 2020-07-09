<?php
    /* =============================================================
    /   * CSV Viewer
    	* Version 1.0 (05/07/2017)
    	*
    	* This application loads and parses a CSV file in the HTML format for browser viewing.
    	* Optionally the user can set a password in the configuration and then enter it using a GET request:
    	* Example: www.mysite.com/csvlogview.php?Password=mypassword 
    	* 
    	* Developed by Daniel Brooke Peig - daniel@danbp.org
    	* http://www.danbp.org
    	* Copyright 2017 - Daniel Brooke Peig
    	*
    	* This software is distributed under the MIT License.
    	* Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
    	*
    	* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
    	*
    	* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
    	*
    	*
    /* =============================================================*/
     
    //------------------------------------------------
    //Configuration
    //
    $fileName = "visitors.csv"; //CSV file location
    $delimiter = ","; //CSV delimiter character: , ; /t
    $enclosure = '"'; //CSV enclosure character: " ' 
    $password = ''; //Optional to prevent abuse. If set to [your_password] will require the &Password=[your_password] GET parameter to open the file
    $ignorePreHeader = 0; //Number of characters to ignore before the table header. Windows UTF-8 BOM has 3 characters.
    //------------------------------------------------
     
    //Variable initialization
    $logLines = array();
    $tableOutput = "<b>No data loaded</b>";
     
    //Verify the password (if set)
    if($_GET["Password"] === $password || $password === ""){
     
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