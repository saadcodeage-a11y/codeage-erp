-- CodeAge ERP
-- Production SQL for employee ID prefix/counter settings and active employee backfill
-- Compatible with MariaDB 10.4 / MySQL

START TRANSACTION;

-- Ensure the employee ID prefix setting exists.
INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`)
SELECT 'employee_id_prefix', 'EMP', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1
  FROM `settings`
  WHERE `key` = 'employee_id_prefix'
);

-- Ensure the employee ID counter setting exists.
INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`)
SELECT 'employee_id_counter', '0', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1
  FROM `settings`
  WHERE `key` = 'employee_id_counter'
);

SET @prefix := (
  SELECT COALESCE(NULLIF(`value`, ''), 'EMP')
  FROM `settings`
  WHERE `key` = 'employee_id_prefix'
  LIMIT 1
);

SET @stored_counter := (
  SELECT COALESCE(CAST(`value` AS UNSIGNED), 0)
  FROM `settings`
  WHERE `key` = 'employee_id_counter'
  LIMIT 1
);

-- Detect the highest numeric suffix already used for the configured prefix.
SET @max_existing := (
  SELECT COALESCE(MAX(CAST(SUBSTRING(`employee_id`, CHAR_LENGTH(@prefix) + 1) AS UNSIGNED)), 0)
  FROM `employees`
  WHERE `employee_id` IS NOT NULL
    AND `employee_id` <> ''
    AND `employee_id` LIKE CONCAT(@prefix, '%')
);

SET @starting_counter := GREATEST(@stored_counter, @max_existing);

-- Backfill missing IDs for already-active employees.
UPDATE `employees` e
JOIN (
  SELECT pending.`id`, (@rownum := @rownum + 1) AS `next_counter`
  FROM (
    SELECT `id`
    FROM `employees`
    WHERE `status` = 'active'
      AND (`employee_id` IS NULL OR `employee_id` = '')
    ORDER BY `id`
  ) pending
  CROSS JOIN (SELECT @rownum := @starting_counter) seed
) generated ON generated.`id` = e.`id`
SET e.`employee_id` = CONCAT(@prefix, LPAD(generated.`next_counter`, 3, '0'));

SET @assigned_max := (
  SELECT COALESCE(MAX(CAST(SUBSTRING(`employee_id`, CHAR_LENGTH(@prefix) + 1) AS UNSIGNED)), 0)
  FROM `employees`
  WHERE `employee_id` IS NOT NULL
    AND `employee_id` <> ''
    AND `employee_id` LIKE CONCAT(@prefix, '%')
);

UPDATE `settings`
SET `value` = CAST(GREATEST(@stored_counter, @assigned_max) AS CHAR),
    `updated_at` = NOW()
WHERE `key` = 'employee_id_counter';

COMMIT;
