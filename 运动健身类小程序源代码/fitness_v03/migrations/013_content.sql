CREATE TABLE IF NOT EXISTS notice_instances (
  instance_id VARCHAR(64) NOT NULL,
  page_key VARCHAR(50) NOT NULL DEFAULT '',
  label VARCHAR(100) NOT NULL DEFAULT '',
  content TEXT NOT NULL,
  text_color VARCHAR(20) NOT NULL DEFAULT '#333333',
  bg_color VARCHAR(20) NOT NULL DEFAULT '#ffffff',
  font_size INT NOT NULL DEFAULT 28,
  scroll_direction VARCHAR(10) NOT NULL DEFAULT 'left',
  scroll_speed INT NOT NULL DEFAULT 50,
  status TINYINT NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS swiper_instances (
  instance_id VARCHAR(64) NOT NULL,
  page_key VARCHAR(50) NOT NULL DEFAULT '',
  label VARCHAR(100) NOT NULL DEFAULT '',
  height INT NOT NULL DEFAULT 360,
  autoplay TINYINT NOT NULL DEFAULT 1,
  interval_ms INT NOT NULL DEFAULT 3000,
  status TINYINT NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS swiper_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  instance_id VARCHAR(64) NOT NULL,
  image VARCHAR(500) NOT NULL DEFAULT '',
  link VARCHAR(500) NOT NULL DEFAULT '',
  title VARCHAR(200) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_instance (instance_id),
  UNIQUE KEY uk_instance_image (instance_id, image(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(64) NOT NULL,
  setting_value JSON NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rich_text_blocks (
  instance_id VARCHAR(64) NOT NULL,
  page_key VARCHAR(50) NOT NULL DEFAULT '',
  label VARCHAR(100) NOT NULL DEFAULT '',
  content MEDIUMTEXT,
  status TINYINT NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO notice_instances (instance_id,page_key,label,content,text_color,bg_color,font_size,scroll_direction,scroll_speed) VALUES ('fitness_v03_cart_02','cart','购物车 - 公告',0xe6bba13939e58583e58c85e982aeefbc8ce8afb7e59ca8e7bb93e7ae97e5898de7a1aee8aea4e59586e59381e4bfa1e681af,'#666','#fff8e1',26,'left',40) ON DUPLICATE KEY UPDATE label=VALUES(label),content=VALUES(content);
INSERT INTO notice_instances (instance_id,page_key,label,content,text_color,bg_color,font_size,scroll_direction,scroll_speed) VALUES ('fitness_v03_coupon_02','coupon','优惠券 - 公告',0xe696b0e794a8e688b7e58fafe9a286e58f96e4b893e4baabe4bc98e683a0e588b8efbc8ce4b88be58d95e69bb4e4bc98e683a0efbc81,'#e74c3c','#fff5f5',26,'left',45) ON DUPLICATE KEY UPDATE label=VALUES(label),content=VALUES(content);
INSERT INTO notice_instances (instance_id,page_key,label,content,text_color,bg_color,font_size,scroll_direction,scroll_speed) VALUES ('fitness_v03_contact_02','contact','联系客服 - 公告',0xe5aea2e69c8de783ade7babfefbc9a3430302d3030302d30303030efbc88e7a4bae4be8befbc892020e5b7a5e4bd9ce697b6e997b4efbc9a393a30302d32313a3030,'#333','#f0f9ff',26,'left',35) ON DUPLICATE KEY UPDATE label=VALUES(label),content=VALUES(content);
INSERT INTO swiper_instances (instance_id,page_key,label,height,autoplay,interval_ms) VALUES ('fitness_v03_home_02','home','首页 - 轮播',340,1,3000) ON DUPLICATE KEY UPDATE label=VALUES(label),height=VALUES(height);
INSERT INTO swiper_items (instance_id,image,link,title,sort_order) VALUES ('fitness_v03_home_02','./assets/images/fitness_9.jpg','','运动健身',0) ON DUPLICATE KEY UPDATE link=VALUES(link),title=VALUES(title),sort_order=VALUES(sort_order),status=1;
INSERT INTO swiper_items (instance_id,image,link,title,sort_order) VALUES ('fitness_v03_home_02','./assets/images/fitness_48.jpg','','新人体验',1) ON DUPLICATE KEY UPDATE link=VALUES(link),title=VALUES(title),sort_order=VALUES(sort_order),status=1;
INSERT INTO swiper_items (instance_id,image,link,title,sort_order) VALUES ('fitness_v03_home_02','./assets/images/fitness_49.jpg','','课程优惠',2) ON DUPLICATE KEY UPDATE link=VALUES(link),title=VALUES(title),sort_order=VALUES(sort_order),status=1;
INSERT INTO swiper_instances (instance_id,page_key,label,height,autoplay,interval_ms) VALUES ('fitness_v03_article_03','article','资讯 - 轮播',280,1,4000) ON DUPLICATE KEY UPDATE label=VALUES(label),height=VALUES(height);
INSERT INTO swiper_items (instance_id,image,link,title,sort_order) VALUES ('fitness_v03_article_03','./assets/images/fitness_50.jpg','','资讯',0) ON DUPLICATE KEY UPDATE link=VALUES(link),title=VALUES(title),sort_order=VALUES(sort_order),status=1;
INSERT INTO rich_text_blocks (instance_id,page_key,label,content) VALUES ('fitness_v03_about_03','about','关于我们 - 富文本',0x3c703e3c7374726f6e673ee6b4bbe58a9be581a5e8baab3c2f7374726f6e673ee887b4e58a9be4ba8ee4b8bae794a8e688b7e68f90e4be9be4bc98e8b4a8e79a84e8bf90e58aa8e581a5e8baabe4baa7e59381e5928ce69c8de58aa1e380823c2f703e3c703ee6aca2e8bf8ee9809ae8bf87e59ca8e7babfe5aea2e69c8de68896e8a1a8e58d95e88194e7b3bbe68891e4bbace380823c2f703e) ON DUPLICATE KEY UPDATE content=VALUES(content),label=VALUES(label);
INSERT INTO rich_text_blocks (instance_id,page_key,label,content) VALUES ('fitness_v03_order_03','order','订单 - 富文本',0x3c703ee69a82e697a0e8aea2e58d95e8aeb0e5bd95e38082e59ca8e6b4bbe58a9be581a5e8baabe4b88be58d95e5908eefbc8ce8aea2e58d95e5b086e698bee7a4bae59ca8e8bf99e9878ce380823c2f703e) ON DUPLICATE KEY UPDATE content=VALUES(content),label=VALUES(label);
INSERT INTO site_settings (setting_key, setting_value) VALUES ('global_config', '{"controls":{"sideHome":{"enabled":false},"sideService":{"enabled":false,"phone":"400-000-0000"},"splashPopup":{"enabled":false,"image":"","link":""}},"moduleSkin":"default","pageBackground":{"type":"default"},"tabBar":{"enabled":true,"items":[{"page_key":"home","text":"首页"},{"page_key":"category","text":"分类"},{"page_key":"cart","text":"购物车"},{"page_key":"mine","text":"我的"}]},"theme":{"backgroundColor":"#f5f5f5","colorPreset":"green","primaryColor":"#e74c3c"}}') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
