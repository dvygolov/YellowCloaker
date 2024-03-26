<?php
global $tds_filters, $cur_config, $use_js_checks;
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/main.php';

$cloaker = new Cloaker($tds_filters);

//если используются js-проверки, то сначала используются они
//проверка же обычная идёт далее в файле js/jsprocessing.php
if ($use_js_checks) {
    white(true);
} else if ($cloaker->is_bad_click()) { //Обнаружили бота или модера
    $db = new Db();
    $db->add_white_click($cloaker->click_params, $cloaker->block_reason, $cur_config);
    white(false);
} else
    black($cloaker->click_params);