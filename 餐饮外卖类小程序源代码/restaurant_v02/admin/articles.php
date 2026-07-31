<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/article_sync.php';
$pdo = db();
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = (int)($_GET['ps'] ?? 10);
if (!in_array($pageSize, [10, 50, 100], true)) $pageSize = 10;
$filter = trim((string)($_GET['filter'] ?? ''));
function articles_build_qs(int $pageSize, int $page, string $filter, array $extra = []): string {
    $qs = array_merge(['ps' => $pageSize, 'page' => $page], $extra);
    if ($filter !== '') $qs['filter'] = $filter;
    return http_build_query($qs);
}
$listQs = articles_build_qs($pageSize, $page, $filter);
if (isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['code' => 1, 'message' => '无效 ID']); exit; }
    $s = $pdo->prepare('SELECT * FROM articles WHERE id=? LIMIT 1');
    $s->execute([$id]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['code' => 1, 'message' => '文章不存在']); exit; }
    echo json_encode(['code' => 0, 'data' => $row], JSON_UNESCAPED_UNICODE);
    exit;
}
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    if ($id > 0) {
        $ns = $pdo->prepare('SELECT title FROM articles WHERE id=?');
        $ns->execute([$id]);
        $title = (string)$ns->fetchColumn();
        article_hard_delete($pdo, $id);
        admin_audit('删除', '文章管理', '删除了文章「' . $title . '」(ID:' . $id . ')');
    }
    header('Location: articles.php?' . articles_build_qs($pageSize, 1, $filter, ['msg' => '已删除']));
    exit;
}
if (isset($_GET['offshelf'])) {
    $id = (int)$_GET['offshelf'];
    if ($id > 0) {
        $ns = $pdo->prepare('SELECT title FROM articles WHERE id=?');
        $ns->execute([$id]);
        $title = (string)$ns->fetchColumn();
        $pdo->prepare('UPDATE articles SET status=0 WHERE id=?')->execute([$id]);
        article_remove_from_widget_lists($pdo, $id);
        admin_audit('修改', '文章管理', '下架了文章「' . $title . '」(ID:' . $id . ')');
    }
    header('Location: articles.php?' . $listQs . '&msg=' . urlencode('已下架'));
    exit;
}
if (isset($_GET['onshelf'])) {
    $id = (int)$_GET['onshelf'];
    if ($id > 0) {
        $ns = $pdo->prepare('SELECT title FROM articles WHERE id=?');
        $ns->execute([$id]);
        $title = (string)$ns->fetchColumn();
        $pdo->prepare('UPDATE articles SET status=1 WHERE id=?')->execute([$id]);
        admin_audit('修改', '文章管理', '上架了文章「' . $title . '」(ID:' . $id . ')');
    }
    header('Location: articles.php?' . $listQs . '&msg=' . urlencode('已上架'));
    exit;
}
if (isset($_GET['feature'])) {
    $id = (int)$_GET['feature'];
    $pdo->prepare('UPDATE articles SET is_featured=1 WHERE id=? AND status=1')->execute([$id]);
    article_add_to_widget_lists($pdo, $id);
    header('Location: articles.php?' . $listQs . '&msg=' . urlencode('已设为推荐'));
    exit;
}
if (isset($_GET['unfeature'])) {
    $id = (int)$_GET['unfeature'];
    $pdo->prepare('UPDATE articles SET is_featured=0 WHERE id=?')->execute([$id]);
    article_remove_from_widget_lists($pdo, $id);
    header('Location: articles.php?' . $listQs . '&msg=' . urlencode('已取消推荐'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_del'])) {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) $ids = [];
    $deleted = 0;
    foreach ($ids as $rawId) {
        $id = (int)$rawId;
        if ($id <= 0) continue;
        article_hard_delete($pdo, $id);
        $deleted++;
    }
    if ($deleted > 0) admin_audit('删除', '文章管理', '批量删除了 ' . $deleted . ' 篇文章');
    header('Location: articles.php?' . articles_build_qs($pageSize, $page, $filter, ['msg' => '已批量删除 ' . $deleted . ' 条']));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['article_save'])) {
    $aid = (int)($_POST['id'] ?? 0);
    $content = $_POST['content'] ?? '';
    $catId = (int)($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    if ($aid > 0) {
        $oldStmt = $pdo->prepare('SELECT title,summary FROM articles WHERE id=?');
        $oldStmt->execute([$aid]);
        $oldRow = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $pdo->prepare('UPDATE articles SET title=?,cover=?,summary=?,content=?,category_id=?,is_demo=0 WHERE id=?')->execute([
            $title, trim($_POST['cover'] ?? ''), trim($_POST['summary'] ?? ''), $content, $catId, $aid,
        ]);
        $diff = admin_audit_field_changes($oldRow, ['title' => $title, 'summary' => trim($_POST['summary'] ?? '')], ['标题' => 'title', '摘要' => 'summary']);
        admin_audit('修改', '文章管理', '修改了文章「' . $title . '」(ID:' . $aid . ')' . ($diff !== '' ? '：' . $diff : ''));
        $saveMsg = '修改成功';
    } else {
        $pdo->prepare('INSERT INTO articles (title,cover,summary,content,category_id,status,is_demo) VALUES (?,?,?,?,?,1,0)')->execute([
            $title, trim($_POST['cover'] ?? ''), trim($_POST['summary'] ?? ''), $content, $catId,
        ]);
        admin_audit('新增', '文章管理', '新增了文章「' . $title . '」');
        $saveMsg = '新增成功';
    }
    header('Location: articles.php?' . $listQs . '&msg=' . urlencode($saveMsg));
    exit;
}
$demoIds = ensure_demo_articles($pdo);
sync_article_widgets_featured_ids($pdo, $demoIds);
$msg = trim((string)($_GET['msg'] ?? ''));
$cats = $pdo->query('SELECT id,name FROM article_categories WHERE status=1 ORDER BY sort_order DESC,id ASC')->fetchAll(PDO::FETCH_ASSOC);
$where = 'WHERE 1=1';
$params = [];
if ($filter === 'on') { $where .= ' AND status=1'; }
elseif ($filter === 'off') { $where .= ' AND status=0'; }
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM articles ' . $where);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$stmt = $pdo->prepare('SELECT id,title,cover,is_demo,is_featured,status,view_count,created_at FROM articles ' . $where . ' ORDER BY sort_order DESC,id DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $pageSize, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start('文章管理', 'articles.php', '', '', '', '<button type="button" class="btn" onclick="openArticleModal(\'add\')">新增文章</button>');
echo '<link href="' . htmlspecialchars(asset_url('../assets/vendor/quill/quill.snow.css')) . '" rel="stylesheet">';
if ($msg !== '') echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card">';
echo '<form method="get" class="search-toolbar">';
echo '<input type="hidden" name="ps" value="' . (int)$pageSize . '">';
echo '<span class="filter-chips">';
$allArtCls = ($filter === '') ? 'chip-active' : 'btn btn-sm btn-secondary';
echo '<a class="' . $allArtCls . '" href="articles.php?ps=' . (int)$pageSize . '">所有文章</a> ';
foreach (['on' => '已上架', 'off' => '已下架'] as $fk => $fl) {
    $chipQs = articles_build_qs($pageSize, 1, ($filter === $fk ? '' : $fk));
    $cls = ($filter === $fk) ? 'chip-active' : 'btn btn-sm btn-secondary';
    echo '<a class="' . $cls . '" href="articles.php?' . htmlspecialchars($chipQs) . '">' . htmlspecialchars($fl) . '</a> ';
}
echo '</span></form>';
admin_batch_list_open('确定删除选中的 {n} 篇文章？删除后不可恢复');
echo '<table><tr>';
admin_batch_list_th();
echo '<th>ID</th><th>标题</th><th>浏览量</th><th>标记</th><th>状态</th><th>时间</th><th>操作</th></tr>';
foreach ($rows as $r) {
    $tags = [];
    if ((int)($r['is_featured'] ?? 0) === 1) $tags[] = '推荐';
    $isOn = (int)($r['status'] ?? 0) === 1;
    echo '<tr>';
    admin_batch_list_td((int)$r['id']);
    echo '<td>' . (int)$r['id'] . '</td><td>' . htmlspecialchars($r['title']) . '</td>';
    echo '<td>' . (int)($r['view_count'] ?? 0) . '</td>';
    echo '<td>' . htmlspecialchars(implode(' / ', $tags) ?: '—') . '</td>';
    echo '<td><span class="' . ($isOn ? 'status-on' : 'status-off') . '">' . ($isOn ? '正常' : '已下架') . '</span></td>';
    echo '<td>' . htmlspecialchars(admin_format_datetime((string)$r['created_at'])) . '</td>';
    echo '<td><button type="button" class="btn btn-sm" onclick="openArticleModal(\'edit\',' . (int)$r['id'] . ')">编辑</button> ';
    if ((int)($r['is_featured'] ?? 0) === 1) {
        echo '<a class="btn btn-sm btn-featured-done" href="?unfeature=' . (int)$r['id'] . '&' . $listQs . '" title="点击取消推荐">已推荐</a> ';
    } else {
        echo '<a class="btn btn-sm btn-featured" href="?feature=' . (int)$r['id'] . '&' . $listQs . '">推荐</a> ';
    }
    if ($isOn) {
        echo '<a class="btn btn-sm btn-secondary" href="?offshelf=' . (int)$r['id'] . '&' . $listQs . '" onclick="return confirm(\'确定下架该文章？\')">下架</a> ';
    } else {
        echo '<a class="btn btn-sm" href="?onshelf=' . (int)$r['id'] . '&' . $listQs . '">上架</a> ';
    }
    echo '<a class="btn btn-sm btn-danger" href="?del=' . (int)$r['id'] . '&' . $listQs . '" onclick="return confirm(\'确定删除该文章？删除后不可恢复\')">删除</a></td></tr>';
}
echo '</table>';
admin_batch_list_close();
admin_pagination($total, $page, $pageSize, 'articles.php?' . preg_replace('/&page=\d+/', '', $listQs));
echo '</div>';
echo '<div id="article-modal" class="admin-form-modal-overlay" onclick="if(event.target===this)closeArticleModal()"><div class="admin-form-modal" onclick="event.stopPropagation()">';
echo '<h3 id="article-modal-title">新增文章</h3>';
echo '<form method="post" id="articleForm" class="form-grid" onsubmit="return syncArticleContent()">';
echo '<input type="hidden" name="article_save" value="1"><input type="hidden" name="id" id="article_id" value="0">';
admin_field_text('标题', 'title', '');
admin_field_image('封面', 'cover', 'article_cover', '');
admin_field_text('摘要', 'summary', '');
echo '<div class="form-row"><label>分类</label><div class="field"><select name="category_id" id="article_category_id">';
echo '<option value="0">未分类</option>';
foreach ($cats as $c) {
    echo '<option value="' . (int)$c['id'] . '">' . htmlspecialchars($c['name']) . '</option>';
}
echo '</select></div></div>';
echo '<p class="tip">推荐文章请在列表中点击「推荐」按钮。</p>';
echo '<div class="form-row form-row-top"><label>正文</label><div class="field field-editor"><div id="article-editor"></div><input type="hidden" name="content" id="article_content"></div></div>';
echo '<div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeArticleModal()">取消</button><button class="btn" type="submit" id="article-submit-btn">新增文章</button></div>';
echo '</form></div></div>';
echo '<script src="' . htmlspecialchars(asset_url('../assets/vendor/quill/quill.js')) . '"></script>';
echo '<script>
function initArticleQuill(){
  window.adminQuillMap=window.adminQuillMap||{};
  if(window.adminQuillMap["#article-editor"]) return window.adminQuillMap["#article-editor"];
  if(typeof adminInitQuill!=="function") return null;
  return adminInitQuill("#article-editor","");
}
function syncArticleContent(){ return adminQuillSync("#article-editor","article_content"); }
function closeArticleModal(){ document.getElementById("article-modal").style.display="none"; }
function fillArticleForm(d){
  document.getElementById("article_id").value=d.id||0;
  var form=document.getElementById("articleForm");
  form.querySelector("[name=title]").value=d.title||"";
  if(typeof adminApplyImageField==="function") adminApplyImageField("article_cover", d.cover||"");
  else form.querySelector("[name=cover]").value=d.cover||"";
  form.querySelector("[name=summary]").value=d.summary||"";
  document.getElementById("article_category_id").value=d.category_id||0;
  var aq=initArticleQuill();
  if(aq){ aq.root.innerHTML=adminQuillNormalizeHtml(d.content||""); }
}
function clearArticleForm(){ fillArticleForm({id:0,content:"",category_id:0,cover:""}); }
function openArticleModal(mode,id){
  var modal=document.getElementById("article-modal");
  var title=document.getElementById("article-modal-title");
  var btn=document.getElementById("article-submit-btn");
  if(!modal){ showH5Toast("弹窗组件未加载"); return; }
  if(mode==="edit"&&id){
    title.textContent="编辑文章"; btn.textContent="修改文章";
    modal.style.display="flex";
    fetch("articles.php?json=1&id="+id,{credentials:"same-origin"}).then(function(r){return r.json();}).then(function(j){
      if(j.code!==0){ showH5Toast(j.message||"加载失败"); return; }
      fillArticleForm(j.data||{});
      initArticleQuill();
    }).catch(function(){ showH5Toast("加载失败"); });
  } else {
    title.textContent="新增文章"; btn.textContent="新增文章";
    modal.style.display="flex";
    clearArticleForm();
    initArticleQuill();
  }
}
</script>';
admin_layout_end();
