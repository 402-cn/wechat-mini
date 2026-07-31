<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = try_user($pdo);
if (!$user) {
    $stmt = $pdo->query('SELECT c.id,c.name,c.type,c.value,c.min_amount,c.total_count,c.used_count,c.end_at FROM coupons c
      WHERE c.status=1 AND (c.end_at IS NULL OR c.end_at >= NOW())
      AND (c.total_count=0 OR c.used_count < c.total_count)
      ORDER BY c.id DESC');
    json_ok(['logged_in' => false, 'list' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []]);
}
$stmt = $pdo->prepare('SELECT c.id,c.name,c.type,c.value,c.min_amount,c.total_count,c.used_count,c.end_at FROM coupons c
  WHERE c.status=1 AND (c.end_at IS NULL OR c.end_at >= NOW())
  AND (c.total_count=0 OR c.used_count < c.total_count)
  AND NOT EXISTS (SELECT 1 FROM user_coupons uc WHERE uc.user_id=? AND uc.coupon_id=c.id)
  ORDER BY c.id DESC');
$stmt->execute([(int)$user['id']]);
json_ok(['logged_in' => true, 'list' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []]);
