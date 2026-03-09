-- CodeAge ERP
-- Production SQL for employee inactive reason support
-- Compatible with MariaDB 10.4 / MySQL

START TRANSACTION;

-- Add the inactive_reason column only if it does not already exist.
SET @column_exists := (
  SELECT COUNT(*)
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE()
    AND `TABLE_NAME` = 'employees'
    AND `COLUMN_NAME` = 'inactive_reason'
);

SET @alter_sql := IF(
  @column_exists = 0,
  'ALTER TABLE `employees` ADD COLUMN `inactive_reason` TEXT NULL AFTER `status`',
  'SELECT 1'
);

PREPARE alter_stmt FROM @alter_sql;
EXECUTE alter_stmt;
DEALLOCATE PREPARE alter_stmt;

-- Mark the Laravel migration as executed so future artisan runs stay in sync.
SET @next_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_03_09_000003_add_inactive_reason_to_employees_table', @next_batch
WHERE NOT EXISTS (
  SELECT 1
  FROM `migrations`
  WHERE `migration` = '2026_03_09_000003_add_inactive_reason_to_employees_table'
);

COMMIT;
