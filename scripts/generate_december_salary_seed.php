<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$workbookPath = $argv[1] ?? 'C:/Users/M. Saad/Downloads/December Salaries.xlsx';
$outputPath = $argv[2] ?? __DIR__ . '/../database/sql/2026_04_09_000011_december_salary_workbook_seed.sql';

if (! file_exists($workbookPath)) {
    fwrite(STDERR, "Workbook not found: {$workbookPath}" . PHP_EOL);
    exit(1);
}

$spreadsheet = IOFactory::load($workbookPath);

$employeeDetailsSheet = $spreadsheet->getSheetByName('Employee Details');
$adjustmentsSheet = $spreadsheet->getSheetByName('Adjustments');
$securitySheet = $spreadsheet->getSheetByName('Security');
$salariesSheet = $spreadsheet->getSheetByName('Salaries ');
$salarySlipSheet = $spreadsheet->getSheetByName('Salary Slip');

if (! $employeeDetailsSheet || ! $adjustmentsSheet || ! $securitySheet || ! $salariesSheet || ! $salarySlipSheet) {
    fwrite(STDERR, 'Workbook is missing one or more required payroll sheets.' . PHP_EOL);
    exit(1);
}

function resolveCellValue($sheet, string $coordinate): mixed
{
    $cell = $sheet->getCell($coordinate);

    try {
        $calculated = $cell->getCalculatedValue();
        if (! in_array($calculated, ['#NAME?', '#VALUE!'], true) && $calculated !== null && $calculated !== '') {
            return $calculated;
        }
    } catch (Throwable) {
    }

    $raw = $cell->getValue();

    if (! is_string($raw)) {
        return $raw;
    }

    $raw = trim($raw);

    if (preg_match('/^=IFERROR\((.*),\s*"((?:[^"]|"")*)"\)$/s', $raw, $matches)) {
        return str_replace('""', '"', $matches[2]);
    }

    if (preg_match('/^=IFERROR\((.*),\s*([-0-9.E]+)\)$/s', $raw, $matches)) {
        return $matches[2];
    }

    if (str_starts_with($raw, '=')) {
        return null;
    }

    return $raw;
}

function moneyValue(mixed $value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }

    if (is_numeric($value)) {
        return (float) $value;
    }

    $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

    return $clean === '' ? 0.0 : (float) $clean;
}

function stringValue(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    $normalized = trim((string) $value);

    return $normalized === '' ? null : $normalized;
}

function normalizeDigits(mixed $value, int $targetLength = 0, bool $prefixZeroForPhones = false): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_numeric($value)) {
        $normalized = sprintf('%.0f', (float) $value);
    } else {
        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        if (preg_match('/^[0-9.E+-]+$/i', $string)) {
            $normalized = sprintf('%.0f', (float) $string);
        } else {
            $normalized = preg_replace('/\D+/', '', $string) ?? '';
        }
    }

    if ($normalized === '') {
        return null;
    }

    if ($prefixZeroForPhones && strlen($normalized) === 10) {
        $normalized = '0' . $normalized;
    }

    if ($targetLength > 0 && strlen($normalized) < $targetLength) {
        $normalized = str_pad($normalized, $targetLength, '0', STR_PAD_LEFT);
    }

    return $normalized;
}

function sqlString(?string $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . str_replace(
        ["\\", "'"],
        ["\\\\", "\\'"],
        $value
    ) . "'";
}

function sqlNumber(float|int|null $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return number_format((float) $value, 2, '.', '');
}

function parseExcelDateValue(mixed $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_numeric($value)) {
        return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
    }

    return null;
}

$payPeriodMonth = parseExcelDateValue(resolveCellValue($salarySlipSheet, 'K2')) ?? '2025-12-01';
$paymentDate = parseExcelDateValue(resolveCellValue($salarySlipSheet, 'K3')) ?? '2026-01-01';
$emailSubject = 'Salary Slip for December 2025';
$emailBody = "Your salary for the month of December 2025 has been successfully deposited via bank transfer on 01-01-2026 .\nPlease find your salary slip attached for your reference.\n\nIf you have any questions or notice any discrepancies, feel free to reply to this email.\n\nBest Regards,\nMuhammad Junaid\nCodeAge Pvt Ltd";
$fiscalYearLabel = '2025-2026';

$employees = [];
$bankDirectory = [];

for ($row = 2; $row <= 26; $row++) {
    $employeeId = stringValue(resolveCellValue($employeeDetailsSheet, 'B' . $row));
    $fullName = stringValue(resolveCellValue($employeeDetailsSheet, 'C' . $row));

    if (! $employeeId || ! $fullName) {
        continue;
    }

    $designation = stringValue(resolveCellValue($employeeDetailsSheet, 'D' . $row));
    $email = stringValue(resolveCellValue($employeeDetailsSheet, 'E' . $row));
    $paymentMode = stringValue(resolveCellValue($employeeDetailsSheet, 'F' . $row));
    $bankName = stringValue(resolveCellValue($employeeDetailsSheet, 'G' . $row));
    $accountTitle = stringValue(resolveCellValue($employeeDetailsSheet, 'H' . $row));
    $iban = stringValue(resolveCellValue($employeeDetailsSheet, 'I' . $row));
    $currentSalary = moneyValue(resolveCellValue($employeeDetailsSheet, 'J' . $row));
    $lastIncrement = moneyValue(resolveCellValue($employeeDetailsSheet, 'K' . $row));

    $bankCode = stringValue(resolveCellValue($salariesSheet, 'G' . $row));
    $cnic = normalizeDigits(resolveCellValue($salariesSheet, 'C' . $row), 13);
    $contactNumber = normalizeDigits(resolveCellValue($salariesSheet, 'K' . $row), 11, true);
    $salaryEmail = stringValue(resolveCellValue($salariesSheet, 'L' . $row));

    $daysAbsent = (int) moneyValue(resolveCellValue($adjustmentsSheet, 'C' . $row));
    $shortHoursDays = (int) moneyValue(resolveCellValue($adjustmentsSheet, 'D' . $row));
    $incentivesBonus = moneyValue(resolveCellValue($adjustmentsSheet, 'F' . $row));
    $arrearsRaw = moneyValue(resolveCellValue($adjustmentsSheet, 'I' . $row));
    $otherRaw = moneyValue(resolveCellValue($adjustmentsSheet, 'J' . $row));

    $openingArrears = moneyValue(resolveCellValue($securitySheet, 'D' . $row));
    $julyAmount = moneyValue(resolveCellValue($securitySheet, 'E' . $row));
    $augustAmount = moneyValue(resolveCellValue($securitySheet, 'F' . $row));
    $septemberAmount = moneyValue(resolveCellValue($securitySheet, 'G' . $row));
    $octoberAmount = moneyValue(resolveCellValue($securitySheet, 'H' . $row));
    $novemberAmount = moneyValue(resolveCellValue($securitySheet, 'I' . $row));
    $decemberAmount = moneyValue(resolveCellValue($securitySheet, 'J' . $row));
    $januaryAmount = moneyValue(resolveCellValue($securitySheet, 'K' . $row));
    $februaryAmount = moneyValue(resolveCellValue($securitySheet, 'L' . $row));
    $marchAmount = moneyValue(resolveCellValue($securitySheet, 'M' . $row));
    $aprilAmount = moneyValue(resolveCellValue($securitySheet, 'N' . $row));
    $mayAmount = moneyValue(resolveCellValue($securitySheet, 'O' . $row));
    $juneAmount = moneyValue(resolveCellValue($securitySheet, 'P' . $row));
    $paidAmount = moneyValue(resolveCellValue($securitySheet, 'Q' . $row));
    $balanceInAccount = moneyValue(resolveCellValue($securitySheet, 'R' . $row));
    $securityDeduction = $balanceInAccount > 0 ? min(1000.0, $balanceInAccount) : 0.0;

    $nonPaidLeaveDeduction = $daysAbsent > 2 ? ($daysAbsent - 2) * 500.0 : 0.0;
    $punctualityBonus = $daysAbsent === 1 ? 500.0 : ($daysAbsent === 0 ? 1000.0 : 0.0);
    $attendancePenalty = 0.0;

    $positiveArrears = max(0.0, $arrearsRaw);
    $arrearsDeduction = abs(min(0.0, $arrearsRaw));
    $positiveOther = max(0.0, $otherRaw);
    $otherDeduction = abs(min(0.0, $otherRaw));

    $grossSalary = $currentSalary
        + $lastIncrement
        + $incentivesBonus
        + $punctualityBonus
        + $positiveArrears
        + $positiveOther
        - $securityDeduction
        - $nonPaidLeaveDeduction
        - $attendancePenalty
        - $arrearsDeduction
        - $otherDeduction;

    $incomeTax = $grossSalary <= 50000
        ? 0.0
        : ($grossSalary <= 100000
            ? ($grossSalary - 50000) * 0.01
            : (($grossSalary - 50000) * 0.11) + 6000);

    $netSalary = $grossSalary - $incomeTax;

    $effectiveEmail = $email ?: strtolower(str_replace([' ', '/'], ['.', '-'], $employeeId)) . '@imported.codeage.local';
    $hrComment = $email
        ? 'Imported from December Salaries.xlsx workbook on 2026-04-09.'
        : 'Imported from December Salaries.xlsx workbook on 2026-04-09. Placeholder email assigned because the workbook email field was blank.';

    $employees[] = [
        'employee_id' => $employeeId,
        'full_name' => $fullName,
        'designation' => $designation ?: 'Not assigned',
        'email' => $effectiveEmail,
        'source_email' => $salaryEmail ?: $email,
        'phone' => $contactNumber,
        'cnic' => $cnic,
        'gender' => null,
        'status' => 'active',
        'department_name' => 'Unassigned',
        'payment_mode' => $paymentMode ?: ($bankName ? 'Bank Transfer' : null),
        'bank_code' => $bankCode,
        'bank_name' => $bankName,
        'bank_account_title' => $accountTitle,
        'iban' => $iban,
        'current_salary' => $currentSalary,
        'last_increment' => $lastIncrement,
        'payroll_status' => 'Paid',
        'hr_comments' => $hrComment,
        'payroll_record' => [
            'days_absent' => $daysAbsent,
            'short_hours_days' => $shortHoursDays,
            'basic_salary' => $currentSalary,
            'last_increment' => $lastIncrement,
            'incentives_bonus' => $incentivesBonus,
            'punctuality_bonus' => $punctualityBonus,
            'positive_arrears' => $positiveArrears,
            'positive_other' => $positiveOther,
            'security_deduction' => $securityDeduction,
            'non_paid_leave_deduction' => $nonPaidLeaveDeduction,
            'attendance_penalty' => $attendancePenalty,
            'arrears_deduction' => $arrearsDeduction,
            'other_deduction' => $otherDeduction,
            'gross_salary' => $grossSalary,
            'income_tax' => $incomeTax,
            'net_salary' => $netSalary,
        ],
        'security_snapshot' => [
            'opening_arrears' => $openingArrears,
            'july_amount' => $julyAmount,
            'august_amount' => $augustAmount,
            'september_amount' => $septemberAmount,
            'october_amount' => $octoberAmount,
            'november_amount' => $novemberAmount,
            'december_amount' => $decemberAmount,
            'january_amount' => $januaryAmount,
            'february_amount' => $februaryAmount,
            'march_amount' => $marchAmount,
            'april_amount' => $aprilAmount,
            'may_amount' => $mayAmount,
            'june_amount' => $juneAmount,
            'paid_amount' => $paidAmount,
            'balance_in_account' => $balanceInAccount,
            'remarks' => null,
        ],
    ];

    if ($bankCode && $bankName) {
        $bankDirectory[$bankCode] = $bankName;
    }
}

usort($employees, fn (array $left, array $right) => strcmp($left['employee_id'], $right['employee_id']));
ksort($bankDirectory);

$employeeCounter = 0;
foreach ($employees as $employee) {
    if (preg_match('/^CA-E-(\d+)$/', $employee['employee_id'], $matches)) {
        $employeeCounter = max($employeeCounter, (int) $matches[1]);
    }
}

$lines = [];
$lines[] = '-- December 2025 payroll workbook seed for CodeAge Pvt. Ltd.';
$lines[] = '-- Generated from December Salaries.xlsx on 2026-04-09.';
$lines[] = 'SET NAMES utf8mb4;';
$lines[] = '';
$lines[] = '-- Schema alignment for payroll fields and tables.';
$lines[] = "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `payment_mode` varchar(255) NULL AFTER `payroll_status`;";
$lines[] = "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `bank_code` varchar(20) NULL AFTER `bank_name`;";
$lines[] = "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `current_salary` decimal(12,2) NULL AFTER `iban`;";
$lines[] = "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `last_increment` decimal(12,2) NULL AFTER `current_salary`;";
$lines[] = '';
$lines[] = "CREATE TABLE IF NOT EXISTS `payroll_runs` (";
$lines[] = "  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,";
$lines[] = "  `name` varchar(255) NOT NULL,";
$lines[] = "  `pay_period_month` date NOT NULL,";
$lines[] = "  `payment_date` date DEFAULT NULL,";
$lines[] = "  `email_subject` varchar(255) DEFAULT NULL,";
$lines[] = "  `email_body` text DEFAULT NULL,";
$lines[] = "  `source_workbook` varchar(255) DEFAULT NULL,";
$lines[] = "  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,";
$lines[] = "  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,";
$lines[] = "  PRIMARY KEY (`id`),";
$lines[] = "  UNIQUE KEY `payroll_runs_pay_period_month_unique` (`pay_period_month`)";
$lines[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$lines[] = '';
$lines[] = "CREATE TABLE IF NOT EXISTS `employee_payroll_records` (";
$lines[] = "  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,";
$lines[] = "  `payroll_run_id` bigint(20) unsigned NOT NULL,";
$lines[] = "  `employee_id` bigint(20) unsigned NOT NULL,";
$lines[] = "  `bank_code` varchar(20) DEFAULT NULL,";
$lines[] = "  `beneficiary_name` varchar(255) DEFAULT NULL,";
$lines[] = "  `beneficiary_account_no` varchar(255) DEFAULT NULL,";
$lines[] = "  `contact_number` varchar(255) DEFAULT NULL,";
$lines[] = "  `email_address` varchar(255) DEFAULT NULL,";
$lines[] = "  `days_absent` int unsigned NOT NULL DEFAULT 0,";
$lines[] = "  `short_hours_days` int unsigned NOT NULL DEFAULT 0,";
$lines[] = "  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `last_increment` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `incentives_bonus` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `punctuality_bonus` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `positive_arrears` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `positive_other` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `security_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `non_paid_leave_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `attendance_penalty` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `arrears_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `other_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `gross_salary` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `income_tax` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `net_salary` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,";
$lines[] = "  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,";
$lines[] = "  PRIMARY KEY (`id`),";
$lines[] = "  UNIQUE KEY `employee_payroll_records_payroll_run_id_employee_id_unique` (`payroll_run_id`, `employee_id`)";
$lines[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$lines[] = '';
$lines[] = "CREATE TABLE IF NOT EXISTS `employee_security_fund_snapshots` (";
$lines[] = "  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,";
$lines[] = "  `employee_id` bigint(20) unsigned NOT NULL,";
$lines[] = "  `fiscal_year_label` varchar(20) NOT NULL,";
$lines[] = "  `snapshot_month` date NOT NULL,";
$lines[] = "  `opening_arrears` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `july_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `august_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `september_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `october_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `november_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `december_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `january_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `february_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `march_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `april_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `may_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `june_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `balance_in_account` decimal(12,2) NOT NULL DEFAULT 0.00,";
$lines[] = "  `remarks` text DEFAULT NULL,";
$lines[] = "  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,";
$lines[] = "  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,";
$lines[] = "  PRIMARY KEY (`id`),";
$lines[] = "  UNIQUE KEY `security_snapshot_unique` (`employee_id`, `fiscal_year_label`, `snapshot_month`)";
$lines[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$lines[] = '';
$lines[] = '-- Migration bookkeeping.';
$lines[] = 'SET @next_batch := (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`);';
$lines[] = "INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES";
$lines[] = "('2026_04_09_000011_add_payroll_fields_to_employees_table', @next_batch),";
$lines[] = "('2026_04_09_000012_create_payroll_runs_table', @next_batch),";
$lines[] = "('2026_04_09_000013_create_employee_payroll_records_table', @next_batch),";
$lines[] = "('2026_04_09_000014_create_employee_security_fund_snapshots_table', @next_batch);";
$lines[] = '';
$lines[] = '-- Base reference data.';
$lines[] = "INSERT INTO `departments` (`name`, `total_employees`, `created_at`, `updated_at`)";
$lines[] = "SELECT 'Unassigned', 0, NOW(), NOW()";
$lines[] = "WHERE NOT EXISTS (SELECT 1 FROM `departments` WHERE `name` = 'Unassigned');";
$lines[] = '';

foreach ($bankDirectory as $bankCode => $bankName) {
    $lines[] = "INSERT INTO `banks` (`name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES ("
        . sqlString($bankName) . ', '
        . sqlString($bankCode) . ", 1, NOW(), NOW())";
    $lines[] = "ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `is_active` = 1, `updated_at` = NOW();";
}

$lines[] = '';
$lines[] = '-- Employee ID configuration stays aligned with the imported workbook.';
$lines[] = "INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`) VALUES ('employee_id_prefix', 'CA-E-', NOW(), NOW())";
$lines[] = "ON DUPLICATE KEY UPDATE `value` = CASE WHEN `value` IS NULL OR `value` = '' THEN 'CA-E-' ELSE `value` END, `updated_at` = NOW();";
$lines[] = "INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`) VALUES ('employee_id_counter', '{$employeeCounter}', NOW(), NOW())";
$lines[] = "ON DUPLICATE KEY UPDATE `value` = CASE WHEN CAST(`value` AS UNSIGNED) < {$employeeCounter} THEN '{$employeeCounter}' ELSE `value` END, `updated_at` = NOW();";
$lines[] = '';
$lines[] = '-- Payroll run metadata for December 2025.';
$lines[] = "INSERT INTO `payroll_runs` (`name`, `pay_period_month`, `payment_date`, `email_subject`, `email_body`, `source_workbook`, `created_at`, `updated_at`) VALUES ("
    . sqlString('December 2025 Payroll') . ', '
    . sqlString($payPeriodMonth) . ', '
    . sqlString($paymentDate) . ', '
    . sqlString($emailSubject) . ', '
    . sqlString($emailBody) . ', '
    . sqlString(basename($workbookPath)) . ", NOW(), NOW())";
$lines[] = "ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `payment_date` = VALUES(`payment_date`), `email_subject` = VALUES(`email_subject`), `email_body` = VALUES(`email_body`), `source_workbook` = VALUES(`source_workbook`), `updated_at` = NOW();";
$lines[] = '';
$lines[] = '-- Employee master upserts.';

foreach ($employees as $employee) {
    $lines[] = "INSERT INTO `employees` ("
        . "`full_name`, `email`, `employee_id`, `designation`, `department_id`, `status`, `hiring_date`, `cnic`, `phone`, `gender`, `payroll_status`, `payment_mode`, `bank_name`, `bank_code`, `bank_account_title`, `iban`, `bank_id`, `current_salary`, `last_increment`, `hr_comments`, `created_at`, `updated_at`"
        . ") VALUES ("
        . sqlString($employee['full_name']) . ', '
        . sqlString($employee['email']) . ', '
        . sqlString($employee['employee_id']) . ', '
        . sqlString($employee['designation']) . ", "
        . "(SELECT `id` FROM `departments` WHERE `name` = 'Unassigned' ORDER BY `id` ASC LIMIT 1), "
        . sqlString($employee['status']) . ', '
        . 'NULL, '
        . sqlString($employee['cnic']) . ', '
        . sqlString($employee['phone']) . ', '
        . 'NULL, '
        . sqlString($employee['payroll_status']) . ', '
        . sqlString($employee['payment_mode']) . ', '
        . sqlString($employee['bank_name']) . ', '
        . sqlString($employee['bank_code']) . ', '
        . sqlString($employee['bank_account_title']) . ', '
        . sqlString($employee['iban']) . ', '
        . ($employee['bank_code']
            ? "(SELECT `id` FROM `banks` WHERE `code` = " . sqlString($employee['bank_code']) . ' LIMIT 1)'
            : 'NULL')
        . ', '
        . sqlNumber($employee['current_salary']) . ', '
        . sqlNumber($employee['last_increment']) . ', '
        . sqlString($employee['hr_comments']) . ", NOW(), NOW())";
    $lines[] = "ON DUPLICATE KEY UPDATE "
        . "`full_name` = VALUES(`full_name`), "
        . "`designation` = VALUES(`designation`), "
        . "`department_id` = VALUES(`department_id`), "
        . "`status` = VALUES(`status`), "
        . "`cnic` = VALUES(`cnic`), "
        . "`phone` = VALUES(`phone`), "
        . "`payroll_status` = VALUES(`payroll_status`), "
        . "`payment_mode` = VALUES(`payment_mode`), "
        . "`bank_name` = VALUES(`bank_name`), "
        . "`bank_code` = VALUES(`bank_code`), "
        . "`bank_account_title` = VALUES(`bank_account_title`), "
        . "`iban` = VALUES(`iban`), "
        . "`bank_id` = VALUES(`bank_id`), "
        . "`current_salary` = VALUES(`current_salary`), "
        . "`last_increment` = VALUES(`last_increment`), "
        . "`hr_comments` = VALUES(`hr_comments`), "
        . "`updated_at` = NOW();";
}

$lines[] = '';
$lines[] = '-- Security fund snapshots from the workbook.';

foreach ($employees as $employee) {
    $snapshot = $employee['security_snapshot'];

    $lines[] = "INSERT INTO `employee_security_fund_snapshots` ("
        . "`employee_id`, `fiscal_year_label`, `snapshot_month`, `opening_arrears`, `july_amount`, `august_amount`, `september_amount`, `october_amount`, `november_amount`, `december_amount`, `january_amount`, `february_amount`, `march_amount`, `april_amount`, `may_amount`, `june_amount`, `paid_amount`, `balance_in_account`, `remarks`, `created_at`, `updated_at`"
        . ") VALUES ("
        . "(SELECT `id` FROM `employees` WHERE `employee_id` = " . sqlString($employee['employee_id']) . ' LIMIT 1), '
        . sqlString($fiscalYearLabel) . ', '
        . sqlString($payPeriodMonth) . ', '
        . sqlNumber($snapshot['opening_arrears']) . ', '
        . sqlNumber($snapshot['july_amount']) . ', '
        . sqlNumber($snapshot['august_amount']) . ', '
        . sqlNumber($snapshot['september_amount']) . ', '
        . sqlNumber($snapshot['october_amount']) . ', '
        . sqlNumber($snapshot['november_amount']) . ', '
        . sqlNumber($snapshot['december_amount']) . ', '
        . sqlNumber($snapshot['january_amount']) . ', '
        . sqlNumber($snapshot['february_amount']) . ', '
        . sqlNumber($snapshot['march_amount']) . ', '
        . sqlNumber($snapshot['april_amount']) . ', '
        . sqlNumber($snapshot['may_amount']) . ', '
        . sqlNumber($snapshot['june_amount']) . ', '
        . sqlNumber($snapshot['paid_amount']) . ', '
        . sqlNumber($snapshot['balance_in_account']) . ', '
        . sqlString($snapshot['remarks']) . ", NOW(), NOW())";
    $lines[] = "ON DUPLICATE KEY UPDATE "
        . "`opening_arrears` = VALUES(`opening_arrears`), "
        . "`july_amount` = VALUES(`july_amount`), "
        . "`august_amount` = VALUES(`august_amount`), "
        . "`september_amount` = VALUES(`september_amount`), "
        . "`october_amount` = VALUES(`october_amount`), "
        . "`november_amount` = VALUES(`november_amount`), "
        . "`december_amount` = VALUES(`december_amount`), "
        . "`january_amount` = VALUES(`january_amount`), "
        . "`february_amount` = VALUES(`february_amount`), "
        . "`march_amount` = VALUES(`march_amount`), "
        . "`april_amount` = VALUES(`april_amount`), "
        . "`may_amount` = VALUES(`may_amount`), "
        . "`june_amount` = VALUES(`june_amount`), "
        . "`paid_amount` = VALUES(`paid_amount`), "
        . "`balance_in_account` = VALUES(`balance_in_account`), "
        . "`remarks` = VALUES(`remarks`), "
        . "`updated_at` = NOW();";
}

$lines[] = '';
$lines[] = '-- Employee payroll results for December 2025.';

foreach ($employees as $employee) {
    $record = $employee['payroll_record'];

    $lines[] = "INSERT INTO `employee_payroll_records` ("
        . "`payroll_run_id`, `employee_id`, `bank_code`, `beneficiary_name`, `beneficiary_account_no`, `contact_number`, `email_address`, `days_absent`, `short_hours_days`, `basic_salary`, `last_increment`, `incentives_bonus`, `punctuality_bonus`, `positive_arrears`, `positive_other`, `security_deduction`, `non_paid_leave_deduction`, `attendance_penalty`, `arrears_deduction`, `other_deduction`, `gross_salary`, `income_tax`, `net_salary`, `created_at`, `updated_at`"
        . ") VALUES ("
        . "(SELECT `id` FROM `payroll_runs` WHERE `pay_period_month` = " . sqlString($payPeriodMonth) . ' LIMIT 1), '
        . "(SELECT `id` FROM `employees` WHERE `employee_id` = " . sqlString($employee['employee_id']) . ' LIMIT 1), '
        . sqlString($employee['bank_code']) . ', '
        . sqlString($employee['bank_account_title']) . ', '
        . sqlString($employee['iban']) . ', '
        . sqlString($employee['phone']) . ', '
        . sqlString($employee['source_email']) . ', '
        . (int) $record['days_absent'] . ', '
        . (int) $record['short_hours_days'] . ', '
        . sqlNumber($record['basic_salary']) . ', '
        . sqlNumber($record['last_increment']) . ', '
        . sqlNumber($record['incentives_bonus']) . ', '
        . sqlNumber($record['punctuality_bonus']) . ', '
        . sqlNumber($record['positive_arrears']) . ', '
        . sqlNumber($record['positive_other']) . ', '
        . sqlNumber($record['security_deduction']) . ', '
        . sqlNumber($record['non_paid_leave_deduction']) . ', '
        . sqlNumber($record['attendance_penalty']) . ', '
        . sqlNumber($record['arrears_deduction']) . ', '
        . sqlNumber($record['other_deduction']) . ', '
        . sqlNumber($record['gross_salary']) . ', '
        . sqlNumber($record['income_tax']) . ', '
        . sqlNumber($record['net_salary']) . ", NOW(), NOW())";
    $lines[] = "ON DUPLICATE KEY UPDATE "
        . "`bank_code` = VALUES(`bank_code`), "
        . "`beneficiary_name` = VALUES(`beneficiary_name`), "
        . "`beneficiary_account_no` = VALUES(`beneficiary_account_no`), "
        . "`contact_number` = VALUES(`contact_number`), "
        . "`email_address` = VALUES(`email_address`), "
        . "`days_absent` = VALUES(`days_absent`), "
        . "`short_hours_days` = VALUES(`short_hours_days`), "
        . "`basic_salary` = VALUES(`basic_salary`), "
        . "`last_increment` = VALUES(`last_increment`), "
        . "`incentives_bonus` = VALUES(`incentives_bonus`), "
        . "`punctuality_bonus` = VALUES(`punctuality_bonus`), "
        . "`positive_arrears` = VALUES(`positive_arrears`), "
        . "`positive_other` = VALUES(`positive_other`), "
        . "`security_deduction` = VALUES(`security_deduction`), "
        . "`non_paid_leave_deduction` = VALUES(`non_paid_leave_deduction`), "
        . "`attendance_penalty` = VALUES(`attendance_penalty`), "
        . "`arrears_deduction` = VALUES(`arrears_deduction`), "
        . "`other_deduction` = VALUES(`other_deduction`), "
        . "`gross_salary` = VALUES(`gross_salary`), "
        . "`income_tax` = VALUES(`income_tax`), "
        . "`net_salary` = VALUES(`net_salary`), "
        . "`updated_at` = NOW();";
}

$lines[] = '';
$lines[] = '-- Keep cached department totals aligned after import.';
$lines[] = "UPDATE `departments` d SET d.`total_employees` = (SELECT COUNT(*) FROM `employees` e WHERE e.`department_id` = d.`id`);";

file_put_contents($outputPath, implode(PHP_EOL, $lines) . PHP_EOL);

echo 'Generated ' . count($employees) . ' employees into ' . $outputPath . PHP_EOL;
