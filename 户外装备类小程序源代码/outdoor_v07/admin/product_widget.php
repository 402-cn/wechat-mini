<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/product_sync.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') { header('Location: dashboard.php'); exit; }
$pdo = db();
ensure_demo_products($pdo);
ensure_product_widget_row($pdo, $id);
$searchQ = trim((string)($_GET['q'] ?? ''));
$searchCat = (int)($_GET['cat'] ?? 0);
$searchQs = 'id=' . urlencode($id);
if ($searchQ !== '') $searchQs .= '&q=' . urlencode($searchQ);
if ($searchCat > 0) $searchQs .= '&cat=' . $searchCat;
$msg = '';
if (isset($_GET['add'])) {
    $pid = (int)$_GET['add'];
    if ($pid > 0) {
        $chk = $pdo->prepare('SELECT id FROM products WHERE id=? AND status=1 LIMIT 1');
        $chk->execute([$pid]);
        if ($chk->fetch()) {
            $stmt = $pdo->prepare('SELECT product_ids FROM product_widgets WHERE instance_id=? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $ids = json_decode($row['product_ids'] ?: '[]', true);
            if (!is_array($ids)) $ids = [];
            $ids = array_values(array_map('intval', $ids));
            if (!in_array($pid, $ids, true)) {
                $ids[] = $pid;
                $pdo->prepare('UPDATE product_widgets SET product_ids=?,featured_ids=? WHERE instance_id=?')->execute([json_encode($ids), json_encode($ids), $id]);
            }
            $msg = '已添加到商品列表';
        }
    }
    header('Location: product_widget.php?' . $searchQs . '&msg=' . urlencode($msg ?: '操作完成'));
    exit;
}
if (isset($_GET['remove'])) {
    $pid = (int)$_GET['remove'];
    if ($pid > 0) {
        $stmt = $pdo->prepare('SELECT product_ids FROM product_widgets WHERE instance_id=? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $ids = json_decode($row['product_ids'] ?: '[]', true);
        if (!is_array($ids)) $ids = [];
        $ids = array_values(array_filter(array_map('intval', $ids), function ($v) use ($pid) { return $v !== $pid; }));
        $pdo->prepare('UPDATE product_widgets SET product_ids=?,featured_ids=? WHERE instance_id=?')->execute([json_encode($ids), json_encode($ids), $id]);
    }
    header('Location: product_widget.php?' . $searchQs . '&msg=' . urlencode('已移除'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productIds = json_decode($_POST['product_ids_json'] ?? '[]', true);
    if (!is_array($productIds)) $productIds = [];
    $clean = [];
    foreach ($productIds as $fid) {
        $fid = (int)$fid;
        if ($fid > 0 && !in_array($fid, $clean, true)) $clean[] = $fid;
    }
    $cols = max(1, min(6, (int)($_POST['columns'] ?? 2)));
    $rowCount = max(1, min(10, (int)($_POST['rows'] ?? 3)));
    $showLimit = $cols * $rowCount;
    $pdo->prepare('UPDATE product_widgets SET layout=?,columns=?,row_count=?,show_limit=?,product_ids=?,featured_ids=? WHERE instance_id=?')->execute([
        trim($_POST['layout'] ?? 'grid'),
        $cols, $rowCount, $showLimit,
        json_encode($clean), json_encode($clean),
        $id,
    ]);
    $msg = '保存成功';
}
if (!empty($_GET['msg'])) $msg = (string)$_GET['msg'];
$stmt = $pdo->prepare('SELECT * FROM product_widgets WHERE instance_id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '商品组件不存在'; exit; }
backfill_widget_product_ids($pdo, 'product_widgets', $id, 6);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$productIds = json_decode($row['product_ids'] ?: '[]', true) ?: [];
if (!$productIds) {
    $productIds = json_decode($row['featured_ids'] ?: '[]', true) ?: [];
}
$selectedSet = [];
foreach ($productIds as $pid) $selectedSet[(int)$pid] = true;
$cats = $pdo->query('SELECT id,name FROM product_categories WHERE status=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
$searchRows = [];
if ($searchQ !== '' || $searchCat > 0) {
    $where = 'WHERE p.status=1';
    $params = [];
    if ($searchQ !== '') { $where .= ' AND p.name LIKE ?'; $params[] = '%' . $searchQ . '%'; }
    if ($searchCat > 0) { $where .= ' AND p.category_id=?'; $params[] = $searchCat; }
    $sql = 'SELECT p.id,p.name,p.price,p.image,COALESCE(c.name,\'未分类\') AS category_name FROM products p LEFT JOIN product_categories c ON p.category_id=c.id AND c.status=1 ' . $where . ' ORDER BY p.sort_order DESC,p.id DESC LIMIT 50';
    $s = $pdo->prepare($sql);
    $s->execute($params);
    $searchRows = $s->fetchAll(PDO::FETCH_ASSOC);
}
$ordered = [];
if ($productIds) {
    $place = implode(',', array_fill(0, count($productIds), '?'));
    $s = $pdo->prepare("SELECT p.id,p.name,p.price,p.image,p.created_at,COALESCE(c.name,'未分类') AS category_name FROM products p LEFT JOIN product_categories c ON p.category_id=c.id AND c.status=1 WHERE p.status=1 AND p.id IN ($place)");
    $s->execute(array_map('intval', $productIds));
    $map = [];
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $p) $map[(int)$p['id']] = $p;
    foreach ($productIds as $pid) {
        $pid = (int)$pid;
        if (isset($map[$pid])) $ordered[] = $map[$pid];
    }
}
$idsJson = json_encode(array_map(function($p){ return (int)$p['id']; }, $ordered));
admin_layout_start($row['label'], 'product_widget.php?id=' . $id, $id, '本列表独立于商品库，可单独增删商品；默认展示 8 条演示数据。', admin_guide_flow_key((string)($row['page_key'] ?? ''), $id));
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid" id="widgetForm" onsubmit="return syncProductOrder()">';
admin_field_select('列表布局', 'layout', ['grid'=>'宫格','list'=>'列表','row'=>'左右一行一列'], $row['layout']);
admin_field_text('列数', 'columns', (string)(int)$row['columns'], 'number');
admin_field_text('行数', 'rows', (string)(int)$row['row_count'], 'number');
echo '<p class="tip">前台展示条数 = 列数 × 行数（当前 ' . (int)$row['show_limit'] . ' 个）。下方列表可拖动排序、移除；在页面底部搜索并添加商品。</p>';
echo '<div class="card" style="padding:0;margin-top:12px"><table class="sortable-table"><thead><tr><th style="width:40px"></th><th>ID</th><th>分类</th><th>商品名称</th><th>价格</th><th>操作</th></tr></thead><tbody id="product-sortable">';
if (!$ordered) {
    echo '<tr><td colspan="6" style="color:#999;padding:16px">暂无商品，请通过下方搜索添加</td></tr>';
} else {
    foreach ($ordered as $p) {
        echo '<tr data-id="' . (int)$p['id'] . '"><td class="drag-handle" title="拖动排序">≡</td>';
        echo '<td>' . (int)$p['id'] . '</td><td>' . htmlspecialchars($p['category_name']) . '</td>';
        echo '<td>' . htmlspecialchars($p['name']) . '</td><td>¥' . htmlspecialchars((string)$p['price']) . '</td>';
        echo '<td><a class="btn btn-sm btn-danger" href="?remove=' . (int)$p['id'] . '&' . htmlspecialchars($searchQs) . '" onclick="return confirm(\'确定从本列表移除？\')">移除</a></td></tr>';
    }
}
echo '</tbody></table></div>';
echo '<input type="hidden" name="product_ids_json" id="product_ids_json" value="' . htmlspecialchars($idsJson) . '">';
echo '<p style="margin-top:16px"><button class="btn" type="submit">保存</button></p></form></div>';
echo '<div class="card"><form method="get" class="form-grid"><h3>搜索并添加商品</h3>';
echo '<input type="hidden" name="id" value="' . htmlspecialchars($id) . '">';
admin_field_text('商品名称', 'q', $searchQ);
echo '<div class="form-row"><label>商品分类</label><div class="field"><select name="cat">';
echo '<option value="0">全部分类</option>';
foreach ($cats as $c) {
    $sel = ($searchCat === (int)$c['id']) ? ' selected' : '';
    echo '<option value="' . (int)$c['id'] . '"' . $sel . '>' . htmlspecialchars($c['name']) . '</option>';
}
echo '</select></div></div>';
echo '<p><button class="btn" type="submit">搜索</button></p></form>';
if ($searchQ !== '' || $searchCat > 0) {
    echo '<table style="margin-top:12px"><tr><th>ID</th><th>分类</th><th>名称</th><th>价格</th><th>操作</th></tr>';
    if (!$searchRows) {
        echo '<tr><td colspan="5" style="color:#999;padding:12px">未找到匹配商品</td></tr>';
    } else {
        foreach ($searchRows as $p) {
            $pid = (int)$p['id'];
            echo '<tr><td>' . $pid . '</td><td>' . htmlspecialchars($p['category_name']) . '</td>';
            echo '<td>' . htmlspecialchars($p['name']) . '</td><td>¥' . htmlspecialchars((string)$p['price']) . '</td><td>';
            if (isset($selectedSet[$pid])) {
                echo '<span style="color:#999">已添加</span>';
            } else {
                echo '<a class="btn btn-sm" href="?add=' . $pid . '&' . htmlspecialchars($searchQs) . '">添加</a>';
            }
            echo '</td></tr>';
        }
    }
    echo '</table>';
}
echo '</div>';
echo '<script src="' . htmlspecialchars(asset_url('../assets/vendor/sortablejs/Sortable.min.js')) . '"></script>';
echo '<script>
function syncProductOrder(){
  var tbody=document.getElementById("product-sortable"); if(!tbody) return true;
  var ids=[]; tbody.querySelectorAll("tr[data-id]").forEach(function(tr){ ids.push(parseInt(tr.getAttribute("data-id"),10)); });
  document.getElementById("product_ids_json").value=JSON.stringify(ids); return true;
}
var sortEl=document.getElementById("product-sortable");
if(sortEl&&sortEl.querySelector("tr[data-id]")){
  Sortable.create(sortEl,{handle:".drag-handle",animation:150,ghostClass:"sortable-ghost",onEnd:syncProductOrder});
}
</script>';
admin_layout_end();
