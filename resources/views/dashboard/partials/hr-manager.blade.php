<div class="dashboard-section-grid">
    <section class="card dashboard-panel">
        <div class="card-header">
            <h3>Upcoming Leave Calendar</h3>
        </div>
        @if($dashboard['upcoming_leaves']->isEmpty())
            <div class="dashboard-empty-state">No upcoming approved leave requests.</div>
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
    </section>

    <section class="card dashboard-panel">
        <div class="card-header">
            <h3>Recent Workforce Changes</h3>
        </div>
        @if($dashboard['status_changes']->isEmpty())
            <div class="dashboard-empty-state">No recent status changes.</div>
        @else
            <div class="dashboard-list">
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
    </section>
</div>

<div class="dashboard-section-grid">
    <section class="card dashboard-panel">
        <div class="card-header">
            <h3>Recent Hires</h3>
        </div>
        @if($dashboard['recent_hires']->isEmpty())
            <div class="dashboard-empty-state">No recent hires found.</div>
        @else
            <div class="dashboard-list">
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
    </section>

    <section class="card dashboard-panel">
        <div class="card-header">
            <h3>Latest Announcements</h3>
        </div>
        @if($dashboard['announcements']->isEmpty())
            <div class="dashboard-empty-state">No announcements available.</div>
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
