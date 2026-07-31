<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = try_user($pdo);
if (!$user) json_ok(['logged_in' => false, 'list' => [], 'total' => 0, 'page' => 1, 'has_more' => false]);
$status = trim((string)($_GET['status'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = max(1, min(20, (int)($_GET['page_size'] ?? 10)));
$where = 'WHERE user_id=?';
$params = [(int)$user['id']];
if ($status !== '') {
    if ($status === 'completed') {
        $where .= ' AND status IN (?,?)';
        $params[] = 'completed';
        $params[] = 'pending_review';
    } else {
        $where .= ' AND status=?';
        $params[] = $status;
    }
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$stmt = $pdo->prepare("SELECT id,order_no,status,total_amount,pay_amount,pay_type,created_at,paid_at FROM orders $where ORDER BY id DESC LIMIT $pageSize OFFSET $offset");
$stmt->execute($params);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($list as &$o) {
    $o['status_label'] = order_status_label($o['status']);
    $is = $pdo->prepare('SELECT product_name,product_image,price,quantity FROM order_items WHERE order_id=? LIMIT 3');
    $is->execute([(int)$o['id']]);
    $o['items'] = $is->fetchAll(PDO::FETCH_ASSOC);
}
json_ok(['logged_in' => true, 'list' => $list, 'total' => $total, 'page' => $page, 'has_more' => ($offset + count($list)) < $total]);
