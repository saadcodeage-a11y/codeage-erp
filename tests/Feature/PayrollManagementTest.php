<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePayrollAdjustment;
use App\Models\EmployeePayrollRecord;
use App\Models\EmployeeSecurityFundSnapshot;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
        ]);
    }

    protected function createPayrollEmployee(array $attributes = []): Employee
    {
        $department = Department::firstOrCreate(['name' => 'Operations']);

        return Employee::create(array_merge([
            'full_name' => 'Payroll Employee',
            'email' => 'payroll-' . uniqid() . '@example.com',
            'status' => 'active',
            'department_id' => $department->id,
            'designation' => 'Executive',
            'employee_id' => 'CA-E-' . random_int(100, 999),
            'current_salary' => 50000,
            'last_increment' => 10000,
            'payment_mode' => 'Bank Transfer',
            'bank_code' => 'MBL',
            'bank_account_title' => 'Payroll Employee',
            'iban' => 'PK00TEST1234567890',
            'phone' => '03001234567',
        ], $attributes));
    }

    public function test_accounts_manager_can_review_payroll_but_hr_manager_cannot_generate_runs(): void
    {
        $employee = $this->createPayrollEmployee(['employee_id' => 'CA-E-301']);
        $accountsUser = $this->createUser('Accounts Manager');
        $hrUser = $this->createUser('HR Manager');

        $this->actingAs($accountsUser)
            ->get(route('payroll.index', ['month' => '2026-03']))
            ->assertOk()
            ->assertSee('Payroll');

        $this->actingAs($hrUser)
            ->get(route('payroll.index', ['month' => '2026-03']))
            ->assertOk();

        $this->actingAs($hrUser)
            ->post(route('payroll.generate'), [
                'month' => '2026-03',
            ])
            ->assertForbidden();
    }

    public function test_can_save_adjustments_and_generate_payroll_run_from_attendance_and_security(): void
    {
        $accountsUser = $this->createUser('Accounts Manager');
        $employee = $this->createPayrollEmployee([
            'employee_id' => 'CA-E-302',
            'current_salary' => 50000,
            'last_increment' => 10000,
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-03',
            'status' => 'absent',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-10',
            'status' => 'absent',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-17',
            'status' => 'absent',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-25',
            'clock_in' => '09:00:00',
            'clock_out' => '16:00:00',
            'work_duration' => '07:00',
            'status' => 'present',
        ]);

        EmployeeSecurityFundSnapshot::create([
            'employee_id' => $employee->id,
            'fiscal_year_label' => 'FY 2025-26',
            'snapshot_month' => '2026-03-01',
            'opening_arrears' => 1000,
            'march_amount' => 1000,
            'balance_in_account' => 5000,
        ]);

        $this->actingAs($accountsUser)->post(route('payroll.adjustments.update'), [
            'month' => '2026-03',
            'adjustments' => [
                $employee->id => [
                    'incentives_bonus' => 1000,
                    'punctuality_bonus' => 0,
                    'security_deduction' => 1500,
                    'attendance_penalty' => 0,
                    'arrears_adjustment' => -500,
                    'other_adjustment' => 250,
                    'remarks' => 'Month-end adjustments',
                ],
            ],
        ])->assertRedirect(route('payroll.index', ['month' => '2026-03']));

        $this->assertTrue(
            EmployeePayrollAdjustment::query()
                ->where('employee_id', $employee->id)
                ->whereDate('adjustment_month', '2026-03-01')
                ->where('incentives_bonus', 1000)
                ->where('security_deduction', 1500)
                ->where('arrears_adjustment', -500)
                ->where('other_adjustment', 250)
                ->exists()
        );

        $this->actingAs($accountsUser)->post(route('payroll.generate'), [
            'month' => '2026-03',
            'payment_date' => '2026-04-01',
            'notes' => 'Calculated from attendance and adjustments',
        ])->assertRedirect();

        $payrollRun = PayrollRun::whereDate('pay_period_month', '2026-03-01')->firstOrFail();
        $record = EmployeePayrollRecord::where('payroll_run_id', $payrollRun->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $this->assertSame('draft', $payrollRun->status);
        $this->assertSame(3, $record->days_absent);
        $this->assertSame(0, $record->late_count);
        $this->assertSame(0, $record->late_absent_equivalent);
        $this->assertSame(3, $record->unpaid_leave_days);
        $this->assertSame(1, $record->short_hours_days);
        $this->assertSame('1500.00', $record->security_deduction);
        $this->assertSame('5500.00', $record->security_total_deducted);
        $this->assertSame('5925.00', $record->non_paid_leave_deduction);
        $this->assertSame('50000.00', $record->basic_salary);
        $this->assertSame('10000.00', $record->last_increment);
        $this->assertSame('1000.00', $record->incentives_bonus);
        $this->assertSame('250.00', $record->positive_other);
        $this->assertSame('500.00', $record->arrears_deduction);
        $this->assertSame('53325.00', $record->gross_salary);
        $this->assertSame('83.13', $record->income_tax);
        $this->assertSame('83.13', $record->annual_tax_total);
        $this->assertSame('53241.87', $record->net_salary);
    }

    public function test_can_autosave_single_employee_payroll_adjustment(): void
    {
        $accountsUser = $this->createUser('Accounts Manager');
        $employee = $this->createPayrollEmployee([
            'employee_id' => 'CA-E-304',
            'current_salary' => 45000,
            'last_increment' => 5000,
        ]);

        $response = $this->actingAs($accountsUser)->postJson(route('payroll.adjustments.autosave'), [
            'month' => '2026-03',
            'employee_id' => $employee->id,
            'adjustment' => [
                'incentives_bonus' => 1500,
                'punctuality_bonus' => 500,
                'security_deduction' => 750,
                'attendance_penalty' => 250,
                'arrears_adjustment' => -100,
                'other_adjustment' => 200,
                'remarks' => 'Autosaved from payroll card',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertTrue(
            EmployeePayrollAdjustment::query()
                ->where('employee_id', $employee->id)
                ->whereDate('adjustment_month', '2026-03-01')
                ->where('incentives_bonus', 1500)
                ->where('punctuality_bonus', 500)
                ->where('security_deduction', 750)
                ->where('attendance_penalty', 250)
                ->where('arrears_adjustment', -100)
                ->where('other_adjustment', 200)
                ->exists()
        );
    }

    public function test_can_regenerate_existing_draft_payout_before_finalizing(): void
    {
        $accountsUser = $this->createUser('Accounts Manager');
        $employee = $this->createPayrollEmployee([
            'employee_id' => 'CA-E-305',
            'current_salary' => 50000,
            'last_increment' => 10000,
        ]);

        $existingRun = PayrollRun::create([
            'name' => 'March 2026 Payroll',
            'pay_period_month' => '2026-03-01',
            'payment_date' => '2026-04-01',
            'source_workbook' => 'system-calculated',
            'status' => 'draft',
            'generated_by' => $accountsUser->id,
            'generated_at' => now()->subDay(),
            'notes' => 'Initial draft',
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $existingRun->id,
            'employee_id' => $employee->id,
            'bank_code' => 'MBL',
            'beneficiary_name' => 'Payroll Employee',
            'beneficiary_account_no' => 'PK00TEST1234567890',
            'contact_number' => '03001234567',
            'email_address' => $employee->email,
            'days_absent' => 0,
            'late_count' => 0,
            'late_absent_equivalent' => 0,
            'unpaid_leave_days' => 0,
            'short_hours_days' => 0,
            'basic_salary' => 50000,
            'last_increment' => 10000,
            'incentives_bonus' => 0,
            'punctuality_bonus' => 0,
            'positive_arrears' => 0,
            'positive_other' => 0,
            'security_deduction' => 0,
            'security_total_deducted' => 0,
            'non_paid_leave_deduction' => 0,
            'attendance_penalty' => 0,
            'arrears_deduction' => 0,
            'other_deduction' => 0,
            'gross_salary' => 60000,
            'income_tax' => 100,
            'annual_tax_total' => 100,
            'net_salary' => 59900,
        ]);

        $response = $this->actingAs($accountsUser)->post(route('payroll.generate'), [
            'month' => '2026-03',
            'payment_date' => '2026-04-05',
            'notes' => 'Edited draft payout',
            'adjustments' => [
                $employee->id => [
                    'incentives_bonus' => 1500,
                    'punctuality_bonus' => 500,
                    'attendance_penalty' => 0,
                    'arrears_adjustment' => 0,
                    'other_adjustment' => 0,
                    'remarks' => 'Updated before finalizing',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('payroll_runs', 1);
        $this->assertDatabaseHas('payroll_runs', [
            'id' => $existingRun->id,
            'notes' => 'Edited draft payout',
            'status' => 'draft',
        ]);
        $this->assertSame('2026-04-05', PayrollRun::findOrFail($existingRun->id)->payment_date->toDateString());
        $this->assertDatabaseHas('employee_payroll_records', [
            'payroll_run_id' => $existingRun->id,
            'employee_id' => $employee->id,
            'incentives_bonus' => '1500.00',
            'punctuality_bonus' => '500.00',
        ]);
    }

    public function test_can_finalize_payroll_run_and_download_pdf_payslip(): void
    {
        $accountsUser = $this->createUser('Accounts Manager');
        $employee = $this->createPayrollEmployee([
            'employee_id' => 'CA-E-303',
            'full_name' => 'Payslip Employee',
        ]);

        $payrollRun = PayrollRun::create([
            'name' => 'March 2026 Payroll',
            'pay_period_month' => '2026-03-01',
            'payment_date' => '2026-04-01',
            'source_workbook' => 'system-calculated',
            'status' => 'draft',
            'generated_by' => $accountsUser->id,
            'generated_at' => now(),
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $payrollRun->id,
            'employee_id' => $employee->id,
            'bank_code' => 'MBL',
            'beneficiary_name' => 'Payslip Employee',
            'beneficiary_account_no' => 'PK00TEST1234567890',
            'days_absent' => 1,
            'late_count' => 3,
            'late_absent_equivalent' => 1,
            'unpaid_leave_days' => 2,
            'short_hours_days' => 0,
            'basic_salary' => 50000,
            'last_increment' => 10000,
            'incentives_bonus' => 0,
            'punctuality_bonus' => 0,
            'positive_arrears' => 0,
            'positive_other' => 0,
            'security_deduction' => 1000,
            'security_total_deducted' => 1000,
            'non_paid_leave_deduction' => 0,
            'attendance_penalty' => 0,
            'arrears_deduction' => 0,
            'other_deduction' => 0,
            'gross_salary' => 59000,
            'income_tax' => 90,
            'annual_tax_total' => 400,
            'net_salary' => 58910,
        ]);

        $this->actingAs($accountsUser)
            ->post(route('payroll.finalize', $payrollRun))
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_runs', [
            'id' => $payrollRun->id,
            'status' => 'finalized',
        ]);

        $response = $this->actingAs($accountsUser)
            ->get(route('payroll.payslip.download', [$payrollRun, $employee]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_download_payslip_zip_and_bank_transfer_workbooks(): void
    {
        $accountsUser = $this->createUser('Accounts Manager');
        $faysalBank = Bank::create(['name' => 'Faysal Bank Limited', 'code' => 'FAYS']);
        $meezanBank = Bank::create(['name' => 'Meezan Bank', 'code' => 'MBL']);

        $iftEmployee = $this->createPayrollEmployee([
            'employee_id' => 'CA-E-401',
            'full_name' => 'IFT Employee',
            'bank_id' => $faysalBank->id,
            'bank_code' => 'FAYS',
            'iban' => 'PK93FAYS3062301000005500',
        ]);

        $ibftEmployee = $this->createPayrollEmployee([
            'employee_id' => 'CA-E-402',
            'full_name' => 'IBFT Employee',
            'bank_id' => $meezanBank->id,
            'bank_code' => 'MBL',
            'iban' => 'PK58MEZN0008140112027659',
        ]);

        $payrollRun = PayrollRun::create([
            'name' => 'March 2026 Payroll',
            'pay_period_month' => '2026-03-01',
            'payment_date' => '2026-04-01',
            'source_workbook' => 'system-calculated',
            'status' => 'draft',
            'generated_by' => $accountsUser->id,
            'generated_at' => now(),
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $payrollRun->id,
            'employee_id' => $iftEmployee->id,
            'bank_code' => 'FAYS',
            'beneficiary_name' => 'IFT Employee',
            'beneficiary_account_no' => 'PK93FAYS3062301000005500',
            'contact_number' => '03001234567',
            'email_address' => $iftEmployee->email,
            'days_absent' => 0,
            'late_count' => 0,
            'late_absent_equivalent' => 0,
            'unpaid_leave_days' => 0,
            'short_hours_days' => 0,
            'basic_salary' => 50000,
            'last_increment' => 10000,
            'incentives_bonus' => 0,
            'punctuality_bonus' => 0,
            'positive_arrears' => 0,
            'positive_other' => 0,
            'security_deduction' => 1000,
            'security_total_deducted' => 1000,
            'non_paid_leave_deduction' => 0,
            'attendance_penalty' => 0,
            'arrears_deduction' => 0,
            'other_deduction' => 0,
            'gross_salary' => 59000,
            'income_tax' => 90,
            'annual_tax_total' => 90,
            'net_salary' => 58910,
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $payrollRun->id,
            'employee_id' => $ibftEmployee->id,
            'bank_code' => 'MBL',
            'beneficiary_name' => 'IBFT Employee',
            'beneficiary_account_no' => 'PK58MEZN0008140112027659',
            'contact_number' => '03007654321',
            'email_address' => $ibftEmployee->email,
            'days_absent' => 0,
            'late_count' => 0,
            'late_absent_equivalent' => 0,
            'unpaid_leave_days' => 0,
            'short_hours_days' => 0,
            'basic_salary' => 50000,
            'last_increment' => 10000,
            'incentives_bonus' => 0,
            'punctuality_bonus' => 0,
            'positive_arrears' => 0,
            'positive_other' => 0,
            'security_deduction' => 1000,
            'security_total_deducted' => 1000,
            'non_paid_leave_deduction' => 0,
            'attendance_penalty' => 0,
            'arrears_deduction' => 0,
            'other_deduction' => 0,
            'gross_salary' => 59000,
            'income_tax' => 90,
            'annual_tax_total' => 90,
            'net_salary' => 58910,
        ]);

        $zipResponse = $this->actingAs($accountsUser)
            ->get(route('payroll.payslips.zip.download', $payrollRun));

        $zipResponse->assertOk();
        $zipResponse->assertHeader('content-disposition', 'attachment; filename=march-2026-payroll-payslips.zip');

        $iftResponse = $this->actingAs($accountsUser)
            ->get(route('payroll.ift.download', $payrollRun));

        $iftResponse->assertOk();
        $iftResponse->assertHeader('content-disposition', 'attachment; filename=ift-march-2026.xlsx');

        $ibftResponse = $this->actingAs($accountsUser)
            ->get(route('payroll.ibft.download', $payrollRun));

        $ibftResponse->assertOk();
        $ibftResponse->assertHeader('content-disposition', 'attachment; filename=ibft-march-2026.xlsx');
    }

    public function test_late_arrivals_convert_to_unpaid_leave_and_use_selected_month_only(): void
    {
        $accountsUser = $this->createUser('Accounts Manager');
        $employee = $this->createPayrollEmployee([
            'employee_id' => 'CA-E-499',
            'current_salary' => 60000,
            'last_increment' => 0,
        ]);

        foreach (['2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05'] as $date) {
            AttendanceRecord::create([
                'employee_id' => $employee->id,
                'attendance_date' => $date,
                'status' => 'late',
                'clock_in' => '09:20:00',
                'clock_out' => '18:00:00',
                'work_duration' => '08:40',
            ]);
        }

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-02-15',
            'status' => 'absent',
        ]);

        $this->actingAs($accountsUser)->post(route('payroll.generate'), [
            'month' => '2026-03',
            'payment_date' => '2026-04-01',
        ])->assertRedirect();

        $payrollRun = PayrollRun::whereDate('pay_period_month', '2026-03-01')->firstOrFail();
        $record = EmployeePayrollRecord::where('payroll_run_id', $payrollRun->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $this->assertSame(0, $record->days_absent);
        $this->assertSame(4, $record->late_count);
        $this->assertSame(1, $record->late_absent_equivalent);
        $this->assertSame(1, $record->unpaid_leave_days);
        $this->assertSame('2000.00', $record->non_paid_leave_deduction);
        $this->assertSame('58000.00', $record->gross_salary);
    }

    public function test_cumulative_annual_tax_total_includes_prior_fiscal_year_months_only(): void
    {
        $accountsUser = $this->createUser('Accounts Manager');
        $employee = $this->createPayrollEmployee([
            'employee_id' => 'CA-E-598',
            'current_salary' => 250000,
            'last_increment' => 0,
        ]);

        $previousRun = PayrollRun::create([
            'name' => 'February 2026 Payroll',
            'pay_period_month' => '2026-02-01',
            'payment_date' => '2026-03-01',
            'source_workbook' => 'system-calculated',
            'status' => 'finalized',
            'generated_by' => $accountsUser->id,
            'generated_at' => now()->subMonth(),
            'finalized_at' => now()->subMonth(),
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $previousRun->id,
            'employee_id' => $employee->id,
            'bank_code' => 'MBL',
            'beneficiary_name' => $employee->full_name,
            'beneficiary_account_no' => $employee->iban,
            'contact_number' => $employee->phone,
            'email_address' => $employee->email,
            'days_absent' => 0,
            'late_count' => 0,
            'late_absent_equivalent' => 0,
            'unpaid_leave_days' => 0,
            'short_hours_days' => 0,
            'basic_salary' => 250000,
            'last_increment' => 0,
            'incentives_bonus' => 0,
            'punctuality_bonus' => 0,
            'positive_arrears' => 0,
            'positive_other' => 0,
            'security_deduction' => 0,
            'security_total_deducted' => 0,
            'non_paid_leave_deduction' => 0,
            'attendance_penalty' => 0,
            'arrears_deduction' => 0,
            'other_deduction' => 0,
            'gross_salary' => 250000,
            'income_tax' => 66000,
            'annual_tax_total' => 66000,
            'net_salary' => 184000,
        ]);

        $this->actingAs($accountsUser)->post(route('payroll.generate'), [
            'month' => '2026-03',
            'payment_date' => '2026-04-01',
        ])->assertRedirect();

        $payrollRun = PayrollRun::whereDate('pay_period_month', '2026-03-01')->firstOrFail();
        $record = EmployeePayrollRecord::where('payroll_run_id', $payrollRun->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $this->assertSame('250000.00', $record->gross_salary);
        $this->assertSame('25000.00', $record->income_tax);
        $this->assertSame('91000.00', $record->annual_tax_total);
    }
}
