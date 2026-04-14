<div class="dashboard-section-grid">
    <section class="card dashboard-panel">
        <div class="card-header">
            <h3>Accessible Modules</h3>
        </div>
        @if($dashboard['accessible_modules']->isEmpty())
            <div class="dashboard-empty-state">No additional modules are available for this role.</div>
        @else
            <div class="dashboard-chip-grid">
                @foreach($dashboard['accessible_modules'] as $module)
                    <span class="dashboard-status-chip">{{ $module['label'] }}</span>
                @endforeach
            </div>
        @endif
    </section>

    @if($dashboard['announcements']->isNotEmpty())
        <section class="card dashboard-panel">
            <div class="card-header">
                <h3>Latest Announcements</h3>
            </div>
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

@if($dashboard['recent_activity']->isNotEmpty())
    <section class="card dashboard-panel">
        <div class="card-header">
            <h3>Recent Activity</h3>
        </div>
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
    </section>
@endif
