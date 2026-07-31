<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../../core/form_sync.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$data = get_json_input();
$__name = trim((string)($data['name'] ?? ''));
if ($__name === '') { json_error('请输入姓名'); }
$__phone = trim((string)($data['phone'] ?? ''));
if ($__phone === '') { json_error('请输入手机号'); }
if ($__phone !== '' && !preg_match('/^1[3-9]\d{9}$/', $__phone)) { json_error('字段 手机号 格式不正确'); }
$__message = trim((string)($data['message'] ?? ''));
if ($__message === '') { json_error('请输入留言内容'); }
$values = [];
$values[] = $__name;
$values[] = $__phone;
$values[] = $__message;
try {
    $pdo = db();
    ensure_form_table_columns($pdo, 'bakery_v04_home_contact', array(
        ['key'=>'name','type'=>'text','label'=>'姓名','options'=>array()],
        ['key'=>'phone','type'=>'phone','label'=>'手机号','options'=>array()],
        ['key'=>'message','type'=>'textarea','label'=>'留言','options'=>array()]
    ));
    $stmt = $pdo->prepare('INSERT INTO `bakery_v04_home_contact` (`name`, `phone`, `message`) VALUES (?, ?, ?)');
    $stmt->execute($values);
    json_ok(['id' => $pdo->lastInsertId(), 'message' => '提交成功']);
} catch (Throwable $e) {
    json_error('提交失败');
}
