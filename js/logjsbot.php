<?php
require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../core.php';
require_once __DIR__ . '/../db.php';

//Добавляем, по какому из js-событий мы поймали бота
$reason = $_GET['reason'] ?? 'js_tests';
$db = new Db();
$db->add_white_click(Cloaker::get_click_params(), $reason, $cur_config);