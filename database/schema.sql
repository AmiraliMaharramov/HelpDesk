-- ============================================================
--  QuickFixDesk — Core MySQL Schema
--  Compatible with MySQL 5.7+ / MariaDB 10.3+
--  Encoding: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Table: settings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100)    NOT NULL,
  `setting_val` TEXT            DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: roles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id`          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(50)      NOT NULL,
  `slug`        VARCHAR(50)      NOT NULL,
  `description` VARCHAR(255)     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`, `slug`, `description`) VALUES
  ('Administrator',    'admin',      'Full system access'),
  ('Technician',       'technician', 'Handles repair workflow & tickets'),
  ('Corporate Client', 'corporate',  'Company account with billing info'),
  ('Individual Client','individual', 'Personal account');

-- ------------------------------------------------------------
-- Table: permissions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)      NOT NULL,
  `slug`        VARCHAR(100)      NOT NULL,
  `module`      VARCHAR(50)       DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perm_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: permission_groups
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permission_groups` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)      NOT NULL,
  `description` VARCHAR(255)      DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: permission_group_permissions  (pivot)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permission_group_permissions` (
  `group_id`      SMALLINT UNSIGNED NOT NULL,
  `permission_id` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`group_id`, `permission_id`),
  CONSTRAINT `fk_pgp_group`      FOREIGN KEY (`group_id`)      REFERENCES `permission_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pgp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions`       (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `role_id`             TINYINT UNSIGNED NOT NULL DEFAULT 4,   -- 4 = individual
  `permission_group_id` SMALLINT UNSIGNED DEFAULT NULL,        -- staff groups (HR, Sales …)
  `first_name`          VARCHAR(100)     NOT NULL,
  `last_name`           VARCHAR(100)     NOT NULL,
  `email`               VARCHAR(180)     NOT NULL,
  `phone`               VARCHAR(30)      DEFAULT NULL,
  `password_hash`       VARCHAR(255)     NOT NULL,
  `avatar`              VARCHAR(255)     DEFAULT NULL,
  -- Corporate fields
  `company_name`        VARCHAR(200)     DEFAULT NULL,
  `tax_id`              VARCHAR(50)      DEFAULT NULL,
  `tax_office`          VARCHAR(100)     DEFAULT NULL,
  `authorized_person`   VARCHAR(200)     DEFAULT NULL,
  -- Security
  `totp_secret`         VARCHAR(64)      DEFAULT NULL,
  `totp_enabled`        TINYINT(1)       NOT NULL DEFAULT 0,
  `email_verified_at`   TIMESTAMP        DEFAULT NULL,
  `remember_token`      VARCHAR(100)     DEFAULT NULL,
  -- Staff
  `work_start`          TIME             DEFAULT '09:00:00',
  `work_end`            TIME             DEFAULT '18:00:00',
  `is_active`           TINYINT(1)       NOT NULL DEFAULT 1,
  -- Agreement
  `terms_agreed_at`     TIMESTAMP        DEFAULT NULL,
  `data_agreed_at`      TIMESTAMP        DEFAULT NULL,
  -- Language
  `lang`                CHAR(2)          NOT NULL DEFAULT 'en',
  `created_at`          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`),
  KEY `idx_user_role`  (`role_id`),
  CONSTRAINT `fk_user_role`             FOREIGN KEY (`role_id`)             REFERENCES `roles`            (`id`),
  CONSTRAINT `fk_user_perm_group`       FOREIGN KEY (`permission_group_id`) REFERENCES `permission_groups`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: user_permissions  (granular overrides)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `user_id`       INT UNSIGNED      NOT NULL,
  `permission_id` SMALLINT UNSIGNED NOT NULL,
  `granted`       TINYINT(1)        NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`, `permission_id`),
  CONSTRAINT `fk_up_user`       FOREIGN KEY (`user_id`)       REFERENCES `users`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_up_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: addresses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `addresses` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED  NOT NULL,
  `label`         VARCHAR(50)   DEFAULT 'Home',
  `full_name`     VARCHAR(200)  NOT NULL,
  `phone`         VARCHAR(30)   DEFAULT NULL,
  `address_line1` VARCHAR(255)  NOT NULL,
  `address_line2` VARCHAR(255)  DEFAULT NULL,
  `city`          VARCHAR(100)  NOT NULL,
  `state`         VARCHAR(100)  DEFAULT NULL,
  `postal_code`   VARCHAR(20)   DEFAULT NULL,
  `country`       VARCHAR(80)   NOT NULL DEFAULT 'TR',
  `is_default`    TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_addr_user` (`user_id`),
  CONSTRAINT `fk_addr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: password_resets
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      VARCHAR(64)  NOT NULL,
  `totp_code`  VARCHAR(10)  DEFAULT NULL,  -- 2FA code for reset flow
  `expires_at` TIMESTAMP    NOT NULL,
  `used_at`    TIMESTAMP    DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_token` (`token`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: login_sessions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_sessions` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED    NOT NULL,
  `session_id` VARCHAR(128)    NOT NULL,
  `ip_address` VARCHAR(45)     DEFAULT NULL,
  `user_agent` VARCHAR(500)    DEFAULT NULL,
  `last_active`TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logged_out` TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ls_user`    (`user_id`),
  KEY `idx_ls_session` (`session_id`),
  CONSTRAINT `fk_ls_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: audit_logs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED    DEFAULT NULL,
  `action`      VARCHAR(100)    NOT NULL,
  `module`      VARCHAR(50)     DEFAULT NULL,
  `entity_type` VARCHAR(80)     DEFAULT NULL,
  `entity_id`   INT UNSIGNED    DEFAULT NULL,
  `old_values`  JSON            DEFAULT NULL,
  `new_values`  JSON            DEFAULT NULL,
  `ip_address`  VARCHAR(45)     DEFAULT NULL,
  `user_agent`  VARCHAR(500)    DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_user`   (`user_id`),
  KEY `idx_al_action` (`action`),
  KEY `idx_al_entity` (`entity_type`, `entity_id`),
  CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: ticket_categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ticket_categories` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`   SMALLINT UNSIGNED DEFAULT NULL,
  `name`        VARCHAR(120)      NOT NULL,
  `slug`        VARCHAR(120)      NOT NULL,
  `icon`        VARCHAR(80)       DEFAULT NULL,
  `sort_order`  TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tc_slug` (`slug`),
  CONSTRAINT `fk_tc_parent` FOREIGN KEY (`parent_id`) REFERENCES `ticket_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: tickets
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tickets` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `ticket_number`   VARCHAR(20)       NOT NULL,               -- e.g. TKT-20240001
  `client_id`       INT UNSIGNED      NOT NULL,
  `assigned_to`     INT UNSIGNED      DEFAULT NULL,           -- technician
  `category_id`     SMALLINT UNSIGNED DEFAULT NULL,
  `type`            ENUM('onsite','remote','device') NOT NULL DEFAULT 'remote',
  `priority`        ENUM('low','medium','high','urgent')      NOT NULL DEFAULT 'medium',
  `status`          ENUM(
                      'open',
                      'in_review',
                      'awaiting_parts',
                      'repaired',
                      'delivered',
                      'pending_price_approval',
                      'closed',
                      'cancelled'
                    ) NOT NULL DEFAULT 'open',
  `subject`         VARCHAR(255)      NOT NULL,
  `description`     TEXT              NOT NULL,
  -- On-site specific
  `service_address_id` INT UNSIGNED   DEFAULT NULL,
  -- Remote specific
  `remote_tool`     VARCHAR(100)      DEFAULT NULL,           -- e.g. AnyDesk, TeamViewer
  `remote_code`     VARCHAR(50)       DEFAULT NULL,
  -- SLA
  `sla_deadline`    TIMESTAMP         DEFAULT NULL,
  `sla_violated`    TINYINT(1)        NOT NULL DEFAULT 0,
  -- Survey
  `survey_sent_at`  TIMESTAMP         DEFAULT NULL,
  `survey_rating`   TINYINT UNSIGNED  DEFAULT NULL,           -- 1-5
  `survey_comment`  TEXT              DEFAULT NULL,
  -- Canned response / routing
  `auto_routed`     TINYINT(1)        NOT NULL DEFAULT 0,
  `canned_response_id` INT UNSIGNED   DEFAULT NULL,
  `closed_at`       TIMESTAMP         DEFAULT NULL,
  `created_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ticket_number` (`ticket_number`),
  KEY `idx_tkt_client`   (`client_id`),
  KEY `idx_tkt_assigned` (`assigned_to`),
  KEY `idx_tkt_status`   (`status`),
  KEY `idx_tkt_category` (`category_id`),
  CONSTRAINT `fk_tkt_client`   FOREIGN KEY (`client_id`)          REFERENCES `users`             (`id`),
  CONSTRAINT `fk_tkt_tech`     FOREIGN KEY (`assigned_to`)        REFERENCES `users`             (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tkt_category` FOREIGN KEY (`category_id`)        REFERENCES `ticket_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tkt_address`  FOREIGN KEY (`service_address_id`) REFERENCES `addresses`         (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: ticket_messages  (threaded chat)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ticket_id`   INT UNSIGNED  NOT NULL,
  `sender_id`   INT UNSIGNED  NOT NULL,
  `message`     TEXT          NOT NULL,
  `is_internal` TINYINT(1)    NOT NULL DEFAULT 0,  -- internal note (staff only)
  `attachments` JSON          DEFAULT NULL,
  `read_at`     TIMESTAMP     DEFAULT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tm_ticket` (`ticket_id`),
  CONSTRAINT `fk_tm_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tm_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: ticket_status_history
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ticket_status_history` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ticket_id`   INT UNSIGNED  NOT NULL,
  `changed_by`  INT UNSIGNED  DEFAULT NULL,
  `old_status`  VARCHAR(40)   DEFAULT NULL,
  `new_status`  VARCHAR(40)   NOT NULL,
  `note`        TEXT          DEFAULT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tsh_ticket` (`ticket_id`),
  CONSTRAINT `fk_tsh_ticket` FOREIGN KEY (`ticket_id`)  REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tsh_user`   FOREIGN KEY (`changed_by`) REFERENCES `users`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: canned_responses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `canned_responses` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(150)  NOT NULL,
  `body`        TEXT          NOT NULL,
  `category_id` SMALLINT UNSIGNED DEFAULT NULL,
  `created_by`  INT UNSIGNED  DEFAULT NULL,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cr_creator`  FOREIGN KEY (`created_by`)  REFERENCES `users`             (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cr_category` FOREIGN KEY (`category_id`) REFERENCES `ticket_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: devices_service  (repair workflow)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `devices_service` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ticket_id`       INT UNSIGNED  NOT NULL,
  `qr_code`         VARCHAR(128)  DEFAULT NULL,
  `qr_image_path`   VARCHAR(255)  DEFAULT NULL,
  -- Device info
  `device_type`     VARCHAR(80)   DEFAULT NULL,   -- Desktop, Laptop, Phone, Tablet…
  `brand`           VARCHAR(80)   DEFAULT NULL,
  `model`           VARCHAR(100)  DEFAULT NULL,
  `serial_number`   VARCHAR(100)  DEFAULT NULL,
  -- Hardware specs (JSON: RAM, CPU, HDD, GPU …)
  `hardware_specs`  JSON          DEFAULT NULL,
  `condition_notes` TEXT          DEFAULT NULL,
  `accessories`     TEXT          DEFAULT NULL,
  -- Repair workflow
  `repair_status`   ENUM(
                      'received',
                      'in_review',
                      'awaiting_parts',
                      'repaired',
                      'quality_check',
                      'delivered',
                      'pending_price_approval',
                      'cancelled'
                    ) NOT NULL DEFAULT 'received',
  `received_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `repaired_at`     TIMESTAMP     DEFAULT NULL,
  `delivered_at`    TIMESTAMP     DEFAULT NULL,
  -- Parts used
  `parts_used`      JSON          DEFAULT NULL,
  -- Warranty
  `warranty_days`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `warranty_ends`   DATE              DEFAULT NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ds_qr` (`qr_code`),
  KEY `idx_ds_ticket` (`ticket_id`),
  CONSTRAINT `fk_ds_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: sla_policies
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sla_policies` (
  `id`              SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(100)      NOT NULL,
  `priority`        ENUM('low','medium','high','urgent') NOT NULL,
  `response_hours`  SMALLINT UNSIGNED NOT NULL DEFAULT 8,   -- first reply SLA
  `resolve_hours`   SMALLINT UNSIGNED NOT NULL DEFAULT 48,  -- resolution SLA
  `is_active`       TINYINT(1)        NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sla_policies` (`name`, `priority`, `response_hours`, `resolve_hours`) VALUES
  ('Low Priority SLA',    'low',    24, 120),
  ('Medium Priority SLA', 'medium',  8,  48),
  ('High Priority SLA',   'high',    4,  24),
  ('Urgent SLA',          'urgent',  1,   4);

-- ------------------------------------------------------------
-- Table: categories  (product/knowledge-base)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`   SMALLINT UNSIGNED DEFAULT NULL,
  `type`        ENUM('product','kb') NOT NULL DEFAULT 'product',
  `name`        VARCHAR(120)      NOT NULL,
  `slug`        VARCHAR(120)      NOT NULL,
  `description` TEXT              DEFAULT NULL,
  `image`       VARCHAR(255)      DEFAULT NULL,
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_type_slug` (`type`, `slug`),
  CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: products
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `category_id`     SMALLINT UNSIGNED DEFAULT NULL,
  `sku`             VARCHAR(80)       NOT NULL,
  `name`            VARCHAR(255)      NOT NULL,
  `slug`            VARCHAR(255)      NOT NULL,
  `description`     TEXT              DEFAULT NULL,
  `short_desc`      VARCHAR(500)      DEFAULT NULL,
  `price`           DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
  `compare_price`   DECIMAL(10,2)     DEFAULT NULL,
  `cost_price`      DECIMAL(10,2)     DEFAULT NULL,
  `tax_rate`        DECIMAL(5,2)      NOT NULL DEFAULT 18.00,
  `stock_qty`       INT               NOT NULL DEFAULT 0,
  `low_stock_alert` INT               NOT NULL DEFAULT 5,
  `weight_grams`    INT UNSIGNED      DEFAULT NULL,
  `images`          JSON              DEFAULT NULL,   -- array of image paths
  `attributes`      JSON              DEFAULT NULL,   -- color, size, etc.
  `is_active`       TINYINT(1)        NOT NULL DEFAULT 1,
  `is_featured`     TINYINT(1)        NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prod_sku`  (`sku`),
  UNIQUE KEY `uq_prod_slug` (`slug`),
  KEY `idx_prod_category` (`category_id`),
  CONSTRAINT `fk_prod_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: campaigns  (discounts & promotions)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campaigns` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(150)      NOT NULL,
  `type`            ENUM('percent','fixed','bogo') NOT NULL DEFAULT 'percent',
  `value`           DECIMAL(10,2)     NOT NULL DEFAULT 0.00,  -- percent or fixed amount
  `code`            VARCHAR(30)       DEFAULT NULL,           -- coupon code
  `min_order_amount`DECIMAL(10,2)     DEFAULT NULL,
  `applies_to`      ENUM('all','category','product') NOT NULL DEFAULT 'all',
  `applies_id`      INT UNSIGNED      DEFAULT NULL,           -- category_id or product_id
  `uses_limit`      INT UNSIGNED      DEFAULT NULL,
  `uses_count`      INT UNSIGNED      NOT NULL DEFAULT 0,
  `starts_at`       TIMESTAMP         DEFAULT NULL,
  `ends_at`         TIMESTAMP         DEFAULT NULL,
  `is_active`       TINYINT(1)        NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_campaign_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `order_number`    VARCHAR(20)   NOT NULL,
  `client_id`       INT UNSIGNED  NOT NULL,
  `campaign_id`     INT UNSIGNED  DEFAULT NULL,
  `shipping_address_id` INT UNSIGNED DEFAULT NULL,
  `billing_address_id`  INT UNSIGNED DEFAULT NULL,
  `status`          ENUM(
                      'pending',
                      'confirmed',
                      'processing',
                      'shipped',
                      'delivered',
                      'returned',
                      'cancelled'
                    ) NOT NULL DEFAULT 'pending',
  `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency`        CHAR(3)       NOT NULL DEFAULT 'TRY',
  `payment_method`  VARCHAR(50)   DEFAULT NULL,
  `payment_status`  ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_ref`     VARCHAR(100)  DEFAULT NULL,
  `notes`           TEXT          DEFAULT NULL,
  `shipped_at`      TIMESTAMP     DEFAULT NULL,
  `delivered_at`    TIMESTAMP     DEFAULT NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_number` (`order_number`),
  KEY `idx_ord_client`   (`client_id`),
  KEY `idx_ord_status`   (`status`),
  KEY `idx_ord_campaign` (`campaign_id`),
  CONSTRAINT `fk_ord_client`        FOREIGN KEY (`client_id`)          REFERENCES `users`    (`id`),
  CONSTRAINT `fk_ord_campaign`      FOREIGN KEY (`campaign_id`)        REFERENCES `campaigns`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ord_ship_address`  FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ord_bill_address`  FOREIGN KEY (`billing_address_id`)  REFERENCES `addresses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: order_items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `order_id`    INT UNSIGNED  NOT NULL,
  `product_id`  INT UNSIGNED  NOT NULL,
  `product_name`VARCHAR(255)  NOT NULL,
  `sku`         VARCHAR(80)   DEFAULT NULL,
  `qty`         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price`  DECIMAL(10,2) NOT NULL,
  `discount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate`    DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `line_total`  DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order`   (`order_id`),
  KEY `idx_oi_product` (`product_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: invoices
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoices` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `invoice_number`  VARCHAR(20)   NOT NULL,
  `client_id`       INT UNSIGNED  NOT NULL,
  `order_id`        INT UNSIGNED  DEFAULT NULL,
  `ticket_id`       INT UNSIGNED  DEFAULT NULL,
  `type`            ENUM('sale','service','proforma','credit_note') NOT NULL DEFAULT 'sale',
  `status`          ENUM('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `issue_date`      DATE          NOT NULL,
  `due_date`        DATE          DEFAULT NULL,
  -- Billing snapshot
  `billing_name`    VARCHAR(200)  NOT NULL,
  `billing_address` TEXT          DEFAULT NULL,
  `billing_tax_id`  VARCHAR(50)   DEFAULT NULL,
  `billing_tax_off` VARCHAR(100)  DEFAULT NULL,
  -- Amounts
  `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency`        CHAR(3)       NOT NULL DEFAULT 'TRY',
  -- Payment
  `paid_at`         TIMESTAMP     DEFAULT NULL,
  `payment_method`  VARCHAR(50)   DEFAULT NULL,
  `notes`           TEXT          DEFAULT NULL,
  `pdf_path`        VARCHAR(255)  DEFAULT NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoice_number` (`invoice_number`),
  KEY `idx_inv_client` (`client_id`),
  KEY `idx_inv_order`  (`order_id`),
  KEY `idx_inv_ticket` (`ticket_id`),
  CONSTRAINT `fk_inv_client` FOREIGN KEY (`client_id`) REFERENCES `users`  (`id`),
  CONSTRAINT `fk_inv_order`  FOREIGN KEY (`order_id`)  REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inv_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: invoice_items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `invoice_id`  INT UNSIGNED  NOT NULL,
  `description` VARCHAR(500)  NOT NULL,
  `qty`         DECIMAL(10,2) NOT NULL DEFAULT 1,
  `unit_price`  DECIMAL(10,2) NOT NULL,
  `discount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate`    DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `line_total`  DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ii_invoice` (`invoice_id`),
  CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: notifications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED    NOT NULL,
  `type`        VARCHAR(80)     NOT NULL,   -- ticket_update, sla_violation, order_shipped …
  `title`       VARCHAR(255)    NOT NULL,
  `body`        TEXT            DEFAULT NULL,
  `data`        JSON            DEFAULT NULL,
  `channel`     ENUM('web','email','sms') NOT NULL DEFAULT 'web',
  `read_at`     TIMESTAMP       DEFAULT NULL,
  `sent_at`     TIMESTAMP       DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_type` (`type`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: kb_articles  (Knowledge Base / SEO Blog)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kb_articles` (
  `id`            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `category_id`   SMALLINT UNSIGNED DEFAULT NULL,
  `author_id`     INT UNSIGNED      DEFAULT NULL,
  `title`         VARCHAR(255)      NOT NULL,
  `slug`          VARCHAR(255)      NOT NULL,
  `excerpt`       TEXT              DEFAULT NULL,
  `body`          LONGTEXT          NOT NULL,
  `meta_title`    VARCHAR(70)       DEFAULT NULL,
  `meta_desc`     VARCHAR(160)      DEFAULT NULL,
  `featured_img`  VARCHAR(255)      DEFAULT NULL,
  `tags`          JSON              DEFAULT NULL,
  `views`         INT UNSIGNED      NOT NULL DEFAULT 0,
  `is_published`  TINYINT(1)        NOT NULL DEFAULT 0,
  `published_at`  TIMESTAMP         DEFAULT NULL,
  `created_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kb_slug` (`slug`),
  KEY `idx_kb_category` (`category_id`),
  KEY `idx_kb_author`   (`author_id`),
  CONSTRAINT `fk_kb_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kb_author`   FOREIGN KEY (`author_id`)   REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: faqs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `faqs` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question`    VARCHAR(500)      NOT NULL,
  `answer`      TEXT              NOT NULL,
  `category`    VARCHAR(80)       DEFAULT NULL,
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: cms_pages  (About, Contact, etc.)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(255)      NOT NULL,
  `slug`        VARCHAR(255)      NOT NULL,
  `body`        LONGTEXT          DEFAULT NULL,
  `meta_title`  VARCHAR(70)       DEFAULT NULL,
  `meta_desc`   VARCHAR(160)      DEFAULT NULL,
  `is_published`TINYINT(1)        NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cms_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: sliders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sliders` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(200)      DEFAULT NULL,
  `subtitle`    VARCHAR(300)      DEFAULT NULL,
  `image_path`  VARCHAR(255)      NOT NULL,
  `button_text` VARCHAR(80)       DEFAULT NULL,
  `button_url`  VARCHAR(255)      DEFAULT NULL,
  `sort_order`  TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: menus
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menus` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `location`    VARCHAR(50)       NOT NULL,   -- header, footer, sidebar
  `name`        VARCHAR(100)      NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menu_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: menu_items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_id`     SMALLINT UNSIGNED NOT NULL,
  `parent_id`   SMALLINT UNSIGNED DEFAULT NULL,
  `label`       VARCHAR(100)      NOT NULL,
  `url`         VARCHAR(255)      NOT NULL,
  `icon`        VARCHAR(80)       DEFAULT NULL,
  `target`      VARCHAR(10)       NOT NULL DEFAULT '_self',
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_mi_menu`   FOREIGN KEY (`menu_id`)   REFERENCES `menus`     (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mi_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: stats_counters  (Homepage stats display)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stats_counters` (
  `id`          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label`       VARCHAR(80)      NOT NULL,
  `value`       INT UNSIGNED     NOT NULL DEFAULT 0,
  `icon`        VARCHAR(80)      DEFAULT NULL,
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stats_counters` (`label`, `value`, `icon`, `sort_order`) VALUES
  ('Happy Customers', 1500, 'users',       1),
  ('Tickets Resolved', 4200, 'check-circle', 2),
  ('Devices Repaired', 890,  'tool',         3),
  ('Expert Technicians', 12, 'award',        4);

-- ------------------------------------------------------------
-- Table: social_links
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `social_links` (
  `id`       TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform` VARCHAR(50)      NOT NULL,
  `url`      VARCHAR(255)     NOT NULL,
  `icon`     VARCHAR(80)      DEFAULT NULL,
  `is_active`TINYINT(1)       NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: routing_rules  (auto-ticket routing)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `routing_rules` (
  `id`            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100)      NOT NULL,
  `conditions`    JSON              NOT NULL,  -- [{field, operator, value}]
  `assign_to`     INT UNSIGNED      DEFAULT NULL,  -- technician user_id
  `set_priority`  VARCHAR(20)       DEFAULT NULL,
  `set_category`  SMALLINT UNSIGNED DEFAULT NULL,
  `is_active`     TINYINT(1)        NOT NULL DEFAULT 1,
  `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rr_tech`     FOREIGN KEY (`assign_to`)   REFERENCES `users`            (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rr_category` FOREIGN KEY (`set_category`) REFERENCES `ticket_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: live_chat_sessions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `live_chat_sessions` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `visitor_id`  VARCHAR(64)   NOT NULL,   -- anonymous or user_id as string
  `user_id`     INT UNSIGNED  DEFAULT NULL,
  `agent_id`    INT UNSIGNED  DEFAULT NULL,
  `status`      ENUM('waiting','active','closed') NOT NULL DEFAULT 'waiting',
  `started_at`  TIMESTAMP     DEFAULT NULL,
  `ended_at`    TIMESTAMP     DEFAULT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lcs_user`  (`user_id`),
  KEY `idx_lcs_agent` (`agent_id`),
  CONSTRAINT `fk_lcs_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lcs_agent` FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: live_chat_messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `live_chat_messages` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id`  INT UNSIGNED    NOT NULL,
  `sender_type` ENUM('visitor','agent') NOT NULL,
  `message`     TEXT            NOT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lcm_session` (`session_id`),
  CONSTRAINT `fk_lcm_session` FOREIGN KEY (`session_id`) REFERENCES `live_chat_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: contact_messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(150)  NOT NULL,
  `email`       VARCHAR(180)  NOT NULL,
  `phone`       VARCHAR(30)   DEFAULT NULL,
  `subject`     VARCHAR(255)  DEFAULT NULL,
  `message`     TEXT          NOT NULL,
  `is_read`     TINYINT(1)    NOT NULL DEFAULT 0,
  `replied_at`  TIMESTAMP     DEFAULT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
