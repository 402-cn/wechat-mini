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
ensure_promo_banner_widget_row($pdo, $id);
$msg = '';
if (isset($_GET['add'])) {
    $pid = (int)$_GET['add'];
    if ($pid > 0) {
        $stmt = $pdo->prepare('SELECT product_ids FROM promo_banner_widgets WHERE instance_id=? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $ids = json_decode($row['product_ids'] ?: '[]', true) ?: [];
        $ids = array_values(array_map('intval', $ids));
        if (!in_array($pid, $ids, true)) {
            $ids[] = $pid;
            $pdo->prepare('UPDATE promo_banner_widgets SET product_ids=? WHERE instance_id=?')->execute([json_encode($ids), $id]);
        }
        $msg = '已添加商品';
    }
    header('Location: promo_banner_widget.php?id=' . urlencode($id) . '&msg=' . urlencode($msg ?: '操作完成'));
    exit;
}
if (isset($_GET['remove'])) {
    $pid = (int)$_GET['remove'];
    if ($pid > 0) {
        $stmt = $pdo->prepare('SELECT product_ids FROM promo_banner_widgets WHERE instance_id=? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $ids = json_decode($row['product_ids'] ?: '[]', true) ?: [];
        $ids = array_values(array_filter(array_map('intval', $ids), function ($v) use ($pid) { return $v !== $pid; }));
        $pdo->prepare('UPDATE promo_banner_widgets SET product_ids=? WHERE instance_id=?')->execute([json_encode($ids), $id]);
    }
    header('Location: promo_banner_widget.php?id=' . urlencode($id) . '&msg=' . urlencode('已移除'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner'])) {
    $productIds = json_decode($_POST['product_ids_json'] ?? '[]', true);
    if (!is_array($productIds)) $productIds = [];
    $clean = [];
    foreach ($productIds as $fid) {
        $fid = (int)$fid;
        if ($fid > 0 && !in_array($fid, $clean, true)) $clean[] = $fid;
    }
    $pdo->prepare('UPDATE promo_banner_widgets SET title=?,subtitle=?,banner_image=?,banner_bg_color=?,columns=?,row_count=?,product_ids=? WHERE instance_id=?')->execute([
        trim($_POST['title'] ?? ''),
        trim($_POST['subtitle'] ?? ''),
        trim($_POST['banner_image'] ?? ''),
        trim($_POST['banner_bg_color'] ?? '#e8f5e9'),
        max(1, min(3, (int)($_POST['columns'] ?? 2))),
        max(1, min(4, (int)($_POST['row_count'] ?? 2))),
        json_encode($clean),
        $id,
    ]);
    $msg = '保存成功';
}
if (!empty($_GET['msg'])) $msg = (string)$_GET['msg'];
$stmt = $pdo->prepare('SELECT * FROM promo_banner_widgets WHERE instance_id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '主题区块不存在'; exit; }
$productIds = json_decode($row['product_ids'] ?: '[]', true) ?: [];
$selected = [];
foreach ($productIds as $pid) $selected[(int)$pid] = true;
$allProducts = $pdo->query('SELECT id,name,image,price FROM products WHERE status=1 ORDER BY sort_order DESC,id ASC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start($row['label'], 'promo_banner_widget.php?id=' . $id, $id, '配置主题区块横幅与展示商品', admin_guide_flow_key((string)($row['page_key'] ?? ''), $id));
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid">';
echo '<input type="hidden" name="save_banner" value="1">';
admin_field_text('主题标题', 'title', (string)($row['title'] ?? ''));
admin_field_text('副标题', 'subtitle', (string)($row['subtitle'] ?? ''));
admin_field_image('横幅图片', 'banner_image', 'banner_image', (string)($row['banner_image'] ?? ''));
admin_field_text('横幅底色', 'banner_bg_color', (string)($row['banner_bg_color'] ?? '#e8f5e9'));
admin_field_text('列数', 'columns', (string)($row['columns'] ?? 2), 'number');
admin_field_text('行数', 'row_count', (string)($row['row_count'] ?? 2), 'number');
echo '<p class="tip">可在下方勾选/添加商品；列表为空时前台随机展示已上架商品。</p>';
echo '<input type="hidden" name="product_ids_json" id="product_ids_json" value="' . htmlspecialchars(json_encode(array_values(array_map('intval', $productIds)))) . '">';
echo '<p><button class="btn" type="submit">保存配置</button></p></form></div>';
echo '<div class="card"><h3>已选商品</h3><table class="data-table"><tr><th>ID</th><th>名称</th><th>操作</th></tr>';
foreach ($productIds as $pid) {
    $pid = (int)$pid;
    $name = '';
    foreach ($allProducts as $p) { if ((int)$p['id'] === $pid) { $name = $p['name']; break; } }
    echo '<tr><td>' . $pid . '</td><td>' . htmlspecialchars($name ?: ('#' . $pid)) . '</td><td><a href="?id=' . urlencode($id) . '&remove=' . $pid . '">移除</a></td></tr>';
}
echo '</table></div>';
echo '<div class="card"><h3>添加商品</h3><table class="data-table"><tr><th>ID</th><th>名称</th><th>操作</th></tr>';
foreach ($allProducts as $p) {
    $pid = (int)$p['id'];
    if (!empty($selected[$pid])) continue;
    echo '<tr><td>' . $pid . '</td><td>' . htmlspecialchars($p['name']) . '</td><td><a href="?id=' . urlencode($id) . '&add=' . $pid . '">添加</a></td></tr>';
}
echo '</table></div>';
admin_layout_end();
