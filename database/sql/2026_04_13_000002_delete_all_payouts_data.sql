-- Delete all payout data for CodeAge Pvt. Ltd ERP.
-- This removes generated payout runs, payout records, and saved payout adjustments.
-- It does NOT delete employees, attendance, salary setup fields, or security fund snapshots.

SET NAMES utf8mb4;

SET @delete_payroll_records_sql = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = 'employee_payroll_records'
        ),
        'DELETE FROM `employee_payroll_records`',
        'SELECT ''employee_payroll_records table not found'' AS message'
    )
);
PREPARE delete_payroll_records_stmt FROM @delete_payroll_records_sql;
EXECUTE delete_payroll_records_stmt;
DEALLOCATE PREPARE delete_payroll_records_stmt;

SET @delete_payroll_runs_sql = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = 'payroll_runs'
        ),
        'DELETE FROM `payroll_runs`',
        'SELECT ''payroll_runs table not found'' AS message'
    )
);
PREPARE delete_payroll_runs_stmt FROM @delete_payroll_runs_sql;
EXECUTE delete_payroll_runs_stmt;
DEALLOCATE PREPARE delete_payroll_runs_stmt;

SET @delete_payroll_adjustments_sql = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = 'employee_payroll_adjustments'
        ),
        'DELETE FROM `employee_payroll_adjustments`',
        'SELECT ''employee_payroll_adjustments table not found'' AS message'
    )
);
PREPARE delete_payroll_adjustments_stmt FROM @delete_payroll_adjustments_sql;
EXECUTE delete_payroll_adjustments_stmt;
DEALLOCATE PREPARE delete_payroll_adjustments_stmt;

SET @reset_payroll_records_ai_sql = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = 'employee_payroll_records'
        ),
        'ALTER TABLE `employee_payroll_records` AUTO_INCREMENT = 1',
        'SELECT ''skip employee_payroll_records auto_increment reset'' AS message'
    )
);
PREPARE reset_payroll_records_ai_stmt FROM @reset_payroll_records_ai_sql;
EXECUTE reset_payroll_records_ai_stmt;
DEALLOCATE PREPARE reset_payroll_records_ai_stmt;

SET @reset_payroll_runs_ai_sql = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = 'payroll_runs'
        ),
        'ALTER TABLE `payroll_runs` AUTO_INCREMENT = 1',
        'SELECT ''skip payroll_runs auto_increment reset'' AS message'
    )
);
PREPARE reset_payroll_runs_ai_stmt FROM @reset_payroll_runs_ai_sql;
EXECUTE reset_payroll_runs_ai_stmt;
DEALLOCATE PREPARE reset_payroll_runs_ai_stmt;

SET @reset_payroll_adjustments_ai_sql = (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = 'employee_payroll_adjustments'
        ),
        'ALTER TABLE `employee_payroll_adjustments` AUTO_INCREMENT = 1',
        'SELECT ''skip employee_payroll_adjustments auto_increment reset'' AS message'
    )
);
PREPARE reset_payroll_adjustments_ai_stmt FROM @reset_payroll_adjustments_ai_sql;
EXECUTE reset_payroll_adjustments_ai_stmt;
DEALLOCATE PREPARE reset_payroll_adjustments_ai_stmt;

SELECT 'All payout data deleted successfully.' AS message;
