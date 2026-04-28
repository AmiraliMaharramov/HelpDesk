-- ============================================================
--  QuickFixDesk — Migration 002: Appointments & Subscriptions
--  Phase 3 (Step 5) + Phase 4 (Step 7)
--  Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── 1. Appointments (scheduling conflict prevention) ──────────────────────────
CREATE TABLE IF NOT EXISTS `appointments` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `ticket_id`      INT UNSIGNED     DEFAULT NULL,
  `client_id`      INT UNSIGNED     NOT NULL,
  `technician_id`  INT UNSIGNED     DEFAULT NULL,
  `department_id`  SMALLINT UNSIGNED DEFAULT NULL,
  `type`           ENUM('onsite','remote') NOT NULL DEFAULT 'onsite',
  `title`          VARCHAR(255)     NOT NULL,
  `notes`          TEXT             DEFAULT NULL,
  `start_at`       DATETIME         NOT NULL,
  `end_at`         DATETIME         NOT NULL,
  `status`         ENUM('scheduled','confirmed','in_progress','completed','cancelled','no_show')
                   NOT NULL DEFAULT 'scheduled',
  `address_id`     INT UNSIGNED     DEFAULT NULL,
  `cancelled_at`   TIMESTAMP        DEFAULT NULL,
  `cancel_reason`  VARCHAR(255)     DEFAULT NULL,
  `created_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_apt_client`  (`client_id`),
  KEY `idx_apt_tech`    (`technician_id`),
  KEY `idx_apt_start`   (`start_at`),
  KEY `idx_apt_status`  (`status`),
  CONSTRAINT `fk_apt_ticket`  FOREIGN KEY (`ticket_id`)     REFERENCES `tickets`    (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_apt_client`  FOREIGN KEY (`client_id`)     REFERENCES `users`      (`id`),
  CONSTRAINT `fk_apt_tech`    FOREIGN KEY (`technician_id`) REFERENCES `users`      (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_apt_dept`    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_apt_addr`    FOREIGN KEY (`address_id`)    REFERENCES `addresses`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Subscription Plans ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id`              TINYINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(80)       NOT NULL,
  `slug`            VARCHAR(50)       NOT NULL,
  `price_monthly`   DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
  `price_yearly`    DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
  `sla_response_h`  SMALLINT UNSIGNED NOT NULL DEFAULT 24,  -- first-reply hours
  `sla_resolve_h`   SMALLINT UNSIGNED NOT NULL DEFAULT 120, -- resolution hours
  `ticket_limit`    SMALLINT UNSIGNED DEFAULT NULL,          -- NULL = unlimited
  `features`        JSON              DEFAULT NULL,
  `is_active`       TINYINT(1)        NOT NULL DEFAULT 1,
  `sort_order`      TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plan_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `subscription_plans`
  (`name`, `slug`, `price_monthly`, `price_yearly`, `sla_response_h`, `sla_resolve_h`, `ticket_limit`, `features`, `sort_order`)
VALUES
  ('Basic',      'basic',      199.00,  1990.00, 24,  120, 10,
   '["Email support","24-hour response","10 tickets/month"]', 1),
  ('Pro',        'pro',        499.00,  4990.00,  8,   48, 50,
   '["Priority support","8-hour response","50 tickets/month","Remote sessions"]', 2),
  ('Enterprise', 'enterprise', 999.00,  9990.00,  1,    4, NULL,
   '["Dedicated engineer","1-hour response","Unlimited tickets","SLA guarantee","On-site visits"]', 3);

-- ── 3. User Subscriptions ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED     NOT NULL,
  `plan_id`     TINYINT UNSIGNED NOT NULL,
  `billing`     ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `status`      ENUM('active','cancelled','expired','trial') NOT NULL DEFAULT 'active',
  `starts_at`   DATE             NOT NULL,
  `ends_at`     DATE             NOT NULL,
  `auto_renew`  TINYINT(1)       NOT NULL DEFAULT 1,
  `payment_ref` VARCHAR(100)     DEFAULT NULL,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_us_user`   (`user_id`),
  KEY `idx_us_status` (`status`),
  CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users`             (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_us_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Helper procedure for ADD COLUMN IF NOT EXISTS ────────────────────────────
DROP PROCEDURE IF EXISTS _qfd_add_col2;
DELIMITER $$
CREATE PROCEDURE _qfd_add_col2(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_def TEXT)
BEGIN
    SET @_cnt = (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col
    );
    IF @_cnt = 0 THEN
        SET @_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE _s FROM @_sql; EXECUTE _s; DEALLOCATE PREPARE _s;
    END IF;
END$$
DELIMITER ;

-- ── 4. Add subscription_id to users ──────────────────────────────────────────
CALL _qfd_add_col2('users', 'active_subscription_id', "INT UNSIGNED DEFAULT NULL AFTER `data_agreed_at`");

DROP PROCEDURE IF EXISTS _qfd_add_col2;

SET FOREIGN_KEY_CHECKS = 1;
