@php
    $attendanceTotal = collect($dashboard['attendance_summary'] ?? [])->sum();
    $leaveStatusTotal = collect($dashboard['leave_status_mix'] ?? [])->sum('value');
@endphp

@if($dashboard['linked_employee_missing'])
    <section class="rb-section">
        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Profile',
                'title' => 'Employee Mapping Required',
                'subtitle' => 'This account is active but is not linked to an employee profile yet.'
            ])

            @include('dashboard.partials.shared.rb-empty', [
                'icon' => 'user-x',
                'title' => 'No linked employee record',
                'message' => 'Contact HR to complete the employee mapping before attendance, payroll, leave, and performance data can appear here.'
            ])
        </article>
    </section>
@else
    <section class="rb-section">
        <div class="rb-grid rb-grid--hero">
            <article class="rb-panel rb-panel--spotlight">
                @include('dashboard.partials.shared.rb-panel-header', [
                    'eyebrow' => 'Attendance',
                    'title' => 'This Month at a Glance',
                    'subtitle' => 'Current month attendance summary and work consistency.'
                ])

                <div class="rb-spotlight">
                    <div class="rb-ring-grid">
                        @include('dashboard.partials.shared.rb-ring', [
                            'percentage' => $attendanceTotal > 0 ? (($dashboard['attendance_summary']['present'] ?? 0) / $attendanceTotal) * 100 : 0,
                            'value' => $dashboard['attendance_summary']['present'] ?? 0,
                            'label' => 'Present',
                            'color' => '#16a34a',
                            'meta' => 'Current month presence',
                        ])
                        @include('dashboard.partials.shared.rb-ring', [
                            'percentage' => $attendanceTotal > 0 ? (($dashboard['attendance_summary']['late'] ?? 0) / $attendanceTotal) * 100 : 0,
                            'value' => $attendanceTotal,
                            'label' => 'Month Rows',
                            'color' => '#f59e0b',
                            'meta' => 'Attendance rows this month',
                        ])
                    </div>

                    <div class="rb-legend-list">
                        <div class="rb-legend-row">
                            <div class="rb-legend-row__label"><span class="rb-dot" style="background:#16a34a;"></span><span>Present</span></div>
                            <strong>{{ $dashboard['attendance_summary']['present'] ?? 0 }}</strong>
                        </div>
                        <div class="rb-legend-row">
                            <div class="rb-legend-row__label"><span class="rb-dot" style="background:#f59e0b;"></span><span>Late</span></div>
                            <strong>{{ $dashboard['attendance_summary']['late'] ?? 0 }}</strong>
                        </div>
                        <div class="rb-legend-row">
                            <div class="rb-legend-row__label"><span class="rb-dot" style="background:#ef4444;"></span><span>Absent</span></div>
                            <strong>{{ $dashboard['attendance_summary']['absent'] ?? 0 }}</strong>
                        </div>
                        <div class="rb-legend-row">
                            <div class="rb-legend-row__label"><span class="rb-dot" style="background:#8b5cf6;"></span><span>Incomplete / Early Leave</span></div>
                            <strong>{{ ($dashboard['attendance_summary']['incomplete'] ?? 0) + ($dashboard['attendance_summary']['early_leave'] ?? 0) }}</strong>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rb-panel">
                @include('dashboard.partials.shared.rb-panel-header', [
                    'eyebrow' => 'Announcements',
                    'title' => 'Latest Announcements',
                    'subtitle' => 'Notices and office updates currently visible to you.'
                ])

                @if($dashboard['announcements']->isEmpty())
                    @include('dashboard.partials.shared.rb-empty', [
                        'icon' => 'megaphone',
                        'title' => 'No announcements available',
                        'message' => 'New office notices will appear here when they are published.'
                    ])
                @else
                    <div class="rb-card-list rb-card-list--dense">
                        @foreach($dashboard['announcements'] as $announcement)
                            <div class="rb-list-card">
                                <div>
                                    <span class="rb-list-card__badge">{{ \App\Models\Announcement::types()[$announcement->announcement_type] ?? 'Announcement' }}</span>
                                    <strong>{{ $announcement->title }}</strong>
                                    <p>{{ $announcement->audienceLabel() }} &middot; {{ $announcement->published_at?->format('d M Y') ?? 'N/A' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </div>
    </section>

    <section class="rb-section">
        <div class="rb-grid">
            <article class="rb-panel">
                @include('dashboard.partials.shared.rb-panel-header', [
                    'eyebrow' => 'Leave',
                    'title' => 'Leave Timeline',
                    'subtitle' => 'Your latest leave requests and current status.'
                ])

                @if($dashboard['recent_leaves']->isEmpty())
                    @include('dashboard.partials.shared.rb-empty', [
                        'icon' => 'calendar-range',
                        'title' => 'No leave requests submitted',
                        'message' => 'Leave requests and approval status will appear here.'
                    ])
                @else
                    <div class="rb-timeline">
                        @foreach($dashboard['recent_leaves'] as $leave)
                            <div class="rb-timeline__item">
                                <span class="rb-timeline__dot rb-timeline__dot--{{ $leave->status === 'approved' ? 'green' : 'orange' }}"></span>
                                <div class="rb-timeline__content">
                                    <strong>{{ $leave->leaveType?->name ?? 'Leave' }}</strong>
                                    <p>{{ $leave->start_date?->format('d M') }} to {{ $leave->end_date?->format('d M') }} &middot; {{ $leave->days_count }} day{{ $leave->days_count === 1 ? '' : 's' }} &middot; {{ ucfirst($leave->status) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="rb-inline-stats">
                        @foreach($dashboard['leave_status_mix'] as $item)
                            <div class="rb-inline-stats__item">
                                <span>{{ $item['label'] }}</span>
                                <strong>{{ $item['value'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="rb-panel">
                @include('dashboard.partials.shared.rb-panel-header', [
                    'eyebrow' => 'Review',
                    'title' => 'Latest Finalized Review',
                    'subtitle' => 'Your most recent HR-finalized performance review.'
                ])

                @if(! $dashboard['latest_review'])
                    @include('dashboard.partials.shared.rb-empty', [
                        'icon' => 'chart-column-big',
                        'title' => 'No finalized performance reviews',
                        'message' => 'HR-finalized reviews will appear here once they are completed.'
                    ])
                @else
                    <div class="rb-review-grid">
                        <div class="rb-review-grid__score">
                            <span>Final Score</span>
                            <strong>{{ number_format((float) ($dashboard['latest_review']->hrAverage() ?? 0), 2) }}</strong>
                            <p>{{ $dashboard['latest_review']->periodLabel() }}</p>
                        </div>
                        <div class="rb-review-grid__details">
                            <div class="rb-review-grid__detail">
                                <span>Evaluation Type</span>
                                <strong>{{ \App\Models\PerformanceEvaluation::types()[$dashboard['latest_review']->evaluation_type] ?? 'Evaluation' }}</strong>
                            </div>
                            <div class="rb-review-grid__detail">
                                <span>Feedback</span>
                                <strong>{{ $dashboard['latest_review']->hr_feedback ? 'Available' : 'Not added' }}</strong>
                            </div>
                            <p>{{ \Illuminate\Support\Str::limit($dashboard['latest_review']->hr_feedback ?? $dashboard['latest_review']->manager_feedback, 120) }}</p>
                        </div>
                    </div>
                @endif
            </article>
        </div>
    </section>

    <section class="rb-section">
        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Finance',
                'title' => 'Compensation Snapshot',
                'subtitle' => 'Latest saved salary, tax, and security information.'
            ])

            <div class="rb-financial-grid">
                <div class="rb-financial-card">
                    <span>Latest Net Salary</span>
                    <strong>{{ $dashboard['latest_payroll'] ? 'PKR ' . number_format((float) $dashboard['latest_payroll']->net_salary, 2) : 'N/A' }}</strong>
                    <p>{{ $dashboard['latest_payroll']?->payrollRun?->pay_period_month?->format('F Y') ?? 'No payroll record yet' }}</p>
                </div>
                <div class="rb-financial-card">
                    <span>Income Tax</span>
                    <strong>{{ $dashboard['latest_payroll'] ? 'PKR ' . number_format((float) $dashboard['latest_payroll']->income_tax, 2) : 'N/A' }}</strong>
                    <p>Most recent salary tax deduction.</p>
                </div>
                <div class="rb-financial-card">
                    <span>Security Balance</span>
                    <strong>{{ $dashboard['latest_security_snapshot'] ? 'PKR ' . number_format((float) $dashboard['latest_security_snapshot']->balance_in_account, 2) : 'N/A' }}</strong>
                    <p>{{ $dashboard['latest_security_snapshot']?->snapshot_month?->format('F Y') ?? 'No security record yet' }}</p>
                </div>
            </div>
        </article>
    </section>
@endif
