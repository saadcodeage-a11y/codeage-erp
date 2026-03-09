-- CodeAge ERP
-- Production SQL for employee employment history timeline
-- Compatible with MariaDB 10.4 / MySQL

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `employee_employment_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `payroll_status` varchar(255) DEFAULT NULL,
  `job_location` varchar(255) DEFAULT NULL,
  `employment_status` varchar(255) DEFAULT NULL,
  `effective_from` datetime NOT NULL,
  `effective_to` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_employment_histories_department_id_foreign` (`department_id`),
  KEY `employee_employment_histories_employee_id_effective_from_index` (`employee_id`,`effective_from`),
  CONSTRAINT `employee_employment_histories_department_id_foreign`
    FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_employment_histories_employee_id_foreign`
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill one baseline history row for existing employees that do not have one yet.
INSERT INTO `employee_employment_histories` (
  `employee_id`,
  `department_id`,
  `designation`,
  `payroll_status`,
  `job_location`,
  `employment_status`,
  `effective_from`,
  `effective_to`,
  `created_at`,
  `updated_at`
)
SELECT
  e.`id`,
  e.`department_id`,
  e.`designation`,
  e.`payroll_status`,
  e.`job_location`,
  e.`status`,
  COALESCE(CAST(e.`hiring_date` AS DATETIME), e.`created_at`, NOW()),
  NULL,
  NOW(),
  NOW()
FROM `employees` e
LEFT JOIN `employee_employment_histories` h
  ON h.`employee_id` = e.`id`
WHERE h.`id` IS NULL;

-- Mark both Laravel migrations as executed so future artisan runs stay in sync.
SET @next_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_03_09_000001_create_employee_employment_histories_table', @next_batch
WHERE NOT EXISTS (
  SELECT 1
  FROM `migrations`
  WHERE `migration` = '2026_03_09_000001_create_employee_employment_histories_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_03_09_000002_fix_employee_employment_histories_effective_columns', @next_batch
WHERE NOT EXISTS (
  SELECT 1
  FROM `migrations`
  WHERE `migration` = '2026_03_09_000002_fix_employee_employment_histories_effective_columns'
);

COMMIT;