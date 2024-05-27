<?php

    $cssPath = __DIR__.'/css';
?>
    <!-- Google Fonts
		============================================ -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet" />
    <!-- main CSS
		============================================ -->
    <link rel="stylesheet" href="./css/main.css?v=<?= filemtime($cssPath.'/main.css') ?>" />
    <!-- style CSS
		============================================ -->
    <link rel="stylesheet" href="./css/style.css?v=<?= filemtime($cssPath.'/style.css') ?>" />
    <!--Bootstrap-->
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/bootstrap-icons.min.css">
    <!--QueryBuilder-->
    <link rel="stylesheet" href="./css/query-builder.dark.min.css"/>

    <!--Date Picker -->
    <link rel="stylesheet" href="./css/flatpickr.min.css">
    <link rel="stylesheet" href="./css/dark.css">
    <!--Data tables-->
    <link rel="stylesheet" href="./css/tabulator_midnight.css?v=<?= filemtime($cssPath.'/tabulator_midnight.css') ?>" >