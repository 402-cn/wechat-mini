<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
$pdo = db();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = (string)($_POST['old_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if ($new === '' || strlen($new) < 4) $err = '新密码至少 4 位';
    elseif ($new !== $confirm) $err = '两次输入的新密码不一致';
    else {
        $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id=? LIMIT 1');
        $stmt->execute([(int)$_SESSION['admin_id']]);
        $hash = (string)$stmt->fetchColumn();
        if (!password_verify($old, $hash)) $err = '原密码不正确';
        else {
            $pdo->prepare('UPDATE admins SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_BCRYPT), (int)$_SESSION['admin_id']]);
            admin_audit('修改', '账号安全', '修改了登录密码');
            $msg = '密码已更新，请牢记新密码';
        }
    }
}
admin_layout_start('修改密码', 'change_password.php');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
if ($err) echo '<div class="msg msg-err">' . htmlspecialchars($err) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><h3>修改登录密码</h3>';
admin_field_text('原密码', 'old_password', '', 'password', 'autocomplete="current-password"');
admin_field_text('新密码', 'new_password', '', 'password', 'autocomplete="new-password"');
admin_field_text('确认新密码', 'confirm_password', '', 'password', 'autocomplete="new-password"');
echo '<button type="submit" class="btn">保存</button></form></div>';
admin_layout_end();
