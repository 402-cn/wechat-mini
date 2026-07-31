
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
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_order_01','titleBar','order','订单 - 标题栏','{"align":"left","subtitle":"全部/待付款/待发货/已完成","title":"我的订单"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_cat_03','gridNav','category','分类 - 宫格导航','{"columns":5,"items":[{"icon":"./assets/images/media_culture_1.jpg","text":"策划"},{"icon":"./assets/images/media_culture_2.jpg","text":"设计"},{"icon":"./assets/images/media_culture_3.jpg","text":"拍摄"},{"icon":"./assets/images/media_culture_4.jpg","text":"短视频"},{"icon":"./assets/images/media_culture_5.jpg","text":"广告"},{"icon":"./assets/images/media_culture_6.jpg","text":"活动"},{"icon":"./assets/images/media_culture_55.jpg","text":"品牌"},{"icon":"./assets/images/media_culture_56.jpg","text":"公关"},{"icon":"./assets/images/media_culture_57.jpg","text":"案例"},{"icon":"./assets/images/media_culture_58.jpg","text":"更多"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_1.jpg","text":"策划"}',0 UNION ALL
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_2.jpg","text":"设计"}',1 UNION ALL
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_3.jpg","text":"拍摄"}',2 UNION ALL
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_4.jpg","text":"短视频"}',3 UNION ALL
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_5.jpg","text":"广告"}',4 UNION ALL
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_6.jpg","text":"活动"}',5 UNION ALL
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_55.jpg","text":"品牌"}',6 UNION ALL
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_56.jpg","text":"公关"}',7 UNION ALL
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_57.jpg","text":"案例"}',8 UNION ALL
SELECT 'media_culture_v07_cat_03','item','{"icon":"./assets/images/media_culture_58.jpg","text":"更多"}',9
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='media_culture_v07_cat_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_mine_01','pageHeader','mine','我的 - 页面头部','{"bgColor":"#795548","brand":"创媒文化","location":"个人中心","placeholder":"搜索订单","showMessage":true,"showScan":false}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_contact_01','titleBar','contact','联系客服 - 标题栏','{"align":"center","subtitle":"7×12小时在线","title":"联系客服"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_mine_03','gridNav','mine','我的 - 宫格导航','{"columns":4,"items":[{"icon":"./assets/images/media_culture_1.jpg","link":{"module":"system","systemRoute":"order-list","type":"internal"},"text":"我的订单"},{"icon":"./assets/images/media_culture_2.jpg","link":{"module":"system","systemRoute":"coupon-list","type":"internal"},"text":"优惠券"},{"icon":"./assets/images/media_culture_3.jpg","link":{"module":"system","systemRoute":"address-list","type":"internal"},"text":"收货地址"},{"icon":"./assets/images/media_culture_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"客服中心"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'media_culture_v07_mine_03','item','{"icon":"./assets/images/media_culture_1.jpg","link":{"module":"system","systemRoute":"order-list","type":"internal"},"text":"我的订单"}',0 UNION ALL
SELECT 'media_culture_v07_mine_03','item','{"icon":"./assets/images/media_culture_2.jpg","link":{"module":"system","systemRoute":"coupon-list","type":"internal"},"text":"优惠券"}',1 UNION ALL
SELECT 'media_culture_v07_mine_03','item','{"icon":"./assets/images/media_culture_3.jpg","link":{"module":"system","systemRoute":"address-list","type":"internal"},"text":"收货地址"}',2 UNION ALL
SELECT 'media_culture_v07_mine_03','item','{"icon":"./assets/images/media_culture_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"客服中心"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='media_culture_v07_mine_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_member_03','gridNav','member','会员中心 - 宫格导航','{"columns":3,"items":[{"icon":"./assets/images/media_culture_1.jpg","text":"会员折扣"},{"icon":"./assets/images/media_culture_2.jpg","text":"积分翻倍"},{"icon":"./assets/images/media_culture_3.jpg","text":"专属客服"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'media_culture_v07_member_03','item','{"icon":"./assets/images/media_culture_1.jpg","text":"会员折扣"}',0 UNION ALL
SELECT 'media_culture_v07_member_03','item','{"icon":"./assets/images/media_culture_2.jpg","text":"积分翻倍"}',1 UNION ALL
SELECT 'media_culture_v07_member_03','item','{"icon":"./assets/images/media_culture_3.jpg","text":"专属客服"}',2
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='media_culture_v07_member_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_home_04','gridNav','home','首页 - 宫格导航','{"columns":2,"gridStyle":"grid","items":[{"icon":"./assets/images/media_culture_1.jpg","link":{"module":"system","systemRoute":"search-article","type":"internal"},"text":"公司概况"},{"icon":"./assets/images/media_culture_2.jpg","link":{"module":"page","pageKey":"category","type":"internal"},"text":"产品中心"},{"icon":"./assets/images/media_culture_3.jpg","link":{"module":"system","systemRoute":"search-article","type":"internal"},"text":"新闻资讯"},{"icon":"./assets/images/media_culture_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"联系我们"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'media_culture_v07_home_04','item','{"icon":"./assets/images/media_culture_1.jpg","link":{"module":"system","systemRoute":"search-article","type":"internal"},"text":"公司概况"}',0 UNION ALL
SELECT 'media_culture_v07_home_04','item','{"icon":"./assets/images/media_culture_2.jpg","link":{"module":"page","pageKey":"category","type":"internal"},"text":"产品中心"}',1 UNION ALL
SELECT 'media_culture_v07_home_04','item','{"icon":"./assets/images/media_culture_3.jpg","link":{"module":"system","systemRoute":"search-article","type":"internal"},"text":"新闻资讯"}',2 UNION ALL
SELECT 'media_culture_v07_home_04','item','{"icon":"./assets/images/media_culture_4.jpg","link":{"module":"page","pageKey":"contact","type":"internal"},"text":"联系我们"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='media_culture_v07_home_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_mine_02','user','mine','我的 - 用户中心','{"enableRegister":true,"enableWechatLogin":true}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_mine_02b','userVip','mine','我的 - VIP卡片','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_article_01','titleBar','article','资讯 - 标题栏','{"align":"left","subtitle":"文化传媒最新资讯","title":"资讯动态"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_member_01','titleBar','member','会员中心 - 标题栏','{"align":"center","subtitle":"尊享会员权益","title":"会员中心"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_coupon_03','promoPair','coupon','优惠券 - 双列促销','{"items":[{"bgColor":"#ffe0e0","image":"./assets/images/media_culture_40.jpg","link":"","subtitle":"全场通用","title":"满100减20"},{"bgColor":"#e8f8f0","image":"./assets/images/media_culture_41.jpg","link":"","subtitle":"首单专享","title":"新人立减"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'media_culture_v07_coupon_03','item','{"bgColor":"#ffe0e0","image":"./assets/images/media_culture_40.jpg","link":"","subtitle":"全场通用","title":"满100减20"}',0 UNION ALL
SELECT 'media_culture_v07_coupon_03','item','{"bgColor":"#e8f8f0","image":"./assets/images/media_culture_41.jpg","link":"","subtitle":"首单专享","title":"新人立减"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='media_culture_v07_coupon_03' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_mine_04','gridNav','mine','我的 - 宫格导航2','{"columns":4,"items":[{"icon":"./assets/images/media_culture_1.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"会员中心"},{"icon":"./assets/images/media_culture_2.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"积分商城"},{"icon":"./assets/images/media_culture_3.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"text":"邀请好友"},{"icon":"./assets/images/media_culture_4.jpg","link":{"module":"system","systemRoute":"settings","type":"internal"},"text":"设置"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'media_culture_v07_mine_04','item','{"icon":"./assets/images/media_culture_1.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"会员中心"}',0 UNION ALL
SELECT 'media_culture_v07_mine_04','item','{"icon":"./assets/images/media_culture_2.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"text":"积分商城"}',1 UNION ALL
SELECT 'media_culture_v07_mine_04','item','{"icon":"./assets/images/media_culture_3.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"text":"邀请好友"}',2 UNION ALL
SELECT 'media_culture_v07_mine_04','item','{"icon":"./assets/images/media_culture_4.jpg","link":{"module":"system","systemRoute":"settings","type":"internal"},"text":"设置"}',3
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='media_culture_v07_mine_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_coupon_04','promoPair','coupon','优惠券 - 双列促销2','{"items":[{"bgColor":"#f3e8ff","image":"./assets/images/media_culture_42.jpg","link":"","subtitle":"会员专属","title":"会员券"},{"bgColor":"#fff8e1","image":"./assets/images/media_culture_43.jpg","link":"","subtitle":"指定分类","title":"品类券"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'media_culture_v07_coupon_04','item','{"bgColor":"#f3e8ff","image":"./assets/images/media_culture_42.jpg","link":"","subtitle":"会员专属","title":"会员券"}',0 UNION ALL
SELECT 'media_culture_v07_coupon_04','item','{"bgColor":"#fff8e1","image":"./assets/images/media_culture_43.jpg","link":"","subtitle":"指定分类","title":"品类券"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='media_culture_v07_coupon_04' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_mine_05','userCommunity','mine','我的 - 会员社区','{"link":{"module":"system","systemRoute":"product-list","type":"internal"}}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_coupon_01','titleBar','coupon','优惠券 - 标题栏','{"align":"center","subtitle":"领券中心","title":"优惠券"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_about_01','titleBar','about','关于我们 - 标题栏','{"align":"center","subtitle":"创媒文化","title":"关于我们"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_home_05','statsRow','home','首页 - 数据统计','{"columns":3,"items":[{"label":"服务客户","unit":"+","value":"1000"},{"label":"行业经验","unit":"年","value":"10"},{"label":"好评率","unit":"%","value":"99"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'media_culture_v07_home_05','item','{"label":"服务客户","unit":"+","value":"1000"}',0 UNION ALL
SELECT 'media_culture_v07_home_05','item','{"label":"行业经验","unit":"年","value":"10"}',1 UNION ALL
SELECT 'media_culture_v07_home_05','item','{"label":"好评率","unit":"%","value":"99"}',2
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='media_culture_v07_home_05' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_cat_02','searchBar','category','分类 - 搜索栏','{"bgColor":"#ffffff","placeholder":"搜索传媒服务"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_mine_06','promoPair','mine','我的 - 双列促销','{"items":[{"bgColor":"#f3e8ff","image":"./assets/images/media_culture_44.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"subtitle":"更多优惠","title":"会员专享"},{"bgColor":"#e8f8f0","image":"./assets/images/media_culture_45.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"subtitle":"分享赚积分","title":"邀请有礼"}]}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_items (instance_id,item_key,item_json,sort_order)
SELECT * FROM (
SELECT 'media_culture_v07_mine_06','item','{"bgColor":"#f3e8ff","image":"./assets/images/media_culture_44.jpg","link":{"module":"system","systemRoute":"member-center","type":"internal"},"subtitle":"更多优惠","title":"会员专享"}',0 UNION ALL
SELECT 'media_culture_v07_mine_06','item','{"bgColor":"#e8f8f0","image":"./assets/images/media_culture_45.jpg","link":{"module":"system","systemRoute":"invite","type":"internal"},"subtitle":"分享赚积分","title":"邀请有礼"}',1
) AS seed_rows WHERE NOT EXISTS (SELECT 1 FROM widget_items WHERE instance_id='media_culture_v07_mine_06' LIMIT 1);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_home_02','image','home','首页 - 图片','{"height":"auto","link":{"type":"none"},"src":"./assets/images/media_culture_9.jpg","width":"100%"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_mine_02c','userBenefits','mine','我的 - 会员权益','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_mine_02d','userOrders','mine','我的 - 订单速查','{}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_cart_01','titleBar','cart','购物车 - 标题栏','{"align":"center","subtitle":"共0件商品","title":"购物车"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_home_01','pageHeader','home','首页 - 页面头部','{"bgColor":"#795548","brand":"创媒文化","location":"当前定位","placeholder":"搜索传媒服务","showMessage":false,"showScan":false}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_cat_01','titleBar','category','分类 - 标题栏','{"align":"center","subtitle":"文化传媒","title":"商品分类"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
INSERT INTO widget_instances (instance_id,component_type,page_key,label,props_json) VALUES ('media_culture_v07_about_02','image','about','关于我们 - 图片','{"link":{"linkType":"none"},"src":"./assets/images/media_culture_51.jpg"}') ON DUPLICATE KEY UPDATE component_type=VALUES(component_type),page_key=VALUES(page_key),label=VALUES(label),props_json=VALUES(props_json);
