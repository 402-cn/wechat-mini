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
    $pdo->prepare('UPDATE product_categories SET status=0 WHERE id=?')->execute([(int)$_GET['del']]);
    $msg = '已删除分类';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_order']) && isset($_POST['categories_order_json'])) {
        $order = json_decode($_POST['categories_order_json'], true);
        if (is_array($order)) {
            $total = count($order);
            $stmt = $pdo->prepare('UPDATE product_categories SET sort_order=? WHERE id=?');
            foreach ($order as $i => $cid) {
                $stmt->execute([$total - $i, (int)$cid]);
            }
            $msg = '排序已保存';
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            if ($id > 0) {
                $pdo->prepare('UPDATE product_categories SET name=? WHERE id=?')->execute([$name, $id]);
            } else {
                $maxSort = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM product_categories WHERE status=1')->fetchColumn();
                $pdo->prepare('INSERT INTO product_categories (name,sort_order) VALUES (?,?)')->execute([$name, $maxSort]);
            }
            $msg = '保存成功';
        }
    }
}
$edit = null;
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM product_categories WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
}
$rows = $pdo->query('SELECT id,name FROM product_categories WHERE status=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start('商品分类', 'product_categories.php');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><h3>' . ($edit?'编辑分类':'新增分类') . '</h3>';
if ($edit) echo '<input type="hidden" name="id" value="' . (int)$edit['id'] . '">';
admin_field_text('分类名称', 'name', $edit['name'] ?? '');
echo '<p style="margin-top:16px"><button class="btn" type="submit">保存</button>';
if ($edit) echo ' <a class="btn btn-secondary" href="product_categories.php">取消</a>';
echo '</p></form></div>';
echo '<div class="card"><form method="post" onsubmit="return syncCategoryOrder()">';
echo '<input type="hidden" name="save_order" value="1">';
echo '<input type="hidden" name="categories_order_json" id="categories_order_json" value="' . htmlspecialchars(json_encode(array_map(function($r){ return (int)$r['id']; }, $rows))) . '">';
echo '<table class="sortable-table"><thead><tr><th style="width:40px"></th><th>ID</th><th>名称</th><th>操作</th></tr></thead><tbody id="category-sortable">';
foreach ($rows as $r) {
    echo '<tr data-id="' . (int)$r['id'] . '"><td class="drag-handle" title="拖动排序">≡</td>';
    echo '<td>' . (int)$r['id'] . '</td><td>' . htmlspecialchars($r['name']) . '</td>';
    echo '<td><a class="btn btn-sm" href="?edit=' . (int)$r['id'] . '">编辑</a> <a class="btn btn-sm btn-danger" href="?del=' . (int)$r['id'] . '">删除</a></td></tr>';
}
echo '</tbody></table>';
echo '<p style="margin-top:16px"><button class="btn" type="submit">保存排序</button></p></form></div>';
echo '<script src="' . htmlspecialchars(asset_url('../assets/vendor/sortablejs/Sortable.min.js')) . '"></script>';
echo '<script>
function syncCategoryOrder(){
  var tbody=document.getElementById("category-sortable"); if(!tbody) return true;
  var ids=[]; tbody.querySelectorAll("tr[data-id]").forEach(function(tr){ ids.push(parseInt(tr.getAttribute("data-id"),10)); });
  document.getElementById("categories_order_json").value=JSON.stringify(ids); return true;
}
var sortEl=document.getElementById("category-sortable");
if(sortEl&&sortEl.querySelector("tr[data-id]")){
  Sortable.create(sortEl,{handle:".drag-handle",animation:150,ghostClass:"sortable-ghost",onEnd:syncCategoryOrder});
}
</script>';
admin_layout_end();
