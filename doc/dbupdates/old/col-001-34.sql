ALTER TABLE `client` ADD COLUMN `is_deleted` TINYINT(4) NOT NULL DEFAULT '0';
ALTER TABLE `project` ADD COLUMN `is_deleted` TINYINT(4) NOT NULL DEFAULT '0';
ALTER TABLE `quote` ADD COLUMN `is_deleted` TINYINT(4) NOT NULL DEFAULT '0';
ALTER TABLE `task` ADD COLUMN `is_deleted` TINYINT(4) NOT NULL DEFAULT '0';
