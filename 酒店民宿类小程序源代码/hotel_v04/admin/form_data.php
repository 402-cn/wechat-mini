<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/form_display.php';
$formId = trim($_GET['form_id'] ?? '');
if ($formId === '') { header('Location: dashboard.php'); exit; }
$manifestRaw = @file_get_contents(dirname(__DIR__) . '/migrations/manifest.json');
$manifest = json_decode($manifestRaw ?: '{}', true);
if (!is_array($manifest)) $manifest = [];
$table = null;
$label = $formId;
$formFields = [];
foreach (($manifest['forms'] ?? []) as $f) {
    if (($f['formId'] ?? '') === $formId) {
        $table = $f['tableName'] ?? null;
        $label = $f['formName'] ?? $formId;
        $formFields = $f['fields'] ?? [];
        break;
    }
}
if (!$table && preg_match('/^[a-zA-Z0-9_]+$/', $formId)) {
    $pdo = db();
    $allTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array($formId, $allTables, true)) { $table = $formId; }
}
if (!$table) {
    admin_layout_start('表单字段', 'form_data.php');
    echo '<div class="card msg-err">表单不存在或 manifest.json 配置有误，请重新生成并部署。</div>';
    admin_layout_end();
    exit;
}
$pdo = db();
$msg = '';
$settingKey = 'form_placeholders_' . $formId;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $overrides = [];
    foreach ($formFields as $f) {
        $key = (string)($f['key'] ?? '');
        if ($key === '') continue;
        $overrides[$key] = trim((string)($_POST['ph_' . $key] ?? ''));
    }
    form_save_placeholders($pdo, $formId, $overrides);
    $msg = '保存成功';
}
$saved = form_load_placeholders($pdo, $formId);
admin_layout_start($label . ' - 表单字段', 'form_data.php?form_id=' . $formId, 'form_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $formId));
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><p class="tip">此处仅可修改各字段的占位提示文字，不能增减字段（字段结构在安装时已固定）。用户提交的数据请在 <a href="form_submissions.php?form_id=' . htmlspecialchars($formId) . '">提交记录</a> 或 <a href="forms.php">表单提交记录</a> 中查看。</p>';
echo '<form method="post" class="form-grid">';
foreach ($formFields as $f) {
    $key = (string)($f['key'] ?? '');
    if ($key === '') continue;
    $fieldLabel = trim((string)($f['label'] ?? $key));
    $defaultPh = (string)($f['placeholder'] ?? '');
    $val = $saved[$key] ?? $defaultPh;
    echo '<div class="form-row"><label>' . htmlspecialchars($fieldLabel) . '</label><div class="field">';
    echo '<input type="text" name="ph_' . htmlspecialchars($key) . '" value="' . htmlspecialchars($val) . '" placeholder="' . htmlspecialchars($defaultPh ?: '占位提示，如：请输入手机号码') . '">';
    echo '</div></div>';
}
echo '<p style="margin-top:16px"><button class="btn" type="submit">保存字段配置</button></p></form></div>';
admin_layout_end();
