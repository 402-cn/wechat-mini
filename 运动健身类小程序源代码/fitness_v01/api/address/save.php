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
$name = trim((string)($data['name'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$detail = trim((string)($data['detail'] ?? ''));
$isDefault = (int)($data['is_default'] ?? 0) ? 1 : 0;
if ($name === '' || $phone === '' || $detail === '') json_error('请填写完整地址信息');
$uid = (int)$user['id'];
if ($isDefault) {
    $pdo->prepare('UPDATE user_addresses SET is_default=0 WHERE user_id=?')->execute([$uid]);
}
if ($id > 0) {
    $chk = $pdo->prepare('SELECT id FROM user_addresses WHERE id=? AND user_id=? LIMIT 1');
    $chk->execute([$id, $uid]);
    if (!$chk->fetch()) json_error('地址不存在');
    $pdo->prepare('UPDATE user_addresses SET name=?,phone=?,detail=?,is_default=? WHERE id=? AND user_id=?')
        ->execute([$name, $phone, $detail, $isDefault, $id, $uid]);
    json_ok(['id' => $id, 'message' => '已保存']);
}
$pdo->prepare('INSERT INTO user_addresses (user_id,name,phone,detail,is_default) VALUES (?,?,?,?,?)')
    ->execute([$uid, $name, $phone, $detail, $isDefault]);
json_ok(['id' => (int)$pdo->lastInsertId(), 'message' => '已添加']);
