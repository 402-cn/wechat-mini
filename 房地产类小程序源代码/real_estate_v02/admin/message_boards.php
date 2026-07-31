<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
admin_layout_start('留言板', 'message_boards.php');
$rows = json_decode(@file_get_contents(__DIR__ . '/_message_boards.json') ?: '[]', true) ?: [];
echo '<div class="card"><p class="tip">管理各页面留言板组件收到的留言。</p>';
if (!$rows) {
    echo '<p style="color:#999">暂无留言板组件</p>';
} else {
    echo '<table><tr><th>页面</th><th>组件</th><th>操作</th></tr>';
    foreach ($rows as $r) {
        echo '<tr><td>' . htmlspecialchars((string)($r['page_name'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string)($r['label'] ?? '')) . '</td>';
        echo '<td><a class="btn btn-sm" href="' . htmlspecialchars((string)($r['href'] ?? '')) . '">管理留言</a></td></tr>';
    }
    echo '</table>';
}
echo '</div>';
admin_layout_end();
