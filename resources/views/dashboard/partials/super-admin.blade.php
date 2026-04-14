<div class="dashboard-section-grid dashboard-section-grid-wide">
    <section class="card dashboard-panel dashboard-panel--feature">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Operational Overview',
            'subtitle' => 'Latest org-wide activity and system movement.'
        ])
        @if($dashboard['recent_activity']->isEmpty())
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'activity',
                'title' => 'No recent activity',
                'message' => 'New employee, payroll, and workflow activity will appear here.'
            ])
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

    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Pending Items',
            'subtitle' => 'Operational queues that still need follow-up.'
        ])
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
    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Department Overview',
            'subtitle' => 'Relative headcount distribution across departments.'
        ])
        @if($dashboard['departments']->isEmpty())
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'building-2',
                'title' => 'No departments available',
                'message' => 'Department distribution will appear once departments and employees exist.'
            ])
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

    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Latest Announcements',
            'subtitle' => 'Most recent office notices visible to this role.'
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
                            <p>
                                {{ $announcement->audienceLabel() }}
                                @if($announcement->eventDateLabel())
                                    &middot; {{ $announcement->eventDateLabel() }}
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
