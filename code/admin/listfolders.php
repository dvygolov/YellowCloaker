<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? 'landing';
$subdirectory = $type === 'white' ? 'whites' : 'landings';
$landingDir = realpath(__DIR__ . '/../' . get_cache_path($subdirectory));
if ($landingDir === false) {
    echo json_encode(['error' => false, 'folders' => []]);
    exit;
}

$folders = [];
$items = @scandir($landingDir);
if ($items !== false) {
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.gitkeep') continue;
        if (is_dir($landingDir . DIRECTORY_SEPARATOR . $item)) {
            $folders[] = $item;
        }
    }
}
sort($folders);

echo json_encode(['error' => false, 'folders' => $folders]);
