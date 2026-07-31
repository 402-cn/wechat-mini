<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = require_user($pdo);
$stmt = $pdo->prepare('SELECT points,type,remark,created_at FROM user_points_logs WHERE user_id=? ORDER BY id DESC LIMIT 100');
$stmt->execute([(int)$user['id']]);
json_ok(['list' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
