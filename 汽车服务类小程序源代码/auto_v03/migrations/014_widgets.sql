
CREATE TABLE IF NOT EXISTS widget_instances (
  instance_id VARCHAR(64) NOT NULL,
  component_type VARCHAR(32) NOT NULL DEFAULT '',
  page_key VARCHAR(50) NOT NULL DEFAULT '',
  label VARCHAR(100) NOT NULL DEFAULT '',
  props_json JSON NOT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (instance_id),
  KEY idx_widget_type (component_type),
  KEY idx_widget_page (page_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS widget_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  instance_id VARCHAR(64) NOT NULL,
  item_key VARCHAR(32) NOT NULL DEFAULT 'item',
  item_json JSON NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_wi_instance (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_submissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  instance_id VARCHAR(64) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  nickname VARCHAR(100) NOT NULL DEFAULT '',
  fields_json JSON NOT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_msg_instance (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS checkin_records (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  instance_id VARCHAR(64) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  checkin_date DATE NOT NULL,
  streak INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_checkin (instance_id, user_id, checkin_date),
  KEY idx_checkin_instance (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_submissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  instance_id VARCHAR(64) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  score INT NOT NULL DEFAULT 0,
  total INT NOT NULL DEFAULT 0,
  answers_json JSON NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_quiz_sub (instance_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_votes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  instance_id VARCHAR(64) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  visitor_key VARCHAR(64) NOT NULL DEFAULT '',
  score TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_rate_vote (instance_id, user_id, visitor_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELETE FROM widget_instances WHERE page_key='order-list';
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_order_01','titleBar','order','订单 - 标题栏','{"align":"left","subtitle":"全部/待付款/待发货/已完成","title":"我的订单"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_home_05','promoPair','home','首页 - 双列促销','{"items":[{"bgColor":"#e8f8f0","image":"./assets/images/auto_42.jpg","link":"","subtitle":"精选","title":"保养套餐"},{"bgColor":"#fff3e0","image":"./assets/images/auto_43.jpg","link":"","subtitle":"优惠","title":"洗车优惠"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_home_05','item','{"bgColor":"#e8f8f0","image":"./assets/images/auto_42.jpg","link":"","subtitle":"精选","title":"保养套餐"}',0 UNION ALL
SELECT 'auto_v03_home_05','item','{"bgColor":"#fff3e0","image":"./assets/images/auto_43.jpg","link":"","subtitle":"优惠","title":"洗车优惠"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_home_05' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_cat_01','titleBar','category','分类 - 标题栏','{"align":"center","subtitle":"汽车服务","title":"商品分类"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_mine_03','gridNav','mine','我的 - 宫格导航','{"columns":4,"items":[{"icon":"./assets/images/auto_1.jpg","link":{"module":"system","systemRoute":"order-list","type":"internal"},"text":"我的订单"},{"icon":"./assets/images/auto_2.jpg","link":{"module":"system","systemRoute":"coupon-list","type":"internal"},"text":"优惠券"},{"icon":"./assets/images/auto_3.jpg","link":{"module":"system","systemRoute":"address-list","type":"internal"},"text":"收货地址"},{"icon":"./assets/images/auto_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"客服中心"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_mine_03','item','{"icon":"./assets/images/auto_1.jpg","link":{"module":"system","systemRoute":"order-list","type":"internal"},"text":"我的订单"}',0 UNION ALL
SELECT 'auto_v03_mine_03','item','{"icon":"./assets/images/auto_2.jpg","link":{"module":"system","systemRoute":"coupon-list","type":"internal"},"text":"优惠券"}',1 UNION ALL
SELECT 'auto_v03_mine_03','item','{"icon":"./assets/images/auto_3.jpg","link":{"module":"system","systemRoute":"address-list","type":"internal"},"text":"收货地址"}',2 UNION ALL
SELECT 'auto_v03_mine_03','item','{"icon":"./assets/images/auto_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"客服中心"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_mine_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_member_01','titleBar','member','会员中心 - 标题栏','{"align":"center","subtitle":"尊享会员权益","title":"会员中心"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_mine_02d','userOrders','mine','我的 - 订单速查','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_mine_06','promoPair','mine','我的 - 双列促销','{"items":[{"bgColor":"#f3e8ff","image":"./assets/images/auto_44.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"subtitle":"更多优惠","title":"会员专享"},{"bgColor":"#e8f8f0","image":"./assets/images/auto_45.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"subtitle":"分享赚积分","title":"邀请有礼"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_mine_06','item','{"bgColor":"#f3e8ff","image":"./assets/images/auto_44.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"subtitle":"更多优惠","title":"会员专享"}',0 UNION ALL
SELECT 'auto_v03_mine_06','item','{"bgColor":"#e8f8f0","image":"./assets/images/auto_45.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"subtitle":"分享赚积分","title":"邀请有礼"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_mine_06' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_cat_02','searchBar','category','分类 - 搜索栏','{"bgColor":"#ffffff","placeholder":"搜索汽车服务"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_article_01','titleBar','article','资讯 - 标题栏','{"align":"left","subtitle":"汽车服务最新资讯","title":"资讯动态"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_member_03','gridNav','member','会员中心 - 宫格导航','{"columns":3,"items":[{"icon":"./assets/images/auto_1.jpg","text":"会员折扣"},{"icon":"./assets/images/auto_2.jpg","text":"积分翻倍"},{"icon":"./assets/images/auto_3.jpg","text":"专属客服"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_member_03','item','{"icon":"./assets/images/auto_1.jpg","text":"会员折扣"}',0 UNION ALL
SELECT 'auto_v03_member_03','item','{"icon":"./assets/images/auto_2.jpg","text":"积分翻倍"}',1 UNION ALL
SELECT 'auto_v03_member_03','item','{"icon":"./assets/images/auto_3.jpg","text":"专属客服"}',2
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_member_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_coupon_04','promoPair','coupon','优惠券 - 双列促销2','{"items":[{"bgColor":"#f3e8ff","image":"./assets/images/auto_42.jpg","link":"","subtitle":"会员专属","title":"会员券"},{"bgColor":"#fff8e1","image":"./assets/images/auto_43.jpg","link":"","subtitle":"指定分类","title":"品类券"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_coupon_04','item','{"bgColor":"#f3e8ff","image":"./assets/images/auto_42.jpg","link":"","subtitle":"会员专属","title":"会员券"}',0 UNION ALL
SELECT 'auto_v03_coupon_04','item','{"bgColor":"#fff8e1","image":"./assets/images/auto_43.jpg","link":"","subtitle":"指定分类","title":"品类券"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_coupon_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_home_07','flashSale','home','首页 - 秒杀入口','{"bgColor":"#fff3e0","showCountdown":true,"subtitle":"每日限时","title":"整点秒杀"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_coupon_03','promoPair','coupon','优惠券 - 双列促销','{"items":[{"bgColor":"#ffe0e0","image":"./assets/images/auto_40.jpg","link":"","subtitle":"全场通用","title":"满100减20"},{"bgColor":"#e8f8f0","image":"./assets/images/auto_41.jpg","link":"","subtitle":"首单专享","title":"新人立减"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_coupon_03','item','{"bgColor":"#ffe0e0","image":"./assets/images/auto_40.jpg","link":"","subtitle":"全场通用","title":"满100减20"}',0 UNION ALL
SELECT 'auto_v03_coupon_03','item','{"bgColor":"#e8f8f0","image":"./assets/images/auto_41.jpg","link":"","subtitle":"首单专享","title":"新人立减"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_coupon_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_coupon_01','titleBar','coupon','优惠券 - 标题栏','{"align":"center","subtitle":"领券中心","title":"优惠券"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_about_02','image','about','关于我们 - 图片','{"link":{"linkType":"none"},"src":"./assets/images/auto_51.jpg"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_mine_02b','userVip','mine','我的 - VIP卡片','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('sub_title_flash-sale','titleBar','flash-sale','限时秒杀 - 标题栏','{"align":"left","subtitle":"限时特惠 抢完即止","title":"限时秒杀"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_home_06','promoPair','home','首页 - 双列促销2','{"items":[{"bgColor":"#e8f8f0","image":"./assets/images/auto_44.jpg","link":"","subtitle":"精选","title":"会员特权"},{"bgColor":"#fff3e0","image":"./assets/images/auto_45.jpg","link":"","subtitle":"优惠","title":"限时特惠"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_home_06','item','{"bgColor":"#e8f8f0","image":"./assets/images/auto_44.jpg","link":"","subtitle":"精选","title":"会员特权"}',0 UNION ALL
SELECT 'auto_v03_home_06','item','{"bgColor":"#fff3e0","image":"./assets/images/auto_45.jpg","link":"","subtitle":"优惠","title":"限时特惠"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_home_06' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_mine_01','pageHeader','mine','我的 - 页面头部','{"bgColor":"#e74c3c","brand":"车生活","location":"个人中心","placeholder":"搜索订单","showMessage":true,"showScan":false}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_contact_01','titleBar','contact','联系客服 - 标题栏','{"align":"center","subtitle":"7×12小时在线","title":"联系客服"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('sub_hdr_flash-sale','pageHeader','flash-sale','限时秒杀 - 页面头部','{"bgColor":"#2ecc71","brand":"生鲜商城","location":"限时秒杀","placeholder":"搜索秒杀商品","searchType":"product","showMessage":true,"showScan":true}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_cat_03','gridNav','category','分类 - 宫格导航','{"columns":5,"items":[{"icon":"./assets/images/auto_1.jpg","text":"保养"},{"icon":"./assets/images/auto_2.jpg","text":"维修"},{"icon":"./assets/images/auto_3.jpg","text":"洗车"},{"icon":"./assets/images/auto_4.jpg","text":"美容"},{"icon":"./assets/images/auto_5.jpg","text":"轮胎"},{"icon":"./assets/images/auto_6.jpg","text":"机油"},{"icon":"./assets/images/auto_55.jpg","text":"配件"},{"icon":"./assets/images/auto_56.jpg","text":"保险"},{"icon":"./assets/images/auto_57.jpg","text":"年检"},{"icon":"./assets/images/auto_58.jpg","text":"更多"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_1.jpg","text":"保养"}',0 UNION ALL
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_2.jpg","text":"维修"}',1 UNION ALL
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_3.jpg","text":"洗车"}',2 UNION ALL
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_4.jpg","text":"美容"}',3 UNION ALL
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_5.jpg","text":"轮胎"}',4 UNION ALL
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_6.jpg","text":"机油"}',5 UNION ALL
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_55.jpg","text":"配件"}',6 UNION ALL
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_56.jpg","text":"保险"}',7 UNION ALL
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_57.jpg","text":"年检"}',8 UNION ALL
SELECT 'auto_v03_cat_03','item','{"icon":"./assets/images/auto_58.jpg","text":"更多"}',9
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_cat_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_mine_02','user','mine','我的 - 用户中心','{"enableRegister":true,"enableWechatLogin":true}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_mine_04','gridNav','mine','我的 - 宫格导航2','{"columns":4,"items":[{"icon":"./assets/images/auto_1.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"会员中心"},{"icon":"./assets/images/auto_2.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"积分商城"},{"icon":"./assets/images/auto_3.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"text":"邀请好友"},{"icon":"./assets/images/auto_4.jpg","link":{"module":"system","systemRoute":"settings","type":"internal"},"text":"设置"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_mine_04','item','{"icon":"./assets/images/auto_1.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"会员中心"}',0 UNION ALL
SELECT 'auto_v03_mine_04','item','{"icon":"./assets/images/auto_2.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"积分商城"}',1 UNION ALL
SELECT 'auto_v03_mine_04','item','{"icon":"./assets/images/auto_3.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"text":"邀请好友"}',2 UNION ALL
SELECT 'auto_v03_mine_04','item','{"icon":"./assets/images/auto_4.jpg","link":{"module":"system","systemRoute":"settings","type":"internal"},"text":"设置"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_mine_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_mine_05','userCommunity','mine','我的 - 会员社区','{"link":{"module":"system","systemRoute":"product-list","type":"internal"}}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_home_01','pageHeader','home','首页 - 页面头部','{"bgColor":"#e74c3c","brand":"车生活","location":"当前定位","placeholder":"搜索汽车服务","showMessage":true,"showScan":false}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_cart_01','titleBar','cart','购物车 - 标题栏','{"align":"center","subtitle":"共0件商品","title":"购物车"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_mine_02c','userBenefits','mine','我的 - 会员权益','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_home_03','promoGrid','home','首页 - 营销宫格','{"columns":2,"items":[{"badge":"HOT","bgColor":"#ffeef0","image":"./assets/images/auto_42.jpg","link":{"type":"none"},"subtitle":"精选","title":"保养套餐"},{"bgColor":"#eef5ff","image":"./assets/images/auto_43.jpg","link":{"type":"none"},"subtitle":"特惠","title":"洗车优惠"},{"bgColor":"#eefaf0","image":"./assets/images/auto_44.jpg","link":{"type":"none"},"subtitle":"福利","title":"会员特权"},{"bgColor":"#fff8e8","image":"./assets/images/auto_45.jpg","link":{"type":"none"},"subtitle":"爆款","title":"限时特惠"}],"rows":2}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'auto_v03_home_03','item','{"badge":"HOT","bgColor":"#ffeef0","image":"./assets/images/auto_42.jpg","link":{"type":"none"},"subtitle":"精选","title":"保养套餐"}',0 UNION ALL
SELECT 'auto_v03_home_03','item','{"bgColor":"#eef5ff","image":"./assets/images/auto_43.jpg","link":{"type":"none"},"subtitle":"特惠","title":"洗车优惠"}',1 UNION ALL
SELECT 'auto_v03_home_03','item','{"bgColor":"#eefaf0","image":"./assets/images/auto_44.jpg","link":{"type":"none"},"subtitle":"福利","title":"会员特权"}',2 UNION ALL
SELECT 'auto_v03_home_03','item','{"bgColor":"#fff8e8","image":"./assets/images/auto_45.jpg","link":{"type":"none"},"subtitle":"爆款","title":"限时特惠"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='auto_v03_home_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('auto_v03_about_01','titleBar','about','关于我们 - 标题栏','{"align":"center","subtitle":"车生活","title":"关于我们"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
