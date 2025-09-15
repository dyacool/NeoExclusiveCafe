-- Cleanup unused tables with backup
-- This script will create timestamped backups then drop the original tables if they exist
-- Target tables: blog_categories, login_attempts, orders_new, post_categories

SET @backup_suffix := DATE_FORMAT(NOW(), '%Y%m%d%H%i%S');
SET FOREIGN_KEY_CHECKS = 0;

DROP PROCEDURE IF EXISTS backup_and_drop_if_exists;
DELIMITER $$
CREATE PROCEDURE backup_and_drop_if_exists(IN tbl VARCHAR(255))
BEGIN
  DECLARE tbl_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO tbl_exists
  FROM information_schema.tables 
  WHERE table_schema = DATABASE() AND table_name = tbl;

  IF tbl_exists > 0 THEN
    SET @bkp_table = CONCAT('backup_', tbl, '_', @backup_suffix);

    -- Create backup table with identical structure
    SET @sql_create = CONCAT('CREATE TABLE IF NOT EXISTS ', @bkp_table, ' LIKE ', tbl);
    PREPARE stmt FROM @sql_create; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    -- Copy data into backup table
    SET @sql_insert = CONCAT('INSERT INTO ', @bkp_table, ' SELECT * FROM ', tbl);
    PREPARE stmt FROM @sql_insert; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    -- Drop the original table
    SET @sql_drop = CONCAT('DROP TABLE ', tbl);
    PREPARE stmt FROM @sql_drop; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END $$
DELIMITER ;

-- Execute for each unused table
CALL backup_and_drop_if_exists('blog_categories');
CALL backup_and_drop_if_exists('login_attempts');
CALL backup_and_drop_if_exists('orders_new');
CALL backup_and_drop_if_exists('post_categories');

-- Cleanup
DROP PROCEDURE IF EXISTS backup_and_drop_if_exists;
SET FOREIGN_KEY_CHECKS = 1;

-- Verification queries (optional)
-- SHOW TABLES LIKE 'backup_blog_categories_%';
-- SHOW TABLES LIKE 'backup_login_attempts_%';
-- SHOW TABLES LIKE 'backup_orders_new_%';
-- SHOW TABLES LIKE 'backup_post_categories_%';

