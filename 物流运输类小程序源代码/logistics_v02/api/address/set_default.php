<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = require_user($pdo);
$data = get_json_input();
$id = (int)($data['id'] ?? 0);
if ($id <= 0) json_error('参数错误');
$uid = (int)$user['id'];
$chk = $pdo->prepare('SELECT id FROM user_addresses WHERE id=? AND user_id=? LIMIT 1');
$chk->execute([$id, $uid]);
if (!$chk->fetch()) json_error('地址不存在');
$pdo->prepare('UPDATE user_addresses SET is_default=0 WHERE user_id=?')->execute([$uid]);
$pdo->prepare('UPDATE user_addresses SET is_default=1 WHERE id=? AND user_id=?')->execute([$id, $uid]);
json_ok(['message' => '已设为默认']);
