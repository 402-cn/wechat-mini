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
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    $pdo->prepare('UPDATE rich_text_blocks SET content=? WHERE instance_id=?')->execute([$content, $id]);
    $msg = '保存成功';
}
$stmt = $pdo->prepare('SELECT * FROM rich_text_blocks WHERE instance_id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '富文本块不存在'; exit; }
$richContent = $row['content'] ?? '';
admin_layout_start($row['label'], 'richtext.php?id=' . $id, $id, '', admin_guide_flow_key((string)($row['page_key'] ?? ''), $id));
echo '<link href="' . htmlspecialchars(asset_url('../assets/vendor/quill/quill.snow.css')) . '" rel="stylesheet">';
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" id="rtForm" onsubmit="return adminQuillSync(\'#editor\',\'rt_content\')">';
echo '<div class="form-row form-row-top field-editor-row"><label>HTML 编辑器</label><div class="field field-editor"><div id="editor"></div><input type="hidden" name="content" id="rt_content"></div></div>';
echo '<p style="margin-top:16px"><button class="btn" type="submit">保存</button></p></form></div>';
echo '<script src="' . htmlspecialchars(asset_url('../assets/vendor/quill/quill.js')) . '"></script>';
echo '<script>window.__adminQuillBoot={sel:"#editor",html:' . json_encode($richContent, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) . '};</script>';
admin_layout_end();
