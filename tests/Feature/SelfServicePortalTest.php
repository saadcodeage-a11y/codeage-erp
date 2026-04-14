<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePayrollRecord;
use App\Models\EmployeeSecurityFundSnapshot;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\PerformanceEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SelfServicePortalTest extends TestCase
{
    use RefreshDatabase;

    protected function createEmployee(array $attributes = []): Employee
    {
        $department = Department::firstOrCreate(['name' => 'Operations']);

        return Employee::create(array_merge([
            'full_name' => 'Portal Employee',
            'email' => 'portal-' . uniqid() . '@example.com',
            'status' => 'active',
            'department_id' => $department->id,
            'designation' => 'Coordinator',
            'employee_id' => 'CA-E-' . random_int(100, 999),
            'hiring_date' => now()->subMonth()->toDateString(),
            'current_salary' => 50000,
            'last_increment' => 5000,
        ], $attributes));
    }

    protected function createEmployeeUser(?Employee $employee = null): User
    {
        return User::factory()->create([
            'role' => 'Employee',
            'employee_id' => $employee?->id,
        ]);
    }

    public function test_profile_page_embeds_self_service_for_linked_non_super_admin_accounts(): void
    {
        $employee = $this->createEmployee([
            'full_name' => 'Linked Employee',
            'employee_id' => 'CA-E-810',
        ]);

        $linkedUser = $this->createEmployeeUser($employee);
        $unlinkedUser = $this->createEmployeeUser();
        $hrUser = User::factory()->create(['role' => 'HR Manager']);
        $superAdmin = User::factory()->create(['role' => 'Super Admin']);

        $this->actingAs($linkedUser)
            ->get(route('profile.index', ['tab' => 'profile']))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('Linked Employee');

        $this->actingAs($unlinkedUser)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertSee('No Linked Employee Profile');

        $this->actingAs($hrUser)
            ->get(route('profile.index'))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertDontSee('Employee Self-Service');
    }

    public function test_self_service_only_shows_linked_employee_data_and_hides_non_finalized_reviews(): void
    {
        Carbon::setTestNow('2026-04-14 09:00:00');

        $department = Department::firstOrCreate(['name' => 'Engineering']);
        $employee = $this->createEmployee([
            'full_name' => 'Visible Employee',
            'email' => 'visible@example.com',
            'employee_id' => 'CA-E-820',
            'department_id' => $department->id,
        ]);
        $otherEmployee = $this->createEmployee([
            'full_name' => 'Hidden Employee',
            'email' => 'hidden@example.com',
            'employee_id' => 'CA-E-821',
            'department_id' => $department->id,
        ]);

        $employeeUser = $this->createEmployeeUser($employee);
        $manager = User::factory()->create(['role' => 'Team Manager']);
        $hrUser = User::factory()->create(['role' => 'HR Manager']);

        $run = PayrollRun::create([
            'name' => 'April 2026 Payroll',
            'pay_period_month' => '2026-04-01',
            'payment_date' => '2026-04-30',
            'status' => 'finalized',
            'generated_by' => $hrUser->id,
            'generated_at' => now(),
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'days_absent' => 1,
            'late_count' => 2,
            'late_absent_equivalent' => 0,
            'unpaid_leave_days' => 1,
            'short_hours_days' => 0,
            'basic_salary' => 50000,
            'last_increment' => 5000,
            'security_deduction' => 1000,
            'security_total_deducted' => 7000,
            'non_paid_leave_deduction' => 1500,
            'attendance_penalty' => 0,
            'arrears_deduction' => 0,
            'other_deduction' => 0,
            'gross_salary' => 53500,
            'income_tax' => 35,
            'annual_tax_total' => 120,
            'net_salary' => 53465,
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $otherEmployee->id,
            'days_absent' => 0,
            'late_count' => 0,
            'late_absent_equivalent' => 0,
            'unpaid_leave_days' => 0,
            'short_hours_days' => 0,
            'basic_salary' => 90000,
            'last_increment' => 10000,
            'security_deduction' => 2000,
            'security_total_deducted' => 12000,
            'non_paid_leave_deduction' => 0,
            'attendance_penalty' => 0,
            'arrears_deduction' => 0,
            'other_deduction' => 0,
            'gross_salary' => 98000,
            'income_tax' => 480,
            'annual_tax_total' => 1000,
            'net_salary' => 97520,
        ]);

        EmployeeSecurityFundSnapshot::create([
            'employee_id' => $employee->id,
            'fiscal_year_label' => 'FY 2025-26',
            'snapshot_month' => '2026-04-01',
            'paid_amount' => 2000,
            'balance_in_account' => 5000,
            'remarks' => 'Visible snapshot',
        ]);

        EmployeeSecurityFundSnapshot::create([
            'employee_id' => $otherEmployee->id,
            'fiscal_year_label' => 'FY 2025-26',
            'snapshot_month' => '2026-04-01',
            'paid_amount' => 9000,
            'balance_in_account' => 15000,
            'remarks' => 'Hidden snapshot',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-04-03',
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'work_duration' => '08:00',
            'status' => 'present',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-28',
            'clock_in' => '09:10:00',
            'clock_out' => '17:00:00',
            'work_duration' => '07:50',
            'status' => 'late',
        ]);

        AttendanceRecord::create([
            'employee_id' => $otherEmployee->id,
            'attendance_date' => '2026-04-03',
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'work_duration' => '08:00',
            'status' => 'present',
        ]);

        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'is_active' => true,
        ]);

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'requested_by_user_id' => $employeeUser->id,
            'start_date' => '2026-04-20',
            'end_date' => '2026-04-22',
            'days_count' => 3,
            'reason' => 'Visible reason',
            'status' => 'pending',
        ]);

        LeaveRequest::create([
            'employee_id' => $otherEmployee->id,
            'leave_type_id' => $leaveType->id,
            'requested_by_user_id' => $employeeUser->id,
            'start_date' => '2026-04-24',
            'end_date' => '2026-04-25',
            'days_count' => 2,
            'reason' => 'Hidden reason',
            'status' => 'approved',
        ]);

        PerformanceEvaluation::create([
            'employee_id' => $employee->id,
            'manager_user_id' => $manager->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'status' => PerformanceEvaluation::STATUS_FINALIZED,
            'manager_performance' => 4,
            'manager_punctuality' => 4,
            'manager_behaviour' => 5,
            'manager_learning' => 4,
            'manager_participation' => 4,
            'manager_feedback' => 'Visible manager feedback',
            'hr_performance' => 4,
            'hr_punctuality' => 4,
            'hr_behaviour' => 5,
            'hr_learning' => 4,
            'hr_participation' => 4,
            'hr_feedback' => 'Visible HR feedback',
            'hr_finalized_by_user_id' => $hrUser->id,
            'manager_submitted_at' => now(),
            'hr_finalized_at' => now(),
        ]);

        PerformanceEvaluation::create([
            'employee_id' => $employee->id,
            'manager_user_id' => $manager->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => PerformanceEvaluation::STATUS_PENDING_HR,
            'manager_performance' => 3,
            'manager_punctuality' => 3,
            'manager_behaviour' => 3,
            'manager_learning' => 3,
            'manager_participation' => 3,
            'manager_feedback' => 'Hidden manager feedback',
            'manager_submitted_at' => now(),
        ]);

        PerformanceEvaluation::create([
            'employee_id' => $otherEmployee->id,
            'manager_user_id' => $manager->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'status' => PerformanceEvaluation::STATUS_FINALIZED,
            'manager_performance' => 5,
            'manager_punctuality' => 5,
            'manager_behaviour' => 5,
            'manager_learning' => 5,
            'manager_participation' => 5,
            'manager_feedback' => 'Other manager feedback',
            'hr_performance' => 5,
            'hr_punctuality' => 5,
            'hr_behaviour' => 5,
            'hr_learning' => 5,
            'hr_participation' => 5,
            'hr_feedback' => 'Other HR feedback',
            'hr_finalized_by_user_id' => $hrUser->id,
            'manager_submitted_at' => now(),
            'hr_finalized_at' => now(),
        ]);

        $response = $this->actingAs($employeeUser)->get(route('profile.index', ['tab' => 'performance']));

        $response->assertOk();
        $response->assertSee('Visible Employee');
        $response->assertSee('Visible snapshot');
        $response->assertSee('Visible reason');
        $response->assertSee('Visible HR feedback');
        $response->assertSee('03 Apr 2026');
        $response->assertDontSee('28 Mar 2026');
        $response->assertDontSee('Hidden Employee');
        $response->assertDontSee('Hidden snapshot');
        $response->assertDontSee('Hidden reason');
        $response->assertDontSee('Hidden manager feedback');

        Carbon::setTestNow();
    }

    public function test_employee_can_submit_and_cancel_leave_from_self_service(): void
    {
        $employee = $this->createEmployee([
            'full_name' => 'Leave Portal Employee',
            'employee_id' => 'CA-E-830',
        ]);
        $employeeUser = $this->createEmployeeUser($employee);
        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'is_active' => true,
        ]);

        $this->actingAs($employeeUser)->post(route('profile.self-service.leaves.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'reason' => 'Portal submission',
        ])->assertRedirect(route('profile.index', ['tab' => 'leave']));

        $leaveRequest = LeaveRequest::firstOrFail();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'employee_id' => $employee->id,
            'status' => 'pending',
            'reason' => 'Portal submission',
        ]);

        $this->actingAs($employeeUser)
            ->post(route('profile.self-service.leaves.cancel', $leaveRequest))
            ->assertRedirect(route('profile.index', ['tab' => 'leave']));

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'cancelled',
            'reviewed_by_user_id' => $employeeUser->id,
        ]);
    }

    public function test_employee_can_download_own_payslip_but_not_another_employees_payslip(): void
    {
        $employee = $this->createEmployee(['employee_id' => 'CA-E-840']);
        $otherEmployee = $this->createEmployee(['employee_id' => 'CA-E-841']);
        $employeeUser = $this->createEmployeeUser($employee);
        $hrUser = User::factory()->create(['role' => 'HR Manager']);

        $run = PayrollRun::create([
            'name' => 'April 2026 Payroll',
            'pay_period_month' => '2026-04-01',
            'payment_date' => '2026-04-30',
            'status' => 'finalized',
            'generated_by' => $hrUser->id,
            'generated_at' => now(),
        ]);

        $ownRecord = EmployeePayrollRecord::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'basic_salary' => 50000,
            'last_increment' => 5000,
            'gross_salary' => 55000,
            'income_tax' => 50,
            'net_salary' => 54950,
        ]);

        $otherRecord = EmployeePayrollRecord::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $otherEmployee->id,
            'basic_salary' => 60000,
            'last_increment' => 5000,
            'gross_salary' => 65000,
            'income_tax' => 150,
            'net_salary' => 64850,
        ]);

        $this->actingAs($employeeUser)
            ->get(route('profile.self-service.payroll.payslip', $ownRecord))
            ->assertOk();

        $this->actingAs($employeeUser)
            ->get(route('profile.self-service.payroll.payslip', $otherRecord))
            ->assertForbidden();
    }
}
