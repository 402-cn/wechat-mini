<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/image_process.php';
header('Content-Type: application/json; charset=utf-8');

$action = trim($_REQUEST['action'] ?? 'browse');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    if ($action === 'create_folder') {
        $parent = admin_media_safe_rel(trim($_POST['folder'] ?? ''));
        if ($parent === '' && trim($_POST['folder'] ?? '') !== '') {
            json_error('文件夹路径无效');
        }
        $name = admin_media_unique_folder_name($parent);
        $path = $parent === '' ? $name : $parent . '/' . $name;
        $dir = admin_media_abs_dir($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            json_error('创建文件夹失败');
        }
        json_ok(['path' => $path, 'name' => $name]);
    }
    if ($action === 'rename_folder') {
        $path = admin_media_safe_rel(trim($_POST['path'] ?? ''));
        if ($path === '') json_error('请选择文件夹');
        $name = trim($_POST['name'] ?? '');
        if ($name === '') json_error('文件夹名称不能为空');
        if (!preg_match('/^[a-zA-Z0-9_\-\x{4e00}-\x{9fa5}()（）]+$/u', $name)) {
            json_error('名称格式无效');
        }
        $dir = admin_media_abs_dir($path);
        $root = admin_media_uploads_root();
        if (!is_dir($dir) || strpos(realpath($dir) ?: '', realpath($root) ?: '') !== 0) {
            json_error('文件夹不存在');
        }
        $parts = explode('/', $path);
        $oldName = array_pop($parts);
        if ($oldName === $name) {
            json_ok(['path' => $path, 'name' => $name, 'old_path' => $path]);
        }
        $parentRel = implode('/', $parts);
        $parentDir = admin_media_abs_dir($parentRel);
        if (is_dir($parentDir . '/' . $name)) {
            json_error('同级目录下已存在同名文件夹');
        }
        $newPath = $parentRel === '' ? $name : $parentRel . '/' . $name;
        $newDir = admin_media_abs_dir($newPath);
        if (!rename($dir, $newDir)) {
            json_error('重命名失败');
        }
        json_ok(['path' => $newPath, 'name' => $name, 'old_path' => $path]);
    }
    if ($action === 'delete_folder') {
        $path = admin_media_safe_rel(trim($_POST['path'] ?? ''));
        if ($path === '') json_error('请选择文件夹');
        $dir = admin_media_abs_dir($path);
        $root = admin_media_uploads_root();
        if (!is_dir($dir) || strpos(realpath($dir) ?: '', realpath($root) ?: '') !== 0) {
            json_error('文件夹不存在');
        }
        admin_media_delete_recursive($dir);
        json_ok(['deleted' => $path]);
    }
    if ($action === 'delete_file') {
        $url = trim($_POST['url'] ?? '');
        if ($url === '' || !admin_media_delete_file_by_url($url)) {
            json_error('删除图片失败');
        }
        json_ok(['deleted' => $url]);
    }
    json_error('未知操作');
}

$tab = trim($_GET['tab'] ?? 'mine');
$q = trim($_GET['q'] ?? '');
$folder = admin_media_safe_rel(trim($_GET['folder'] ?? ''));
if ($folder === '' && trim($_GET['folder'] ?? '') !== '') {
    json_error('文件夹路径无效');
}
$files = [];
$folders = [];
$breadcrumb = [];

if ($tab === 'stock') {
    $dir = admin_project_root() . '/assets/images';
    if (is_dir($dir)) {
        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..' || preg_match('/_thumb\./i', $name)) continue;
            if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $name)) continue;
            if ($q !== '' && stripos($name, $q) === false) continue;
            $url = './assets/images/' . $name;
            $files[] = ['url' => $url, 'thumb' => $url, 'name' => $name];
        }
    }
    usort($files, function ($a, $b) { return strcmp($a['name'], $b['name']); });
} else {
    $dir = admin_media_abs_dir($folder);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $breadcrumb = admin_media_breadcrumb($folder);
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $dir . '/' . $name;
        if (is_dir($path)) {
            if ($q !== '' && stripos($name, $q) === false) continue;
            $rel = $folder === '' ? $name : $folder . '/' . $name;
            $folders[] = ['name' => $name, 'path' => $rel];
            continue;
        }
        if (preg_match('/_thumb\./i', $name)) continue;
        if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $name)) continue;
        if ($q !== '' && stripos($name, $q) === false) continue;
        $url = admin_media_file_url($folder, $name);
        $thumb = image_thumb_url($url);
        $thumbDisk = admin_media_uploads_root() . '/' . ($folder !== '' ? $folder . '/' : '') . basename($thumb);
        if (!is_file($thumbDisk)) $thumb = $url;
        $files[] = [
            'url' => $url,
            'thumb' => $thumb,
            'name' => $name,
            'size' => (int)filesize($path),
        ];
    }
    usort($folders, function ($a, $b) { return strcmp($a['name'], $b['name']); });
    usort($files, function ($a, $b) { return strcmp($b['name'], $a['name']); });
}

json_ok([
    'files' => $files,
    'folders' => $folders,
    'breadcrumb' => $breadcrumb,
    'storage' => ['used' => admin_media_storage_used()],
]);
