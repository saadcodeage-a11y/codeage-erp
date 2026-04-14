<div class="dashboard-section-grid">
    <section class="card dashboard-panel dashboard-panel--feature">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Accessible Modules',
            'subtitle' => 'Modules this role can currently open from the sidebar.'
        ])
        @if($dashboard['accessible_modules']->isEmpty())
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'layout-grid',
                'title' => 'No additional modules available',
                'message' => 'This role currently has no extra modules enabled beyond the dashboard.'
            ])
        @else
            <div class="dashboard-chip-grid">
                @foreach($dashboard['accessible_modules'] as $module)
                    <span class="dashboard-status-chip">{{ $module['label'] }}</span>
                @endforeach
            </div>
        @endif
    </section>

    @if($dashboard['announcements']->isNotEmpty())
        <section class="card dashboard-panel dashboard-panel--support">
            @include('dashboard.partials.shared.panel-header', [
                'title' => 'Latest Announcements',
                'subtitle' => 'Published notices currently visible to this role.'
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

@if($dashboard['recent_activity']->isNotEmpty())
    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Recent Activity',
            'subtitle' => 'Latest activity available to this role.'
        ])
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
