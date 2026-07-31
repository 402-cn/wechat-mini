<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/article_sync.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') { header('Location: dashboard.php'); exit; }
$pdo = db();
$demoIds = ensure_demo_articles($pdo);
ensure_article_widget_row($pdo, $id);
sync_article_widgets_featured_ids($pdo, $demoIds);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $featured = json_decode($_POST['featured_ids_json'] ?? '[]', true);
    if (!is_array($featured)) $featured = [];
    $featuredSet = [];
    $stmtFeat = $pdo->query('SELECT id FROM articles WHERE status=1 AND is_featured=1');
    while ($fr = $stmtFeat->fetch(PDO::FETCH_ASSOC)) $featuredSet[(int)$fr['id']] = true;
    $clean = [];
    foreach ($featured as $fid) {
        $fid = (int)$fid;
        if ($fid > 0 && isset($featuredSet[$fid]) && !in_array($fid, $clean, true)) $clean[] = $fid;
    }
    foreach ($featuredSet as $fid => $_) {
        if (!in_array($fid, $clean, true)) $clean[] = $fid;
    }
    $json = json_encode($clean);
    $pdo->prepare('UPDATE article_widgets SET label=?,layout=?,show_limit=?,show_more=?,featured_ids=? WHERE instance_id=?')->execute([
        trim($_POST['title'] ?? ''),
        trim($_POST['layout'] ?? 'image-text'),
        max(1, min(20, (int)($_POST['show_limit'] ?? 5))),
        !empty($_POST['show_more']) ? 1 : 0,
        $json,
        $id,
    ]);
    $msg = '保存成功';
}
$stmt = $pdo->prepare('SELECT * FROM article_widgets WHERE instance_id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '文章组件不存在'; exit; }
$featuredIds = json_decode($row['featured_ids'] ?: '[]', true) ?: [];
$articles = $pdo->query('SELECT a.id,a.title,a.created_at,a.category_id,COALESCE(c.name,\'未分类\') AS category_name FROM articles a LEFT JOIN article_categories c ON a.category_id=c.id AND c.status=1 WHERE a.status=1 AND a.is_featured=1 ORDER BY a.sort_order DESC,a.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$map = [];
foreach ($articles as $a) $map[(int)$a['id']] = $a;
$ordered = [];
foreach ($featuredIds as $fid) {
    $fid = (int)$fid;
    if (isset($map[$fid])) { $ordered[] = $map[$fid]; unset($map[$fid]); }
}
foreach ($map as $a) $ordered[] = $a;
$idsJson = json_encode(array_map(function($a){ return (int)$a['id']; }, $ordered));
admin_layout_start($row['label'], 'article_widget.php?id=' . $id, $id, '', admin_guide_flow_key((string)($row['page_key'] ?? ''), $id));
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid article-widget-form" id="widgetForm" onsubmit="return syncFeaturedOrder()">';
echo '<div class="form-row form-row-fixed"><label>模块标题</label><div class="field"><input type="text" name="title" maxlength="40" value="' . htmlspecialchars((string)($row['label'] ?? '')) . '"></div></div>';
admin_field_select('列表布局', 'layout', ['image-text'=>'图文列表','big-image'=>'大图卡片'], $row['layout']);
echo '<div class="form-row form-row-fixed"><label>展示条数</label><div class="field"><input type="number" name="show_limit" min="1" max="20" value="' . (int)$row['show_limit'] . '"></div></div>';
echo '<label><input type="checkbox" name="show_more" value="1"' . ((int)($row['show_more'] ?? 1) === 1 ? ' checked' : '') . '> 显示「更多」入口</label>';
echo '<div class="card" style="padding:0;margin-top:12px"><table class="sortable-table"><thead><tr><th style="width:40px"></th><th>ID</th><th>文章类型</th><th>文章标题</th><th>发表时间</th></tr></thead><tbody id="featured-sortable">';
if (!$ordered) {
    echo '<tr><td colspan="5" style="color:#999;padding:16px">暂无推荐文章，请先在「内容管理」中点击推荐</td></tr>';
} else {
    foreach ($ordered as $a) {
        echo '<tr data-id="' . (int)$a['id'] . '"><td class="drag-handle" title="拖动排序">≡</td>';
        echo '<td>' . (int)$a['id'] . '</td><td>' . htmlspecialchars($a['category_name']) . '</td>';
        echo '<td>' . htmlspecialchars($a['title']) . '</td><td>' . htmlspecialchars(admin_format_datetime((string)$a['created_at'])) . '</td></tr>';
    }
}
echo '</tbody></table></div>';
echo '<input type="hidden" name="featured_ids_json" id="featured_ids_json" value="' . htmlspecialchars($idsJson) . '">';
echo '<p style="margin-top:16px"><button class="btn" type="submit">保存</button></p></form></div>';
echo '<p class="tip">仅显示已在 <a href="articles.php">内容管理</a> 中标记为「推荐」的文章。拖动可调整首页展示顺序；「首页展示条数」控制首页显示前几篇。</p>';
echo '<script src="' . htmlspecialchars(asset_url('../assets/vendor/sortablejs/Sortable.min.js')) . '"></script>';
echo '<script>
function syncFeaturedOrder(){
  var tbody=document.getElementById("featured-sortable"); if(!tbody) return true;
  var ids=[]; tbody.querySelectorAll("tr[data-id]").forEach(function(tr){ ids.push(parseInt(tr.getAttribute("data-id"),10)); });
  document.getElementById("featured_ids_json").value=JSON.stringify(ids); return true;
}
var sortEl=document.getElementById("featured-sortable");
if(sortEl&&sortEl.querySelector("tr[data-id]")){
  Sortable.create(sortEl,{handle:".drag-handle",animation:150,ghostClass:"sortable-ghost",onEnd:syncFeaturedOrder});
}
</script>';
admin_layout_end();
