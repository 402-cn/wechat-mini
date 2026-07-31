<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('请使用 POST 上传');
}
if (empty($_FILES['file'])) {
    $cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($cl > 0) {
        json_error('上传体积超过服务器限制（413），请填写 CDN 外链或调大 Nginx client_max_body_size / PHP post_max_size');
    }
    json_error('请选择视频文件');
}
$file = $_FILES['file'];
if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
    json_error('视频过大，请填写 CDN 外链或联系管理员调大上传限制');
}
if ($file['error'] !== UPLOAD_ERR_OK) json_error('上传失败');
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['mp4','webm','mov'], true)) json_error('仅支持 mp4/webm/mov');
$folder = admin_video_safe_rel(trim($_POST['folder'] ?? ''));
if ($folder === '' && trim($_POST['folder'] ?? '') !== '') json_error('文件夹路径无效');
$dir = admin_video_abs_dir($folder);
if (!is_dir($dir) && !mkdir($dir, 0755, true)) json_error('无法创建上传目录');
$name = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $dir . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) json_error('保存失败');
$url = admin_video_file_url($folder, $name);
json_ok(['url' => $url, 'name' => $name, 'size' => (int)filesize($dest)]);
