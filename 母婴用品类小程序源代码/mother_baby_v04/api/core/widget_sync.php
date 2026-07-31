<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/install_guard.php';
/** 覆盖部署后自动同步 widget_instances（由 Build 生成，勿手改） */
function widget_instances_seed_hash(): string {
    return '2ae3d3bdd43207e2dbd27ff98745798ba514d37aa15b2a382b72ee90eaa1d4db';
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
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_article_01','titleBar','article','资讯 - 标题栏','{"align":"left","subtitle":"母婴用品最新资讯","title":"资讯动态"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_contact_01','titleBar','contact','联系客服 - 标题栏','{"align":"center","subtitle":"7×12小时在线","title":"联系客服"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_home_04','serviceCard','home','首页 - 服务商卡片','{"items":[{"distance":"2.5km","images":["./assets/images/mother_baby_46.jpg","./assets/images/mother_baby_47.jpg","./assets/images/mother_baby_40.jpg"],"link":{"type":"none"},"logo":"./assets/images/mother_baby_1.jpg","name":"宝贝优选","rating":4.9,"ratingCount":128,"status":"营业中","tags":["口碑好店","金牌服务"]}],"title":"推荐服务"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_home_04','item','{"distance":"2.5km","images":["./assets/images/mother_baby_46.jpg","./assets/images/mother_baby_47.jpg","./assets/images/mother_baby_40.jpg"],"link":{"type":"none"},"logo":"./assets/images/mother_baby_1.jpg","name":"宝贝优选","rating":4.9,"ratingCount":128,"status":"营业中","tags":["口碑好店","金牌服务"]}',0
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_home_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_mine_01','pageHeader','mine','我的 - 页面头部','{"bgColor":"#1abc9c","brand":"宝贝优选","location":"个人中心","placeholder":"搜索订单","showMessage":true,"showScan":false}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_cat_03','gridNav','category','分类 - 宫格导航','{"columns":5,"items":[{"icon":"./assets/images/mother_baby_1.jpg","text":"奶粉"},{"icon":"./assets/images/mother_baby_2.jpg","text":"尿裤"},{"icon":"./assets/images/mother_baby_3.jpg","text":"辅食"},{"icon":"./assets/images/mother_baby_4.jpg","text":"玩具"},{"icon":"./assets/images/mother_baby_5.jpg","text":"童装"},{"icon":"./assets/images/mother_baby_6.jpg","text":"推车"},{"icon":"./assets/images/mother_baby_55.jpg","text":"洗护"},{"icon":"./assets/images/mother_baby_56.jpg","text":"孕妈"},{"icon":"./assets/images/mother_baby_57.jpg","text":"绘本"},{"icon":"./assets/images/mother_baby_58.jpg","text":"更多"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_1.jpg","text":"奶粉"}',0 UNION ALL
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_2.jpg","text":"尿裤"}',1 UNION ALL
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_3.jpg","text":"辅食"}',2 UNION ALL
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_4.jpg","text":"玩具"}',3 UNION ALL
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_5.jpg","text":"童装"}',4 UNION ALL
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_6.jpg","text":"推车"}',5 UNION ALL
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_55.jpg","text":"洗护"}',6 UNION ALL
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_56.jpg","text":"孕妈"}',7 UNION ALL
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_57.jpg","text":"绘本"}',8 UNION ALL
SELECT 'mother_baby_v04_cat_03','item','{"icon":"./assets/images/mother_baby_58.jpg","text":"更多"}',9
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_cat_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_home_03','featureCard','home','首页 - 特色大卡','{"items":[{"bannerText":"呵护宝宝","bgColor":"#fff3e0","image":"./assets/images/mother_baby_47.jpg","link":{"type":"none"},"subtitle":"母婴用品","title":"宝妈推荐","topics":["奶粉","尿裤","辅食"]},{"bannerText":"新用户专享优惠，欢迎选购母婴用品好物！","bgColor":"#e3f2fd","image":"./assets/images/mother_baby_40.jpg","link":{"type":"none"},"subtitle":"宝贝优选","title":"热销母婴","topics":["品质","服务","保障"]}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_home_03','item','{"bannerText":"呵护宝宝","bgColor":"#fff3e0","image":"./assets/images/mother_baby_47.jpg","link":{"type":"none"},"subtitle":"母婴用品","title":"宝妈推荐","topics":["奶粉","尿裤","辅食"]}',0 UNION ALL
SELECT 'mother_baby_v04_home_03','item','{"bannerText":"新用户专享优惠，欢迎选购母婴用品好物！","bgColor":"#e3f2fd","image":"./assets/images/mother_baby_40.jpg","link":{"type":"none"},"subtitle":"宝贝优选","title":"热销母婴","topics":["品质","服务","保障"]}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_home_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_home_06','listMenu','home','首页 - 菜单列表','{"items":[{"icon":"📋","link":{"type":"none"},"text":"服务介绍","value":""},{"icon":"⭐","link":{"type":"none"},"text":"客户案例","value":""},{"icon":"📞","link":{"type":"none"},"text":"联系咨询","value":""}],"showArrow":true}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_home_06','item','{"icon":"📋","link":{"type":"none"},"text":"服务介绍","value":""}',0 UNION ALL
SELECT 'mother_baby_v04_home_06','item','{"icon":"⭐","link":{"type":"none"},"text":"客户案例","value":""}',1 UNION ALL
SELECT 'mother_baby_v04_home_06','item','{"icon":"📞","link":{"type":"none"},"text":"联系咨询","value":""}',2
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_home_06' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_cat_02','searchBar','category','分类 - 搜索栏','{"bgColor":"#ffffff","placeholder":"搜索母婴用品"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_mine_02b','userVip','mine','我的 - VIP卡片','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_mine_02c','userBenefits','mine','我的 - 会员权益','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_mine_02d','userOrders','mine','我的 - 订单速查','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_coupon_01','titleBar','coupon','优惠券 - 标题栏','{"align":"center","subtitle":"领券中心","title":"优惠券"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_mine_04','gridNav','mine','我的 - 宫格导航2','{"columns":4,"items":[{"icon":"./assets/images/mother_baby_1.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"会员中心"},{"icon":"./assets/images/mother_baby_2.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"积分商城"},{"icon":"./assets/images/mother_baby_3.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"text":"邀请好友"},{"icon":"./assets/images/mother_baby_4.jpg","link":{"module":"system","systemRoute":"settings","type":"internal"},"text":"设置"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_mine_04','item','{"icon":"./assets/images/mother_baby_1.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"会员中心"}',0 UNION ALL
SELECT 'mother_baby_v04_mine_04','item','{"icon":"./assets/images/mother_baby_2.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"积分商城"}',1 UNION ALL
SELECT 'mother_baby_v04_mine_04','item','{"icon":"./assets/images/mother_baby_3.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"text":"邀请好友"}',2 UNION ALL
SELECT 'mother_baby_v04_mine_04','item','{"icon":"./assets/images/mother_baby_4.jpg","link":{"module":"system","systemRoute":"settings","type":"internal"},"text":"设置"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_mine_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_home_01','pageHeader','home','首页 - 页面头部','{"bgColor":"#1abc9c","brand":"宝贝优选","location":"当前定位","placeholder":"搜索母婴用品","showMessage":true,"showScan":false}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_cat_01','titleBar','category','分类 - 标题栏','{"align":"center","subtitle":"母婴用品","title":"商品分类"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_order_01','titleBar','order','订单 - 标题栏','{"align":"left","subtitle":"全部/待付款/待发货/已完成","title":"我的订单"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_home_05','gridNav','home','首页 - 宫格导航','{"columns":4,"items":[{"icon":"./assets/images/mother_baby_1.jpg","text":"极速达"},{"icon":"./assets/images/mother_baby_2.jpg","text":"品质保障"},{"icon":"./assets/images/mother_baby_3.jpg","text":"售后无忧"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_home_05','item','{"icon":"./assets/images/mother_baby_1.jpg","text":"极速达"}',0 UNION ALL
SELECT 'mother_baby_v04_home_05','item','{"icon":"./assets/images/mother_baby_2.jpg","text":"品质保障"}',1 UNION ALL
SELECT 'mother_baby_v04_home_05','item','{"icon":"./assets/images/mother_baby_3.jpg","text":"售后无忧"}',2
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_home_05' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_mine_03','gridNav','mine','我的 - 宫格导航','{"columns":4,"items":[{"icon":"./assets/images/mother_baby_1.jpg","link":{"module":"system","systemRoute":"order-list","type":"internal"},"text":"我的订单"},{"icon":"./assets/images/mother_baby_2.jpg","link":{"module":"system","systemRoute":"coupon-list","type":"internal"},"text":"优惠券"},{"icon":"./assets/images/mother_baby_3.jpg","link":{"module":"system","systemRoute":"address-list","type":"internal"},"text":"收货地址"},{"icon":"./assets/images/mother_baby_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"客服中心"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_mine_03','item','{"icon":"./assets/images/mother_baby_1.jpg","link":{"module":"system","systemRoute":"order-list","type":"internal"},"text":"我的订单"}',0 UNION ALL
SELECT 'mother_baby_v04_mine_03','item','{"icon":"./assets/images/mother_baby_2.jpg","link":{"module":"system","systemRoute":"coupon-list","type":"internal"},"text":"优惠券"}',1 UNION ALL
SELECT 'mother_baby_v04_mine_03','item','{"icon":"./assets/images/mother_baby_3.jpg","link":{"module":"system","systemRoute":"address-list","type":"internal"},"text":"收货地址"}',2 UNION ALL
SELECT 'mother_baby_v04_mine_03','item','{"icon":"./assets/images/mother_baby_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"客服中心"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_mine_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_mine_05','userCommunity','mine','我的 - 会员社区','{"link":{"module":"system","systemRoute":"product-list","type":"internal"}}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_cart_01','titleBar','cart','购物车 - 标题栏','{"align":"center","subtitle":"共0件商品","title":"购物车"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_mine_06','promoPair','mine','我的 - 双列促销','{"items":[{"bgColor":"#f3e8ff","image":"./assets/images/mother_baby_44.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"subtitle":"更多优惠","title":"会员专享"},{"bgColor":"#e8f8f0","image":"./assets/images/mother_baby_45.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"subtitle":"分享赚积分","title":"邀请有礼"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_mine_06','item','{"bgColor":"#f3e8ff","image":"./assets/images/mother_baby_44.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"subtitle":"更多优惠","title":"会员专享"}',0 UNION ALL
SELECT 'mother_baby_v04_mine_06','item','{"bgColor":"#e8f8f0","image":"./assets/images/mother_baby_45.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"subtitle":"分享赚积分","title":"邀请有礼"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_mine_06' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_about_01','titleBar','about','关于我们 - 标题栏','{"align":"center","subtitle":"宝贝优选","title":"关于我们"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_mine_02','user','mine','我的 - 用户中心','{"enableRegister":true,"enableWechatLogin":true}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_member_03','gridNav','member','会员中心 - 宫格导航','{"columns":3,"items":[{"icon":"./assets/images/mother_baby_1.jpg","text":"会员折扣"},{"icon":"./assets/images/mother_baby_2.jpg","text":"积分翻倍"},{"icon":"./assets/images/mother_baby_3.jpg","text":"专属客服"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_member_03','item','{"icon":"./assets/images/mother_baby_1.jpg","text":"会员折扣"}',0 UNION ALL
SELECT 'mother_baby_v04_member_03','item','{"icon":"./assets/images/mother_baby_2.jpg","text":"积分翻倍"}',1 UNION ALL
SELECT 'mother_baby_v04_member_03','item','{"icon":"./assets/images/mother_baby_3.jpg","text":"专属客服"}',2
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_member_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_about_02','image','about','关于我们 - 图片','{"link":{"linkType":"none"},"src":"./assets/images/mother_baby_51.jpg"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_coupon_03','promoPair','coupon','优惠券 - 双列促销','{"items":[{"bgColor":"#ffe0e0","image":"./assets/images/mother_baby_40.jpg","link":"","subtitle":"全场通用","title":"满100减20"},{"bgColor":"#e8f8f0","image":"./assets/images/mother_baby_41.jpg","link":"","subtitle":"首单专享","title":"新人立减"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_coupon_03','item','{"bgColor":"#ffe0e0","image":"./assets/images/mother_baby_40.jpg","link":"","subtitle":"全场通用","title":"满100减20"}',0 UNION ALL
SELECT 'mother_baby_v04_coupon_03','item','{"bgColor":"#e8f8f0","image":"./assets/images/mother_baby_41.jpg","link":"","subtitle":"首单专享","title":"新人立减"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_coupon_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_coupon_04','promoPair','coupon','优惠券 - 双列促销2','{"items":[{"bgColor":"#f3e8ff","image":"./assets/images/mother_baby_42.jpg","link":"","subtitle":"会员专属","title":"会员券"},{"bgColor":"#fff8e1","image":"./assets/images/mother_baby_43.jpg","link":"","subtitle":"指定分类","title":"品类券"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'mother_baby_v04_coupon_04','item','{"bgColor":"#f3e8ff","image":"./assets/images/mother_baby_42.jpg","link":"","subtitle":"会员专属","title":"会员券"}',0 UNION ALL
SELECT 'mother_baby_v04_coupon_04','item','{"bgColor":"#fff8e1","image":"./assets/images/mother_baby_43.jpg","link":"","subtitle":"指定分类","title":"品类券"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='mother_baby_v04_coupon_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('mother_baby_v04_member_01','titleBar','member','会员中心 - 标题栏','{"align":"center","subtitle":"尊享会员权益","title":"会员中心"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);

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
