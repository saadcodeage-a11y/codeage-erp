@if($dashboard['linked_employee_missing'])
    <section class="card dashboard-panel dashboard-notice-card">
        <div class="card-header">
            <h3>No Linked Employee Profile</h3>
        </div>
        <p class="dashboard-note-text">Your login is active, but it is not linked to an employee record yet. Contact HR to complete the employee mapping before attendance, payroll, leave, and performance data can appear here.</p>
    </section>
@else
    <div class="dashboard-section-grid">
        <section class="card dashboard-panel">
            <div class="card-header">
                <h3>Latest Announcements</h3>
            </div>
            @if($dashboard['announcements']->isEmpty())
                <div class="dashboard-empty-state">No announcements available right now.</div>
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

        <section class="card dashboard-panel">
            <div class="card-header">
                <h3>Current Month Attendance</h3>
            </div>
            @if($dashboard['attendance_rows']->isEmpty())
                <div class="dashboard-empty-state">No attendance rows found for this month.</div>
            @else
                <div class="dashboard-list">
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
    </div>

    <div class="dashboard-section-grid">
        <section class="card dashboard-panel">
            <div class="card-header">
                <h3>Recent Leave Status</h3>
            </div>
            @if($dashboard['recent_leaves']->isEmpty())
                <div class="dashboard-empty-state">No leave requests submitted yet.</div>
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

        <section class="card dashboard-panel">
            <div class="card-header">
                <h3>Latest Finalized Review</h3>
            </div>
            @if(! $dashboard['latest_review'])
                <div class="dashboard-empty-state">No finalized performance reviews available.</div>
            @else
                <div class="dashboard-metric-grid">
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
        <section class="card dashboard-panel">
            <div class="card-header">
                <h3>Payroll, Tax, and Security Highlights</h3>
            </div>
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
