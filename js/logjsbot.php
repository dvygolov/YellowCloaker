<?php
require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../core.php';
require_once __DIR__ . '/../db.php';

global $filters;
$cloaker = new Cloaker($filters);
//Добавляем, по какому из js-событий мы поймали бота
$reason = $_GET['reason'] ?? 'js_tests';
$cloaker->block_reason[] = $reason;
$db = new Db();
$db->add_white_click($cloaker->click_params, $cloaker->block_reason, $cur_config);