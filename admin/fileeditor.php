<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';

header('Content-Type: application/json');

function fe_error(string $msg): void
{
    echo json_encode(['error' => true, 'result' => $msg]);
    exit;
}

function get_lcache_dir(): string
{
    $type = $_REQUEST['type'] ?? 'landing';
    $subdirectory = $type === 'white' ? 'whites' : 'landings';
    $dir = realpath(__DIR__ . '/../' . get_cache_path($subdirectory));
    if ($dir === false) {
        fe_error('Target folder does not exist');
    }
    return $dir;
}

function safe_path(string $lcacheDir, string $folder, string $relativePath = ''): string
{
    // Build the target path
    $target = $lcacheDir . DIRECTORY_SEPARATOR . $folder;
    if ($relativePath !== '') {
        $target .= DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    // For existing paths, use realpath
    $real = realpath($target);
    if ($real !== false) {
        // Must be within cache/<folder>
        $folderReal = realpath($lcacheDir . DIRECTORY_SEPARATOR . $folder);
        if ($folderReal === false || !str_starts_with($real, $folderReal)) {
            fe_error('Access denied: path outside allowed directory');
        }
        return $real;
    }

    // For non-existing paths (create/save), resolve parent
    $parent = realpath(dirname($target));
    if ($parent === false) {
        fe_error('Parent directory does not exist');
    }
    $folderReal = realpath($lcacheDir . DIRECTORY_SEPARATOR . $folder);
    if ($folderReal === false || !str_starts_with($parent, $folderReal)) {
        fe_error('Access denied: path outside allowed directory');
    }
    return $parent . DIRECTORY_SEPARATOR . basename($target);
}

function scan_dir_recursive(string $dir, string $prefix = ''): array
{
    $result = [];
    $items = @scandir($dir);
    if ($items === false) return $result;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        $relPath = $prefix === '' ? $item : $prefix . '/' . $item;
        if (is_dir($fullPath)) {
            $result[] = [
                'name' => $item,
                'path' => $relPath,
                'type' => 'dir',
                'children' => scan_dir_recursive($fullPath, $relPath)
            ];
        } else {
            $result[] = [
                'name' => $item,
                'path' => $relPath,
                'type' => 'file',
                'size' => filesize($fullPath)
            ];
        }
    }
    return $result;
}

$action = $_REQUEST['action'] ?? '';
$folder = $_REQUEST['folder'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $folder)) {
    fe_error('Invalid folder name');
}

$lcacheDir = get_lcache_dir();
$folderPath = $lcacheDir . DIRECTORY_SEPARATOR . $folder;

if (!is_dir($folderPath)) {
    fe_error('Folder "' . $folder . '" does not exist');
}

switch ($action) {
    case 'list':
        $tree = scan_dir_recursive($folderPath);
        echo json_encode(['error' => false, 'tree' => $tree]);
        break;

    case 'read':
        $file = $_REQUEST['file'] ?? '';
        if (empty($file)) fe_error('No file specified');
        $path = safe_path($lcacheDir, $folder, $file);
        if (!is_file($path)) fe_error('File not found');
        $content = file_get_contents($path);
        if ($content === false) fe_error('Cannot read file');
        echo json_encode(['error' => false, 'content' => $content, 'file' => $file]);
        break;

    case 'save':
        $file = $_POST['file'] ?? '';
        $content = $_POST['content'] ?? '';
        if (empty($file)) fe_error('No file specified');
        $path = safe_path($lcacheDir, $folder, $file);
        if (file_put_contents($path, $content) === false) {
            fe_error('Cannot write file');
        }
        echo json_encode(['error' => false, 'result' => 'OK']);
        break;

    case 'create':
        $file = $_POST['file'] ?? '';
        $type = $_POST['type'] ?? 'file'; // 'file' or 'dir'
        if (empty($file)) fe_error('No name specified');
        $path = safe_path($lcacheDir, $folder, $file);
        if (file_exists($path)) fe_error('Already exists');
        if ($type === 'dir') {
            if (!mkdir($path, 0755, true)) fe_error('Cannot create directory');
        } else {
            $dir = dirname($path);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            if (file_put_contents($path, '') === false) fe_error('Cannot create file');
        }
        echo json_encode(['error' => false, 'result' => 'OK']);
        break;

    case 'delete':
        $file = $_POST['file'] ?? '';
        if (empty($file)) fe_error('No file specified');
        $path = safe_path($lcacheDir, $folder, $file);
        if (!file_exists($path)) fe_error('Not found');
        if (is_dir($path)) {
            if (!delete_dir_recursive($path)) fe_error('Cannot delete directory');
        } else {
            if (!unlink($path)) fe_error('Cannot delete file');
        }
        echo json_encode(['error' => false, 'result' => 'OK']);
        break;

    case 'rename':
        $file = $_POST['file'] ?? '';
        $newName = $_POST['newName'] ?? '';
        if (empty($file) || empty($newName)) fe_error('Missing parameters');
        $oldPath = safe_path($lcacheDir, $folder, $file);
        if (!file_exists($oldPath)) fe_error('Not found');
        // Build new path: same parent dir, new basename
        $parentRel = dirname($file);
        $newRel = ($parentRel === '.' ? '' : $parentRel . '/') . $newName;
        $newPath = safe_path($lcacheDir, $folder, $newRel);
        if (file_exists($newPath)) fe_error('Target already exists');
        if (!rename($oldPath, $newPath)) fe_error('Cannot rename');
        echo json_encode(['error' => false, 'result' => 'OK', 'newPath' => $newRel]);
        break;

    case 'upload':
        $subpath = $_POST['subpath'] ?? '';
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            fe_error('No file uploaded');
        }
        $targetRel = ($subpath !== '' ? $subpath . '/' : '') . basename($_FILES['file']['name']);
        $targetPath = safe_path($lcacheDir, $folder, $targetRel);
        $dir = dirname($targetPath);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            fe_error('Cannot save uploaded file');
        }
        echo json_encode(['error' => false, 'result' => 'OK', 'file' => $targetRel]);
        break;

    default:
        fe_error('Unknown action');
}

function delete_dir_recursive(string $dir): bool
{
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            if (!delete_dir_recursive($path)) return false;
        } else {
            if (!unlink($path)) return false;
        }
    }
    return rmdir($dir);
}
