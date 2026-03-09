-- CodeAge ERP
-- Production SQL for roles and role permissions
-- Compatible with MariaDB 10.4 / MySQL

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `module` varchar(255) NOT NULL,
  `can_read` tinyint(1) NOT NULL DEFAULT 0,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_module_unique` (`role_id`,`module`),
  CONSTRAINT `role_permissions_role_id_foreign`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @now := NOW();

INSERT INTO `roles` (`name`, `created_at`, `updated_at`)
SELECT 'Super Admin', @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM `roles` WHERE `name` = 'Super Admin'
);

INSERT INTO `roles` (`name`, `created_at`, `updated_at`)
SELECT 'HR Manager', @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM `roles` WHERE `name` = 'HR Manager'
);

INSERT INTO `roles` (`name`, `created_at`, `updated_at`)
SELECT 'Accounts Manager', @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM `roles` WHERE `name` = 'Accounts Manager'
);

INSERT INTO `roles` (`name`, `created_at`, `updated_at`)
SELECT 'Employee', @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM `roles` WHERE `name` = 'Employee'
);

-- Backfill any existing user role names into the managed roles table.
INSERT INTO `roles` (`name`, `created_at`, `updated_at`)
SELECT DISTINCT u.`role`, @now, @now
FROM `users` u
LEFT JOIN `roles` r ON r.`name` = u.`role`
WHERE u.`role` IS NOT NULL
  AND u.`role` <> ''
  AND r.`id` IS NULL;

-- Seed module permissions for each role/module combination if missing.
INSERT INTO `role_permissions` (`role_id`, `module`, `can_read`, `can_create`, `can_edit`, `created_at`, `updated_at`)
SELECT r.`id`, perms.`module`, perms.`can_read`, perms.`can_create`, perms.`can_edit`, @now, @now
FROM `roles` r
JOIN (
  SELECT 'dashboard' AS `module`, 'Super Admin' AS `role_name`, 1 AS `can_read`, 1 AS `can_create`, 1 AS `can_edit`
  UNION ALL SELECT 'employees', 'Super Admin', 1, 1, 1
  UNION ALL SELECT 'user_management', 'Super Admin', 1, 1, 1
  UNION ALL SELECT 'settings', 'Super Admin', 1, 1, 1
  UNION ALL SELECT 'templates', 'Super Admin', 1, 1, 1
  UNION ALL SELECT 'activity_logs', 'Super Admin', 1, 1, 1

  UNION ALL SELECT 'dashboard', 'HR Manager', 1, 0, 0
  UNION ALL SELECT 'employees', 'HR Manager', 1, 1, 1
  UNION ALL SELECT 'user_management', 'HR Manager', 1, 1, 1
  UNION ALL SELECT 'settings', 'HR Manager', 1, 0, 0
  UNION ALL SELECT 'templates', 'HR Manager', 1, 1, 1
  UNION ALL SELECT 'activity_logs', 'HR Manager', 1, 1, 1

  UNION ALL SELECT 'dashboard', 'Accounts Manager', 1, 0, 0
  UNION ALL SELECT 'employees', 'Accounts Manager', 1, 0, 0
  UNION ALL SELECT 'user_management', 'Accounts Manager', 0, 0, 0
  UNION ALL SELECT 'settings', 'Accounts Manager', 1, 0, 0
  UNION ALL SELECT 'templates', 'Accounts Manager', 1, 0, 0
  UNION ALL SELECT 'activity_logs', 'Accounts Manager', 1, 0, 0

  UNION ALL SELECT 'dashboard', 'Employee', 1, 0, 0
  UNION ALL SELECT 'employees', 'Employee', 0, 0, 0
  UNION ALL SELECT 'user_management', 'Employee', 0, 0, 0
  UNION ALL SELECT 'settings', 'Employee', 0, 0, 0
  UNION ALL SELECT 'templates', 'Employee', 0, 0, 0
  UNION ALL SELECT 'activity_logs', 'Employee', 0, 0, 0
) perms ON perms.`role_name` = r.`name`
LEFT JOIN `role_permissions` rp
  ON rp.`role_id` = r.`id`
 AND rp.`module` = perms.`module`
WHERE rp.`id` IS NULL;

-- For any custom roles backfilled from users, ensure each module has a default denied row.
INSERT INTO `role_permissions` (`role_id`, `module`, `can_read`, `can_create`, `can_edit`, `created_at`, `updated_at`)
SELECT r.`id`, modules.`module`, 0, 0, 0, @now, @now
FROM `roles` r
JOIN (
  SELECT 'dashboard' AS `module`
  UNION ALL SELECT 'employees'
  UNION ALL SELECT 'user_management'
  UNION ALL SELECT 'settings'
  UNION ALL SELECT 'templates'
  UNION ALL SELECT 'activity_logs'
) modules
LEFT JOIN `role_permissions` rp
  ON rp.`role_id` = r.`id`
 AND rp.`module` = modules.`module`
WHERE rp.`id` IS NULL;

-- Mark both Laravel migrations as executed so future artisan runs stay in sync.
SET @next_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_03_09_000004_create_roles_table', @next_batch
WHERE NOT EXISTS (
  SELECT 1
  FROM `migrations`
  WHERE `migration` = '2026_03_09_000004_create_roles_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_03_09_000005_create_role_permissions_table', @next_batch
WHERE NOT EXISTS (
  SELECT 1
  FROM `migrations`
  WHERE `migration` = '2026_03_09_000005_create_role_permissions_table'
);

COMMIT;
