<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/install_guard.php';
/** 覆盖部署后自动同步 widget_instances（由 Build 生成，勿手改） */
function widget_instances_seed_hash(): string {
    return '1ddbdf987fc23f730f29d99db10b8a92f99ca063750ccc18b86c7ab02658d25a';
}

function widget_instances_maybe_sync(): void {
    if (!function_exists('app_is_installed') || !app_is_installed()) {
        return;
    }
    $root = app_root_dir();
    $marker = $root . '/config/.widget_sync_hash';
    $hash = widget_instances_seed_hash();
    if ($hash === '' || (is_file($marker) && trim((string)@file_get_contents($marker)) === $hash)) {
        return;
    }
    try {
        $pdo = db();
        $sql = <<<'WIDGETSEED'
DELETE FROM widget_instances WHERE page_key='order-list';
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_mine_03','gridNav','mine','我的 - 宫格导航','{"columns":4,"items":[{"icon":"./assets/images/travel_1.jpg","link":{"module":"system","systemRoute":"order-list","type":"internal"},"text":"我的订单"},{"icon":"./assets/images/travel_2.jpg","link":{"module":"system","systemRoute":"coupon-list","type":"internal"},"text":"优惠券"},{"icon":"./assets/images/travel_3.jpg","link":{"module":"system","systemRoute":"address-list","type":"internal"},"text":"收货地址"},{"icon":"./assets/images/travel_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"客服中心"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'travel_v06_mine_03','item','{"icon":"./assets/images/travel_1.jpg","link":{"module":"system","systemRoute":"order-list","type":"internal"},"text":"我的订单"}',0 UNION ALL
SELECT 'travel_v06_mine_03','item','{"icon":"./assets/images/travel_2.jpg","link":{"module":"system","systemRoute":"coupon-list","type":"internal"},"text":"优惠券"}',1 UNION ALL
SELECT 'travel_v06_mine_03','item','{"icon":"./assets/images/travel_3.jpg","link":{"module":"system","systemRoute":"address-list","type":"internal"},"text":"收货地址"}',2 UNION ALL
SELECT 'travel_v06_mine_03','item','{"icon":"./assets/images/travel_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"客服中心"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='travel_v06_mine_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_mine_02','user','mine','我的 - 用户中心','{"enableRegister":true,"enableWechatLogin":true}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_mine_04','gridNav','mine','我的 - 宫格导航2','{"columns":4,"items":[{"icon":"./assets/images/travel_1.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"会员中心"},{"icon":"./assets/images/travel_2.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"积分商城"},{"icon":"./assets/images/travel_3.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"text":"邀请好友"},{"icon":"./assets/images/travel_4.jpg","link":{"module":"system","systemRoute":"settings","type":"internal"},"text":"设置"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'travel_v06_mine_04','item','{"icon":"./assets/images/travel_1.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"会员中心"}',0 UNION ALL
SELECT 'travel_v06_mine_04','item','{"icon":"./assets/images/travel_2.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"积分商城"}',1 UNION ALL
SELECT 'travel_v06_mine_04','item','{"icon":"./assets/images/travel_3.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"text":"邀请好友"}',2 UNION ALL
SELECT 'travel_v06_mine_04','item','{"icon":"./assets/images/travel_4.jpg","link":{"module":"system","systemRoute":"settings","type":"internal"},"text":"设置"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='travel_v06_mine_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_coupon_03','promoPair','coupon','优惠券 - 双列促销','{"items":[{"bgColor":"#ffe0e0","image":"./assets/images/travel_40.jpg","link":"","subtitle":"全场通用","title":"满100减20"},{"bgColor":"#e8f8f0","image":"./assets/images/travel_41.jpg","link":"","subtitle":"首单专享","title":"新人立减"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'travel_v06_coupon_03','item','{"bgColor":"#ffe0e0","image":"./assets/images/travel_40.jpg","link":"","subtitle":"全场通用","title":"满100减20"}',0 UNION ALL
SELECT 'travel_v06_coupon_03','item','{"bgColor":"#e8f8f0","image":"./assets/images/travel_41.jpg","link":"","subtitle":"首单专享","title":"新人立减"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='travel_v06_coupon_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_cat_02','searchBar','category','分类 - 搜索栏','{"bgColor":"#ffffff","placeholder":"搜索旅游线路"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_article_01','titleBar','article','资讯 - 标题栏','{"align":"left","subtitle":"旅游出行最新资讯","title":"资讯动态"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_order_01','titleBar','order','订单 - 标题栏','{"align":"left","subtitle":"全部/待付款/待发货/已完成","title":"我的订单"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_home_03','waterfall','home','首页 - 瀑布流','{"columns":2,"items":[{"height":160,"image":"./assets/images/travel_45.jpg","isVideo":false,"link":{"type":"none"},"title":"国内"},{"height":200,"image":"./assets/images/travel_46.jpg","isVideo":false,"link":{"type":"none"},"title":"出境"},{"height":240,"image":"./assets/images/travel_47.jpg","isVideo":false,"link":{"type":"none"},"title":"周边"},{"height":160,"image":"./assets/images/travel_40.jpg","isVideo":false,"link":{"type":"none"},"title":"自驾"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'travel_v06_home_03','item','{"height":160,"image":"./assets/images/travel_45.jpg","isVideo":false,"link":{"type":"none"},"title":"国内"}',0 UNION ALL
SELECT 'travel_v06_home_03','item','{"height":200,"image":"./assets/images/travel_46.jpg","isVideo":false,"link":{"type":"none"},"title":"出境"}',1 UNION ALL
SELECT 'travel_v06_home_03','item','{"height":240,"image":"./assets/images/travel_47.jpg","isVideo":false,"link":{"type":"none"},"title":"周边"}',2 UNION ALL
SELECT 'travel_v06_home_03','item','{"height":160,"image":"./assets/images/travel_40.jpg","isVideo":false,"link":{"type":"none"},"title":"自驾"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='travel_v06_home_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_cat_01','titleBar','category','分类 - 标题栏','{"align":"center","subtitle":"旅游出行","title":"商品分类"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_coupon_04','promoPair','coupon','优惠券 - 双列促销2','{"items":[{"bgColor":"#f3e8ff","image":"./assets/images/travel_42.jpg","link":"","subtitle":"会员专属","title":"会员券"},{"bgColor":"#fff8e1","image":"./assets/images/travel_43.jpg","link":"","subtitle":"指定分类","title":"品类券"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'travel_v06_coupon_04','item','{"bgColor":"#f3e8ff","image":"./assets/images/travel_42.jpg","link":"","subtitle":"会员专属","title":"会员券"}',0 UNION ALL
SELECT 'travel_v06_coupon_04','item','{"bgColor":"#fff8e1","image":"./assets/images/travel_43.jpg","link":"","subtitle":"指定分类","title":"品类券"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='travel_v06_coupon_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_member_03','gridNav','member','会员中心 - 宫格导航','{"columns":3,"items":[{"icon":"./assets/images/travel_1.jpg","text":"会员折扣"},{"icon":"./assets/images/travel_2.jpg","text":"积分翻倍"},{"icon":"./assets/images/travel_3.jpg","text":"专属客服"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'travel_v06_member_03','item','{"icon":"./assets/images/travel_1.jpg","text":"会员折扣"}',0 UNION ALL
SELECT 'travel_v06_member_03','item','{"icon":"./assets/images/travel_2.jpg","text":"积分翻倍"}',1 UNION ALL
SELECT 'travel_v06_member_03','item','{"icon":"./assets/images/travel_3.jpg","text":"专属客服"}',2
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='travel_v06_member_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_about_02','image','about','关于我们 - 图片','{"link":{"linkType":"none"},"src":"./assets/images/travel_51.jpg"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_mine_01','pageHeader','mine','我的 - 页面头部','{"bgColor":"#9b59b6","brand":"畅游天下","location":"个人中心","placeholder":"搜索订单","showMessage":true,"showScan":false}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_mine_06','promoPair','mine','我的 - 双列促销','{"items":[{"bgColor":"#f3e8ff","image":"./assets/images/travel_44.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"subtitle":"更多优惠","title":"会员专享"},{"bgColor":"#e8f8f0","image":"./assets/images/travel_45.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"subtitle":"分享赚积分","title":"邀请有礼"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'travel_v06_mine_06','item','{"bgColor":"#f3e8ff","image":"./assets/images/travel_44.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"subtitle":"更多优惠","title":"会员专享"}',0 UNION ALL
SELECT 'travel_v06_mine_06','item','{"bgColor":"#e8f8f0","image":"./assets/images/travel_45.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"subtitle":"分享赚积分","title":"邀请有礼"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='travel_v06_mine_06' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_member_01','titleBar','member','会员中心 - 标题栏','{"align":"center","subtitle":"尊享会员权益","title":"会员中心"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_about_01','titleBar','about','关于我们 - 标题栏','{"align":"center","subtitle":"畅游天下","title":"关于我们"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_mine_05','userCommunity','mine','我的 - 会员社区','{"link":{"module":"system","systemRoute":"product-list","type":"internal"}}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_contact_01','titleBar','contact','联系客服 - 标题栏','{"align":"center","subtitle":"7×12小时在线","title":"联系客服"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_cart_01','titleBar','cart','购物车 - 标题栏','{"align":"center","subtitle":"共0件商品","title":"购物车"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_coupon_01','titleBar','coupon','优惠券 - 标题栏','{"align":"center","subtitle":"领券中心","title":"优惠券"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_mine_02b','userVip','mine','我的 - VIP卡片','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_mine_02c','userBenefits','mine','我的 - 会员权益','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_mine_02d','userOrders','mine','我的 - 订单速查','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_home_01','loginBanner','home','首页 - 登录引导','{"bgColor":"rgba(0,0,0,.7)","buttonText":"立即登录","text":"登录解锁更多优惠"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('travel_v06_cat_03','gridNav','category','分类 - 宫格导航','{"columns":5,"items":[{"icon":"./assets/images/travel_1.jpg","text":"国内"},{"icon":"./assets/images/travel_2.jpg","text":"出境"},{"icon":"./assets/images/travel_3.jpg","text":"周边"},{"icon":"./assets/images/travel_4.jpg","text":"自驾"},{"icon":"./assets/images/travel_5.jpg","text":"亲子"},{"icon":"./assets/images/travel_6.jpg","text":"蜜月"},{"icon":"./assets/images/travel_55.jpg","text":"团建"},{"icon":"./assets/images/travel_56.jpg","text":"签证"},{"icon":"./assets/images/travel_57.jpg","text":"门票"},{"icon":"./assets/images/travel_58.jpg","text":"更多"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_1.jpg","text":"国内"}',0 UNION ALL
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_2.jpg","text":"出境"}',1 UNION ALL
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_3.jpg","text":"周边"}',2 UNION ALL
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_4.jpg","text":"自驾"}',3 UNION ALL
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_5.jpg","text":"亲子"}',4 UNION ALL
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_6.jpg","text":"蜜月"}',5 UNION ALL
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_55.jpg","text":"团建"}',6 UNION ALL
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_56.jpg","text":"签证"}',7 UNION ALL
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_57.jpg","text":"门票"}',8 UNION ALL
SELECT 'travel_v06_cat_03','item','{"icon":"./assets/images/travel_58.jpg","text":"更多"}',9
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='travel_v06_cat_03' LIMIT 1);

WIDGETSEED;
        foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || stripos($stmt, 'CREATE TABLE') === 0) {
                continue;
            }
            $pdo->exec($stmt);
        }
        @file_put_contents($marker, $hash);
    } catch (Throwable $e) {
        // 同步失败不阻断 API
    }
}
