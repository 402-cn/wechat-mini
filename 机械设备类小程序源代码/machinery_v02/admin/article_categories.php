<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
$pdo = db();
$msg = '';
if (isset($_GET['del'])) {
    $pdo->prepare('UPDATE article_categories SET status=0 WHERE id=?')->execute([(int)$_GET['del']]);
    $msg = '已删除分类';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    if ($name !== '') {
        if ($id > 0) {
            $pdo->prepare('UPDATE article_categories SET name=?,sort_order=? WHERE id=?')->execute([$name, $sort, $id]);
        } else {
            $pdo->prepare('INSERT INTO article_categories (name,sort_order) VALUES (?,?)')->execute([$name, $sort]);
        }
        $msg = '保存成功';
    }
}
$edit = null;
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM article_categories WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
}
$rows = $pdo->query('SELECT id,name,sort_order FROM article_categories WHERE status=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start('文章分类', 'article_categories.php');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><h3>' . ($edit?'编辑分类':'新增分类') . '</h3>';
if ($edit) echo '<input type="hidden" name="id" value="' . (int)$edit['id'] . '">';
admin_field_text('分类名称', 'name', $edit['name'] ?? '');
admin_field_text('排序', 'sort_order', (string)(int)($edit['sort_order'] ?? 0), 'number');
echo '<p style="margin-top:16px"><button class="btn" type="submit">保存</button>';
if ($edit) echo ' <a class="btn btn-secondary" href="article_categories.php">取消</a>';
echo '</p></form></div>';
echo '<div class="card"><table><tr><th>ID</th><th>名称</th><th>排序</th><th>操作</th></tr>';
foreach ($rows as $r) {
    echo '<tr><td>' . (int)$r['id'] . '</td><td>' . htmlspecialchars($r['name']) . '</td><td>' . (int)$r['sort_order'] . '</td>';
    echo '<td><a class="btn btn-sm" href="?edit=' . (int)$r['id'] . '">编辑</a> <a class="btn btn-sm btn-danger" href="?del=' . (int)$r['id'] . '">删除</a></td></tr>';
}
echo '</table></div>';
admin_layout_end();
