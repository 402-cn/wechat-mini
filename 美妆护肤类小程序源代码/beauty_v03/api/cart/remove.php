<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/user_sync.php';
$pdo = db();
$user = require_user($pdo);
$data = get_json_input();
$id = (int)($data['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) json_error('参数错误');
$pdo->prepare('DELETE FROM cart_items WHERE id=? AND user_id=?')->execute([$id, (int)$user['id']]);
json_ok(['message' => '已删除']);
