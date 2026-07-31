<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/../../../core/bootstrap.php';
require_once __DIR__ . '/../../../core/form_display.php';
require_admin();

$formFields = array(
        ['key'=>'name','type'=>'text','label'=>'姓名','options'=>array()],
        ['key'=>'phone','type'=>'phone','label'=>'手机号','options'=>array()],
        ['key'=>'message','type'=>'textarea','label'=>'留言','options'=>array()]
    );
$fieldsByKey = form_fields_by_key($formFields);
$pdo = db();
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));
$where = '1=1';
$params = [];
if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) { $where .= ' AND created_at >= ?'; $params[] = $dateFrom . ' 00:00:00'; }
if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) { $where .= ' AND created_at <= ?'; $params[] = $dateTo . ' 23:59:59'; }
$sql = 'SELECT id, `name`, `phone`, `message`, created_at FROM `software_it_v07_home_contact` WHERE ' + $where + ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

form_send_csv_headers(form_export_filename());
echo "\xEF\xBB\xBF";
echo "ID,姓名,手机号,留言,提交时间\n";
foreach ($rows as $row) {
    $line = [$row['id']];
    foreach (['name', 'phone', 'message'] as $col) {
        $line[] = form_format_csv_value($col, $row[$col] ?? '', $fieldsByKey);
    }
    $line[] = $row['created_at'];
    echo implode(',', array_map(function ($v) { return '"' . str_replace('"', '""', (string)$v) . '"'; }, $line)) . "\n";
}
exit;
