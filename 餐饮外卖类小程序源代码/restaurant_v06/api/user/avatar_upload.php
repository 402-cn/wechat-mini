<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
require_once __DIR__ . '/../core/image_process.php';
$pdo = db();
$user = require_user($pdo);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('请使用 POST 上传');
if (empty($_FILES['file'])) json_error('请选择图片');
$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) json_error('上传失败');
$ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) json_error('仅支持 jpg/png/gif/webp');
$root = dirname(__DIR__, 2);
$dir = $root . '/uploads/avatars';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) json_error('无法创建上传目录');
$name = 'u' . (int)$user['id'] . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
$dest = $dir . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) json_error('保存失败');
image_process_uploaded_file($dest);
$url = '/uploads/avatars/' . $name;
$pdo->prepare('UPDATE users SET avatar=? WHERE id=?')->execute([$url, (int)$user['id']]);
$stmt = $pdo->prepare('SELECT id,username,openid,nickname,avatar,phone,balance,points,deposit,member_level,login_type FROM users WHERE id=? LIMIT 1');
$stmt->execute([(int)$user['id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
json_ok(['url' => $url, 'user' => user_public($row ?: [])], '头像已更新');
