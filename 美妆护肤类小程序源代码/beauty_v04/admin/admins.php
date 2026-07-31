<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
$pdo = db();
admin_rbac_ensure_tables($pdo);
$msg = '';
if (isset($_GET['ban'])) {
    $id = (int)$_GET['ban'];
    if ($id > 0 && $id !== (int)$_SESSION['admin_id']) {
        $row = $pdo->prepare('SELECT username FROM admins WHERE id=?');
        $row->execute([$id]);
        $uname = (string)$row->fetchColumn();
        if ($uname !== 'admin') {
            $pdo->prepare('UPDATE admins SET status=0 WHERE id=?')->execute([$id]);
            admin_audit('修改', '管理员', '封禁了管理员「' . $uname . '」');
            header('Location: admins.php?msg=' . urlencode('已封禁'));
            exit;
        }
    }
}
if (isset($_GET['unban'])) {
    $id = (int)$_GET['unban'];
    if ($id > 0) {
        $row = $pdo->prepare('SELECT username FROM admins WHERE id=?');
        $row->execute([$id]);
        $uname = (string)$row->fetchColumn();
        $pdo->prepare('UPDATE admins SET status=1 WHERE id=?')->execute([$id]);
        admin_audit('修改', '管理员', '解封了管理员「' . $uname . '」');
        header('Location: admins.php?msg=' . urlencode('已解封'));
        exit;
    }
}
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    if ($id > 0 && $id !== (int)$_SESSION['admin_id']) {
        $row = $pdo->prepare('SELECT username FROM admins WHERE id=?');
        $row->execute([$id]);
        $uname = (string)$row->fetchColumn();
        if ($uname !== 'admin') {
            $pdo->prepare('DELETE FROM admins WHERE id=?')->execute([$id]);
            admin_audit('删除', '管理员', '删除了管理员「' . $uname . '」');
            header('Location: admins.php?msg=' . urlencode('已删除'));
            exit;
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $username = trim((string)($_POST['username'] ?? ''));
    $nickname = trim((string)($_POST['nickname'] ?? ''));
    $groupId = (int)($_POST['group_id'] ?? 0);
    $password = (string)($_POST['password'] ?? '');
    $superId = admin_rbac_super_group_id($pdo);
    if ($groupId <= 0) $groupId = $superId;
    if ($id > 0) {
        $cur = $pdo->prepare('SELECT * FROM admins WHERE id=?');
        $cur->execute([$id]);
        $old = $cur->fetch(PDO::FETCH_ASSOC) ?: [];
        if ((string)($old['username'] ?? '') === 'admin') $groupId = $superId;
        $sql = 'UPDATE admins SET nickname=?, group_id=?';
        $args = [$nickname !== '' ? $nickname : (string)($old['username'] ?? ''), $groupId];
        if ($password !== '') { $sql .= ', password_hash=?'; $args[] = password_hash($password, PASSWORD_BCRYPT); }
        $sql .= ' WHERE id=?'; $args[] = $id;
        $pdo->prepare($sql)->execute($args);
        admin_audit('修改', '管理员', '修改了管理员「' . (string)($old['username'] ?? '') . '」' . ($password !== '' ? '（含密码）' : ''));
        $msg = '保存成功';
    } else {
        if ($username === '' || $password === '') $msg = '请填写用户名和密码';
        elseif (strlen($password) < 4) $msg = '密码至少 4 位';
        else {
            try {
                $pdo->prepare('INSERT INTO admins (username,password_hash,nickname,group_id,status) VALUES (?,?,?,?,1)')->execute([
                    $username, password_hash($password, PASSWORD_BCRYPT), $nickname !== '' ? $nickname : $username, $groupId,
                ]);
                admin_audit('新增', '管理员', '新增了管理员「' . $username . '」');
                $msg = '创建成功';
            } catch (Throwable $e) { $msg = '用户名已存在'; }
        }
    }
}
if (!empty($_GET['msg'])) $msg = (string)$_GET['msg'];
$groups = $pdo->query('SELECT id,name,slug,is_system FROM admin_groups ORDER BY is_system DESC,id ASC')->fetchAll(PDO::FETCH_ASSOC);
$rows = $pdo->query('SELECT a.id,a.username,a.nickname,a.status,a.created_at,g.name AS group_name,g.slug AS group_slug FROM admins a LEFT JOIN admin_groups g ON a.group_id=g.id ORDER BY a.id ASC')->fetchAll(PDO::FETCH_ASSOC);
$edit = null;
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM admins WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
}
admin_layout_start('管理员', 'admins.php');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><h3>' . ($edit ? '编辑管理员' : '新增管理员') . '</h3>';
if ($edit) echo '<input type="hidden" name="id" value="' . (int)$edit['id'] . '">';
if ($edit) {
    echo '<div class="form-row"><label>用户名</label><div class="field"><input value="' . htmlspecialchars((string)$edit['username']) . '" disabled></div></div>';
} else {
    admin_field_text('用户名', 'username', '');
}
admin_field_text('昵称', 'nickname', (string)($edit['nickname'] ?? ''));
echo '<div class="form-row"><label>所属组</label><div class="field"><select name="group_id">';
foreach ($groups as $g) {
    $sel = ((int)($edit['group_id'] ?? 0) === (int)$g['id']) ? ' selected' : '';
    $lock = ((string)($edit['username'] ?? '') === 'admin' && (string)$g['slug'] !== 'super') ? ' disabled' : '';
    echo '<option value="' . (int)$g['id'] . '"' . $sel . $lock . '>' . htmlspecialchars((string)$g['name']) . '</option>';
}
echo '</select></div></div>';
admin_field_text('密码' . ($edit ? '（留空不改）' : ''), 'password', '', 'password', 'autocomplete="new-password"');
echo '<button type="submit" class="btn">保存</button></form></div>';
echo '<div class="card"><h3>管理员列表</h3><table><thead><tr><th>ID</th><th>用户名</th><th>昵称</th><th>组</th><th>状态</th><th>操作</th></tr></thead><tbody>';
foreach ($rows as $r) {
    $st = (int)$r['status'] === 1 ? '正常' : '已封禁';
    echo '<tr><td>' . (int)$r['id'] . '</td><td>' . htmlspecialchars((string)$r['username']) . '</td><td>' . htmlspecialchars((string)$r['nickname']) . '</td><td>' . htmlspecialchars((string)$r['group_name']) . '</td><td>' . $st . '</td><td>';
    echo '<a class="btn btn-sm btn-secondary" href="admins.php?edit=' . (int)$r['id'] . '">编辑</a> ';
    if ((string)$r['username'] !== 'admin' && (int)$r['id'] !== (int)$_SESSION['admin_id']) {
        if ((int)$r['status'] === 1) echo '<a class="btn btn-sm btn-danger" href="admins.php?ban=' . (int)$r['id'] . '" onclick="return confirm(\'确定封禁？\')">封禁</a> ';
        else echo '<a class="btn btn-sm" href="admins.php?unban=' . (int)$r['id'] . '">解封</a> ';
        echo '<a class="btn btn-sm btn-danger" href="admins.php?del=' . (int)$r['id'] . '" onclick="return confirm(\'确定删除？\')">删除</a>';
    } else echo '<span class="tip">内置超管</span>';
    echo '</td></tr>';
}
echo '</tbody></table></div>';
admin_layout_end();
