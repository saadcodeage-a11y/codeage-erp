<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePayrollRecord;
use App\Models\EmployeeSecurityFundSnapshot;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\PerformanceEvaluation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function build(User $user): array
    {
        return match ($user->role) {
            'Super Admin' => $this->buildSuperAdminDashboard($user),
            'HR Manager' => $this->buildHrDashboard($user),
            'Accounts Manager' => $this->buildAccountsDashboard($user),
            'Team Manager' => $this->buildTeamManagerDashboard($user),
            'Employee' => $this->buildEmployeeDashboard($user),
            default => $this->buildFallbackDashboard($user),
        };
    }

    protected function buildSuperAdminDashboard(User $user): array
    {
        $pendingApprovals = Employee::query()->where('status', 'pending_approval')->count();
        $draftRuns = PayrollRun::query()->where('status', 'draft')->count();
        $pendingLeaves = LeaveRequest::query()->where('status', 'pending')->count();
        $pendingHrReviews = PerformanceEvaluation::query()
            ->where('status', PerformanceEvaluation::STATUS_PENDING_HR)
            ->count();

        return [
            'view' => 'super-admin',
            'title' => 'Dashboard',
            'subtitle' => 'Org-wide overview, operations backlog, and admin shortcuts.',
            'role_label' => 'Super Admin',
            'stats' => [
                $this->stat('Total Employees', (string) Employee::count(), 'users', 'orange'),
                $this->stat('Active Employees', (string) Employee::query()->where('status', 'active')->count(), 'user-check', 'green'),
                $this->stat('Total Users', (string) User::count(), 'shield', 'purple'),
                $this->stat('Active Users', (string) $this->activeUsersCount(), 'activity', 'blue'),
                $this->stat('Pending Approvals', (string) $pendingApprovals, 'clock-3', 'yellow'),
                $this->stat('Draft Payroll Runs', (string) $draftRuns, 'wallet-cards', 'blue-dark'),
            ],
            'quick_actions' => $this->filterQuickActions($user, [
                $this->action('Employees', 'Manage employee records', 'users', route('employees.index'), 'orange-light', 'employees'),
                $this->action('User Management', 'Roles and access control', 'user-cog', route('users.index'), 'purple-light', 'user_management'),
                $this->action('Payroll', 'Review payouts and drafts', 'wallet-cards', route('payroll.index'), 'blue-light', 'payroll_management'),
                $this->action('Reports', 'Export org-wide reports', 'files', route('reports.index'), 'green-light', 'reports'),
                $this->action('Settings', 'System-wide configuration', 'settings', route('settings.index'), 'yellow-light', 'settings'),
            ]),
            'recent_activity' => ActivityLog::query()->latest()->take(6)->get(),
            'departments' => Department::query()
                ->withCount('employees')
                ->orderByDesc('employees_count')
                ->orderBy('name')
                ->get(),
            'pending_items' => [
                [
                    'label' => 'Employee approvals',
                    'value' => $pendingApprovals,
                    'description' => 'Employees waiting for approval.',
                ],
                [
                    'label' => 'Pending leave requests',
                    'value' => $pendingLeaves,
                    'description' => 'Leave requests still awaiting review.',
                ],
                [
                    'label' => 'Pending HR evaluations',
                    'value' => $pendingHrReviews,
                    'description' => 'Performance reviews waiting for HR finalization.',
                ],
                [
                    'label' => 'Draft payroll runs',
                    'value' => $draftRuns,
                    'description' => 'Payout months still pending final review.',
                ],
            ],
            'announcements' => $this->latestAnnouncementsFor($user),
        ];
    }

    protected function buildHrDashboard(User $user): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return [
            'view' => 'hr-manager',
            'title' => 'Dashboard',
            'subtitle' => 'People operations, approvals, attendance exceptions, and workforce changes.',
            'role_label' => 'HR Manager',
            'stats' => [
                $this->stat('Active Employees', (string) Employee::query()->where('status', 'active')->count(), 'users', 'orange'),
                $this->stat('Pending Leave Requests', (string) LeaveRequest::query()->where('status', 'pending')->count(), 'calendar-range', 'yellow'),
                $this->stat('Pending HR Finalizations', (string) PerformanceEvaluation::query()->where('status', PerformanceEvaluation::STATUS_PENDING_HR)->count(), 'chart-column-big', 'blue'),
                $this->stat('Attendance Exceptions', (string) AttendanceRecord::query()
                    ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->whereIn('status', [
                        AttendanceRecord::STATUS_LATE,
                        AttendanceRecord::STATUS_ABSENT,
                        AttendanceRecord::STATUS_INCOMPLETE,
                        AttendanceRecord::STATUS_EARLY_LEAVE,
                    ])
                    ->count(), 'fingerprint', 'green'),
            ],
            'quick_actions' => $this->filterQuickActions($user, [
                $this->action('Employees', 'Employee records and status changes', 'users', route('employees.index'), 'orange-light', 'employees'),
                $this->action('Leave Management', 'Review leave requests', 'calendar-range', route('leaves.index'), 'blue-light', 'leave_management'),
                $this->action('Performance', 'Finalize reviews', 'chart-column-big', route('performance.index'), 'purple-light', 'performance_management'),
                $this->action('Announcements', 'Publish office notices', 'megaphone', route('announcements.index'), 'yellow-light', 'announcements'),
                $this->action('Attendance', 'Inspect attendance records', 'fingerprint', route('attendance.index'), 'green-light', 'attendance_management'),
            ]),
            'upcoming_leaves' => LeaveRequest::query()
                ->with(['employee.department', 'leaveType'])
                ->where('status', 'approved')
                ->whereDate('start_date', '>=', now()->toDateString())
                ->orderBy('start_date')
                ->take(6)
                ->get(),
            'recent_hires' => Employee::query()
                ->with('department')
                ->whereNotNull('hiring_date')
                ->orderByDesc('hiring_date')
                ->take(6)
                ->get(),
            'status_changes' => Employee::query()
                ->with('department')
                ->whereIn('status', ['inactive', 'terminated', 'resigned', 'on_leave', 'pending_approval'])
                ->orderByDesc('updated_at')
                ->take(6)
                ->get(),
            'announcements' => $this->latestAnnouncementsFor($user),
        ];
    }

    protected function buildAccountsDashboard(User $user): array
    {
        $latestRun = PayrollRun::query()
            ->withCount('records')
            ->has('records')
            ->latest('pay_period_month')
            ->first();

        $currentMonthStart = now()->startOfMonth()->toDateString();
        $latestRunGross = $latestRun ? (float) $latestRun->records()->sum('gross_salary') : 0;
        $latestRunTax = $latestRun ? (float) $latestRun->records()->sum('income_tax') : 0;
        $latestRunNet = $latestRun ? (float) $latestRun->records()->sum('net_salary') : 0;

        return [
            'view' => 'accounts-manager',
            'title' => 'Dashboard',
            'subtitle' => 'Payroll totals, draft payout follow-up, and reporting shortcuts for accounts.',
            'role_label' => 'Accounts Manager',
            'stats' => [
                $this->stat('Latest Payout Month', $latestRun?->pay_period_month?->format('F Y') ?? 'No payouts', 'calendar-days', 'orange'),
                $this->stat('Draft Payouts', (string) PayrollRun::query()->where('status', 'draft')->count(), 'wallet-cards', 'yellow'),
                $this->stat('Current Month Tax', $this->money(EmployeePayrollRecord::query()
                    ->whereHas('payrollRun', fn ($query) => $query->whereDate('pay_period_month', $currentMonthStart))
                    ->sum('income_tax')), 'receipt-text', 'blue'),
                $this->stat('Security Deductions', $this->money(EmployeePayrollRecord::query()
                    ->whereHas('payrollRun', fn ($query) => $query->whereDate('pay_period_month', $currentMonthStart))
                    ->sum('security_deduction')), 'shield', 'green'),
            ],
            'quick_actions' => $this->filterQuickActions($user, [
                $this->action('Payroll', 'Manage payout months', 'wallet-cards', route('payroll.index'), 'orange-light', 'payroll_management'),
                $this->action('Payroll Reports', 'Monthly and yearly payroll analysis', 'files', route('reports.index', ['tab' => 'payroll']), 'blue-light', 'reports'),
                $this->action('Tax Reports', 'Fiscal tax summaries', 'receipt-text', route('reports.index', ['tab' => 'tax']), 'purple-light', 'reports'),
            ]),
            'latest_run' => $latestRun,
            'latest_run_totals' => [
                'gross' => $latestRunGross,
                'tax' => $latestRunTax,
                'net' => $latestRunNet,
            ],
            'recent_runs' => PayrollRun::query()
                ->withCount('records')
                ->latest('pay_period_month')
                ->take(6)
                ->get(),
            'payroll_exceptions' => $latestRun
                ? $latestRun->records()
                    ->with('employee.department')
                    ->where(function ($query) {
                        $query->where('non_paid_leave_deduction', '>', 0)
                            ->orWhere('attendance_penalty', '>', 0)
                            ->orWhere('security_deduction', '>', 0)
                            ->orWhere('short_hours_days', '>', 0);
                    })
                    ->orderByDesc('non_paid_leave_deduction')
                    ->take(6)
                    ->get()
                : collect(),
            'report_shortcuts' => [
                [
                    'title' => 'Payroll Report',
                    'description' => 'Month-wise gross, tax, security, and net salary totals.',
                    'href' => route('reports.index', ['tab' => 'payroll']),
                ],
                [
                    'title' => 'Tax Report',
                    'description' => 'Fiscal-year employee tax deductions and annual totals.',
                    'href' => route('reports.index', ['tab' => 'tax']),
                ],
            ],
            'announcements' => $this->latestAnnouncementsFor($user),
        ];
    }

    protected function buildTeamManagerDashboard(User $user): array
    {
        $assignedEmployeeIds = Employee::query()
            ->where('team_manager_user_id', $user->id)
            ->pluck('id');

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return [
            'view' => 'team-manager',
            'title' => 'Dashboard',
            'subtitle' => 'Team workload, current month issues, and evaluation follow-up for assigned employees.',
            'role_label' => 'Team Manager',
            'stats' => [
                $this->stat('Assigned Employees', (string) $assignedEmployeeIds->count(), 'users-round', 'orange'),
                $this->stat('Active Team Members', (string) Employee::query()
                    ->whereIn('id', $assignedEmployeeIds)
                    ->where('status', 'active')
                    ->count(), 'user-check', 'green'),
                $this->stat('Pending Manager Evaluations', (string) PerformanceEvaluation::query()
                    ->whereHas('employee', fn ($query) => $query->where('team_manager_user_id', $user->id))
                    ->where('status', PerformanceEvaluation::STATUS_MANAGER_DRAFT)
                    ->count(), 'chart-column-big', 'blue'),
                $this->stat('Attendance Issues', (string) AttendanceRecord::query()
                    ->whereHas('employee', fn ($query) => $query->where('team_manager_user_id', $user->id))
                    ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->whereIn('status', [
                        AttendanceRecord::STATUS_LATE,
                        AttendanceRecord::STATUS_ABSENT,
                        AttendanceRecord::STATUS_INCOMPLETE,
                        AttendanceRecord::STATUS_EARLY_LEAVE,
                    ])
                    ->count(), 'fingerprint', 'yellow'),
            ],
            'quick_actions' => $this->filterQuickActions($user, [
                $this->action('My Team', 'Assigned employee overview', 'user-round-search', route('team.index'), 'orange-light', 'team_management'),
                $this->action('Performance', 'Open team evaluations', 'chart-column-big', route('performance.index'), 'blue-light', 'performance_management'),
            ]),
            'team_roster' => Employee::query()
                ->with('department')
                ->where('team_manager_user_id', $user->id)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('full_name')
                ->take(8)
                ->get(),
            'recent_evaluations' => PerformanceEvaluation::query()
                ->with('employee.department')
                ->whereHas('employee', fn ($query) => $query->where('team_manager_user_id', $user->id))
                ->orderByRaw("CASE WHEN status = '" . PerformanceEvaluation::STATUS_MANAGER_DRAFT . "' THEN 0 WHEN status = '" . PerformanceEvaluation::STATUS_PENDING_HR . "' THEN 1 ELSE 2 END")
                ->orderByDesc('period_start')
                ->take(8)
                ->get(),
            'team_leaves' => $user->canAccessModule('leave_management')
                ? LeaveRequest::query()
                    ->with(['employee.department', 'leaveType'])
                    ->whereHas('employee', fn ($query) => $query->where('team_manager_user_id', $user->id))
                    ->latest('start_date')
                    ->take(6)
                    ->get()
                : collect(),
            'announcements' => $user->canAccessModule('announcements')
                ? $this->latestAnnouncementsFor($user)
                : collect(),
        ];
    }

    protected function buildEmployeeDashboard(User $user): array
    {
        $employee = Employee::query()
            ->with(['department', 'teamManager'])
            ->find($user->employee_id);

        if (! $employee) {
            return [
                'view' => 'employee',
                'title' => 'Dashboard',
                'subtitle' => 'Your personal work overview and quick access to self-service actions.',
                'role_label' => 'Employee',
                'stats' => [
                    $this->stat('Current Month Attendance', '0', 'fingerprint', 'orange'),
                    $this->stat('Pending Leave Requests', '0', 'calendar-range', 'yellow'),
                    $this->stat('Latest Net Salary', 'N/A', 'wallet', 'blue'),
                    $this->stat('Finalized Reviews', '0', 'chart-column-big', 'green'),
                ],
                'quick_actions' => [
                    $this->action('My Profile', 'Open your account and employee tabs', 'user', route('profile.index', ['tab' => 'account']), 'orange-light'),
                ],
                'linked_employee_missing' => true,
                'employee' => null,
                'announcements' => collect(),
                'attendance_rows' => collect(),
                'recent_leaves' => collect(),
                'latest_review' => null,
                'latest_payroll' => null,
                'latest_security_snapshot' => null,
            ];
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $attendanceRows = $employee->attendanceRecords()
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->latest('attendance_date')
            ->take(6)
            ->get();

        $latestPayroll = EmployeePayrollRecord::query()
            ->with('payrollRun')
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun')
            ->get()
            ->sortByDesc(fn (EmployeePayrollRecord $record) => $record->payrollRun?->pay_period_month?->timestamp ?? 0)
            ->first();

        $latestReview = $employee->performanceEvaluations()
            ->with(['manager', 'hrFinalizer'])
            ->where('status', PerformanceEvaluation::STATUS_FINALIZED)
            ->orderByDesc('period_start')
            ->first();

        $latestSecuritySnapshot = $employee->securityFundSnapshots()->latest('snapshot_month')->first();

        return [
            'view' => 'employee',
            'title' => 'Dashboard',
            'subtitle' => 'Your current month work summary, recent updates, and self-service shortcuts.',
            'role_label' => 'Employee',
            'stats' => [
                $this->stat('Current Month Attendance', (string) $attendanceRows->count(), 'fingerprint', 'orange'),
                $this->stat('Pending Leave Requests', (string) $employee->leaveRequests()->where('status', 'pending')->count(), 'calendar-range', 'yellow'),
                $this->stat('Latest Net Salary', $this->money($latestPayroll?->net_salary), 'wallet', 'blue'),
                $this->stat('Finalized Reviews', (string) $employee->performanceEvaluations()->where('status', PerformanceEvaluation::STATUS_FINALIZED)->count(), 'chart-column-big', 'green'),
            ],
            'quick_actions' => [
                $this->action('Attendance', 'Open current month attendance', 'fingerprint', route('profile.index', ['tab' => 'attendance']), 'orange-light'),
                $this->action('Leave', 'Apply for leave or track status', 'calendar-range', route('profile.index', ['tab' => 'leave']), 'blue-light'),
                $this->action('Salary History', 'View salary and payslips', 'wallet', route('profile.index', ['tab' => 'salary']), 'purple-light'),
                $this->action('Performance', 'Open finalized reviews', 'chart-column-big', route('profile.index', ['tab' => 'performance']), 'green-light'),
            ],
            'linked_employee_missing' => false,
            'employee' => $employee,
            'announcements' => $user->canAccessModule('announcements')
                ? $this->latestAnnouncementsFor($user)
                : collect(),
            'attendance_rows' => $attendanceRows,
            'recent_leaves' => $employee->leaveRequests()
                ->with('leaveType')
                ->latest('start_date')
                ->take(5)
                ->get(),
            'latest_review' => $latestReview,
            'latest_payroll' => $latestPayroll,
            'latest_security_snapshot' => $latestSecuritySnapshot,
        ];
    }

    protected function buildFallbackDashboard(User $user): array
    {
        $accessibleModules = collect(Role::availableModules())
            ->reject(fn ($label, $module) => $module === 'dashboard')
            ->filter(fn ($label, $module) => $user->canAccessModule($module))
            ->map(fn ($label, $module) => ['module' => $module, 'label' => $label])
            ->values();

        return [
            'view' => 'fallback',
            'title' => 'Dashboard',
            'subtitle' => 'Operational overview based on the modules available to this role.',
            'role_label' => $user->role ?: 'User',
            'stats' => [
                $this->stat('Accessible Modules', (string) $accessibleModules->count(), 'layout-grid', 'orange'),
                $this->stat('Active Employees', $user->canAccessModule('employees') ? (string) Employee::query()->where('status', 'active')->count() : 'N/A', 'users', 'green'),
                $this->stat('Pending Leaves', $user->canAccessModule('leave_management') ? (string) LeaveRequest::query()->where('status', 'pending')->count() : 'N/A', 'calendar-range', 'yellow'),
                $this->stat('Draft Payroll Runs', $user->canAccessModule('payroll_management') ? (string) PayrollRun::query()->where('status', 'draft')->count() : 'N/A', 'wallet-cards', 'blue'),
            ],
            'quick_actions' => $accessibleModules
                ->map(fn (array $module) => $this->quickActionForModule($module['module']))
                ->filter()
                ->take(4)
                ->values()
                ->all(),
            'accessible_modules' => $accessibleModules,
            'recent_activity' => $user->canAccessModule('activity_logs')
                ? ActivityLog::query()->latest()->take(6)->get()
                : collect(),
            'announcements' => $user->canAccessModule('announcements')
                ? $this->latestAnnouncementsFor($user)
                : collect(),
        ];
    }

    protected function latestAnnouncementsFor(User $user): Collection
    {
        return Announcement::query()
            ->visibleTo($user)
            ->with(['departments', 'creator'])
            ->latest('published_at')
            ->latest('created_at')
            ->take(5)
            ->get();
    }

    protected function activeUsersCount(): int
    {
        return User::query()
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->count();
    }

    protected function stat(string $label, string $value, string $icon, string $color, ?string $helper = null): array
    {
        return compact('label', 'value', 'icon', 'color', 'helper');
    }

    protected function action(string $label, string $description, string $icon, string $href, string $tone, ?string $module = null): array
    {
        return compact('label', 'description', 'icon', 'href', 'tone', 'module');
    }

    protected function filterQuickActions(User $user, array $actions): array
    {
        return collect($actions)
            ->filter(fn (array $action) => ! isset($action['module']) || $user->canAccessModule($action['module']))
            ->values()
            ->all();
    }

    protected function quickActionForModule(string $module): ?array
    {
        return match ($module) {
            'employees' => $this->action('Employees', 'Employee records', 'users', route('employees.index'), 'orange-light'),
            'team_management' => $this->action('My Team', 'Assigned employees', 'user-round-search', route('team.index'), 'blue-light'),
            'performance_management' => $this->action('Performance', 'Evaluations and reviews', 'chart-column-big', route('performance.index'), 'purple-light'),
            'leave_management' => $this->action('Leave Management', 'Leave requests', 'calendar-range', route('leaves.index'), 'green-light'),
            'attendance_management' => $this->action('Attendance', 'Attendance records', 'fingerprint', route('attendance.index'), 'orange-light'),
            'payroll_management' => $this->action('Payroll', 'Payout management', 'wallet-cards', route('payroll.index'), 'blue-light'),
            'reports' => $this->action('Reports', 'Analytics and exports', 'files', route('reports.index'), 'purple-light'),
            'announcements' => $this->action('Announcements', 'Company notices', 'megaphone', route('announcements.index'), 'yellow-light'),
            'user_management' => $this->action('User Management', 'Roles and users', 'user-cog', route('users.index'), 'green-light'),
            'settings' => $this->action('Settings', 'System settings', 'settings', route('settings.index'), 'orange-light'),
            'templates' => $this->action('Templates', 'Email and document templates', 'mail', route('templates.index'), 'blue-light'),
            'activity_logs' => $this->action('Activity Logs', 'System audit trail', 'activity', route('activity-logs.index'), 'purple-light'),
            default => null,
        };
    }

    protected function money(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return 'PKR ' . number_format((float) $value, 2);
    }
}
