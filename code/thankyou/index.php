<?php
require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/template.php';
$t = new ThankyouTemplate();
$t->processTemplate();
$t->processMacros();
$t->addPixelCode();
echo $t->getPage();
return;