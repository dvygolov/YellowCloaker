<?php
@ini_set('upload_max_filesize', '256M');
@ini_set('post_max_size', '256M');
@ini_set('max_execution_time', '300');
@ini_set('display_errors', '0');
error_reporting(0);

// Clean any output that PHP may have already emitted (e.g. post_max_size warning)
if (ob_get_level()) ob_end_clean();
ob_start();

require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/zipupload_lib.php';
ob_end_clean();
header('Content-Type: application/json');

function zip_error(string $msg): void
{
    echo json_encode(['error' => true, 'result' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    zip_error('Only POST allowed');
}

// Detect if POST body was too large (PHP drops $_POST and $_FILES when post_max_size is exceeded)
if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
    $maxSize = ini_get('post_max_size');
    zip_error('File too large. Maximum upload size is ' . $maxSize . '. Restart the server with higher limits or upload manually using FTP/SSH.');
}

if (empty($_FILES['zipfile'])) {
    zip_error('No file uploaded');
}

if ($_FILES['zipfile']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload_max_filesize limit (' . ini_get('upload_max_filesize') . ')',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form MAX_FILE_SIZE limit',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload stopped by a PHP extension',
    ];
    $code = $_FILES['zipfile']['error'];
    $msg = $uploadErrors[$code] ?? 'Unknown upload error (code ' . $code . ')';
    zip_error($msg);
}

$folder = $_POST['folder'] ?? '';
$folder = trim($folder);

// Sanitize: only alphanumeric, hyphens, underscores, dots
if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $folder)) {
    zip_error('Invalid folder name. Use only letters, numbers, hyphens, underscores, dots.');
}

$uploadType = $_POST['type'] ?? 'landing';
$subdirectory = $uploadType === 'white' ? 'whites' : 'landings';
$baseFolder = get_cache_path($subdirectory);
$landingDir = realpath(__DIR__ . '/../' . $baseFolder);
if ($landingDir === false) {
    // Try to create it
    @mkdir(__DIR__ . '/../' . $baseFolder, 0755, true);
    $landingDir = realpath(__DIR__ . '/../' . $baseFolder);
    if ($landingDir === false) {
        zip_error('Target folder does not exist and could not be created');
    }
}

$targetDir = $landingDir . DIRECTORY_SEPARATOR . $folder;

if (file_exists($targetDir)) {
    zip_error('Folder "' . $folder . '" already exists. Choose a different name or delete it first.');
}

// Open and validate ZIP
$zip = new ZipArchive();
$res = $zip->open($_FILES['zipfile']['tmp_name']);
if ($res !== true) {
    zip_error('Cannot open ZIP archive (error code: ' . $res . ')');
}

$plan = zip_upload_analyze_archive($zip);
if (isset($plan['error'])) {
    $zip->close();
    zip_error((string)$plan['error']);
}

// Create target directory
if (!mkdir($targetDir, 0755, true)) {
    $zip->close();
    zip_error('Failed to create target directory');
}

try {
    zip_upload_extract_plan($zip, $plan, $targetDir);
} catch (Throwable $e) {
    $zip->close();
    zip_upload_remove_directory($targetDir);
    zip_error($e->getMessage());
}

$zip->close();

echo json_encode(['error' => false, 'folder' => $folder, 'result' => 'OK']);
