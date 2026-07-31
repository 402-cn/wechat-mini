<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/product_sync.php';
$pdo = db();
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = (int)($_GET['ps'] ?? 10);
if (!in_array($pageSize, [10, 50, 100], true)) $pageSize = 10;
$searchQ = trim((string)($_GET['q'] ?? ''));
$searchCat = (int)($_GET['cat'] ?? 0);
$filter = trim((string)($_GET['filter'] ?? ''));
function products_build_qs(int $pageSize, int $page, string $searchQ, int $searchCat, string $filter, array $extra = []): string {
    $qs = array_merge(['ps' => $pageSize, 'page' => $page], $extra);
    if ($searchQ !== '') $qs['q'] = $searchQ;
    if ($searchCat > 0) $qs['cat'] = $searchCat;
    if ($filter !== '') $qs['filter'] = $filter;
    return http_build_query($qs);
}
$listQs = products_build_qs($pageSize, $page, $searchQ, $searchCat, $filter);
if (isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['code' => 1, 'message' => '无效 ID']); exit; }
    $s = $pdo->prepare('SELECT * FROM products WHERE id=? LIMIT 1');
    $s->execute([$id]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['code' => 1, 'message' => '商品不存在']); exit; }
    echo json_encode(['code' => 0, 'data' => $row], JSON_UNESCAPED_UNICODE);
    exit;
}
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    if ($id > 0) {
        $ns = $pdo->prepare('SELECT name FROM products WHERE id=?');
        $ns->execute([$id]);
        $pname = (string)$ns->fetchColumn();
        product_hard_delete($pdo, $id);
        admin_audit('删除', '商品管理', '删除了商品「' . $pname . '」(ID:' . $id . ')');
    }
    header('Location: products.php?' . products_build_qs($pageSize, 1, $searchQ, $searchCat, $filter, ['msg' => '已删除']));
    exit;
}
if (isset($_GET['offshelf'])) {
    $id = (int)$_GET['offshelf'];
    if ($id > 0) {
        $ns = $pdo->prepare('SELECT name FROM products WHERE id=?');
        $ns->execute([$id]);
        $pname = (string)$ns->fetchColumn();
        $pdo->prepare('UPDATE products SET status=0 WHERE id=?')->execute([$id]);
        product_remove_from_widget_lists($pdo, $id);
        admin_audit('修改', '商品管理', '下架了商品「' . $pname . '」(ID:' . $id . ')');
    }
    header('Location: products.php?' . $listQs . '&msg=' . urlencode('已下架'));
    exit;
}
if (isset($_GET['onshelf'])) {
    $id = (int)$_GET['onshelf'];
    if ($id > 0) {
        $ns = $pdo->prepare('SELECT name FROM products WHERE id=?');
        $ns->execute([$id]);
        $pname = (string)$ns->fetchColumn();
        $pdo->prepare('UPDATE products SET status=1 WHERE id=?')->execute([$id]);
        admin_audit('修改', '商品管理', '上架了商品「' . $pname . '」(ID:' . $id . ')');
    }
    header('Location: products.php?' . $listQs . '&msg=' . urlencode('已上架'));
    exit;
}
if (isset($_GET['feature'])) {
    $id = (int)$_GET['feature'];
    $pdo->prepare('UPDATE products SET is_featured=1 WHERE id=? AND status=1')->execute([$id]);
    product_add_to_widget_lists($pdo, $id);
    header('Location: products.php?' . $listQs . '&msg=' . urlencode('已设为推荐'));
    exit;
}
if (isset($_GET['unfeature'])) {
    $id = (int)$_GET['unfeature'];
    $pdo->prepare('UPDATE products SET is_featured=0 WHERE id=?')->execute([$id]);
    product_remove_from_widget_lists($pdo, $id);
    header('Location: products.php?' . $listQs . '&msg=' . urlencode('已取消推荐'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_del'])) {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) $ids = [];
    $deleted = 0;
    foreach ($ids as $rawId) {
        $id = (int)$rawId;
        if ($id <= 0) continue;
        product_hard_delete($pdo, $id);
        $deleted++;
    }
    if ($deleted > 0) admin_audit('删除', '商品管理', '批量删除了 ' . $deleted . ' 条商品');
    header('Location: products.php?' . products_build_qs($pageSize, $page, $searchQ, $searchCat, $filter, ['msg' => '已批量删除 ' . $deleted . ' 条']));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_save'])) {
    $pid = (int)($_POST['id'] ?? 0);
    $catId = (int)($_POST['category_id'] ?? 0);
    $hasDeadline = !empty($_POST['has_deadline']);
    $flashEnd = null;
    if ($hasDeadline) {
        $flashEndRaw = trim((string)($_POST['flash_end_at'] ?? ''));
        $flashEnd = $flashEndRaw !== '' ? admin_datetime_from_input($flashEndRaw) : null;
    }
    $isFlashSale = !empty($_POST['is_flash_sale']) ? 1 : 0;
    $pname = trim($_POST['name'] ?? '');
    $pimage = trim($_POST['image'] ?? '');
    $pdesc = (string)($_POST['description'] ?? '');
    if ($pid > 0) {
        $oldStmt = $pdo->prepare('SELECT name,price,stock FROM products WHERE id=?');
        $oldStmt->execute([$pid]);
        $oldRow = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $pdo->prepare('UPDATE products SET name=?,image=?,price=?,description=?,category_id=?,sort_order=?,stock=?,is_flash_sale=?,flash_stock=?,flash_end_at=?,is_demo=0 WHERE id=?')->execute([
            $pname, $pimage, (float)($_POST['price'] ?? 0),
            $pdesc, $catId, (int)($_POST['sort_order'] ?? 0),
            (int)($_POST['stock'] ?? -1), $isFlashSale, (int)($_POST['flash_stock'] ?? -1),
            $flashEnd, $pid,
        ]);
        $diff = admin_audit_field_changes($oldRow, ['name' => $pname, 'price' => (string)(float)($_POST['price'] ?? 0), 'stock' => (string)(int)($_POST['stock'] ?? -1)], ['名称' => 'name', '价格' => 'price', '库存' => 'stock']);
        admin_audit('修改', '商品管理', '修改了商品「' . $pname . '」(ID:' . $pid . ')' . ($diff !== '' ? '：' . $diff : ''));
        $saveMsg = '修改成功';
    } else {
        $pdo->prepare('INSERT INTO products (name,image,price,description,category_id,sort_order,stock,is_flash_sale,flash_stock,flash_end_at,status,is_demo) VALUES (?,?,?,?,?,?,?,?,?,?,1,0)')->execute([
            $pname, $pimage, (float)($_POST['price'] ?? 0),
            $pdesc, $catId, (int)($_POST['sort_order'] ?? 0),
            (int)($_POST['stock'] ?? -1), $isFlashSale, (int)($_POST['flash_stock'] ?? -1),
            $flashEnd,
        ]);
        admin_audit('新增', '商品管理', '新增了商品「' . $pname . '」');
        $saveMsg = '新增成功';
    }
    header('Location: products.php?' . $listQs . '&msg=' . urlencode($saveMsg));
    exit;
}
$demoIds = ensure_demo_products($pdo);
sync_product_widgets_featured_ids($pdo, $demoIds);
$msg = trim((string)($_GET['msg'] ?? ''));
$hasOrders = (bool)$pdo->query("SHOW TABLES LIKE 'order_items'")->fetch();
$cats = $pdo->query('SELECT id,name FROM product_categories WHERE status=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_ASSOC);
$where = 'WHERE 1=1';
$params = [];
if ($searchQ !== '') { $where .= ' AND p.name LIKE ?'; $params[] = '%' . $searchQ . '%'; }
if ($searchCat > 0) { $where .= ' AND p.category_id=?'; $params[] = $searchCat; }
if ($filter === 'on') { $where .= ' AND p.status=1'; }
elseif ($filter === 'off') { $where .= ' AND p.status=0'; }
elseif ($filter === 'low_stock') { $where .= ' AND p.stock >= 0 AND p.stock < 10'; }
elseif ($filter === 'featured') { $where .= ' AND p.is_featured=1'; }
elseif ($filter === 'hot') { $where .= ' AND p.is_flash_sale=1'; }
elseif ($filter === 'soldout') { $where .= ' AND p.stock=0'; }
$soldSub = $hasOrders
    ? '(SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi INNER JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=p.id AND o.paid_at IS NOT NULL)'
    : '0';
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM products p ' . $where);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$sql = 'SELECT p.id,p.name,p.image,p.price,p.stock,p.status,p.is_demo,p.is_featured,p.is_flash_sale,p.sort_order,p.created_at,p.view_count,' . $soldSub . ' AS sold_qty,COALESCE(c.name,\'未分类\') AS category_name FROM products p LEFT JOIN product_categories c ON p.category_id=c.id AND c.status=1 ' . $where . ' ORDER BY p.sort_order DESC,p.id DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($sql);
foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v);
$stmt->bindValue(count($params) + 1, $pageSize, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$filterChips = [
    'low_stock' => '预警商品',
    'on' => '已上架',
    'off' => '已下架',
    'featured' => '推荐商品',
    'hot' => '热销商品',
    'soldout' => '已售空',
];
admin_layout_start('商品管理', 'products.php', '', '', '', '<button type="button" class="btn" onclick="openProductModal(\'add\')">新增商品</button>');
echo '<link href="' . htmlspecialchars(asset_url('../assets/vendor/quill/quill.snow.css')) . '" rel="stylesheet">';
if ($msg !== '') echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card">';
echo '<form method="get" class="search-toolbar">';
echo '<input type="hidden" name="ps" value="' . (int)$pageSize . '">';
echo '<label>商品名称</label><input class="input-short" name="q" value="' . htmlspecialchars($searchQ) . '" placeholder="搜索名称">';
echo '<label>商品分类</label><select class="input-short" name="cat"><option value="0">全部分类</option>';
foreach ($cats as $c) {
    $sel = ($searchCat === (int)$c['id']) ? ' selected' : '';
    echo '<option value="' . (int)$c['id'] . '"' . $sel . '>' . htmlspecialchars($c['name']) . '</option>';
}
echo '</select>';
if ($filter !== '') echo '<input type="hidden" name="filter" value="' . htmlspecialchars($filter) . '">';
echo '<button class="btn btn-sm" type="submit">搜索</button>';
if ($searchQ !== '' || $searchCat > 0 || $filter !== '') {
    echo ' <a class="btn btn-sm btn-secondary" href="products.php?ps=' . (int)$pageSize . '">清除</a>';
}
echo '<span class="filter-chips">';
$allProdCls = ($filter === '' && $searchQ === '' && $searchCat <= 0) ? 'chip-active' : 'btn btn-sm btn-secondary';
echo '<a class="' . $allProdCls . '" href="products.php?ps=' . (int)$pageSize . '">所有商品</a> ';
foreach ($filterChips as $fk => $fl) {
    $chipQs = products_build_qs($pageSize, 1, $searchQ, $searchCat, ($filter === $fk ? '' : $fk));
    $cls = 'btn btn-sm btn-secondary';
    if ($filter === $fk) $cls = 'chip-active';
    echo '<a class="' . $cls . '" href="products.php?' . htmlspecialchars($chipQs) . '">' . htmlspecialchars($fl) . '</a> ';
}
echo '</span></form>';
admin_batch_list_open('确定删除选中的 {n} 个商品？删除后不可恢复');
echo '<table><tr>';
admin_batch_list_th();
echo '<th>ID</th><th>图</th><th>分类</th><th>名称</th><th>价格</th><th>库存</th><th>销量</th><th>浏览量</th><th>推荐</th><th>热销</th><th>状态</th><th>操作</th></tr>';
foreach ($rows as $r) {
    $stock = (int)($r['stock'] ?? -1);
    $stockLabel = $stock < 0 ? '不限' : (string)$stock;
    if ($stock >= 0 && $stock < 10) $stockLabel = '<span style="color:#e67e22">' . $stockLabel . '</span>';
    $isOn = (int)($r['status'] ?? 0) === 1;
    echo '<tr>';
    admin_batch_list_td((int)$r['id']);
    echo '<td>' . (int)$r['id'] . '</td>';
    echo '<td><img src="' . htmlspecialchars(admin_asset_url($r['image'])) . '?v=' . (int)$r['id'] . '" style="height:36px;max-width:80px;object-fit:cover"></td>';
    echo '<td>' . htmlspecialchars($r['category_name'] ?? '未分类') . '</td>';
    echo '<td>' . htmlspecialchars($r['name']) . '</td>';
    echo '<td>¥' . htmlspecialchars((string)$r['price']) . '</td>';
    echo '<td>' . $stockLabel . '</td>';
    echo '<td>' . (int)($r['sold_qty'] ?? 0) . '</td>';
    echo '<td>' . (int)($r['view_count'] ?? 0) . '</td>';
    echo '<td>' . ((int)($r['is_featured'] ?? 0) === 1 ? '是' : '—') . '</td>';
    echo '<td>' . ((int)($r['is_flash_sale'] ?? 0) === 1 ? '是' : '—') . '</td>';
    echo '<td><span class="' . ($isOn ? 'status-on' : 'status-off') . '">' . ($isOn ? '上架' : '下架') . '</span></td>';
    echo '<td><button type="button" class="btn btn-sm" onclick="openProductModal(\'edit\',' . (int)$r['id'] . ')">编辑</button> ';
    if ((int)($r['is_featured'] ?? 0) === 1) {
        echo '<a class="btn btn-sm btn-featured-done" href="?unfeature=' . (int)$r['id'] . '&' . $listQs . '" title="点击取消推荐">已推荐</a> ';
    } else {
        echo '<a class="btn btn-sm btn-featured" href="?feature=' . (int)$r['id'] . '&' . $listQs . '">推荐</a> ';
    }
    if ($isOn) {
        echo '<a class="btn btn-sm btn-secondary" href="?offshelf=' . (int)$r['id'] . '&' . $listQs . '" onclick="return confirm(\'确定下架该商品？\')">下架</a> ';
    } else {
        echo '<a class="btn btn-sm" href="?onshelf=' . (int)$r['id'] . '&' . $listQs . '">上架</a> ';
    }
    echo '<a class="btn btn-sm btn-danger" href="?del=' . (int)$r['id'] . '&' . $listQs . '" onclick="return confirm(\'确定删除该商品？删除后不可恢复\')">删除</a></td></tr>';
}
echo '</table>';
admin_batch_list_close();
admin_pagination($total, $page, $pageSize, 'products.php?' . preg_replace('/&page=\d+/', '', $listQs));
echo '</div>';
echo '<div id="product-modal" class="admin-form-modal-overlay" onclick="if(event.target===this)closeProductModal()"><div class="admin-form-modal" onclick="event.stopPropagation()">';
echo '<h3 id="product-modal-title">新增商品</h3>';
echo '<form method="post" id="productForm" class="form-grid" onsubmit="return productFormSubmit()">';
echo '<input type="hidden" name="product_save" value="1"><input type="hidden" name="id" id="product_id" value="0">';
admin_field_text('名称', 'name', '');
admin_field_image('图片', 'image', 'product_image', '');
admin_field_text('价格', 'price', '0', 'number', 'step="0.01"');
echo '<div class="form-row"><label>分类</label><div class="field"><select name="category_id" id="product_category_id">';
echo '<option value="0">未分类</option>';
foreach ($cats as $c) {
    echo '<option value="' . (int)$c['id'] . '">' . htmlspecialchars($c['name']) . '</option>';
}
echo '</select></div></div>';
echo '<div class="form-row form-row-top"><label>描述</label><div class="field field-editor"><div id="product-editor"></div><input type="hidden" name="description" id="product_description"></div></div>';
admin_field_text('库存', 'stock', '-1', 'number', 'placeholder="-1 表示不限库存"');
admin_field_checkbox_hint('截止时间', 'has_deadline', false, '开启后显示截止时间选项');
echo '<div id="deadline-section" style="display:none">';
admin_field_datetime('截止时间', 'flash_end_at', '');
echo '</div>';
admin_field_checkbox_hint('限时秒杀', 'is_flash_sale', false, '参与秒杀活动');
echo '<div id="flash-stock-section" style="display:none">';
admin_field_text('秒杀限量', 'flash_stock', '-1', 'number', 'placeholder="-1 表示不限"');
echo '</div>';
admin_field_text('排序', 'sort_order', '0', 'number');
echo '<p class="tip">库存 -1 表示不限；支付成功时扣减，取消已支付订单会归还。推荐请在列表中点击「推荐」。</p>';
echo '<div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeProductModal()">取消</button><button class="btn" type="submit" id="product-submit-btn">新增商品</button></div>';
echo '</form></div></div>';
echo '<script src="' . htmlspecialchars(asset_url('../assets/vendor/quill/quill.js')) . '"></script>';
echo '<script>
function initProductQuill(){
  window.adminQuillMap=window.adminQuillMap||{};
  if(window.adminQuillMap["#product-editor"]) return window.adminQuillMap["#product-editor"];
  if(typeof adminInitQuill!=="function") return null;
  return adminInitQuill("#product-editor","");
}
function closeProductModal(){ document.getElementById("product-modal").style.display="none"; }
function productBindToggle(){
  var cb=document.querySelector("#productForm input[name=has_deadline]");
  var sec=document.getElementById("deadline-section");
  if(cb&&sec){ cb.onchange=function(){ sec.style.display=cb.checked?"":"none"; }; }
  var fs=document.querySelector("#productForm input[name=is_flash_sale]");
  var fsec=document.getElementById("flash-stock-section");
  if(fs&&fsec){ fs.onchange=function(){ fsec.style.display=fs.checked?"":"none"; }; }
}
function fillProductForm(d){
  document.getElementById("product_id").value=d.id||0;
  var form=document.getElementById("productForm");
  form.querySelector("[name=name]").value=d.name||"";
  if(typeof adminApplyImageField==="function") adminApplyImageField("product_image", d.image||"");
  else { form.querySelector("[name=image]").value=d.image||""; }
  form.querySelector("[name=price]").value=d.price||0;
  document.getElementById("product_category_id").value=d.category_id||0;
  var pq=initProductQuill();
  if(pq){ pq.root.innerHTML=adminQuillNormalizeHtml(d.description||""); }
  document.getElementById("product_description").value=d.description||"";
  form.querySelector("[name=stock]").value=(d.stock!=null?d.stock:-1);
  form.querySelector("[name=sort_order]").value=d.sort_order||0;
  form.querySelector("[name=flash_stock]").value=(d.flash_stock!=null?d.flash_stock:-1);
  var hasDl=!!(d.flash_end_at);
  form.querySelector("[name=has_deadline]").checked=hasDl;
  document.getElementById("deadline-section").style.display=hasDl?"":"none";
  form.querySelector("[name=flash_end_at]").value=d.flash_end_at||"";
  var isFlash=!!(parseInt(d.is_flash_sale,10));
  form.querySelector("[name=is_flash_sale]").checked=isFlash;
  document.getElementById("flash-stock-section").style.display=isFlash?"":"none";
}
function clearProductForm(){
  fillProductForm({id:0,stock:-1,flash_stock:-1,sort_order:0,price:0,category_id:0,description:""});
}
function openProductModal(mode,id){
  productBindToggle();
  var modal=document.getElementById("product-modal");
  var title=document.getElementById("product-modal-title");
  var btn=document.getElementById("product-submit-btn");
  if(!modal){ showH5Toast("弹窗组件未加载"); return; }
  if(mode==="edit"&&id){
    title.textContent="编辑商品"; btn.textContent="修改商品";
    modal.style.display="flex";
    fetch("products.php?json=1&id="+id,{credentials:"same-origin"}).then(function(r){return r.json();}).then(function(j){
      if(j.code!==0){ showH5Toast(j.message||"加载失败"); return; }
      fillProductForm(j.data||{});
      initProductQuill();
      adminBindDatetimeFields(document.getElementById("productForm"));
    }).catch(function(){ showH5Toast("加载失败"); });
  } else {
    title.textContent="新增商品"; btn.textContent="新增商品";
    modal.style.display="flex";
    clearProductForm();
    initProductQuill();
    adminBindDatetimeFields(document.getElementById("productForm"));
  }
}
function productFormSubmit(){
  adminQuillSync("#product-editor","product_description");
  var img=document.getElementById("product_image");
  if(!img||!String(img.value||"").trim()){ showH5Toast("请选择商品图片"); return false; }
  return adminValidateDatetimeFields(document.getElementById("productForm"));
}
productBindToggle();
</script>';
admin_layout_end();
