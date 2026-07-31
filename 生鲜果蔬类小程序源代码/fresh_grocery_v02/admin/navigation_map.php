<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
$pdo = db();
$projectPages = json_decode(@file_get_contents(__DIR__ . '/_pages.json') ?: '[]', true) ?: [];
$navMapData = json_decode(@file_get_contents(__DIR__ . '/assets/guides/_nav_map_rects.json') ?: '', true);
$typeNames = [
    'image'=>'图片','swiper'=>'轮播图','noticeBar'=>'公告栏','gridNav'=>'宫格导航',
    'richText'=>'富文本','searchBar'=>'搜索栏','pageHeader'=>'页面头部',
    'promoPair'=>'双列促销','productScroll'=>'商品横滑','promoBanner'=>'主题区块',
    'titleBar'=>'标题栏','form'=>'表单','user'=>'用户中心','userVip'=>'VIP卡片',
    'userBenefits'=>'会员权益','userOrders'=>'订单快查','userCommunity'=>'会员社区',
    'product'=>'商品列表','article'=>'文章列表','button'=>'按钮','container'=>'自由容器',
    'tabNav'=>'顶部Tab','filterBar'=>'筛选栏','promoGrid'=>'营销宫格',
    'video'=>'视频','serviceCard'=>'服务商卡片','listMenu'=>'菜单列表',
    'statsRow'=>'数据统计','walletCard'=>'钱包充值','floatingButton'=>'悬浮按钮',
    'waterfall'=>'瀑布流','featureCard'=>'特色大卡','loginBanner'=>'登录引导',
    'rate'=>'评分','serviceFloat'=>'客服浮窗','locationPicker'=>'城市选择',
    'groupBuy'=>'拼团入口','flashSale'=>'限时特惠','liveEntry'=>'直播入口',
    'checkIn'=>'签到入口','messageBoard'=>'留言板','quiz'=>'在线答题',
    'checkinActivity'=>'打卡活动','map'=>'地图',
];
function navMapAdminUrl($type, $id) {
    $special = [
        'swiper'=>'swiper.php','noticeBar'=>'notice.php','richText'=>'richtext.php',
        'product'=>'product_widget.php','article'=>'article_widget.php',
        'productScroll'=>'product_scroll_widget.php','promoBanner'=>'promo_banner_widget.php',
        'messageBoard'=>'message_inbox.php','quiz'=>'quiz_admin.php',
        'checkinActivity'=>'checkin_admin.php','form'=>'form_data.php',
    ];
    if ($type === 'form') return ($special[$type] ?? 'widget.php') . '?form_id=' . urlencode($id);
    if (isset($special[$type])) return $special[$type] . '?id=' . urlencode($id);
    return 'widget.php?id=' . urlencode($id);
}
function navMapAdminGroup($type) {
    switch ($type) {
        case 'messageBoard': case 'quiz': case 'checkinActivity': return '互动';
        case 'map': case 'locationPicker': return '位置';
        case 'groupBuy': case 'flashSale': case 'liveEntry': case 'checkIn': case 'promoGrid': return '营销';
        case 'noticeBar': case 'swiper': case 'richText': case 'product': case 'article':
        case 'productScroll': case 'promoBanner': return '内容管理';
        default: return '组件';
    }
}
function navMapMenuItemLabel($type, $pageComponentLabel) {
    switch ($type) {
        case 'noticeBar': case 'swiper': case 'richText': return $pageComponentLabel;
        case 'product': case 'article': case 'productScroll': case 'promoBanner': return $pageComponentLabel . '配置';
        default:
            $suffix = '配置';
            if ($type === 'messageBoard') $suffix = '留言';
            elseif ($type === 'quiz') $suffix = '题库';
            elseif ($type === 'checkinActivity') $suffix = '打卡';
            elseif ($type === 'map') $suffix = '地图';
            return $pageComponentLabel . ' · ' . $suffix;
    }
}
function navMapSidebarLabel($type, $instanceLabel, $pageName = '') {
    if ($instanceLabel !== '') {
        return navMapAdminGroup($type) . ' -> ' . navMapMenuItemLabel($type, $instanceLabel);
    }
    $kind = $typeNames[$type] ?? $type;
    if ($pageName !== '') {
        $kind = $pageName . ' - ' . $kind;
    }
    return navMapAdminGroup($type) . ' -> ' . navMapMenuItemLabel($type, $kind);
}
$sysPages = admin_nav_map_sys_pages();
$globals = admin_nav_map_globals();
admin_layout_start('导航地图', 'navigation_map.php');
echo '<style>
.nav-map-section{border:1px solid #e8e8e8;border-radius:8px;padding:16px;margin-bottom:24px;background:#fff;overflow:hidden}
.nav-map-section-title{font-size:16px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.nav-map-badge{background:#2ecc71;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px}
.nav-map-row{display:flex;gap:0}
.nav-map-shot-wrap{position:relative;width:280px;flex-shrink:0}
.nav-map-shot-wrap img{width:280px;display:block;border-radius:6px;border:1px solid #eee}
.nav-map-shot-wrap svg{position:absolute;top:0;left:0;width:280px;height:100%;pointer-events:none}
.nav-map-right{position:relative;flex:1;min-width:200px;padding-left:20px}
.nav-map-link{position:absolute;display:flex;align-items:center;gap:6px;padding:4px 10px;border-radius:4px;font-size:13px;background:#fff;border:1px solid #eee;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,.06);cursor:pointer;transition:border-color .15s,background .15s;text-decoration:none;color:#333}
.nav-map-link:hover{background:#edfbf3;border-color:#2ecc71}
.nav-map-link-dot{width:8px;height:8px;border-radius:50%;background:#E74C3C;flex-shrink:0}
.nav-map-link-name{font-weight:500}
.nav-map-link-sep{color:#2ecc71;margin:0 2px}
.nav-map-link-label{color:#2ecc71;font-size:12px}
.nav-map-sys-section{border:1px solid #e8e8e8;border-radius:8px;padding:16px;margin-bottom:20px;background:#fff}
.nav-map-sys-title{font-size:16px;font-weight:600;margin-bottom:12px}
.nav-map-sys-table{width:100%;border-collapse:collapse;font-size:14px}
.nav-map-sys-th{padding:8px;text-align:left;background:#f5f5f5}
.nav-map-sys-td{padding:8px;border-bottom:1px solid #f0f0f0}
.nav-map-sys-a{color:#2ecc71;font-size:13px;text-decoration:none}
.nav-map-sys-a:hover{text-decoration:underline}
.nav-map-no-admin{color:#999;font-size:12px}
.nav-map-globals{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.nav-map-fallback-card{border:1px solid #e8e8e8;border-radius:8px;padding:16px;margin-bottom:20px;background:#fff}
.nav-map-fallback-title{font-size:16px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:10px}
.nav-map-fallback-thumb-placeholder{width:100px;height:170px;border-radius:6px;border:1px solid #eee;background:#f0f4f8;display:flex;align-items:center;justify-content:center;font-size:36px;color:#bbb;flex-shrink:0}
.nav-map-fallback-row{display:flex;gap:16px}
.nav-map-fallback-body{flex:1;min-width:0}
</style>';
$hasVisual = $navMapData && !empty($navMapData['pages']);
if ($hasVisual) {
    foreach ($navMapData['pages'] as $pd) {
        $pk = $pd['page_key'] ?? '';
        $pn = $pd['page_name'] ?? $pk;
        $shotFile = $pd['screenshot'] ?? '';
        $shotW = (int)($pd['shot_width'] ?? 430);
        $shotH = (int)($pd['shot_height'] ?? 800);
        $comps = $pd['components'] ?? [];
        if (!$shotFile) continue;
        $imgPath = asset_url('assets/guides/' . $shotFile);
        $scale = 280 / max($shotW, 1);
        $displayH = round($shotH * $scale);
        $rightH = max($displayH, 200);
        echo '<div class="nav-map-section">';
        echo '<div class="nav-map-section-title"><span class="nav-map-badge">' . htmlspecialchars($pk) . '</span> ' . htmlspecialchars($pn) . '</div>';
        echo '<div class="nav-map-row">';
        echo '<div class="nav-map-shot-wrap">';
        echo '<img src="' . htmlspecialchars($imgPath) . '" alt="' . htmlspecialchars($pn) . '" style="width:280px;height:' . $displayH . 'px;object-fit:contain;object-position:top center;display:block;border-radius:6px;border:1px solid #eee">';
        if (!empty($comps)) {
            echo '<svg viewBox="0 0 ' . $shotW . ' ' . $shotH . '" preserveAspectRatio="xMidYMin meet" style="position:absolute;top:0;left:0;width:280px;height:' . $displayH . 'px">';
            foreach ($comps as $c) {
                echo '<rect x="' . $c['x'] . '" y="' . $c['y'] . '" width="' . $c['w'] . '" height="' . $c['h'] . '" stroke="#E74C3C" stroke-width="2" fill="rgba(231,76,60,0.06)" rx="3"/>';
                $cy = $c['y'] + $c['h']/2;
                echo '<line x1="' . ($c['x'] + $c['w']) . '" y1="' . $cy . '" x2="' . $shotW . '" y2="' . $cy . '" stroke="#E74C3C" stroke-width="1.5" stroke-dasharray="6,4" opacity="0.7"/>';
            }
            echo '</svg>';
        }
        echo '</div>';
        echo '<div class="nav-map-right" style="height:' . $rightH . 'px">';
        if (!empty($comps)) {
            foreach ($comps as $c) {
                $compName = $c['name'] ?? ($typeNames[$c['type'] ?? ''] ?? $c['type'] ?? '');
                $adminLabel = $c['admin_label'] ?? ($compName . '配置');
                $adminHref = $c['admin_href'] ?? navMapAdminUrl($c['type'] ?? '', $c['instance_id'] ?? '');
                $linkTop = round(($c['y'] + $c['h']/2) * $scale - 14);
                echo '<a class="nav-map-link" href="' . htmlspecialchars($adminHref) . '" style="top:' . $linkTop . 'px">';
                echo '<span class="nav-map-link-dot"></span>';
                echo '<span class="nav-map-link-name">' . htmlspecialchars($compName) . '</span>';
                echo '<span class="nav-map-link-sep">→</span>';
                echo '<span class="nav-map-link-label">' . htmlspecialchars($adminLabel) . '</span>';
                echo '</a>';
            }
        }
        echo '</div>';
        echo '</div>';
        // ===== 浮窗组件模拟区 =====
        $floatComps = $pd['float_components'] ?? [];
        if (!empty($floatComps)) {
            echo '<div style="position:relative;width:280px;height:52px;margin-top:8px;background:#f5f5f5;border-radius:8px;border:1px solid #eee;display:flex;align-items:center;justify-content:center;gap:8px">';
            foreach ($floatComps as $fc) {
                $fcName = $fc['name'] ?? ($typeNames[$fc['type'] ?? ''] ?? $fc['type'] ?? '');
                $fcBg = $fc['float_bg'] ?? (($fc['type'] ?? '') === 'serviceFloat' ? '#2ecc71' : '#e74c3c');
                $fcText = $fc['float_text'] ?? (($fc['type'] ?? '') === 'serviceFloat' ? '客服' : '发布');
                $fcHref = $fc['admin_href'] ?? navMapAdminUrl($fc['type'] ?? '', $fc['instance_id'] ?? '');
                $fcLabel = $fc['admin_label'] ?? ($fcName . '配置');
                echo '<a href="' . htmlspecialchars($fcHref) . '" style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;background:' . htmlspecialchars($fcBg) . ';color:#fff;font-size:11px;text-decoration:none;box-shadow:0 2px 6px rgba(0,0,0,.15);cursor:pointer" title="' . htmlspecialchars($fcName) . ' → ' . htmlspecialchars($fcLabel) . '">' . htmlspecialchars($fcText) . '</a>';
            }
            echo '<div style="font-size:11px;color:#888;margin-left:4px">';
            foreach ($floatComps as $fc) {
                $fcName = $fc['name'] ?? ($typeNames[$fc['type'] ?? ''] ?? $fc['type'] ?? '');
                $fcHref = $fc['admin_href'] ?? navMapAdminUrl($fc['type'] ?? '', $fc['instance_id'] ?? '');
                $fcLabel = $fc['admin_label'] ?? ($fcName . '配置');
                echo '<div><a style="color:#2ecc71;font-size:12px" href="' . htmlspecialchars($fcHref) . '">' . htmlspecialchars($fcName) . ' → ' . htmlspecialchars($fcLabel) . '</a></div>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
} else {
    $widgetRows = $pdo->query('SELECT instance_id,component_type,page_key,label FROM widget_instances WHERE status=1 ORDER BY page_key,component_type')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $swiperRows = $pdo->query('SELECT instance_id,page_key FROM swiper_instances WHERE status=1')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $noticeRows = $pdo->query('SELECT instance_id,page_key FROM notice_instances WHERE status=1')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $richtextRows = ($pdo->query("SHOW TABLES LIKE 'richtext_instances'")->fetch()) ? $pdo->query('SELECT instance_id,page_key FROM richtext_instances WHERE status=1')->fetchAll(PDO::FETCH_ASSOC) ?: [] : [];
    $productWidgetRows = ($pdo->query("SHOW TABLES LIKE 'product_widgets'")->fetch()) ? $pdo->query('SELECT instance_id,page_key FROM product_widgets WHERE status=1')->fetchAll(PDO::FETCH_ASSOC) ?: [] : [];
    $scrollWidgetRows = ($pdo->query("SHOW TABLES LIKE 'product_scroll_widgets'")->fetch()) ? $pdo->query('SELECT instance_id,page_key FROM product_scroll_widgets WHERE status=1')->fetchAll(PDO::FETCH_ASSOC) ?: [] : [];
    $promoBannerRows = ($pdo->query("SHOW TABLES LIKE 'promo_banner_widgets'")->fetch()) ? $pdo->query('SELECT instance_id,page_key FROM promo_banner_widgets WHERE status=1')->fetchAll(PDO::FETCH_ASSOC) ?: [] : [];
    $allComps = [];
    foreach ($swiperRows as $r) $allComps[] = ['id'=>$r['instance_id'],'type'=>'swiper','page_key'=>$r['page_key']];
    foreach ($noticeRows as $r) $allComps[] = ['id'=>$r['instance_id'],'type'=>'noticeBar','page_key'=>$r['page_key']];
    foreach ($richtextRows as $r) $allComps[] = ['id'=>$r['instance_id'],'type'=>'richText','page_key'=>$r['page_key']];
    foreach ($productWidgetRows as $r) $allComps[] = ['id'=>$r['instance_id'],'type'=>'product','page_key'=>$r['page_key']];
    foreach ($scrollWidgetRows as $r) $allComps[] = ['id'=>$r['instance_id'],'type'=>'productScroll','page_key'=>$r['page_key']];
    foreach ($promoBannerRows as $r) $allComps[] = ['id'=>$r['instance_id'],'type'=>'promoBanner','page_key'=>$r['page_key']];
    foreach ($widgetRows as $r) $allComps[] = ['id'=>$r['instance_id'],'type'=>$r['component_type'],'page_key'=>$r['page_key'],'label'=>$r['label']];
    $byPage = [];
    foreach ($allComps as $c) { $pk2 = $c['page_key'] ?: 'unknown'; if (!isset($byPage[$pk2])) $byPage[$pk2] = []; $byPage[$pk2][] = $c; }
    $pageNameMap = [];
    foreach ($projectPages as $pg) $pageNameMap[$pg['page_key'] ?? ''] = $pg['page_name'] ?? '';
    foreach ($projectPages as $pg) {
        $pk = $pg['page_key'] ?? '';
        $pn = $pg['page_name'] ?? $pk;
        $comps = $byPage[$pk] ?? [];
        echo '<div class="nav-map-fallback-card">';
        echo '<div class="nav-map-fallback-title"><span class="nav-map-badge">' . htmlspecialchars($pk) . '</span> ' . htmlspecialchars($pn) . '</div>';
        echo '<div class="nav-map-fallback-row">';
        echo '<div class="nav-map-fallback-thumb-placeholder">📱</div>';
        echo '<div class="nav-map-fallback-body">';
        if (empty($comps)) { echo '<p style="color:#999;font-size:13px">该页面暂无组件实例</p>'; }
        else {
            foreach ($comps as $c) {
                $typeName = $typeNames[$c['type']] ?? $c['type'];
                $adminUrl = navMapAdminUrl($c['type'], $c['id']);
                $pn2 = $pageNameMap[$c['page_key'] ?? ''] ?? '';
                $sidebarLabel = navMapSidebarLabel($c['type'], $c['label'] ?? '', $pn2);
                echo '<div style="display:flex;align-items:center;gap:8px;padding:5px 0;font-size:14px;border-bottom:1px solid #f0f0f0">';
                echo '<span style="width:8px;height:8px;border-radius:50%;background:#E74C3C"></span>';
                echo '<span style="font-weight:500">' . htmlspecialchars($typeName) . '</span>';
                echo '<span style="color:#2ecc71">→</span>';
                echo '<a style="color:#2ecc71;font-size:13px" href="' . htmlspecialchars($adminUrl) . '">' . htmlspecialchars($sidebarLabel) . '</a>';
                echo '</div>';
            }
        }
        echo '</div></div></div>';
    }
}
echo '<div class="nav-map-sys-section">';
echo '<div class="nav-map-sys-title">系统子页面</div>';
echo '<table class="nav-map-sys-table">';
echo '<tr><th class="nav-map-sys-th">页面</th><th class="nav-map-sys-th">后台管理</th><th class="nav-map-sys-th">说明</th></tr>';
foreach ($sysPages as $sp) {
    echo '<tr>';
    echo '<td class="nav-map-sys-td"><span class="nav-map-badge">' . htmlspecialchars($sp['key']) . '</span> ' . htmlspecialchars($sp['name']) . '</td>';
    if ($sp['admin']) { echo '<td class="nav-map-sys-td"><a class="nav-map-sys-a" href="' . htmlspecialchars($sp['admin']) . '">→ ' . htmlspecialchars(basename($sp['admin'], '.php')) . '</a></td>'; }
    else { echo '<td class="nav-map-sys-td"><span class="nav-map-no-admin">' . htmlspecialchars($sp['desc']) . '</span></td>'; }
    echo '<td class="nav-map-sys-td" style="color:#999;font-size:13px">' . ($sp['admin'] ? htmlspecialchars($sp['desc']) : '') . '</td>';
    echo '</tr>';
}
echo '</table></div>';
echo '<div class="nav-map-sys-section">';
echo '<div class="nav-map-sys-title">全局设置</div>';
echo '<div class="nav-map-globals">';
foreach ($globals as $g) {
    echo '<a class="nav-map-link" style="position:relative" href="' . htmlspecialchars($g['admin']) . '">';
    echo '<span class="nav-map-link-dot" style="background:#2ecc71"></span>';
    echo '<span class="nav-map-link-name">' . htmlspecialchars($g['name']) . '</span>';
    echo '<span class="nav-map-link-sep">→</span>';
    echo '<span class="nav-map-link-label">' . htmlspecialchars($g['desc']) . '</span>';
    echo '</a>';
}
echo '</div></div>';
admin_layout_end();
