<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePayrollRecord;
use App\Models\PayrollRun;
use App\Models\PerformanceEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    protected function createEmployee(array $attributes = []): Employee
    {
        $department = Department::firstOrCreate(['name' => 'Engineering']);

        return Employee::create(array_merge([
            'full_name' => 'Report Employee',
            'email' => 'report-' . uniqid() . '@example.com',
            'status' => 'active',
            'department_id' => $department->id,
            'designation' => 'Executive',
            'employee_id' => 'CA-E-' . random_int(100, 999),
            'current_salary' => 50000,
            'last_increment' => 5000,
        ], $attributes));
    }

    public function test_reports_module_access_is_limited_to_super_admin_hr_and_accounts(): void
    {
        $this->actingAs($this->createUser('Super Admin'))
            ->get(route('reports.index'))
            ->assertOk();

        $this->actingAs($this->createUser('HR Manager'))
            ->get(route('reports.index'))
            ->assertOk();

        $this->actingAs($this->createUser('Accounts Manager'))
            ->get(route('reports.index'))
            ->assertOk();

        $this->actingAs($this->createUser('Team Manager'))
            ->get(route('reports.index'))
            ->assertForbidden();

        $this->actingAs($this->createUser('Employee'))
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_can_render_and_export_tax_report_for_fiscal_year(): void
    {
        $user = $this->createUser('Accounts Manager');
        $employee = $this->createEmployee([
            'employee_id' => 'CA-E-901',
            'full_name' => 'Tax Employee',
        ]);

        $run = PayrollRun::create([
            'name' => 'March 2026 Payroll',
            'pay_period_month' => '2026-03-01',
            'payment_date' => '2026-03-31',
            'status' => 'finalized',
            'generated_by' => $user->id,
            'generated_at' => now(),
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'days_absent' => 0,
            'late_count' => 0,
            'late_absent_equivalent' => 0,
            'unpaid_leave_days' => 0,
            'short_hours_days' => 0,
            'basic_salary' => 50000,
            'last_increment' => 5000,
            'incentives_bonus' => 0,
            'punctuality_bonus' => 0,
            'positive_arrears' => 0,
            'positive_other' => 0,
            'security_deduction' => 500,
            'security_total_deducted' => 500,
            'non_paid_leave_deduction' => 0,
            'attendance_penalty' => 0,
            'arrears_deduction' => 0,
            'other_deduction' => 0,
            'gross_salary' => 54500,
            'income_tax' => 45,
            'annual_tax_total' => 45,
            'net_salary' => 54455,
        ]);

        $this->actingAs($user)
            ->get(route('reports.index', ['tab' => 'tax', 'fiscal_year' => 2025]))
            ->assertOk()
            ->assertSee('Tax Reports')
            ->assertSee('Tax Employee')
            ->assertSee('FY 2025-26')
            ->assertSee('PKR 45.00');

        $csvResponse = $this->actingAs($user)
            ->get(route('reports.csv', ['reportType' => 'tax', 'fiscal_year' => 2025]));

        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('content-type'));
        $this->assertStringContainsString('CA-E-901', $csvResponse->streamedContent());

        $pdfResponse = $this->actingAs($user)
            ->get(route('reports.pdf', ['reportType' => 'tax', 'fiscal_year' => 2025]));

        $pdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('content-type'));
    }

    public function test_can_render_attendance_and_performance_reports(): void
    {
        $user = $this->createUser('HR Manager');
        $employee = $this->createEmployee([
            'employee_id' => 'CA-E-902',
            'full_name' => 'Analytics Employee',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-04',
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'work_duration' => '08:00',
            'status' => 'present',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-05',
            'clock_in' => '09:20:00',
            'clock_out' => '17:00:00',
            'work_duration' => '07:40',
            'late_duration' => '00:20',
            'status' => 'late',
        ]);

        PerformanceEvaluation::create([
            'employee_id' => $employee->id,
            'manager_user_id' => $user->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => PerformanceEvaluation::STATUS_FINALIZED,
            'manager_performance' => 4,
            'manager_punctuality' => 3,
            'manager_behaviour' => 5,
            'manager_learning' => 4,
            'manager_participation' => 4,
            'hr_performance' => 4,
            'hr_punctuality' => 4,
            'hr_behaviour' => 5,
            'hr_learning' => 4,
            'hr_participation' => 4,
            'manager_submitted_at' => now(),
            'hr_finalized_by_user_id' => $user->id,
            'hr_finalized_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.index', ['tab' => 'attendance', 'month' => '2026-03']))
            ->assertOk()
            ->assertSee('Attendance Reports')
            ->assertSee('Analytics Employee')
            ->assertSee('Present / Late');

        $performanceResponse = $this->actingAs($user)
            ->get(route('reports.index', ['tab' => 'performance']))
            ->assertOk()
            ->assertSee('Performance Analytics')
            ->assertSee('Evaluation History')
            ->assertSee('Metric Averages');

        $csvResponse = $this->actingAs($user)
            ->get(route('reports.csv', ['reportType' => 'performance']));

        $csvResponse->assertOk();
        $this->assertStringContainsString('Analytics Employee', $csvResponse->streamedContent());
    }
}
