<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') { header('Location: dashboard.php'); exit; }
$pdo = db();
$msg = '';
if (isset($_GET['del'])) {
    $pdo->prepare('DELETE FROM message_submissions WHERE id = ? AND instance_id = ?')->execute([(int)$_GET['del'], $id]);
    $msg = '已删除';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_props'])) {
    $stmt = $pdo->prepare('SELECT props_json FROM widget_instances WHERE instance_id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $props = json_decode($row['props_json'] ?? '{}', true) ?: [];
    $props['title'] = trim($_POST['title'] ?? '');
    $props['submitText'] = trim($_POST['submitText'] ?? '提交留言');
    $props['requireLogin'] = !empty($_POST['requireLogin']);
    $pdo->prepare('UPDATE widget_instances SET props_json=? WHERE instance_id=?')->execute([
        json_encode($props, JSON_UNESCAPED_UNICODE), $id,
    ]);
    $msg = '配置已保存';
}
$stmt = $pdo->prepare('SELECT * FROM widget_instances WHERE instance_id = ? AND component_type = ? LIMIT 1');
$stmt->execute([$id, 'messageBoard']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '留言组件不存在'; exit; }
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$list = $pdo->prepare('SELECT * FROM message_submissions WHERE instance_id = ? ORDER BY id DESC LIMIT 200');
$list->execute([$id]);
$rows = $list->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start($row['label'] . ' · 留言管理', 'message_inbox.php?id=' . $id, $id, '悬停「?」查看前台留言板位置。此处仅调整页面展示；查看用户留言请前往「表单与留言 → 表单提交记录」。');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><input type="hidden" name="save_props" value="1">';
admin_field_text('模块标题', 'title', (string)($props['title'] ?? '在线留言'));
admin_field_text('提交按钮', 'submitText', (string)($props['submitText'] ?? '提交留言'));
echo '<label><input type="checkbox" name="requireLogin" value="1"' . (!empty($props['requireLogin']) ? ' checked' : '') . '> 需登录后提交</label>';
echo '<p style="margin-top:12px;color:#666;font-size:13px">表单字段在画布编辑器中配置。用户提交的留言数据请在 <a href="forms.php">表单与留言 → 表单提交记录</a> 中查看。</p>';
echo '<p><button class="btn" type="submit">保存配置</button></p></form></div>';
admin_layout_end();
