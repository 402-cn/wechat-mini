<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
admin_layout_start('工作台', 'dashboard.php');
echo '<div class="card"><p>欢迎使用管理后台。请从左侧菜单进入对应模块管理内容。</p>';
echo '<p style="color:#666;font-size:14px">内容修改后 H5/小程序刷新即可生效，无需重新生成 ZIP。</p></div>';
admin_layout_end();
