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
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $g = $pdo->prepare('SELECT slug,is_system FROM admin_groups WHERE id=?');
    $g->execute([$id]);
    $row = $g->fetch(PDO::FETCH_ASSOC);
    if ($row && (int)$row['is_system'] !== 1) {
        $cntStmt = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE group_id=?');
        $cntStmt->execute([$id]);
        $cnt = (int)$cntStmt->fetchColumn();
        if ($cnt === 0) {
            $pdo->prepare('DELETE FROM admin_group_permissions WHERE group_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM admin_groups WHERE id=?')->execute([$id]);
            admin_audit('删除', '管理员组', '删除了管理员组 ID=' . $id);
            header('Location: admin_groups.php?msg=' . urlencode('已删除'));
            exit;
        }
        $msg = '组内仍有管理员，无法删除';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $perms = $_POST['perms'] ?? [];
    if (!is_array($perms)) $perms = [];
    if ($name === '') $msg = '请填写组名称';
    else {
        if ($id > 0) {
            $g = $pdo->prepare('SELECT slug,is_system FROM admin_groups WHERE id=?');
            $g->execute([$id]);
            $row = $g->fetch(PDO::FETCH_ASSOC) ?: [];
            if ((int)($row['is_system'] ?? 0) === 1) {
                admin_rbac_grant_super_all($pdo);
                $msg = '超级管理员组拥有全部权限，不可修改';
            } else {
                $pdo->prepare('UPDATE admin_groups SET name=? WHERE id=?')->execute([$name, $id]);
                $pdo->prepare('DELETE FROM admin_group_permissions WHERE group_id=?')->execute([$id]);
                $stmt = $pdo->prepare('INSERT INTO admin_group_permissions (group_id, permission_key) VALUES (?,?)');
                foreach ($perms as $p) {
                    $p = trim((string)$p);
                    if ($p !== '') $stmt->execute([$id, $p]);
                }
                admin_audit('修改', '管理员组', '修改了管理员组「' . $name . '」的权限（' . count($perms) . ' 项）');
                $msg = '保存成功';
            }
        } else {
            $slug = 'grp_' . substr(md5($name . microtime(true)), 0, 12);
            $pdo->prepare('INSERT INTO admin_groups (name, slug, is_system) VALUES (?,?,0)')->execute([$name, $slug]);
            $newId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO admin_group_permissions (group_id, permission_key) VALUES (?,?)');
            foreach ($perms as $p) {
                $p = trim((string)$p);
                if ($p !== '') $stmt->execute([$newId, $p]);
            }
            admin_audit('新增', '管理员组', '新增了管理员组「' . $name . '」');
            $msg = '创建成功';
        }
    }
}
if (!empty($_GET['msg'])) $msg = (string)$_GET['msg'];
$permDefs = admin_rbac_all_permission_keys();
$groups = $pdo->query('SELECT id,name,slug,is_system FROM admin_groups ORDER BY is_system DESC,id ASC')->fetchAll(PDO::FETCH_ASSOC);
$edit = null; $editPerms = [];
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM admin_groups WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
    if ($edit) {
        $ps = $pdo->prepare('SELECT permission_key FROM admin_group_permissions WHERE group_id=?');
        $ps->execute([(int)$edit['id']]);
        $editPerms = array_column($ps->fetchAll(PDO::FETCH_ASSOC), 'permission_key');
    }
}
$byGroup = [];
foreach ($permDefs as $key => $meta) {
    $gname = (string)($meta['group'] ?? '其他');
    $byGroup[$gname][] = ['key' => $key, 'label' => (string)($meta['label'] ?? $key)];
}
admin_layout_start('管理员组', 'admin_groups.php');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><h3>' . ($edit ? '编辑管理员组' : '新增管理员组') . '</h3>';
if ($edit) echo '<input type="hidden" name="id" value="' . (int)$edit['id'] . '">';
admin_field_text('组名称', 'name', (string)($edit['name'] ?? ''), 'text', ((int)($edit['is_system'] ?? 0) === 1 ? 'readonly' : ''));
echo '<h4>功能权限</h4><p class="tip">勾选该组可访问的左侧菜单功能。超级管理员组固定拥有全部权限。</p>';
$isSuperEdit = $edit && (int)($edit['is_system'] ?? 0) === 1;
foreach ($byGroup as $gname => $items) {
    echo '<div style="margin:12px 0"><strong>' . htmlspecialchars($gname) . '</strong><div style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px;margin-top:8px">';
    foreach ($items as $item) {
        $checked = ($isSuperEdit || in_array($item['key'], $editPerms, true)) ? ' checked' : '';
        $dis = $isSuperEdit ? ' disabled' : '';
        echo '<label class="chk-inline"><input type="checkbox" name="perms[]" value="' . htmlspecialchars($item['key']) . '"' . $checked . $dis . '> ' . htmlspecialchars($item['label']) . '</label>';
    }
    echo '</div></div>';
}
echo '<button type="submit" class="btn"' . ($isSuperEdit ? ' disabled' : '') . '>保存</button></form></div>';
echo '<div class="card"><h3>组列表</h3><table><thead><tr><th>ID</th><th>名称</th><th>标识</th><th>类型</th><th>操作</th></tr></thead><tbody>';
foreach ($groups as $g) {
    echo '<tr><td>' . (int)$g['id'] . '</td><td>' . htmlspecialchars((string)$g['name']) . '</td><td>' . htmlspecialchars((string)$g['slug']) . '</td><td>' . ((int)$g['is_system'] === 1 ? '系统内置' : '自定义') . '</td><td>';
    echo '<a class="btn btn-sm btn-secondary" href="admin_groups.php?edit=' . (int)$g['id'] . '">编辑/赋权</a> ';
    if ((int)$g['is_system'] !== 1) echo '<a class="btn btn-sm btn-danger" href="admin_groups.php?del=' . (int)$g['id'] . '" onclick="return confirm(\'确定删除？组内须无管理员\')">删除</a>';
    echo '</td></tr>';
}
echo '</tbody></table></div>';
admin_layout_end();
