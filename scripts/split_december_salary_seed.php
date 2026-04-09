<?php

declare(strict_types=1);

$inputPath = $argv[1] ?? __DIR__ . '/../database/sql/2026_04_09_000011_december_salary_workbook_seed.sql';
$employeeOutputPath = $argv[2] ?? __DIR__ . '/../database/sql/2026_04_09_000011_employee_master_seed.sql';
$payrollOutputPath = $argv[3] ?? __DIR__ . '/../database/sql/2026_04_09_000012_december_payroll_seed.sql';

if (! file_exists($inputPath)) {
    fwrite(STDERR, "Combined seed file not found: {$inputPath}" . PHP_EOL);
    exit(1);
}

$sql = file_get_contents($inputPath);

if ($sql === false) {
    fwrite(STDERR, "Unable to read combined seed file: {$inputPath}" . PHP_EOL);
    exit(1);
}

function extractSection(string $sql, string $startMarker, string $endMarker): string
{
    $start = strpos($sql, $startMarker);

    if ($start === false) {
        return '';
    }

    $end = strpos($sql, $endMarker, $start);

    if ($end === false) {
        return trim(substr($sql, $start));
    }

    return trim(substr($sql, $start, $end - $start));
}

function splitSqlValues(string $valueList): array
{
    $values = [];
    $current = '';
    $inString = false;
    $escapeNext = false;
    $parenDepth = 0;
    $length = strlen($valueList);

    for ($index = 0; $index < $length; $index++) {
        $char = $valueList[$index];

        if ($inString) {
            $current .= $char;

            if ($escapeNext) {
                $escapeNext = false;
                continue;
            }

            if ($char === '\\') {
                $escapeNext = true;
                continue;
            }

            if ($char === "'") {
                $inString = false;
            }

            continue;
        }

        if ($char === "'") {
            $inString = true;
            $current .= $char;
            continue;
        }

        if ($char === '(') {
            $parenDepth++;
            $current .= $char;
            continue;
        }

        if ($char === ')') {
            if ($parenDepth > 0) {
                $parenDepth--;
            }

            $current .= $char;
            continue;
        }

        if ($char === ',' && $parenDepth === 0) {
            $values[] = trim($current);
            $current = '';
            continue;
        }

        $current .= $char;
    }

    if (trim($current) !== '') {
        $values[] = trim($current);
    }

    return $values;
}

function normalizeEmployeeMasterSection(string $section): string
{
    $lines = preg_split('/\R/', trim($section)) ?: [];
    $normalized = ['-- Employee master upserts.'];

    for ($index = 1; $index < count($lines); $index++) {
        $line = trim($lines[$index]);

        if (! str_starts_with($line, 'INSERT INTO `employees`')) {
            continue;
        }

        if (! preg_match('/VALUES \((.*)\)$/', $line, $matches)) {
            continue;
        }

        $values = splitSqlValues($matches[1]);

        if (count($values) !== 22) {
            continue;
        }

        $selectedValues = [
            $values[0],
            $values[1],
            $values[2],
            $values[3],
            $values[4],
            $values[5],
            $values[6],
            $values[7],
            $values[8],
            $values[9],
            $values[10],
            $values[19],
            $values[20],
            $values[21],
        ];

        $normalized[] = "INSERT INTO `employees` (`full_name`, `email`, `employee_id`, `designation`, `department_id`, `status`, `hiring_date`, `cnic`, `phone`, `gender`, `payroll_status`, `hr_comments`, `created_at`, `updated_at`) VALUES (" . implode(', ', $selectedValues) . ')';
        $normalized[] = "ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`), `designation` = VALUES(`designation`), `department_id` = VALUES(`department_id`), `status` = VALUES(`status`), `cnic` = VALUES(`cnic`), `phone` = VALUES(`phone`), `payroll_status` = VALUES(`payroll_status`), `hr_comments` = VALUES(`hr_comments`), `updated_at` = NOW();";
    }

    return implode(PHP_EOL, $normalized);
}

$baseReferenceSection = extractSection(
    $sql,
    '-- Base reference data.',
    "INSERT INTO `banks`"
);

$employeeCounterSection = extractSection(
    $sql,
    '-- Employee ID configuration stays aligned with the imported workbook.',
    '-- Payroll run metadata for December 2025.'
);

$employeeMasterSection = extractSection(
    $sql,
    '-- Employee master upserts.',
    '-- Security fund snapshots from the workbook.'
);

$employeeMasterSection = normalizeEmployeeMasterSection($employeeMasterSection);

$departmentTotalsSection = extractSection(
    $sql,
    '-- Keep cached department totals aligned after import.',
    ''
);

$employeeLines = [
    '-- Employee master seed for CodeAge Pvt. Ltd.',
    '-- Generated from December Salaries.xlsx on 2026-04-09.',
    '-- Import this first so attendance can bind to existing employee IDs.',
    'SET NAMES utf8mb4;',
    '',
    trim($baseReferenceSection),
    '',
    trim($employeeCounterSection),
    '',
    trim($employeeMasterSection),
    '',
    trim($departmentTotalsSection),
    '',
];

$payrollSql = preg_replace(
    '/^-- December 2025 payroll workbook seed for CodeAge Pvt\. Ltd\.\R-- Generated from December Salaries\.xlsx on 2026-04-09\.\R/m',
    "-- December 2025 payroll seed for CodeAge Pvt. Ltd.\r\n-- Import this after 2026_04_09_000011_employee_master_seed.sql.\r\n",
    $sql,
    1
);

if ($payrollSql === null) {
    fwrite(STDERR, 'Unable to prepare payroll seed output.' . PHP_EOL);
    exit(1);
}

file_put_contents($employeeOutputPath, implode(PHP_EOL, $employeeLines));
file_put_contents($payrollOutputPath, $payrollSql);

echo "Generated employee master seed: {$employeeOutputPath}" . PHP_EOL;
echo "Generated payroll seed: {$payrollOutputPath}" . PHP_EOL;
