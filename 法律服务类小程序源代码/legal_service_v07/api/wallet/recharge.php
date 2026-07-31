<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = require_user($pdo);
$data = get_json_input();
$amount = round((float)($data['amount'] ?? 0), 2);
if ($amount <= 0 || $amount > 100000) json_error('充值金额无效');
wallet_change($pdo, (int)$user['id'], $amount, 'recharge', '', 0, '用户自助充值');
$stmt = $pdo->prepare('SELECT balance FROM users WHERE id=? LIMIT 1');
$stmt->execute([(int)$user['id']]);
json_ok(['message' => '充值成功', 'balance' => (float)$stmt->fetchColumn()]);
