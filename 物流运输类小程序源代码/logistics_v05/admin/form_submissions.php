<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
require_once dirname(__DIR__) . '/api/core/form_display.php';
$formId = trim($_GET['form_id'] ?? '');
if ($formId === '') { header('Location: forms.php'); exit; }
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
    admin_layout_start('提交记录', 'form_submissions.php');
    echo '<div class="card msg-err">表单不存在或 manifest.json 配置有误，请重新生成并部署。</div>';
    admin_layout_end();
    exit;
}
$pdo = db();
$fieldKeys = [];
$fieldLabels = [];
foreach ($formFields as $f) {
    $key = (string)($f['key'] ?? '');
    if ($key === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $key)) continue;
    $fieldKeys[] = $key;
    $fieldLabels[$key] = trim((string)($f['label'] ?? $key));
}
$fieldsByKey = form_fields_by_key($formFields);
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 50;
$where = '1=1';
$params = [];
if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where .= ' AND created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where .= ' AND created_at <= ?';
    $params[] = $dateTo . ' 23:59:59';
}
$safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM ' . $safeTable . ' WHERE ' . $where);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $pageSize;
$colSql = $fieldKeys ? implode(', ', $fieldKeys) : '';
$selectCols = $colSql !== '' ? 'id, ' . $colSql . ', created_at' : 'id, created_at';
$sql = 'SELECT ' . $selectCols . ' FROM ' . $safeTable . ' WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . (int)$pageSize . ' OFFSET ' . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$navHref = 'form_submissions.php?form_id=' . rawurlencode($formId);
admin_layout_start($label . ' - 提交记录', $navHref, 'form_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $formId));
$exportQs = [];
if ($dateFrom !== '') $exportQs['date_from'] = $dateFrom;
if ($dateTo !== '') $exportQs['date_to'] = $dateTo;
$exportHref = '../api/admin/form/' . rawurlencode($formId) . '/export.php' . ($exportQs ? '?' . http_build_query($exportQs) : '');
echo '<div class="card"><p class="tip">列出用户在本表单提交的数据。字段结构在 <a href="form_data.php?form_id=' . htmlspecialchars($formId) . '">表单字段</a> 中配置占位提示。</p>';
echo '<form method="get" class="form-grid" style="margin-bottom:16px">';
echo '<input type="hidden" name="form_id" value="' . htmlspecialchars($formId) . '">';
echo '<div class="form-row"><label>开始日期</label><div class="field"><input type="date" name="date_from" value="' . htmlspecialchars($dateFrom) . '"></div></div>';
echo '<div class="form-row"><label>结束日期</label><div class="field"><input type="date" name="date_to" value="' . htmlspecialchars($dateTo) . '"></div></div>';
echo '<p style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-sm" type="submit">筛选</button>';
echo '<a class="btn btn-sm btn-secondary" href="' . htmlspecialchars($navHref) . '">重置</a>';
echo '<a class="btn btn-sm" href="' . htmlspecialchars($exportHref) . '">导出 CSV</a></p></form>';
if (!$rows) {
    echo '<p style="color:#999">暂无提交记录</p>';
} else {
    echo '<p style="color:#666;margin:0 0 8px">共 ' . $total . ' 条，当前第 ' . $page . ' 页</p>';
    echo '<div style="overflow-x:auto"><table><tr><th>ID</th>';
    foreach ($fieldKeys as $k) {
        echo '<th>' . htmlspecialchars($fieldLabels[$k] ?? $k) . '</th>';
    }
    echo '<th>提交时间</th></tr>';
    foreach ($rows as $r) {
        echo '<tr><td>' . (int)$r['id'] . '</td>';
        foreach ($fieldKeys as $k) {
            $disp = form_format_display_value($k, $r[$k] ?? '', $fieldsByKey);
            echo '<td>' . htmlspecialchars($disp) . '</td>';
        }
        echo '<td>' . htmlspecialchars(admin_format_datetime((string)($r['created_at'] ?? ''))) . '</td></tr>';
    }
    echo '</table></div>';
    $totalPages = max(1, (int)ceil($total / $pageSize));
    if ($totalPages > 1) {
        echo '<p style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">';
        if ($page > 1) {
            $prevQs = http_build_query(array_filter(['form_id'=>$formId,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$page-1]));
            echo '<a class="btn btn-sm btn-secondary" href="?' . htmlspecialchars($prevQs) . '">上一页</a>';
        }
        if ($page < $totalPages) {
            $nextQs = http_build_query(array_filter(['form_id'=>$formId,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$page+1]));
            echo '<a class="btn btn-sm btn-secondary" href="?' . htmlspecialchars($nextQs) . '">下一页</a>';
        }
        echo '</p>';
    }
}
echo '</div>';
admin_layout_end();
