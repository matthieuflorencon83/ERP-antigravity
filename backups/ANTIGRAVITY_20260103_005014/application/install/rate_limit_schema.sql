-- Schema pour Rate Limiting
-- À exécuter dans la base de données antigravity

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `rate_key` VARCHAR(64) NOT NULL COMMENT 'Hash de l\'identifiant + action',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_rate_key_time` (`rate_key`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
