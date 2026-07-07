<?php
require_once __DIR__.'/../paths.php';
$cssFsPath = __DIR__.'/css';
$cssPath = get_admin_base_url().'css';
?>
    <!-- Google Fonts-->
    <link rel="stylesheet" href="<?=$cssPath?>/gfonts.css?v=<?= filemtime($cssFsPath.'/main.css') ?>" />
    <!-- main CSS-->
    <link rel="stylesheet" href="<?=$cssPath?>/main.css?v=<?= filemtime($cssFsPath.'/main.css') ?>" />
    <!-- style CSS-->
    <link rel="stylesheet" href="<?=$cssPath?>/style.css?v=<?= filemtime($cssFsPath.'/style.css') ?>" />
    <!-- header CSS-->
    <link rel="stylesheet" href="<?=$cssPath?>/header.css?v=<?= filemtime($cssFsPath.'/header.css') ?>" />
    <!--Bootstrap-->
    <link rel="stylesheet" href="<?=$cssPath?>/bootstrap.min.css">
    <link rel="stylesheet" href="<?=$cssPath?>/bootstrap-icons.min.css">
    <!--QueryBuilder-->
    <link rel="stylesheet" href="<?=$cssPath?>/query-builder.dark.min.css"/>

    <!--Date Picker -->
    <link rel="stylesheet" href="<?=$cssPath?>/flatpickr.min.css">
    <link rel="stylesheet" href="<?=$cssPath?>/dark.css">
    <!--Data tables-->
    <link rel="stylesheet" href="<?=$cssPath?>/tabulator_clo.css?v=<?=filemtime($cssFsPath.'/tabulator_clo.css') ?>" >
    <link rel="stylesheet" href="<?=$cssPath?>/tabulator_midnight.css?v=<?=filemtime($cssFsPath.'/tabulator_midnight.css') ?>" >

    <!--JQuery Modal-->
    <link rel="stylesheet" href="<?=$cssPath?>/jquery.modal.min.css">
