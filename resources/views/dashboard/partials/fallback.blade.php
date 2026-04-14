<section class="rb-section">
    <div class="rb-grid">
        <article class="rb-panel rb-panel--spotlight">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Modules',
                'title' => 'Accessible Modules',
                'subtitle' => 'Modules this role can currently open from the sidebar.'
            ])

            @if($dashboard['accessible_modules']->isEmpty())
                @include('dashboard.partials.shared.rb-empty', [
                    'icon' => 'layout-grid',
                    'title' => 'No additional modules available',
                    'message' => 'This role currently has no extra modules enabled beyond the dashboard.'
                ])
            @else
                <div class="rb-chip-group">
                    @foreach($dashboard['accessible_modules'] as $module)
                        <span class="rb-chip">{{ $module['label'] }}</span>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Activity',
                'title' => 'Recent Activity',
                'subtitle' => 'Latest visible system activity for this role.'
            ])

            @if($dashboard['recent_activity']->isEmpty())
                @include('dashboard.partials.shared.rb-empty', [
                    'icon' => 'activity',
                    'title' => 'No recent activity',
                    'message' => 'Recent workflow activity will appear here when it becomes available.'
                ])
            @else
                <div class="rb-timeline">
                    @foreach($dashboard['recent_activity'] as $activity)
                        <div class="rb-timeline__item">
                            <span class="rb-timeline__dot rb-timeline__dot--neutral"></span>
                            <div class="rb-timeline__content">
                                <strong>{{ $activity->description }}</strong>
                                <p>{{ ucfirst($activity->type ?? 'info') }} &middot; {{ $activity->created_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </div>
</section>

@if($dashboard['announcements']->isNotEmpty())
    <section class="rb-section">
        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Announcements',
                'title' => 'Latest Announcements',
                'subtitle' => 'Published notices currently visible to this role.'
            ])
            <div class="rb-card-list">
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
        </article>
    </section>
@endif
