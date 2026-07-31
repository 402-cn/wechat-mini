<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = try_user($pdo);
if (!$user) json_ok(['logged_in' => false, 'list' => []]);
$stmt = $pdo->prepare('SELECT id,name,phone,detail,is_default,created_at FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, id DESC');
$stmt->execute([(int)$user['id']]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($list as &$row) {
    $row['id'] = (int)$row['id'];
    $row['is_default'] = (int)$row['is_default'];
}
json_ok(['logged_in' => true, 'list' => $list]);
