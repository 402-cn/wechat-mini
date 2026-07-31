<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$action = trim($_REQUEST['action'] ?? 'browse');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    if ($action === 'create_folder') {
        $parent = admin_video_safe_rel(trim($_POST['folder'] ?? ''));
        if ($parent === '' && trim($_POST['folder'] ?? '') !== '') {
            json_error('文件夹路径无效');
        }
        $name = admin_video_unique_folder_name($parent);
        $path = $parent === '' ? $name : $parent . '/' . $name;
        $dir = admin_video_abs_dir($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            json_error('创建文件夹失败');
        }
        json_ok(['path' => $path, 'name' => $name]);
    }
    if ($action === 'rename_folder') {
        $path = admin_video_safe_rel(trim($_POST['path'] ?? ''));
        if ($path === '') json_error('请选择文件夹');
        $name = trim($_POST['name'] ?? '');
        if ($name === '') json_error('文件夹名称不能为空');
        if (!preg_match('/^[a-zA-Z0-9_\-\x{4e00}-\x{9fa5}()（）]+$/u', $name)) {
            json_error('名称格式无效');
        }
        $dir = admin_video_abs_dir($path);
        $root = admin_video_uploads_root();
        if (!is_dir($dir) || strpos(realpath($dir) ?: '', realpath($root) ?: '') !== 0) {
            json_error('文件夹不存在');
        }
        $parts = explode('/', $path);
        $oldName = array_pop($parts);
        if ($oldName === $name) {
            json_ok(['path' => $path, 'name' => $name, 'old_path' => $path]);
        }
        $parentRel = implode('/', $parts);
        $parentDir = admin_video_abs_dir($parentRel);
        if (is_dir($parentDir . '/' . $name)) {
            json_error('同级目录下已存在同名文件夹');
        }
        $newPath = $parentRel === '' ? $name : $parentRel . '/' . $name;
        $newDir = admin_video_abs_dir($newPath);
        if (!rename($dir, $newDir)) {
            json_error('重命名失败');
        }
        json_ok(['path' => $newPath, 'name' => $name, 'old_path' => $path]);
    }
    if ($action === 'delete_folder') {
        $path = admin_video_safe_rel(trim($_POST['path'] ?? ''));
        if ($path === '') json_error('请选择文件夹');
        $dir = admin_video_abs_dir($path);
        $root = admin_video_uploads_root();
        if (!is_dir($dir) || strpos(realpath($dir) ?: '', realpath($root) ?: '') !== 0) {
            json_error('文件夹不存在');
        }
        admin_video_delete_recursive($dir);
        json_ok(['deleted' => $path]);
    }
    if ($action === 'delete_file') {
        $url = trim($_POST['url'] ?? '');
        if ($url === '' || !admin_video_delete_file_by_url($url)) {
            json_error('删除视频失败');
        }
        json_ok(['deleted' => $url]);
    }
    json_error('未知操作');
}

$folder = admin_video_safe_rel(trim($_GET['folder'] ?? ''));
if ($folder === '' && trim($_GET['folder'] ?? '') !== '') {
    json_error('文件夹路径无效');
}
$files = [];
$folders = [];
$dir = admin_video_abs_dir($folder);
if (!is_dir($dir)) mkdir($dir, 0755, true);
$breadcrumb = admin_video_breadcrumb($folder);
foreach (scandir($dir) ?: [] as $name) {
    if ($name === '.' || $name === '..') continue;
    $path = $dir . '/' . $name;
    if (is_dir($path)) {
        $rel = $folder === '' ? $name : $folder . '/' . $name;
        $folders[] = ['name' => $name, 'path' => $rel];
        continue;
    }
    if (!preg_match('/\.(mp4|webm|mov)$/i', $name)) continue;
    $url = admin_video_file_url($folder, $name);
    $files[] = [
        'url' => $url,
        'name' => $name,
        'size' => (int)filesize($path),
    ];
}
usort($folders, function ($a, $b) { return strcmp($a['name'], $b['name']); });
usort($files, function ($a, $b) { return strcmp($b['name'], $a['name']); });

json_ok([
    'files' => $files,
    'folders' => $folders,
    'breadcrumb' => $breadcrumb,
    'storage' => ['used' => admin_video_storage_used()],
]);
