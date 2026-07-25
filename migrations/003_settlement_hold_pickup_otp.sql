-- 003: 48-hour settlement hold + pickup OTP + platform payment settings

-- Wallet: split held (pending) vs available (withdrawable) funds.
ALTER TABLE `{p}wallets`
  ADD COLUMN `pending_balance` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `balance`;

-- Wallet transactions: track when a held credit becomes available.
ALTER TABLE `{p}wallet_transactions`
  ADD COLUMN `hold_until` DATETIME NULL AFTER `note`,
  ADD COLUMN `released` TINYINT(1) NOT NULL DEFAULT 1 AFTER `hold_until`;

-- Bookings: pickup OTP handover verification.
ALTER TABLE `{p}bookings`
  ADD COLUMN `pickup_otp` VARCHAR(10) NULL AFTER `id_image`,
  ADD COLUMN `pickup_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pickup_otp`;

-- Settings: settlement hold + platform (admin) payment routing.
INSERT IGNORE INTO `{p}settings` (`key`,`value`,`is_encrypted`) VALUES
  ('settlement_hold_hours','48',0),
  ('payments_to_platform','1',0),
  ('platform_upi_id','',0),
  ('platform_upi_qr','',0),
  ('platform_payee_name','',0);
