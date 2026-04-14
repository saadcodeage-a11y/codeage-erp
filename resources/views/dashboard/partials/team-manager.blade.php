<div class="dashboard-section-grid dashboard-section-grid-wide">
    <section class="card dashboard-panel dashboard-panel--feature">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Team Snapshot',
            'subtitle' => 'Assigned employees and current team composition at a glance.'
        ])
        @if($dashboard['team_roster']->isEmpty())
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'users-round',
                'title' => 'No assigned employees yet',
                'message' => 'Assigned team members will appear here once HR maps employees to your account.'
            ])
        @else
            <div class="dashboard-list">
                @foreach($dashboard['team_roster'] as $employee)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $employee->full_name }}</strong>
                            <p>{{ $employee->designation ?: 'No designation' }} &middot; {{ $employee->department?->name ?? 'Unassigned' }}</p>
                        </div>
                        <span class="dashboard-status-chip {{ $employee->status === 'active' ? '' : 'muted' }}">{{ ucfirst($employee->status) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Pending / Recent Evaluations',
            'subtitle' => 'Most recent evaluation activity for your assigned team.'
        ])
        @if($dashboard['recent_evaluations']->isEmpty())
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'chart-column-big',
                'title' => 'No team evaluations available',
                'message' => 'New manager drafts and finalized reviews will appear here.'
            ])
        @else
            <div class="dashboard-list">
                @foreach($dashboard['recent_evaluations'] as $evaluation)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $evaluation->employee?->full_name ?? 'Unknown Employee' }}</strong>
                            <p>{{ $evaluation->periodLabel() }} &middot; {{ \App\Models\PerformanceEvaluation::types()[$evaluation->evaluation_type] ?? 'Evaluation' }}</p>
                        </div>
                        <span class="dashboard-status-chip {{ $evaluation->status === \App\Models\PerformanceEvaluation::STATUS_FINALIZED ? '' : 'muted' }}">
                            {{ \App\Models\PerformanceEvaluation::statuses()[$evaluation->status] ?? ucfirst($evaluation->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

<div class="dashboard-section-grid">
    @if($dashboard['team_leaves']->isNotEmpty())
        <section class="card dashboard-panel dashboard-panel--support">
            @include('dashboard.partials.shared.panel-header', [
                'title' => 'Team Leave Activity',
                'subtitle' => 'Recent leave movement across employees assigned to you.'
            ])
            <div class="dashboard-list">
                @foreach($dashboard['team_leaves'] as $leave)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $leave->employee?->full_name ?? 'Unknown Employee' }}</strong>
                            <p>{{ $leave->leaveType?->name ?? 'Leave' }} &middot; {{ $leave->start_date?->format('d M') }} to {{ $leave->end_date?->format('d M') }}</p>
                        </div>
                        <span class="dashboard-status-chip {{ $leave->status === 'approved' ? '' : 'muted' }}">{{ ucfirst($leave->status) }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($dashboard['announcements']->isNotEmpty())
        <section class="card dashboard-panel dashboard-panel--support">
            @include('dashboard.partials.shared.panel-header', [
                'title' => 'Latest Announcements',
                'subtitle' => 'Latest office notices visible to your team role.'
            ])
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
        </section>
    @endif
</div>
