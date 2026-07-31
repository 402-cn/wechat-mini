<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = try_user($pdo);
if (!$user) json_ok(['logged_in' => false, 'list' => []]);
$stmt = $pdo->prepare('SELECT uc.id,uc.status,c.name,c.type,c.value,c.min_amount,c.end_at FROM user_coupons uc JOIN coupons c ON uc.coupon_id=c.id WHERE uc.user_id=? AND uc.status=0 ORDER BY uc.id DESC');
$stmt->execute([(int)$user['id']]);
json_ok(['logged_in' => true, 'list' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
