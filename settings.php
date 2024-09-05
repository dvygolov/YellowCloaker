<?php
$cloSettings =
[
  //password of the cloaker's admin pages
  "adminPassword" => "12345qweasd",
  
  //if you are using nginx either change your website's config so that it prevents people from
  //downloading your database, or just rename the db file so security through obscurity will work! :-D
  //TODO: add an ability to quickly switch from SQLite to MySQL
  "dbFileName"=>"clicks.db", 
  
  //default timezone to show statistics for all campaigns on the admin's index.php page
  "timezone" => "Europe/Moscow", 

  "deeplApiKey"=> "",
  
  //if you want to automatically update MaxMind's geobases then go to maxmind.com, register, get API key
  //and put it here
  "maxMindKey"=> ""
]
?>