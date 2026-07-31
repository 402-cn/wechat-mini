<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/user_sync.php';
$pdo = db();
ensure_user_schema($pdo);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('UPDATE member_levels SET name=?,min_points=?,discount=?,benefits=?,sort_order=? WHERE id=?')->execute([
            trim($_POST['name']??''), (int)($_POST['min_points']??0), (float)($_POST['discount']??1),
            trim($_POST['benefits']??''), (int)($_POST['sort_order']??0), $id,
        ]);
        $msg = '已保存';
    }
}
$rows = $pdo->query('SELECT * FROM member_levels ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start('会员等级', 'member_levels.php');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><table><tr><th>ID</th><th>名称</th><th>所需积分</th><th>折扣</th><th>权益说明</th><th>操作</th></tr>';
foreach ($rows as $r) {
    echo '<tr><td>' . (int)$r['id'] . '</td><td>' . htmlspecialchars($r['name']) . '</td>';
    echo '<td>' . (int)$r['min_points'] . '</td><td>' . $r['discount'] . '</td>';
    echo '<td>' . htmlspecialchars($r['benefits']??'') . '</td>';
    echo '<td><a class="btn btn-sm" href="?edit=' . (int)$r['id'] . '">编辑</a></td></tr>';
}
echo '</table></div>';
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM member_levels WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
    if ($edit) {
        echo '<div class="card"><form method="post" class="form-grid"><h3>编辑等级</h3>';
        echo '<input type="hidden" name="id" value="' . (int)$edit['id'] . '">';
        admin_field_text('名称', 'name', $edit['name']);
        admin_field_text('所需积分', 'min_points', (string)(int)$edit['min_points'], 'number');
        admin_field_text('折扣(0.85=85折)', 'discount', (string)$edit['discount'], 'number', 'step="0.01"');
        admin_field_textarea('权益说明', 'benefits', $edit['benefits']??'', 3);
        admin_field_text('排序', 'sort_order', (string)(int)$edit['sort_order'], 'number');
        echo '<p><button class="btn" type="submit">保存</button></p></form></div>';
    }
}
admin_layout_end();
