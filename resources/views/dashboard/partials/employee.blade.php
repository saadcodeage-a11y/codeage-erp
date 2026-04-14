@if($dashboard['linked_employee_missing'])
    <section class="card dashboard-panel dashboard-notice-card">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'No Linked Employee Profile',
            'subtitle' => 'This account is active, but it is not mapped to an employee record yet.'
        ])
        <div class="dashboard-panel-body">
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'user-x',
                'title' => 'Employee mapping is still pending',
                'message' => 'Contact HR to complete the employee linkage before attendance, payroll, leave, and performance data can appear here.'
            ])
        </div>
    </section>
@else
    <div class="dashboard-section-grid dashboard-section-grid-wide">
        <section class="card dashboard-panel dashboard-panel--feature">
            @include('dashboard.partials.shared.panel-header', [
                'title' => 'This Month at a Glance',
                'subtitle' => 'Your current attendance picture for this month.'
            ])

            <div class="dashboard-metric-grid dashboard-metric-grid--compact">
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Present</span>
                    <strong>{{ $dashboard['attendance_summary']['present'] }}</strong>
                    <p>Marked present this month.</p>
                </div>
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Late</span>
                    <strong>{{ $dashboard['attendance_summary']['late'] }}</strong>
                    <p>Days marked late this month.</p>
                </div>
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Absent</span>
                    <strong>{{ $dashboard['attendance_summary']['absent'] }}</strong>
                    <p>Absence rows in the current month.</p>
                </div>
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Incomplete / Issues</span>
                    <strong>{{ $dashboard['attendance_summary']['incomplete'] + $dashboard['attendance_summary']['early_leave'] }}</strong>
                    <p>Incomplete and early-leave records combined.</p>
                </div>
            </div>

            @if($dashboard['attendance_rows']->isEmpty())
                @include('dashboard.partials.shared.empty-state', [
                    'icon' => 'fingerprint',
                    'title' => 'No attendance rows found',
                    'message' => 'Current month attendance will appear here after attendance is imported.'
                ])
            @else
                <div class="dashboard-list dashboard-list--compact">
                    @foreach($dashboard['attendance_rows'] as $attendance)
                        <div class="dashboard-list-item">
                            <div>
                                <strong>{{ $attendance->attendance_date?->format('d M Y') }}</strong>
                                <p>{{ $attendance->clock_in ?: '--:--' }} to {{ $attendance->clock_out ?: '--:--' }} &middot; Work {{ $attendance->work_duration ?: '--:--' }}</p>
                            </div>
                            <span class="dashboard-status-chip {{ $attendance->status === \App\Models\AttendanceRecord::STATUS_PRESENT ? '' : 'muted' }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card dashboard-panel dashboard-panel--support">
            @include('dashboard.partials.shared.panel-header', [
                'title' => 'Latest Announcements',
                'subtitle' => 'Notices and office updates currently visible to you.'
            ])
            @if($dashboard['announcements']->isEmpty())
                @include('dashboard.partials.shared.empty-state', [
                    'icon' => 'megaphone',
                    'title' => 'No announcements available',
                    'message' => 'New office notices will appear here when they are published.'
                ])
            @else
                <div class="dashboard-list">
                    @foreach($dashboard['announcements'] as $announcement)
                        <div class="dashboard-list-item">
                            <div>
                                <strong>{{ $announcement->title }}</strong>
                                <p>{{ $announcement->audienceLabel() }} &middot; {{ $announcement->published_at?->format('d M Y') ?? 'N/A' }}</p>
                            </div>
                            <span class="dashboard-status-chip">{{ \App\Models\Announcement::types()[$announcement->announcement_type] ?? 'Announcement' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="dashboard-section-grid">
        <section class="card dashboard-panel dashboard-panel--support">
            @include('dashboard.partials.shared.panel-header', [
                'title' => 'Recent Leave Status',
                'subtitle' => 'Your latest leave requests and their current status.'
            ])
            @if($dashboard['recent_leaves']->isEmpty())
                @include('dashboard.partials.shared.empty-state', [
                    'icon' => 'calendar-range',
                    'title' => 'No leave requests submitted',
                    'message' => 'Your latest leave requests and approval status will appear here.'
                ])
            @else
                <div class="dashboard-list">
                    @foreach($dashboard['recent_leaves'] as $leave)
                        <div class="dashboard-list-item">
                            <div>
                                <strong>{{ $leave->leaveType?->name ?? 'Leave' }}</strong>
                                <p>{{ $leave->start_date?->format('d M') }} to {{ $leave->end_date?->format('d M') }} &middot; {{ $leave->days_count }} day{{ $leave->days_count === 1 ? '' : 's' }}</p>
                            </div>
                            <span class="dashboard-status-chip {{ $leave->status === 'approved' ? '' : 'muted' }}">{{ ucfirst($leave->status) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card dashboard-panel dashboard-panel--support">
            @include('dashboard.partials.shared.panel-header', [
                'title' => 'Latest Finalized Review',
                'subtitle' => 'Your most recent HR-finalized performance review.'
            ])
            @if(! $dashboard['latest_review'])
                @include('dashboard.partials.shared.empty-state', [
                    'icon' => 'chart-column-big',
                    'title' => 'No finalized performance reviews',
                    'message' => 'HR-finalized reviews will appear here once they are completed.'
                ])
            @else
                <div class="dashboard-metric-grid dashboard-metric-grid--compact">
                    <div class="dashboard-metric-card">
                        <span class="dashboard-metric-label">Period</span>
                        <strong>{{ $dashboard['latest_review']->periodLabel() }}</strong>
                        <p>{{ \App\Models\PerformanceEvaluation::types()[$dashboard['latest_review']->evaluation_type] ?? 'Evaluation' }}</p>
                    </div>
                    <div class="dashboard-metric-card">
                        <span class="dashboard-metric-label">Final Score</span>
                        <strong>{{ number_format((float) ($dashboard['latest_review']->hrAverage() ?? 0), 2) }}</strong>
                        <p>Finalized by HR.</p>
                    </div>
                    <div class="dashboard-metric-card">
                        <span class="dashboard-metric-label">Feedback</span>
                        <strong>{{ $dashboard['latest_review']->hr_feedback ? 'Available' : 'Not added' }}</strong>
                        <p>{{ \Illuminate\Support\Str::limit($dashboard['latest_review']->hr_feedback ?? $dashboard['latest_review']->manager_feedback, 90) }}</p>
                    </div>
                </div>
            @endif
        </section>
    </div>

    <div class="dashboard-section-grid">
        <section class="card dashboard-panel dashboard-panel--support">
            @include('dashboard.partials.shared.panel-header', [
                'title' => 'Payroll / Tax / Security Highlights',
                'subtitle' => 'Your latest saved salary, tax, and security snapshot.'
            ])
            <div class="dashboard-metric-grid">
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Latest Net Salary</span>
                    <strong>{{ $dashboard['latest_payroll'] ? 'PKR ' . number_format((float) $dashboard['latest_payroll']->net_salary, 2) : 'N/A' }}</strong>
                    <p>{{ $dashboard['latest_payroll']?->payrollRun?->pay_period_month?->format('F Y') ?? 'No payroll record yet' }}</p>
                </div>
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Latest Tax</span>
                    <strong>{{ $dashboard['latest_payroll'] ? 'PKR ' . number_format((float) $dashboard['latest_payroll']->income_tax, 2) : 'N/A' }}</strong>
                    <p>Most recent salary tax deduction.</p>
                </div>
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Security Balance</span>
                    <strong>{{ $dashboard['latest_security_snapshot'] ? 'PKR ' . number_format((float) $dashboard['latest_security_snapshot']->balance_in_account, 2) : 'N/A' }}</strong>
                    <p>{{ $dashboard['latest_security_snapshot']?->snapshot_month?->format('F Y') ?? 'No security record yet' }}</p>
                </div>
            </div>
        </section>
    </div>
@endif
