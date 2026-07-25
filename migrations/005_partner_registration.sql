-- 005: Partner self-registration + voice greeting settings.

INSERT IGNORE INTO `{p}settings` (`key`,`value`,`is_encrypted`) VALUES
  ('shop_registration_enabled','1',0),
  ('agency_registration_enabled','1',0),
  ('registration_auto_approve','0',0),
  ('voice_greeting_enabled','1',0);

-- Track how a partner joined (admin-created vs self-registered).
ALTER TABLE `{p}shops`    ADD COLUMN `source` ENUM('admin','self') NOT NULL DEFAULT 'admin' AFTER `status`;
ALTER TABLE `{p}agencies` ADD COLUMN `source` ENUM('admin','self') NOT NULL DEFAULT 'admin' AFTER `status`;
