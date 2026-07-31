
CREATE TABLE IF NOT EXISTS user_favorites (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  visitor_key VARCHAR(64) NOT NULL DEFAULT '',
  target_type VARCHAR(16) NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_fav (user_id, visitor_key, target_type, target_id),
  KEY idx_fav_user (user_id, target_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_footprints (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  visitor_key VARCHAR(64) NOT NULL DEFAULT '',
  target_type VARCHAR(16) NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_fp (user_id, visitor_key, target_type, target_id),
  KEY idx_fp_user (user_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_hobbies (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  visitor_key VARCHAR(64) NOT NULL DEFAULT '',
  target_type VARCHAR(16) NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  score TINYINT NOT NULL DEFAULT 5,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_hobby (user_id, visitor_key, target_type, target_id),
  KEY idx_hobby_user (user_id, target_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
