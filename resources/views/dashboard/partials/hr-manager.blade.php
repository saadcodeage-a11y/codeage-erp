<div class="dashboard-section-grid dashboard-section-grid-wide">
    <section class="card dashboard-panel dashboard-panel--feature">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Upcoming Leave & HR Queue',
            'subtitle' => 'Approved leave already on the calendar and HR review work still open.'
        ])
        @if($dashboard['upcoming_leaves']->isEmpty())
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'calendar-range',
                'title' => 'No upcoming leave scheduled',
                'message' => 'Approved leave requests for upcoming dates will appear here.'
            ])
        @else
            <div class="dashboard-list">
                @foreach($dashboard['upcoming_leaves'] as $leave)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $leave->employee?->full_name ?? 'Unknown Employee' }}</strong>
                            <p>{{ $leave->leaveType?->name ?? 'Leave' }} &middot; {{ $leave->start_date?->format('d M') }} to {{ $leave->end_date?->format('d M') }}</p>
                        </div>
                        <span class="dashboard-status-chip">{{ $leave->days_count }} day{{ $leave->days_count === 1 ? '' : 's' }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="dashboard-inline-metrics">
            <div class="dashboard-inline-metric">
                <span>Pending leave requests</span>
                <strong>{{ $dashboard['pending_leave_requests'] }}</strong>
            </div>
            <div class="dashboard-inline-metric">
                <span>Pending HR finalizations</span>
                <strong>{{ $dashboard['pending_hr_finalizations'] }}</strong>
            </div>
            <div class="dashboard-inline-metric">
                <span>Recent hires tracked</span>
                <strong>{{ $dashboard['recent_hires']->count() }}</strong>
            </div>
        </div>
    </section>

    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Attendance Exceptions',
            'subtitle' => 'Current month employee issues requiring HR visibility.'
        ])
        <div class="dashboard-metric-grid">
            <div class="dashboard-metric-card">
                <span class="dashboard-metric-label">Current Month Exceptions</span>
                <strong>{{ $dashboard['attendance_exceptions'] }}</strong>
                <p>Late, absent, incomplete, and early-leave rows this month.</p>
            </div>
            <div class="dashboard-metric-card">
                <span class="dashboard-metric-label">Pending Leave Requests</span>
                <strong>{{ $dashboard['pending_leave_requests'] }}</strong>
                <p>Requests still waiting for review.</p>
            </div>
            <div class="dashboard-metric-card">
                <span class="dashboard-metric-label">Pending HR Finalizations</span>
                <strong>{{ $dashboard['pending_hr_finalizations'] }}</strong>
                <p>Performance reviews still awaiting HR closure.</p>
            </div>
            <div class="dashboard-metric-card">
                <span class="dashboard-metric-label">Tracked Workforce Changes</span>
                <strong>{{ $dashboard['status_changes']->count() }}</strong>
                <p>Recent inactive, resigned, terminated, and on-leave employees.</p>
            </div>
        </div>
    </section>
</div>

<div class="dashboard-section-grid">
    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Recent Workforce Changes',
            'subtitle' => 'Recent hires and status changes across the workforce.'
        ])
        <div class="dashboard-split-content">
            <div class="dashboard-subsection">
                <span class="dashboard-subsection-label">Recent Hires</span>
                @if($dashboard['recent_hires']->isEmpty())
                    @include('dashboard.partials.shared.empty-state', [
                        'icon' => 'user-plus',
                        'title' => 'No recent hires',
                        'message' => 'Newly hired employees will appear here.'
                    ])
                @else
                    <div class="dashboard-list dashboard-list--compact">
                        @foreach($dashboard['recent_hires'] as $employee)
                            <div class="dashboard-list-item">
                                <div>
                                    <strong>{{ $employee->full_name }}</strong>
                                    <p>{{ $employee->designation ?: 'No designation' }} &middot; Joined {{ $employee->hiring_date?->format('d M Y') ?? 'N/A' }}</p>
                                </div>
                                <span class="dashboard-status-chip">{{ $employee->department?->name ?? 'Unassigned' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="dashboard-subsection">
                <span class="dashboard-subsection-label">Status Changes</span>
                @if($dashboard['status_changes']->isEmpty())
                    @include('dashboard.partials.shared.empty-state', [
                        'icon' => 'triangle-alert',
                        'title' => 'No recent status changes',
                        'message' => 'Recent workforce status changes will appear here.'
                    ])
                @else
                    <div class="dashboard-list dashboard-list--compact">
                        @foreach($dashboard['status_changes'] as $employee)
                            <div class="dashboard-list-item">
                                <div>
                                    <strong>{{ $employee->full_name }}</strong>
                                    <p>{{ $employee->designation ?: 'No designation' }} &middot; {{ $employee->department?->name ?? 'Unassigned' }}</p>
                                </div>
                                <span class="dashboard-status-chip muted">{{ str_replace('_', ' ', ucfirst($employee->status)) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Latest Announcements',
            'subtitle' => 'Most recent notices relevant to HR operations.'
        ])
        @if($dashboard['announcements']->isEmpty())
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'megaphone',
                'title' => 'No announcements available',
                'message' => 'Published office notices will appear here.'
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
