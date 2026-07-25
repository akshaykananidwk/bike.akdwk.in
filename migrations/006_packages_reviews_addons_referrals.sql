-- 006: Tour packages, reviews, booking add-ons, referrals/loyalty,
--      vehicle documents and abandoned-booking recovery.

-- ---------------------------------------------------------------------------
-- Tour packages (Dwarka darshan, outstation trips, airport/station transfers)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}packages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `name` VARCHAR(190) NOT NULL,
  `slug` VARCHAR(190) NOT NULL,
  `type` ENUM('darshan','outstation','transfer','custom') NOT NULL DEFAULT 'darshan',
  `short_desc` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `places` TEXT NULL,
  `from_location` VARCHAR(120) NULL,
  `to_location` VARCHAR(120) NULL,
  `duration_text` VARCHAR(60) NULL,
  `included_km` INT NULL,
  `extra_km_rate` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `vehicle_type` VARCHAR(120) NULL,
  `seats` TINYINT UNSIGNED NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `strike_price` DECIMAL(10,2) NULL,
  `advance_percent` DECIMAL(5,2) NOT NULL DEFAULT 100,
  `agency_id` INT UNSIGNED NULL,
  `image` VARCHAR(255) NULL,
  `inclusions` TEXT NULL,
  `exclusions` TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pkg_code` (`code`),
  UNIQUE KEY `uq_pkg_slug` (`slug`),
  KEY `idx_pkg_type` (`type`),
  KEY `idx_pkg_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Customer reviews (real ratings, replacing the placeholder stars)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NULL,
  `vehicle_id` INT UNSIGNED NULL,
  `package_id` INT UNSIGNED NULL,
  `agency_id` INT UNSIGNED NULL,
  `customer_name` VARCHAR(160) NULL,
  `customer_mobile` VARCHAR(20) NULL,
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `comment` TEXT NULL,
  `admin_reply` TEXT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rev_vehicle` (`vehicle_id`),
  KEY `idx_rev_package` (`package_id`),
  KEY `idx_rev_status` (`status`),
  UNIQUE KEY `uq_rev_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Customer referrals (referrer earns credit when a friend books)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{p}customer_referrals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_mobile` VARCHAR(20) NOT NULL,
  `referred_mobile` VARCHAR(20) NOT NULL,
  `booking_id` INT UNSIGNED NULL,
  `reward_referrer` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `reward_referred` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `status` ENUM('pending','credited') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ref_referrer` (`referrer_mobile`),
  KEY `idx_ref_referred` (`referred_mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer loyalty / referral wallet
ALTER TABLE `{p}customers`
  ADD COLUMN `referral_code` VARCHAR(20) NULL AFTER `address`,
  ADD COLUMN `referred_by` VARCHAR(20) NULL AFTER `referral_code`,
  ADD COLUMN `credit_balance` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `referred_by`,
  ADD COLUMN `loyalty_points` INT NOT NULL DEFAULT 0 AFTER `credit_balance`,
  ADD COLUMN `total_bookings` INT NOT NULL DEFAULT 0 AFTER `loyalty_points`,
  ADD UNIQUE KEY `uq_cust_refcode` (`referral_code`);

-- ---------------------------------------------------------------------------
-- Booking add-ons: package link, doorstep delivery, insurance, agreement,
-- review token and abandoned-cart reminder flag.
-- ---------------------------------------------------------------------------
ALTER TABLE `{p}bookings`
  ADD COLUMN `package_id` INT UNSIGNED NULL AFTER `vehicle_id`,
  ADD COLUMN `booking_type` ENUM('vehicle','package') NOT NULL DEFAULT 'vehicle' AFTER `package_id`,
  ADD COLUMN `pax` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `riders`,
  ADD COLUMN `delivery_type` ENUM('pickup','doorstep') NOT NULL DEFAULT 'pickup' AFTER `pax`,
  ADD COLUMN `delivery_address` VARCHAR(255) NULL AFTER `delivery_type`,
  ADD COLUMN `delivery_charge` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `delivery_address`,
  ADD COLUMN `insurance_opted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `delivery_charge`,
  ADD COLUMN `insurance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `insurance_opted`,
  ADD COLUMN `credit_used` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `insurance_amount`,
  ADD COLUMN `agreement_signed_at` DATETIME NULL AFTER `terms_accepted`,
  ADD COLUMN `agreement_name` VARCHAR(160) NULL AFTER `agreement_signed_at`,
  ADD COLUMN `agreement_ip` VARCHAR(45) NULL AFTER `agreement_name`,
  ADD COLUMN `review_token` VARCHAR(40) NULL AFTER `agreement_ip`,
  ADD COLUMN `review_requested_at` DATETIME NULL AFTER `review_token`,
  ADD COLUMN `abandoned_reminded_at` DATETIME NULL AFTER `review_requested_at`,
  ADD KEY `idx_bk_package` (`package_id`),
  ADD KEY `idx_bk_reviewtoken` (`review_token`);

-- ---------------------------------------------------------------------------
-- Vehicle documents (expiry reminders)
-- ---------------------------------------------------------------------------
ALTER TABLE `{p}vehicles`
  ADD COLUMN `insurance_expiry` DATE NULL AFTER `status`,
  ADD COLUMN `puc_expiry` DATE NULL AFTER `insurance_expiry`,
  ADD COLUMN `rc_expiry` DATE NULL AFTER `puc_expiry`,
  ADD COLUMN `fitness_expiry` DATE NULL AFTER `rc_expiry`,
  ADD COLUMN `doc_alert_sent_at` DATE NULL AFTER `fitness_expiry`;

-- ---------------------------------------------------------------------------
-- Settings for the new features
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `{p}settings` (`key`,`value`,`is_encrypted`) VALUES
  ('packages_enabled','1',0),
  ('reviews_enabled','1',0),
  ('reviews_auto_approve','0',0),
  ('google_review_url','',0),
  ('delivery_enabled','1',0),
  ('delivery_charge','150',0),
  ('delivery_free_above','2000',0),
  ('insurance_enabled','1',0),
  ('insurance_amount','99',0),
  ('agreement_enabled','1',0),
  ('referral_enabled','1',0),
  ('referral_reward_referrer','100',0),
  ('referral_reward_referred','100',0),
  ('loyalty_enabled','1',0),
  ('loyalty_points_per_100','5',0),
  ('loyalty_point_value','1',0),
  ('abandoned_reminder_minutes','30',0),
  ('doc_alert_days','15',0);

-- ---------------------------------------------------------------------------
-- WhatsApp templates for the new flows
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `{p}whatsapp_templates` (`key`,`name`,`locale`,`body`,`is_active`) VALUES
('abandoned_booking','Abandoned Booking Reminder','en','Hi {customer_name}, your booking {booking_code} for {vehicle_name} is still waiting for payment. Complete it here: {receipt_link} — we are holding your vehicle for a short while.',1),
('abandoned_booking','Abandoned Booking Reminder','gu','નમસ્તે {customer_name}, {vehicle_name} માટે તમારું બુકિંગ {booking_code} હજી ચૂકવણી બાકી છે. અહીં પૂરું કરો: {receipt_link}',1),
('review_request','Review Request','en','Hi {customer_name}, thanks for riding with us! How was your {vehicle_name}? Rate us here: {receipt_link} — it takes 10 seconds and helps us a lot. 🙏',1),
('review_request','Review Request','gu','નમસ્તે {customer_name}, અમારી સાથે રાઇડ કરવા બદલ આભાર! {vehicle_name} કેવું રહ્યું? અહીં રેટિંગ આપો: {receipt_link} 🙏',1),
('referral_reward','Referral Reward','en','Great news {customer_name}! Your friend booked with us using your code. {amount} credit has been added to your account — use it on your next booking.',1),
('referral_reward','Referral Reward','gu','ખુશખબર {customer_name}! તમારા મિત્રે તમારા કોડથી બુકિંગ કર્યું. તમારા ખાતામાં {amount} ક્રેડિટ ઉમેરાઈ છે.',1),
('doc_expiry_alert','Vehicle Document Expiry','en','Reminder: documents for {vehicle_name} expire soon — {balance}. Please renew to avoid fines and keep the vehicle bookable.',1),
('doc_expiry_alert','Vehicle Document Expiry','gu','યાદ અપાવ: {vehicle_name} ના કાગળ ટૂંક સમયમાં પૂરા થાય છે — {balance}. કૃપા કરી રિન્યુ કરો.',1),
('package_booked','Package Booked','en','Hi {customer_name}, your {vehicle_name} package {booking_code} is confirmed for {pickup_time}. Amount paid {amount}, balance {balance}. Contact: {agency_mobile}',1),
('package_booked','Package Booked','gu','નમસ્તે {customer_name}, તમારું પેકેજ {booking_code} {pickup_time} માટે કન્ફર્મ થયું. ચૂકવેલ {amount}, બાકી {balance}.',1);

-- ---------------------------------------------------------------------------
-- Starter packages for Dwarka (edit or delete them in the admin panel)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `{p}packages`
 (`code`,`name`,`slug`,`type`,`short_desc`,`places`,`from_location`,`to_location`,`duration_text`,`included_km`,`extra_km_rate`,`vehicle_type`,`seats`,`price`,`strike_price`,`advance_percent`,`inclusions`,`exclusions`,`sort_order`,`status`) VALUES
('PKG-001','Dwarka Local Darshan','dwarka-local-darshan','darshan','Cover all main Dwarka temples in one comfortable trip.','Dwarkadhish Temple, Gomti Ghat, Rukmini Devi Temple, Sudama Setu, Bhadkeshwar Mahadev','Dwarka','Dwarka','5-6 hours',60,12,'Sedan (4 seater)',4,1500,2000,30,'Car with driver, fuel, parking, driver allowance','Temple donations, food, entry tickets',1,'active'),
('PKG-002','Dwarka + Beyt Dwarka + Nageshwar','dwarka-beyt-nageshwar-darshan','darshan','The full Dwarka circuit including Beyt Dwarka island and Nageshwar Jyotirlinga.','Dwarkadhish Temple, Nageshwar Jyotirlinga, Gopi Talav, Beyt Dwarka, Rukmini Temple','Dwarka','Dwarka','8-9 hours',120,12,'Sedan / SUV',4,2500,3200,30,'Car with driver, fuel, parking, toll','Boat ticket to Beyt Dwarka, food, donations',2,'active'),
('PKG-003','Dwarka to Somnath (One Way)','dwarka-to-somnath-taxi','outstation','Comfortable one-way drop from Dwarka to Somnath.',NULL,'Dwarka','Somnath','5-6 hours',250,12,'Sedan (4 seater)',4,4500,5500,30,'Car with driver, fuel, toll, driver allowance','Food, temple entry, night halt',3,'active'),
('PKG-004','Dwarka - Somnath 2 Days Tour','dwarka-somnath-2-day-tour','outstation','Two-day pilgrimage covering Dwarka and Somnath with a night halt.','Dwarkadhish Temple, Nageshwar, Beyt Dwarka, Somnath Temple, Bhalka Tirth','Dwarka','Somnath','2 days / 1 night',450,12,'SUV (6 seater)',6,9500,12000,30,'Car with driver, fuel, toll, driver allowance','Hotel, food, temple entry',4,'active'),
('PKG-005','Jamnagar Airport to Dwarka Transfer','jamnagar-airport-to-dwarka-taxi','transfer','Private airport transfer from Jamnagar to your Dwarka hotel.',NULL,'Jamnagar Airport','Dwarka','3 hours',140,12,'Sedan (4 seater)',4,2800,3500,30,'Car with driver, fuel, toll, airport parking','Waiting beyond 60 minutes',5,'active'),
('PKG-006','Dwarka Railway Station Pickup','dwarka-railway-station-pickup','transfer','Quick pickup from Dwarka railway station to your hotel.',NULL,'Dwarka Railway Station','Dwarka Hotel','30 minutes',10,12,'Sedan (4 seater)',4,300,500,100,'Car with driver, fuel','Waiting beyond 30 minutes',6,'active');
