<?php

namespace Tests\Feature;

use App\Models\Announcement;
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
use Database\Seeders\DashboardDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function createEmployee(array $attributes = []): Employee
    {
        $department = $attributes['department_id'] ?? Department::firstOrCreate(['name' => 'Engineering'])->id;

        return Employee::create(array_merge([
            'full_name' => 'Dashboard Employee',
            'email' => 'dashboard-' . uniqid() . '@example.com',
            'status' => 'active',
            'department_id' => $department,
            'designation' => 'Coordinator',
            'employee_id' => 'CA-E-' . random_int(100, 999),
            'hiring_date' => now()->subMonth()->toDateString(),
            'current_salary' => 50000,
            'last_increment' => 5000,
        ], $attributes));
    }

    public function test_super_admin_dashboard_displays_org_wide_sections(): void
    {
        $user = User::factory()->create(['role' => 'Super Admin']);
        $this->seed(DashboardDataSeeder::class);

        PayrollRun::create([
            'name' => 'April 2026 Payroll',
            'pay_period_month' => '2026-04-01',
            'status' => 'draft',
            'generated_by' => $user->id,
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Workforce Mix');
        $response->assertSee('Operational Queue');
        $response->assertSee('Department Footprint');
        $response->assertSee('Engineering');
        $response->assertSee('Draft Payroll Runs');
    }

    public function test_hr_manager_dashboard_shows_hr_operational_content(): void
    {
        $hrUser = User::factory()->create(['role' => 'HR Manager']);
        $department = Department::firstOrCreate(['name' => 'Human Resources']);
        $employee = $this->createEmployee([
            'full_name' => 'HR Visible Employee',
            'department_id' => $department->id,
            'status' => 'resigned',
        ]);
        $leaveType = LeaveType::create(['name' => 'Annual Leave', 'is_active' => true]);

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'requested_by_user_id' => $hrUser->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'days_count' => 2,
            'status' => 'approved',
            'reason' => 'Approved leave',
        ]);

        PerformanceEvaluation::create([
            'employee_id' => $employee->id,
            'manager_user_id' => $hrUser->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'status' => PerformanceEvaluation::STATUS_PENDING_HR,
            'manager_performance' => 4,
            'manager_punctuality' => 4,
            'manager_behaviour' => 4,
            'manager_learning' => 4,
            'manager_participation' => 4,
            'manager_feedback' => 'Ready for HR finalization',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceRecord::STATUS_LATE,
        ]);

        $response = $this->actingAs($hrUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('People Ops Pulse');
        $response->assertSee('Attendance Breakdown');
        $response->assertSee('Workforce Movement');
        $response->assertSee('Pending HR Finalizations');
        $response->assertSee('HR Visible Employee');
    }

    public function test_accounts_manager_dashboard_shows_payroll_and_tax_sections(): void
    {
        $accountsUser = User::factory()->create(['role' => 'Accounts Manager']);
        $employee = $this->createEmployee(['full_name' => 'Accounts Visible Employee']);

        $latestRun = PayrollRun::create([
            'name' => 'March 2026 Payroll',
            'pay_period_month' => now()->startOfMonth()->toDateString(),
            'payment_date' => now()->endOfMonth()->toDateString(),
            'status' => 'finalized',
            'generated_by' => $accountsUser->id,
            'generated_at' => now(),
        ]);

        PayrollRun::create([
            'name' => 'April 2026 Draft Payroll',
            'pay_period_month' => now()->addMonth()->startOfMonth()->toDateString(),
            'status' => 'draft',
            'generated_by' => $accountsUser->id,
            'generated_at' => now(),
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $latestRun->id,
            'employee_id' => $employee->id,
            'basic_salary' => 50000,
            'last_increment' => 5000,
            'gross_salary' => 53000,
            'income_tax' => 30,
            'security_deduction' => 1000,
            'non_paid_leave_deduction' => 500,
            'short_hours_days' => 2,
            'net_salary' => 51970,
        ]);

        $response = $this->actingAs($accountsUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Payroll Composition');
        $response->assertSee('Recent Payroll Runs');
        $response->assertSee('Exception Watchlist');
        $response->assertSee('Report Shortcuts');
        $response->assertSee('Draft Payouts');
        $response->assertSee('Accounts Visible Employee');
    }

    public function test_team_manager_dashboard_only_shows_assigned_team_data(): void
    {
        $manager = User::factory()->create(['role' => 'Team Manager']);
        $assignedEmployee = $this->createEmployee([
            'full_name' => 'Assigned Team Employee',
            'team_manager_user_id' => $manager->id,
        ]);
        $unassignedEmployee = $this->createEmployee([
            'full_name' => 'Other Team Employee',
        ]);

        PerformanceEvaluation::create([
            'employee_id' => $assignedEmployee->id,
            'manager_user_id' => $manager->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'status' => PerformanceEvaluation::STATUS_MANAGER_DRAFT,
            'manager_performance' => 4,
            'manager_punctuality' => 4,
            'manager_behaviour' => 4,
            'manager_learning' => 4,
            'manager_participation' => 4,
            'manager_feedback' => 'Assigned draft review',
        ]);

        AttendanceRecord::create([
            'employee_id' => $assignedEmployee->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceRecord::STATUS_ABSENT,
        ]);

        AttendanceRecord::create([
            'employee_id' => $unassignedEmployee->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceRecord::STATUS_ABSENT,
        ]);

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Team Health');
        $response->assertSee('Evaluation Pipeline');
        $response->assertSee('Assigned Team Members');
        $response->assertSee('Pending Manager Evaluations');
        $response->assertSee('Assigned Team Employee');
        $response->assertDontSee('Other Team Employee');
    }

    public function test_employee_dashboard_only_shows_personal_data(): void
    {
        $visibleDepartment = Department::firstOrCreate(['name' => 'Engineering']);
        $otherDepartment = Department::firstOrCreate(['name' => 'Sales']);

        $employee = $this->createEmployee([
            'full_name' => 'Employee Dashboard User',
            'department_id' => $visibleDepartment->id,
        ]);
        $otherEmployee = $this->createEmployee([
            'full_name' => 'Hidden Employee',
            'department_id' => $otherDepartment->id,
        ]);
        $employeeUser = User::factory()->create([
            'role' => 'Employee',
            'employee_id' => $employee->id,
        ]);
        $leaveType = LeaveType::create(['name' => 'Sick Leave', 'is_active' => true]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceRecord::STATUS_PRESENT,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'work_duration' => '09:00',
        ]);

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'requested_by_user_id' => $employeeUser->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'days_count' => 2,
            'status' => 'pending',
            'reason' => 'Visible leave',
        ]);

        LeaveRequest::create([
            'employee_id' => $otherEmployee->id,
            'leave_type_id' => $leaveType->id,
            'requested_by_user_id' => $employeeUser->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'days_count' => 2,
            'status' => 'approved',
            'reason' => 'Hidden leave',
        ]);

        $run = PayrollRun::create([
            'name' => 'April 2026 Payroll',
            'pay_period_month' => now()->startOfMonth()->toDateString(),
            'payment_date' => now()->endOfMonth()->toDateString(),
            'status' => 'finalized',
            'generated_by' => $employeeUser->id,
            'generated_at' => now(),
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'basic_salary' => 50000,
            'last_increment' => 5000,
            'gross_salary' => 54000,
            'income_tax' => 40,
            'security_deduction' => 1000,
            'net_salary' => 52960,
        ]);

        EmployeePayrollRecord::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $otherEmployee->id,
            'basic_salary' => 70000,
            'last_increment' => 5000,
            'gross_salary' => 75000,
            'income_tax' => 250,
            'security_deduction' => 2000,
            'net_salary' => 72750,
        ]);

        EmployeeSecurityFundSnapshot::create([
            'employee_id' => $employee->id,
            'fiscal_year_label' => 'FY 2025-26',
            'snapshot_month' => now()->startOfMonth()->toDateString(),
            'paid_amount' => 1000,
            'balance_in_account' => 12000,
            'remarks' => 'Visible security',
        ]);

        PerformanceEvaluation::create([
            'employee_id' => $employee->id,
            'manager_user_id' => $employeeUser->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'status' => PerformanceEvaluation::STATUS_FINALIZED,
            'manager_performance' => 4,
            'manager_punctuality' => 4,
            'manager_behaviour' => 4,
            'manager_learning' => 4,
            'manager_participation' => 4,
            'manager_feedback' => 'Visible manager feedback',
            'hr_performance' => 4,
            'hr_punctuality' => 4,
            'hr_behaviour' => 4,
            'hr_learning' => 4,
            'hr_participation' => 4,
            'hr_feedback' => 'Visible final feedback',
            'manager_submitted_at' => now(),
            'hr_finalized_at' => now(),
        ]);

        PerformanceEvaluation::create([
            'employee_id' => $otherEmployee->id,
            'manager_user_id' => $employeeUser->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'status' => PerformanceEvaluation::STATUS_FINALIZED,
            'manager_performance' => 5,
            'manager_punctuality' => 5,
            'manager_behaviour' => 5,
            'manager_learning' => 5,
            'manager_participation' => 5,
            'manager_feedback' => 'Hidden manager feedback',
            'hr_performance' => 5,
            'hr_punctuality' => 5,
            'hr_behaviour' => 5,
            'hr_learning' => 5,
            'hr_participation' => 5,
            'hr_feedback' => 'Hidden final feedback',
            'manager_submitted_at' => now(),
            'hr_finalized_at' => now(),
        ]);

        Announcement::create([
            'title' => 'Visible Announcement',
            'message' => 'Global employee update',
            'announcement_type' => Announcement::TYPE_GENERAL,
            'is_global' => true,
            'is_active' => true,
            'created_by' => $employeeUser->id,
            'published_at' => now(),
        ]);

        $hiddenAnnouncement = Announcement::create([
            'title' => 'Hidden Announcement',
            'message' => 'Sales only update',
            'announcement_type' => Announcement::TYPE_GENERAL,
            'is_global' => false,
            'is_active' => true,
            'created_by' => $employeeUser->id,
            'published_at' => now(),
        ]);
        $hiddenAnnouncement->departments()->sync([$otherDepartment->id]);

        $response = $this->actingAs($employeeUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('This Month at a Glance');
        $response->assertSee('Leave Timeline');
        $response->assertSee('Compensation Snapshot');
        $response->assertSee('Visible Announcement');
        $response->assertSee('Visible final feedback');
        $response->assertDontSee('Hidden Announcement');
        $response->assertDontSee('Hidden Employee');
        $response->assertDontSee('Hidden final feedback');
    }
}
