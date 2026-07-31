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