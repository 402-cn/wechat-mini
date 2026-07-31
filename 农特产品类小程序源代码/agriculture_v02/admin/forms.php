<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
admin_layout_start('表单提交记录', 'forms.php');
$rows = json_decode(@file_get_contents(__DIR__ . '/_form_index.json') ?: '[]', true) ?: [];
echo '<div class="card"><p class="tip">选择表单查看用户提交的数据。各页面表单也可在左侧对应页面分组中进入。</p>';
if (!$rows) {
    echo '<p style="color:#999">暂无表单组件</p>';
} else {
    echo '<table><tr><th>表单</th><th>所在页面</th><th>操作</th></tr>';
    foreach ($rows as $r) {
        $href = (string)($r['href'] ?? '');
        echo '<tr><td>' . htmlspecialchars((string)($r['form_name'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string)($r['page_name'] ?? '-')) . '</td>';
        echo '<td><a class="btn btn-sm" href="' . htmlspecialchars($href) . '">查看数据</a></td></tr>';
    }
    echo '</table>';
}
echo '</div>';
admin_layout_end();
