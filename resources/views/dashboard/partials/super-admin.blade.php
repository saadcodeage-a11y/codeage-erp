@php
    $workforceTotal = collect($dashboard['workforce_mix'])->sum('value');
    $userTotal = collect($dashboard['user_mix'])->sum('value');
    $pendingItemsMax = max(collect($dashboard['pending_items'])->max('value') ?? 0, 1);
    $departmentMax = max($dashboard['departments']->max('employees_count') ?? 0, 1);
@endphp

<section class="rb-section">
    <div class="rb-grid rb-grid--hero">
        <article class="rb-panel rb-panel--spotlight">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Charts',
                'title' => 'Workforce Mix',
                'subtitle' => 'Live employee and user distribution across the organization.'
            ])

            <div class="rb-spotlight">
                <div class="rb-ring-grid">
                    @include('dashboard.partials.shared.rb-ring', [
                        'percentage' => $workforceTotal > 0 ? (($dashboard['workforce_mix'][0]['value'] ?? 0) / $workforceTotal) * 100 : 0,
                        'value' => $workforceTotal,
                        'label' => 'Employees',
                        'color' => '#ff5b2e',
                        'meta' => 'Largest segment: ' . ($dashboard['workforce_mix'][0]['label'] ?? 'Active'),
                    ])
                    @include('dashboard.partials.shared.rb-ring', [
                        'percentage' => $userTotal > 0 ? (($dashboard['user_mix'][0]['value'] ?? 0) / $userTotal) * 100 : 0,
                        'value' => $userTotal,
                        'label' => 'Users',
                        'color' => '#2563eb',
                        'meta' => 'Active user share',
                    ])
                </div>

                <div class="rb-legend-list">
                    @foreach($dashboard['workforce_mix'] as $item)
                        <div class="rb-legend-row">
                            <div class="rb-legend-row__label">
                                <span class="rb-dot" style="background: {{ $item['color'] }};"></span>
                                <span>{{ $item['label'] }}</span>
                            </div>
                            <strong>{{ $item['value'] }}</strong>
                        </div>
                    @endforeach
                    @foreach($dashboard['user_mix'] as $item)
                        <div class="rb-legend-row rb-legend-row--muted">
                            <div class="rb-legend-row__label">
                                <span class="rb-dot" style="background: {{ $item['color'] }};"></span>
                                <span>{{ $item['label'] }}</span>
                            </div>
                            <strong>{{ $item['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Queue',
                'title' => 'Operational Queue',
                'subtitle' => 'Backlog items that still require action.'
            ])

            <div class="rb-bars">
                @foreach($dashboard['pending_items'] as $item)
                    <div class="rb-bar-row">
                        <div class="rb-bar-row__head">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $item['value'] }}</strong>
                        </div>
                        <div class="rb-bar-row__track">
                            <span class="rb-bar-row__fill" style="width: {{ $pendingItemsMax > 0 ? ($item['value'] / $pendingItemsMax) * 100 : 0 }}%;"></span>
                        </div>
                        <p>{{ $item['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </article>
    </div>
</section>

<section class="rb-section">
    <div class="rb-grid">
        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Departments',
                'title' => 'Department Footprint',
                'subtitle' => 'Headcount distribution across departments.'
            ])

            @if($dashboard['departments']->isEmpty())
                @include('dashboard.partials.shared.rb-empty', [
                    'icon' => 'building-2',
                    'title' => 'No departments available',
                    'message' => 'Department distribution will appear here once departments and employees exist.'
                ])
            @else
                <div class="rb-bars">
                    @foreach($dashboard['departments'] as $department)
                        <div class="rb-bar-row">
                            <div class="rb-bar-row__head">
                                <span>{{ $department->name }}</span>
                                <strong>{{ $department->employees_count }}</strong>
                            </div>
                            <div class="rb-bar-row__track">
                                <span class="rb-bar-row__fill rb-bar-row__fill--orange" style="width: {{ $departmentMax > 0 ? ($department->employees_count / $departmentMax) * 100 : 0 }}%;"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Activity',
                'title' => 'Activity Timeline',
                'subtitle' => 'Recent system events and operational movement.'
            ])

            @if($dashboard['recent_activity']->isEmpty())
                @include('dashboard.partials.shared.rb-empty', [
                    'icon' => 'activity',
                    'title' => 'No recent activity',
                    'message' => 'Employee, payroll, and workflow activity will appear here.'
                ])
            @else
                <div class="rb-timeline">
                    @foreach($dashboard['recent_activity'] as $activity)
                        <div class="rb-timeline__item">
                            <span class="rb-timeline__dot rb-timeline__dot--{{ $activity->type === 'success' ? 'green' : 'neutral' }}"></span>
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

<section class="rb-section">
    <article class="rb-panel">
        @include('dashboard.partials.shared.rb-panel-header', [
            'eyebrow' => 'Announcements',
            'title' => 'Latest Announcements',
            'subtitle' => 'Most recent office notices visible to this role.'
        ])

        @if($dashboard['announcements']->isEmpty())
            @include('dashboard.partials.shared.rb-empty', [
                'icon' => 'megaphone',
                'title' => 'No announcements available',
                'message' => 'Published office notices will appear here.'
            ])
        @else
            <div class="rb-card-list">
                @foreach($dashboard['announcements'] as $announcement)
                    <div class="rb-list-card">
                        <div>
                            <span class="rb-list-card__badge">{{ \App\Models\Announcement::types()[$announcement->announcement_type] ?? 'Announcement' }}</span>
                            <strong>{{ $announcement->title }}</strong>
                            <p>
                                {{ $announcement->audienceLabel() }}
                                @if($announcement->eventDateLabel())
                                    &middot; {{ $announcement->eventDateLabel() }}
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </article>
</section>
