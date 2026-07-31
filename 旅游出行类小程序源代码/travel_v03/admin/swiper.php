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
$projectPages = json_decode(@file_get_contents(__DIR__ . '/_pages.json') ?: '[]', true) ?: [];
$pageNameMap = [];
foreach ($projectPages as $pg) {
    $pageNameMap[$pg['page_key'] ?? ''] = $pg['page_name'] ?? '';
}
$articleOptions = admin_article_options($pdo);
$productOptions = admin_product_options($pdo);
$msg = '';
if (isset($_GET['del'])) {
    $pdo->prepare('DELETE FROM swiper_items WHERE id = ? AND instance_id = ?')->execute([(int)$_GET['del'], $id]);
    $msg = '已删除';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        $linkJson = admin_link_build_from_post($_POST);
        $pdo->prepare('INSERT INTO swiper_items (instance_id,image,link,title,sort_order) VALUES (?,?,?,?,?)')->execute([
            $id, trim($_POST['image']??''), $linkJson, trim($_POST['title']??''), (int)($_POST['sort_order']??0),
        ]);
        $msg = '已添加';
    } elseif (isset($_POST['update_item'])) {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $linkJson = admin_link_build_from_post($_POST);
        $pdo->prepare('UPDATE swiper_items SET image=?, link=?, title=?, sort_order=? WHERE id=? AND instance_id=?')->execute([
            trim($_POST['image']??''), $linkJson, trim($_POST['title']??''), (int)($_POST['sort_order']??0), $itemId, $id,
        ]);
        $msg = '已保存';
    } else {
        $pdo->prepare('UPDATE swiper_instances SET height=?, autoplay=?, interval_ms=? WHERE instance_id=?')->execute([
            (int)($_POST['height']??360), !empty($_POST['autoplay'])?1:0, (int)($_POST['interval_ms']??3000), $id,
        ]);
        $msg = '设置已保存';
    }
}
$inst = $pdo->prepare('SELECT * FROM swiper_instances WHERE instance_id=? LIMIT 1');
$inst->execute([$id]);
$row = $inst->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '轮播不存在'; exit; }
$items = $pdo->prepare('SELECT * FROM swiper_items WHERE instance_id=? ORDER BY sort_order,id');
$items->execute([$id]);
$list = $items->fetchAll(PDO::FETCH_ASSOC);
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId > 0) {
    foreach ($list as $it) {
        if ((int)$it['id'] === $editId) { $editRow = $it; break; }
    }
}
admin_layout_start($row['label'], 'swiper.php?id=' . $id, $id, '', admin_guide_flow_key((string)($row['page_key'] ?? ''), $id));
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><h3>轮播设置</h3>';
admin_field_text('高度(rpx)', 'height', (string)(int)$row['height'], 'number');
admin_field_checkbox('自动播放', 'autoplay', (bool)$row['autoplay'], '开启自动轮播');
admin_field_text('间隔(ms)', 'interval_ms', (string)(int)$row['interval_ms'], 'number');
echo '<p><button class="btn" type="submit">保存设置</button></p></form></div>';
echo '<div class="card"><h3>轮播图片</h3><table><tr><th>图</th><th>标题</th><th>链接</th><th>排序</th><th>操作</th></tr>';
foreach ($list as $it) {
    echo '<tr><td><img src="' . htmlspecialchars(admin_asset_url($it['image'])) . '" style="height:40px;max-width:120px;object-fit:cover"></td>';
    echo '<td>' . htmlspecialchars($it['title']) . '</td><td>' . htmlspecialchars(admin_link_format($it['link'], $pageNameMap, $pdo)) . '</td><td>' . (int)$it['sort_order'] . '</td>';
    echo '<td><a class="btn btn-sm" href="?id=' . urlencode($id) . '&edit=' . (int)$it['id'] . '">改</a> ';
    echo '<a class="btn btn-sm btn-danger" href="?id=' . urlencode($id) . '&del=' . (int)$it['id'] . '" onclick="return confirm(\'确认删除?\')">删</a></td></tr>';
}
echo '</table>';
if ($editRow) {
    echo '<form method="post" class="form-grid" style="margin-top:16px"><input type="hidden" name="update_item" value="1"><input type="hidden" name="item_id" value="' . (int)$editRow['id'] . '">';
    echo '<h4>编辑图片 #' . (int)$editRow['id'] . '</h4>';
    admin_field_image('图片', 'image', 'swiper_image', (string)($editRow['image'] ?? ''));
    admin_field_text('标题', 'title', (string)($editRow['title'] ?? ''));
    admin_field_link('链接', $projectPages, $articleOptions, $productOptions, (string)($editRow['link'] ?? ''));
    admin_field_text('排序', 'sort_order', (string)(int)($editRow['sort_order'] ?? 0), 'number');
    echo '<p><button class="btn" type="submit">保存修改</button> <a class="btn btn-sm" href="?id=' . urlencode($id) . '">取消</a></p></form>';
} else {
    echo '<form method="post" class="form-grid" style="margin-top:16px"><input type="hidden" name="add_item" value="1">';
    admin_field_image('图片', 'image', 'swiper_image', '');
    admin_field_text('标题', 'title', '');
    admin_field_link('链接', $projectPages, $articleOptions, $productOptions, '');
    admin_field_text('排序', 'sort_order', '0', 'number');
    echo '<p><button class="btn" type="submit">添加图片</button></p></form>';
}
echo '</div>';
admin_layout_end();
