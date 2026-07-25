-- ===========================================================================
-- Dwarka Rental — Full Schema + Seed Data
-- InnoDB, utf8mb4_unicode_ci. The installer replaces {p} with the table prefix.
-- ===========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Settings (key/value; secrets stored encrypted with is_encrypted=1)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(190) NOT NULL,
  `value` LONGTEXT NULL,
  `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Translations (DB overrides for UI strings)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}translations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `locale` VARCHAR(5) NOT NULL,
  `key` VARCHAR(190) NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_locale_key` (`locale`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Staff roles (granular permissions for sub-admins)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}staff_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `permissions` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Users (super_admin, staff, shop, agency)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` ENUM('super_admin','staff','shop','agency') NOT NULL DEFAULT 'staff',
  `role_id` INT UNSIGNED NULL,
  `shop_id` INT UNSIGNED NULL,
  `agency_id` INT UNSIGNED NULL,
  `name` VARCHAR(160) NOT NULL,
  `mobile` VARCHAR(20) NOT NULL,
  `email` VARCHAR(190) NULL,
  `password` VARCHAR(255) NOT NULL,
  `permissions` JSON NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_mobile` (`mobile`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_shop` (`shop_id`),
  KEY `idx_users_agency` (`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Login attempts (rate limiting / lockout)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(190) NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_la_login` (`login`),
  KEY `idx_la_ip` (`ip`),
  KEY `idx_la_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Shops (the QR poster partners)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}shops` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `name` VARCHAR(190) NOT NULL,
  `name_gu` VARCHAR(190) NULL,
  `owner_name` VARCHAR(160) NULL,
  `mobile` VARCHAR(20) NULL,
  `whatsapp` VARCHAR(20) NULL,
  `email` VARCHAR(190) NULL,
  `address` VARCHAR(255) NULL,
  `area` VARCHAR(120) NULL,
  `city` VARCHAR(120) NULL DEFAULT 'Dwarka',
  `commission_type` ENUM('percent','fixed','inherit') NOT NULL DEFAULT 'inherit',
  `commission_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `bank_holder` VARCHAR(160) NULL,
  `bank_account` VARCHAR(40) NULL,
  `bank_ifsc` VARCHAR(20) NULL,
  `bank_name` VARCHAR(120) NULL,
  `upi_id` VARCHAR(120) NULL,
  `cheque_image` VARCHAR(255) NULL,
  `kyc_doc` VARCHAR(255) NULL,
  `kyc_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `poster_bg` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shops_code` (`code`),
  KEY `idx_shops_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Shop scans (analytics)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}shop_scans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shop_id` INT UNSIGNED NOT NULL,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_scan_shop` (`shop_id`),
  KEY `idx_scan_created` (`created_at`),
  CONSTRAINT `fk_scan_shop` FOREIGN KEY (`shop_id`) REFERENCES `{p}shops`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Agencies (vehicle owners)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}agencies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `name` VARCHAR(190) NOT NULL,
  `name_gu` VARCHAR(190) NULL,
  `owner_name` VARCHAR(160) NULL,
  `mobile` VARCHAR(20) NULL,
  `whatsapp` VARCHAR(20) NULL,
  `email` VARCHAR(190) NULL,
  `address` VARCHAR(255) NULL,
  `upi_id` VARCHAR(120) NULL,
  `upi_qr_image` VARCHAR(255) NULL,
  `commission_type` ENUM('percent','fixed','inherit') NOT NULL DEFAULT 'inherit',
  `commission_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `settlement_rate` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `bank_holder` VARCHAR(160) NULL,
  `bank_account` VARCHAR(40) NULL,
  `bank_ifsc` VARCHAR(20) NULL,
  `bank_name` VARCHAR(120) NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_agencies_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Categories
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `name_gu` VARCHAR(120) NULL,
  `slug` VARCHAR(140) NOT NULL,
  `icon` VARCHAR(60) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Vehicles
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}vehicles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id` INT UNSIGNED NULL,
  `category_id` INT UNSIGNED NULL,
  `name` VARCHAR(190) NOT NULL,
  `name_gu` VARCHAR(190) NULL,
  `brand` VARCHAR(120) NULL,
  `model` VARCHAR(120) NULL,
  `reg_number` VARCHAR(40) NULL,
  `transmission` ENUM('gear','non_gear','manual','automatic','na') NOT NULL DEFAULT 'na',
  `fuel` ENUM('petrol','diesel','ev','cng','none') NOT NULL DEFAULT 'petrol',
  `seats` TINYINT UNSIGNED NULL,
  `units` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `price_hour` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `price_day` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `price_week` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `price_month` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `deposit` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `commission_type` ENUM('percent','fixed','inherit') NOT NULL DEFAULT 'inherit',
  `commission_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `features` JSON NULL,
  `main_image` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_veh_agency` (`agency_id`),
  KEY `idx_veh_category` (`category_id`),
  KEY `idx_veh_status` (`status`),
  CONSTRAINT `fk_veh_agency` FOREIGN KEY (`agency_id`) REFERENCES `{p}agencies`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_veh_category` FOREIGN KEY (`category_id`) REFERENCES `{p}categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{p}vehicle_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_vi_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_vi_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `{p}vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{p}vehicle_blocks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `reason` VARCHAR(190) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vb_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_vb_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `{p}vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Customers + OTP
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile` VARCHAR(20) NOT NULL,
  `name` VARCHAR(160) NULL,
  `alt_mobile` VARCHAR(20) NULL,
  `address` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cust_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{p}otp_verifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile` VARCHAR(20) NOT NULL,
  `otp` VARCHAR(10) NOT NULL,
  `purpose` VARCHAR(40) NOT NULL DEFAULT 'booking',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `verified` TINYINT(1) NOT NULL DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_otp_mobile` (`mobile`),
  KEY `idx_otp_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Bookings
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `customer_id` INT UNSIGNED NULL,
  `shop_id` INT UNSIGNED NULL,
  `agency_id` INT UNSIGNED NULL,
  `vehicle_id` INT UNSIGNED NULL,
  `pickup_at` DATETIME NOT NULL,
  `drop_at` DATETIME NOT NULL,
  `duration_hours` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `base_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `deposit` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `coupon_id` INT UNSIGNED NULL,
  `extra_charges` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `advance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `balance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `riders` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `customer_name` VARCHAR(160) NULL,
  `customer_mobile` VARCHAR(20) NULL,
  `customer_alt_mobile` VARCHAR(20) NULL,
  `customer_address` VARCHAR(255) NULL,
  `dl_image` VARCHAR(255) NULL,
  `id_image` VARCHAR(255) NULL,
  `terms_accepted` TINYINT(1) NOT NULL DEFAULT 0,
  `source` ENUM('qr','direct','walkin') NOT NULL DEFAULT 'direct',
  `status` ENUM('pending_payment','confirmed','picked_up','returned','completed','cancelled','no_show') NOT NULL DEFAULT 'pending_payment',
  `payment_status` ENUM('unpaid','advance_paid','pending_verification','paid','partially_refunded','refunded') NOT NULL DEFAULT 'unpaid',
  `pickup_odo` VARCHAR(30) NULL,
  `pickup_fuel` VARCHAR(30) NULL,
  `pickup_photo` VARCHAR(255) NULL,
  `return_odo` VARCHAR(30) NULL,
  `return_fuel` VARCHAR(30) NULL,
  `return_photo` VARCHAR(255) NULL,
  `picked_up_at` DATETIME NULL,
  `returned_at` DATETIME NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_code` (`code`),
  KEY `idx_bk_shop` (`shop_id`),
  KEY `idx_bk_agency` (`agency_id`),
  KEY `idx_bk_vehicle` (`vehicle_id`),
  KEY `idx_bk_status` (`status`),
  KEY `idx_bk_pay` (`payment_status`),
  KEY `idx_bk_pickup` (`pickup_at`),
  KEY `idx_bk_created` (`created_at`),
  CONSTRAINT `fk_bk_shop` FOREIGN KEY (`shop_id`) REFERENCES `{p}shops`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bk_agency` FOREIGN KEY (`agency_id`) REFERENCES `{p}agencies`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bk_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `{p}vehicles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Payments
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `gateway` ENUM('razorpay','phonepe','cashfree','upi','cash') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `currency` VARCHAR(6) NOT NULL DEFAULT 'INR',
  `status` ENUM('created','pending_verification','paid','failed','refunded') NOT NULL DEFAULT 'created',
  `txn_id` VARCHAR(120) NULL,
  `gateway_order_id` VARCHAR(120) NULL,
  `gateway_payment_id` VARCHAR(120) NULL,
  `gateway_signature` VARCHAR(255) NULL,
  `utr` VARCHAR(60) NULL,
  `screenshot` VARCHAR(255) NULL,
  `response_json` LONGTEXT NULL,
  `verified_by` INT UNSIGNED NULL,
  `verified_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pay_booking` (`booking_id`),
  KEY `idx_pay_status` (`status`),
  UNIQUE KEY `uq_pay_order` (`gateway_order_id`),
  UNIQUE KEY `uq_pay_payment` (`gateway_payment_id`),
  CONSTRAINT `fk_pay_booking` FOREIGN KEY (`booking_id`) REFERENCES `{p}bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Commission ledger
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}commission_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `beneficiary_type` ENUM('shop','platform','agency') NOT NULL,
  `beneficiary_id` INT UNSIGNED NULL,
  `entry_type` ENUM('credit','debit','reversal') NOT NULL DEFAULT 'credit',
  `base_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `rate_type` ENUM('percent','fixed') NULL,
  `rate_value` DECIMAL(10,2) NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cl_booking` (`booking_id`),
  KEY `idx_cl_benef` (`beneficiary_type`,`beneficiary_id`),
  CONSTRAINT `fk_cl_booking` FOREIGN KEY (`booking_id`) REFERENCES `{p}bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Wallets + transactions
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}wallets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_type` ENUM('shop','agency') NOT NULL,
  `owner_id` INT UNSIGNED NOT NULL,
  `balance` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_earned` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_withdrawn` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wallet_owner` (`owner_type`,`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{p}wallet_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wallet_id` INT UNSIGNED NOT NULL,
  `direction` ENUM('credit','debit') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `balance_after` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `ref_type` VARCHAR(40) NULL,
  `ref_id` VARCHAR(40) NULL,
  `note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wt_wallet` (`wallet_id`),
  CONSTRAINT `fk_wt_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `{p}wallets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Payout requests
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}payout_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_type` ENUM('shop','agency') NOT NULL,
  `owner_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status` ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `bank_snapshot` JSON NULL,
  `utr` VARCHAR(60) NULL,
  `proof_image` VARCHAR(255) NULL,
  `note` VARCHAR(255) NULL,
  `processed_by` INT UNSIGNED NULL,
  `processed_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_po_owner` (`owner_type`,`owner_id`),
  KEY `idx_po_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Coupons
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `value` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `min_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `max_discount` DECIMAL(10,2) NULL,
  `usage_limit` INT NULL,
  `used_count` INT NOT NULL DEFAULT 0,
  `per_user_limit` INT NULL,
  `starts_at` DATETIME NULL,
  `ends_at` DATETIME NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupon_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- WhatsApp templates / queue / logs
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}whatsapp_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `locale` VARCHAR(5) NOT NULL DEFAULT 'en',
  `body` TEXT NOT NULL,
  `variables` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wt_key_locale` (`key`,`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{p}whatsapp_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `to_number` VARCHAR(20) NOT NULL,
  `template_key` VARCHAR(80) NULL,
  `message` TEXT NOT NULL,
  `payload` JSON NULL,
  `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` VARCHAR(255) NULL,
  `scheduled_at` DATETIME NOT NULL,
  `sent_at` DATETIME NULL,
  `response_json` LONGTEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wq_status` (`status`),
  KEY `idx_wq_sched` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{p}whatsapp_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `direction` ENUM('out','in') NOT NULL DEFAULT 'out',
  `to_number` VARCHAR(20) NULL,
  `from_number` VARCHAR(20) NULL,
  `template_key` VARCHAR(80) NULL,
  `message` TEXT NULL,
  `status` VARCHAR(40) NULL,
  `response_json` LONGTEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wl_dir` (`direction`),
  KEY `idx_wl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- CMS pages
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(140) NOT NULL,
  `title` VARCHAR(190) NOT NULL,
  `title_gu` VARCHAR(190) NULL,
  `content` LONGTEXT NULL,
  `content_gu` LONGTEXT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_page_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Migrations tracking
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(190) NOT NULL,
  `batch` INT NOT NULL DEFAULT 1,
  `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Update history (GitHub auto-updater)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}update_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_version` VARCHAR(40) NULL,
  `to_version` VARCHAR(40) NULL,
  `from_commit` VARCHAR(64) NULL,
  `to_commit` VARCHAR(64) NULL,
  `status` ENUM('running','success','failed','rolled_back') NOT NULL DEFAULT 'running',
  `files_backup` VARCHAR(255) NULL,
  `db_backup` VARCHAR(255) NULL,
  `changed_files` JSON NULL,
  `log` LONGTEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Activity log (audit trail)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}activity_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `role` VARCHAR(30) NULL,
  `action` VARCHAR(190) NOT NULL,
  `entity` VARCHAR(190) NULL,
  `entity_id` VARCHAR(40) NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_user` (`user_id`),
  KEY `idx_al_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ===========================================================================
-- SEED DATA
-- ===========================================================================

INSERT INTO `{p}settings` (`key`,`value`,`is_encrypted`) VALUES
  ('site_name','Dwarka Rental',0),
  ('site_name_gu','દ્વારકા રેન્ટલ',0),
  ('site_url','',0),
  ('currency_symbol','₹',0),
  ('currency_code','INR',0),
  ('default_language','en',0),
  ('timezone','Asia/Kolkata',0),
  ('gst_percent','0',0),
  ('advance_percent','100',0),
  ('booking_fee','0',0),
  ('auto_cancel_minutes','20',0),
  ('attribution_days','30',0),
  ('min_withdrawal','500',0),
  ('commission_type','percent',0),
  ('commission_value','10',0),
  ('commission_on_base_only','1',0),
  ('maintenance_mode','0',0),
  ('asset_version','1',0),
  ('primary_color','#0d6efd',0),
  ('secondary_color','#20c997',0),
  ('contact_mobile','',0),
  ('contact_whatsapp','',0),
  ('contact_email','',0),
  ('map_link','',0),
  ('logo','',0),
  ('favicon','',0),
  ('wa_api_url','',1),
  ('wa_api_key','',1),
  ('wa_session_id','',1),
  ('wa_sender','',1),
  ('wa_enabled','0',0),
  ('wa_daily_limit','500',0),
  ('wa_min_delay','3',0),
  ('wa_max_delay','8',0),
  ('razorpay_enabled','0',0),
  ('razorpay_key_id','',1),
  ('razorpay_key_secret','',1),
  ('razorpay_webhook_secret','',1),
  ('phonepe_enabled','0',0),
  ('cashfree_enabled','0',0),
  ('upi_enabled','1',0),
  ('cash_enabled','1',0),
  ('github_repo','',0),
  ('github_branch','main',0),
  ('github_token','',1),
  ('github_auto_check','0',0),
  ('update_keep_backups','5',0),
  ('smtp_host','',0),
  ('smtp_port','587',0),
  ('smtp_user','',0),
  ('smtp_pass','',1),
  ('smtp_from','',0);

-- Categories
INSERT INTO `{p}categories` (`name`,`name_gu`,`slug`,`icon`,`sort_order`,`status`) VALUES
  ('Bike','બાઇક','bike','bi-bicycle',1,'active'),
  ('Activa / Scooter','એક્ટિવા / સ્કૂટર','activa','bi-scooter',2,'active'),
  ('Scooty','સ્કૂટી','scooty','bi-scooter',3,'active'),
  ('Car','કાર','car','bi-car-front',4,'active'),
  ('Tempo Traveller','ટેમ્પો ટ્રાવેલર','tempo-traveller','bi-truck',5,'active'),
  ('Cycle','સાયકલ','cycle','bi-bicycle',6,'active');

-- CMS pages
INSERT INTO `{p}pages` (`slug`,`title`,`title_gu`,`content`,`content_gu`,`status`) VALUES
  ('terms','Terms & Conditions','નિયમો અને શરતો','<p>Please read our rental terms carefully before booking.</p>','<p>બુકિંગ પહેલાં અમારી ભાડાની શરતો ધ્યાનથી વાંચો.</p>','active'),
  ('privacy','Privacy Policy','ગોપનીયતા નીતિ','<p>We respect your privacy.</p>','<p>અમે તમારી ગોપનીયતાનું સન્માન કરીએ છીએ.</p>','active'),
  ('cancellation','Cancellation Policy','રદ કરવાની નીતિ','<p>Cancellation terms apply as per booking status.</p>','<p>બુકિંગ સ્થિતિ મુજબ રદ કરવાની શરતો લાગુ પડે છે.</p>','active'),
  ('about','About Us','અમારા વિશે','<p>Dwarka Rental — book bikes and cars across Devbhoomi Dwarka.</p>','<p>દ્વારકા રેન્ટલ — દેવભૂમિ દ્વારકામાં બાઇક અને કાર બુક કરો.</p>','active');

-- WhatsApp templates (English + Gujarati)
INSERT INTO `{p}whatsapp_templates` (`key`,`name`,`locale`,`body`,`is_active`) VALUES
('booking_confirmed_customer','Booking Confirmed (Customer)','en','Hi {customer_name}, your booking {booking_code} for {vehicle_name} is CONFIRMED. Pickup: {pickup_time}. Amount paid: {amount}, balance: {balance}. Pickup location: {map_link}. Agency: {agency_mobile}. Receipt: {receipt_link}',1),
('booking_confirmed_customer','Booking Confirmed (Customer)','gu','નમસ્તે {customer_name}, {vehicle_name} માટે તમારું બુકિંગ {booking_code} કન્ફર્મ થયું છે. પિકઅપ: {pickup_time}. ચૂકવેલ રકમ: {amount}, બાકી: {balance}. સ્થળ: {map_link}. એજન્સી: {agency_mobile}. રસીદ: {receipt_link}',1),
('booking_alert_agency','Booking Alert (Agency)','en','New booking {booking_code}. Customer: {customer_name} ({agency_mobile}). Vehicle: {vehicle_name}. Pickup: {pickup_time}, Drop: {drop_time}. Paid: {amount}, Balance to collect: {balance}.',1),
('booking_alert_agency','Booking Alert (Agency)','gu','નવું બુકિંગ {booking_code}. ગ્રાહક: {customer_name}. વાહન: {vehicle_name}. પિકઅપ: {pickup_time}, ડ્રોપ: {drop_time}. ચૂકવેલ: {amount}, વસૂલવાની બાકી: {balance}.',1),
('commission_alert_shop','Commission Alert (Shop)','en','A booking came from your shop — {booking_code}. Your commission {today_commission}. Total commission {total_commission}.',1),
('commission_alert_shop','Commission Alert (Shop)','gu','તમારી દુકાનમાંથી બુકિંગ થયું — {booking_code}. તમારું કમિશન {today_commission}. કુલ કમિશન {total_commission}.',1),
('booking_alert_admin','Booking Alert (Admin)','en','New booking {booking_code}: {vehicle_name} by {customer_name}. Amount {amount}. Shop: {shop_name}.',1),
('booking_alert_admin','Booking Alert (Admin)','gu','નવું બુકિંગ {booking_code}: {customer_name} દ્વારા {vehicle_name}. રકમ {amount}. દુકાન: {shop_name}.',1),
('payment_received','Payment Received','en','Payment of {amount} received for booking {booking_code}. Thank you!',1),
('payment_received','Payment Received','gu','બુકિંગ {booking_code} માટે {amount} ચૂકવણી મળી. આભાર!',1),
('pickup_reminder','Pickup Reminder','en','Reminder: your {vehicle_name} pickup for {booking_code} is at {pickup_time}. Agency: {agency_mobile}.',1),
('pickup_reminder','Pickup Reminder','gu','યાદ અપાવ: {booking_code} માટે {vehicle_name} પિકઅપ {pickup_time} વાગ્યે છે. એજન્સી: {agency_mobile}.',1),
('return_reminder','Return Reminder','en','Reminder: please return {vehicle_name} ({booking_code}) by {drop_time}.',1),
('return_reminder','Return Reminder','gu','યાદ અપાવ: {vehicle_name} ({booking_code}) {drop_time} સુધીમાં પરત કરો.',1),
('booking_cancelled','Booking Cancelled','en','Booking {booking_code} has been cancelled. Refund (if any): {amount}.',1),
('booking_cancelled','Booking Cancelled','gu','બુકિંગ {booking_code} રદ કરવામાં આવ્યું છે. રિફંડ (જો કોઈ હોય): {amount}.',1),
('refund_processed','Refund Processed','en','Refund of {amount} processed for booking {booking_code}.',1),
('refund_processed','Refund Processed','gu','બુકિંગ {booking_code} માટે {amount} રિફંડ પ્રોસેસ થયું.',1),
('payout_approved','Payout Approved','en','Your withdrawal of {amount} has been approved and is being processed.',1),
('payout_approved','Payout Approved','gu','તમારી {amount} ઉપાડ મંજૂર થઈ છે અને પ્રોસેસ થઈ રહી છે.',1),
('payout_paid','Payout Paid','en','Your withdrawal of {amount} has been paid. UTR: {balance}.',1),
('payout_paid','Payout Paid','gu','તમારી {amount} ઉપાડ ચૂકવાઈ ગઈ છે. UTR: {balance}.',1),
('otp_verification','OTP Verification','en','Your Dwarka Rental OTP is {amount}. Valid for 5 minutes. Do not share it.',1),
('otp_verification','OTP Verification','gu','તમારો દ્વારકા રેન્ટલ OTP છે {amount}. 5 મિનિટ માટે માન્ય. કોઈને શેર કરશો નહીં.',1);

-- Record that the initial schema (001_init) is applied.
INSERT INTO `{p}migrations` (`migration`,`batch`) VALUES ('001_init', 1);
