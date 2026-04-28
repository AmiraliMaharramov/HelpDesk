-- ============================================================
--  QuickFixDesk — Migration 001: FAQ Engine + HR Infrastructure
--  Run once against an already-installed database.
--  Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Helper procedure: safely ADD COLUMN if it does not exist ─────────────────
DROP PROCEDURE IF EXISTS _qfd_add_col;
DELIMITER $$
CREATE PROCEDURE _qfd_add_col(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_def TEXT)
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

-- ── Helper procedure: safely ADD INDEX if it does not exist ──────────────────
DROP PROCEDURE IF EXISTS _qfd_add_idx;
DELIMITER $$
CREATE PROCEDURE _qfd_add_idx(IN p_table VARCHAR(64), IN p_idx VARCHAR(64), IN p_def TEXT)
BEGIN
    SET @_cnt = (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_idx
    );
    IF @_cnt = 0 THEN
        SET @_sql = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_def);
        PREPARE _s FROM @_sql; EXECUTE _s; DEALLOCATE PREPARE _s;
    END IF;
END$$
DELIMITER ;

-- ── 1. Extend `faqs` table ────────────────────────────────────────────────────
CALL _qfd_add_col('faqs', 'lang',       "CHAR(2) NOT NULL DEFAULT 'en' AFTER `is_active`");
CALL _qfd_add_col('faqs', 'updated_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
CALL _qfd_add_idx('faqs', 'ft_faq_search', "FULLTEXT INDEX `ft_faq_search` (`question`, `answer`)");

-- ── 2. Departments table ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `departments` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)      NOT NULL,
  `slug`        VARCHAR(100)      NOT NULL,
  `description` VARCHAR(255)      DEFAULT NULL,
  `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `departments` (`name`, `slug`, `description`) VALUES
  ('Technical',  'technical',  'Software, server and network issues'),
  ('Field',      'field',      'On-site hardware installations and repairs'),
  ('HR',         'hr',         'Human resources and staff management'),
  ('Accounting', 'accounting', 'Billing, invoicing and financial operations'),
  ('Sales',      'sales',      'Sales, quotations and client relations');

-- ── 3. Add department_id to users and tickets ─────────────────────────────────
CALL _qfd_add_col('users',   'department_id', "SMALLINT UNSIGNED DEFAULT NULL AFTER `permission_group_id`");
CALL _qfd_add_col('tickets', 'department_id', "SMALLINT UNSIGNED DEFAULT NULL AFTER `auto_routed`");

-- Add FK for users.department_id
DROP PROCEDURE IF EXISTS _qfd_add_fk;
DELIMITER $$
CREATE PROCEDURE _qfd_add_fk(IN p_table VARCHAR(64), IN p_fk VARCHAR(64), IN p_def TEXT)
BEGIN
    SET @_cnt = (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND CONSTRAINT_NAME = p_fk
    );
    IF @_cnt = 0 THEN
        SET @_sql = CONCAT('ALTER TABLE `', p_table, '` ADD CONSTRAINT `', p_fk, '` ', p_def);
        PREPARE _s FROM @_sql; EXECUTE _s; DEALLOCATE PREPARE _s;
    END IF;
END$$
DELIMITER ;

CALL _qfd_add_fk('users',   'fk_user_dept',   "FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL");
CALL _qfd_add_fk('tickets', 'fk_tkt_dept',    "FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL");

-- ── 4. Seed standard permissions ─────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`) VALUES
  ('View Revenue',          'can_see_revenue',          'billing'),
  ('Delete Tickets',        'can_delete_ticket',         'tickets'),
  ('Manage Users',          'can_manage_users',          'users'),
  ('Manage Products',       'can_manage_products',       'products'),
  ('View Reports',          'can_view_reports',          'reports'),
  ('Manage Settings',       'can_manage_settings',       'settings'),
  ('Manage Staff',          'can_manage_staff',          'hr'),
  ('Manage FAQs',           'can_manage_faqs',           'cms'),
  ('Reassign Tickets',      'can_reassign_tickets',      'tickets'),
  ('Close Tickets',         'can_close_tickets',         'tickets'),
  ('Manage Departments',    'can_manage_departments',    'hr');

-- ── 5. Role-permissions matrix ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id`       TINYINT UNSIGNED  NOT NULL,
  `permission_id` SMALLINT UNSIGNED NOT NULL,
  `granted`       TINYINT(1)        NOT NULL DEFAULT 0,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`)       REFERENCES `roles`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed defaults: admin gets all permissions granted
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `granted`)
SELECT 1, id, 1 FROM `permissions`;

-- Technician gets a restricted set
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `granted`)
SELECT 2, id, CASE
    WHEN slug IN ('can_close_tickets','can_reassign_tickets','can_manage_faqs') THEN 1
    ELSE 0
END FROM `permissions`;

-- ── Cleanup helpers ───────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS _qfd_add_col;
DROP PROCEDURE IF EXISTS _qfd_add_idx;
DROP PROCEDURE IF EXISTS _qfd_add_fk;

SET FOREIGN_KEY_CHECKS = 1;
