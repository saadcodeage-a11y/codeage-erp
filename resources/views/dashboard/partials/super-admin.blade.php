<div class="dashboard-section-grid dashboard-section-grid-wide">
    <section class="card">
        <div class="card-header">
            <h3>Recent Activity</h3>
        </div>
        @if($dashboard['recent_activity']->isEmpty())
            <div class="dashboard-empty-state">No recent activity yet.</div>
        @else
            <div class="dashboard-list">
                @foreach($dashboard['recent_activity'] as $activity)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $activity->description }}</strong>
                            <p>{{ $activity->created_at?->diffForHumans() }}</p>
                        </div>
                        <span class="dashboard-status-chip muted">{{ ucfirst($activity->type ?? 'info') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card">
        <div class="card-header">
            <h3>Pending Operational Items</h3>
        </div>
        <div class="dashboard-metric-grid">
            @foreach($dashboard['pending_items'] as $item)
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">{{ $item['label'] }}</span>
                    <strong>{{ $item['value'] }}</strong>
                    <p>{{ $item['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
</div>

<div class="dashboard-section-grid">
    <section class="card">
        <div class="card-header">
            <h3>Department Overview</h3>
        </div>
        @if($dashboard['departments']->isEmpty())
            <div class="dashboard-empty-state">No departments available.</div>
        @else
            <div class="department-list">
                @php($maxDeptEmployees = $dashboard['departments']->max('employees_count') ?: 1)
                @foreach($dashboard['departments'] as $department)
                    <div class="dept-item">
                        <div class="dept-info">
                            <span class="dept-name">{{ $department->name }}</span>
                            <span class="dept-count">{{ $department->employees_count }}</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ ($department->employees_count / $maxDeptEmployees) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card">
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
                            <p>
                                {{ $announcement->audienceLabel() }}
                                @if($announcement->eventDateLabel())
                                    · {{ $announcement->eventDateLabel() }}
                                @endif
                            </p>
                        </div>
                        <span class="dashboard-status-chip">{{ \App\Models\Announcement::types()[$announcement->announcement_type] ?? 'Announcement' }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
