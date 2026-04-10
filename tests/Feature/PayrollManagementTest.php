<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
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
        $this->assertSame(1, $record->short_hours_days);
        $this->assertSame('1000.00', $record->security_deduction);
        $this->assertSame('500.00', $record->non_paid_leave_deduction);
        $this->assertSame('50000.00', $record->basic_salary);
        $this->assertSame('10000.00', $record->last_increment);
        $this->assertSame('1000.00', $record->incentives_bonus);
        $this->assertSame('250.00', $record->positive_other);
        $this->assertSame('500.00', $record->arrears_deduction);
        $this->assertSame('59250.00', $record->gross_salary);
        $this->assertSame('92.50', $record->income_tax);
        $this->assertSame('59157.50', $record->net_salary);
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
            'short_hours_days' => 0,
            'basic_salary' => 50000,
            'last_increment' => 10000,
            'incentives_bonus' => 0,
            'punctuality_bonus' => 0,
            'positive_arrears' => 0,
            'positive_other' => 0,
            'security_deduction' => 1000,
            'non_paid_leave_deduction' => 0,
            'attendance_penalty' => 0,
            'arrears_deduction' => 0,
            'other_deduction' => 0,
            'gross_salary' => 59000,
            'income_tax' => 90,
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
}
