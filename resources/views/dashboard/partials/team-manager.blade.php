<div class="dashboard-section-grid">
    <section class="card">
        <div class="card-header">
            <h3>Team Roster Snapshot</h3>
        </div>
        @if($dashboard['team_roster']->isEmpty())
            <div class="dashboard-empty-state">No employees are assigned to your team yet.</div>
        @else
            <div class="dashboard-list">
                @foreach($dashboard['team_roster'] as $employee)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $employee->full_name }}</strong>
                            <p>{{ $employee->designation ?: 'No designation' }} · {{ $employee->department?->name ?? 'Unassigned' }}</p>
                        </div>
                        <span class="dashboard-status-chip {{ $employee->status === 'active' ? '' : 'muted' }}">{{ ucfirst($employee->status) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card">
        <div class="card-header">
            <h3>Recent Evaluations</h3>
        </div>
        @if($dashboard['recent_evaluations']->isEmpty())
            <div class="dashboard-empty-state">No team evaluations available.</div>
        @else
            <div class="dashboard-list">
                @foreach($dashboard['recent_evaluations'] as $evaluation)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $evaluation->employee?->full_name ?? 'Unknown Employee' }}</strong>
                            <p>{{ $evaluation->periodLabel() }} · {{ \App\Models\PerformanceEvaluation::types()[$evaluation->evaluation_type] ?? 'Evaluation' }}</p>
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
        <section class="card">
            <div class="card-header">
                <h3>Team Leave Activity</h3>
            </div>
            <div class="dashboard-list">
                @foreach($dashboard['team_leaves'] as $leave)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $leave->employee?->full_name ?? 'Unknown Employee' }}</strong>
                            <p>{{ $leave->leaveType?->name ?? 'Leave' }} · {{ $leave->start_date?->format('d M') }} to {{ $leave->end_date?->format('d M') }}</p>
                        </div>
                        <span class="dashboard-status-chip {{ $leave->status === 'approved' ? '' : 'muted' }}">{{ ucfirst($leave->status) }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($dashboard['announcements']->isNotEmpty())
        <section class="card">
            <div class="card-header">
                <h3>Latest Announcements</h3>
            </div>
            <div class="dashboard-list">
                @foreach($dashboard['announcements'] as $announcement)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $announcement->title }}</strong>
                            <p>{{ $announcement->audienceLabel() }} · {{ $announcement->published_at?->format('d M Y') ?? 'N/A' }}</p>
                        </div>
                        <span class="dashboard-status-chip">{{ \App\Models\Announcement::types()[$announcement->announcement_type] ?? 'Announcement' }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
