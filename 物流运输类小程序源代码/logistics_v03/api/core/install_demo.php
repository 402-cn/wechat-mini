<?php
/**
 * 筑码引擎 www.402.cn
 */

function install_patch_demo_admin_guides(PDO $pdo, string $siteBase, string $adminUser): void {
    $base = rtrim($siteBase, '/');
    $user = $adminUser !== '' ? $adminUser : 'admin';
    $productFooter = '<hr style="margin:24px 0;border:none;border-top:1px solid #eee"/><p><strong>如何替换为真实内容？</strong></p><p>请在后台「内容管理 → 商品管理」中修改名称、价格、图片、库存与秒杀设置。</p><p>请使用超级管理员账号 <code>{{USER}}</code> 登录管理后台（密码为您安装站点时设置的后台密码）。</p><p><a href="{{BASE}}/admin/products.php" target="_blank" rel="noopener">打开商品管理</a> · <a href="{{BASE}}/admin/" target="_blank" rel="noopener">进入管理后台</a></p>';
    $articleFooter = '<hr style="margin:24px 0;border:none;border-top:1px solid #eee"/><p><strong>如何替换为真实内容？</strong></p><p>请在后台「内容管理 → 文章管理」中修改标题、封面与正文。</p><p>请使用超级管理员账号 <code>{{USER}}</code> 登录管理后台（密码为您安装站点时设置的后台密码）。</p><p><a href="{{BASE}}/admin/articles.php" target="_blank" rel="noopener">打开文章管理</a> · <a href="{{BASE}}/admin/" target="_blank" rel="noopener">进入管理后台</a></p>';
    $productFooter = str_replace(['{{BASE}}','{{USER}}'], [$base, $user], $productFooter);
    $articleFooter = str_replace(['{{BASE}}','{{USER}}'], [$base, $user], $articleFooter);
    $ph = '<!--ADMIN_GUIDE_FOOTER-->';
    if ($pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
        $pdo->exec("UPDATE products SET description=REPLACE(description, '$ph', " . $pdo->quote($productFooter) . ") WHERE description LIKE '%$ph%'");
    }
    if ($pdo->query("SHOW TABLES LIKE 'articles'")->fetch()) {
        $pdo->exec("UPDATE articles SET content=REPLACE(content, '$ph', " . $pdo->quote($articleFooter) . ") WHERE content LIKE '%$ph%'");
    }
}
