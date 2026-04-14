@php
    $attendanceTotal = collect($dashboard['attendance_breakdown'])->sum('value');
    $workforceChangeMax = max($dashboard['recent_hires']->count(), $dashboard['status_changes']->count(), 1);
@endphp

<section class="rb-section">
    <div class="rb-grid rb-grid--hero">
        <article class="rb-panel rb-panel--spotlight">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Operations',
                'title' => 'People Ops Pulse',
                'subtitle' => 'Live view of HR workload across leave, reviews, and workforce movement.'
            ])

            <div class="rb-spotlight">
                <div class="rb-ring-grid">
                    @include('dashboard.partials.shared.rb-ring', [
                        'percentage' => ($dashboard['pending_leave_requests'] + $dashboard['pending_hr_finalizations']) > 0
                            ? ($dashboard['pending_leave_requests'] / ($dashboard['pending_leave_requests'] + $dashboard['pending_hr_finalizations'])) * 100
                            : 0,
                        'value' => $dashboard['pending_leave_requests'],
                        'label' => 'Pending Leave',
                        'color' => '#f59e0b',
                        'meta' => 'Requests waiting for review',
                    ])
                    @include('dashboard.partials.shared.rb-ring', [
                        'percentage' => ($dashboard['pending_leave_requests'] + $dashboard['pending_hr_finalizations']) > 0
                            ? ($dashboard['pending_hr_finalizations'] / ($dashboard['pending_leave_requests'] + $dashboard['pending_hr_finalizations'])) * 100
                            : 0,
                        'value' => $dashboard['pending_hr_finalizations'],
                        'label' => 'Pending HR',
                        'color' => '#2563eb',
                        'meta' => 'Evaluations awaiting HR finalization',
                    ])
                </div>

                <div class="rb-inline-stats">
                    <div class="rb-inline-stats__item">
                        <span>Upcoming approved leave</span>
                        <strong>{{ $dashboard['upcoming_leaves']->count() }}</strong>
                    </div>
                    <div class="rb-inline-stats__item">
                        <span>Recent hires tracked</span>
                        <strong>{{ $dashboard['recent_hires']->count() }}</strong>
                    </div>
                    <div class="rb-inline-stats__item">
                        <span>Status changes tracked</span>
                        <strong>{{ $dashboard['status_changes']->count() }}</strong>
                    </div>
                </div>
            </div>
        </article>

        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Attendance',
                'title' => 'Attendance Breakdown',
                'subtitle' => 'Current month attendance exceptions requiring HR visibility.'
            ])

            <div class="rb-bars">
                @foreach($dashboard['attendance_breakdown'] as $item)
                    <div class="rb-bar-row">
                        <div class="rb-bar-row__head">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $item['value'] }}</strong>
                        </div>
                        <div class="rb-bar-row__track">
                            <span class="rb-bar-row__fill" style="width: {{ $attendanceTotal > 0 ? ($item['value'] / $attendanceTotal) * 100 : 0 }}%; background: {{ $item['color'] }};"></span>
                        </div>
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
                'eyebrow' => 'Workforce',
                'title' => 'Workforce Movement',
                'subtitle' => 'Recent hires and tracked employee status changes.'
            ])

            <div class="rb-comparison">
                <div class="rb-comparison__item">
                    <div class="rb-comparison__head">
                        <span>Recent Hires</span>
                        <strong>{{ $dashboard['recent_hires']->count() }}</strong>
                    </div>
                    <div class="rb-bar-row__track">
                        <span class="rb-bar-row__fill rb-bar-row__fill--green" style="width: {{ ($dashboard['recent_hires']->count() / $workforceChangeMax) * 100 }}%;"></span>
                    </div>
                </div>
                <div class="rb-comparison__item">
                    <div class="rb-comparison__head">
                        <span>Status Changes</span>
                        <strong>{{ $dashboard['status_changes']->count() }}</strong>
                    </div>
                    <div class="rb-bar-row__track">
                        <span class="rb-bar-row__fill rb-bar-row__fill--purple" style="width: {{ ($dashboard['status_changes']->count() / $workforceChangeMax) * 100 }}%;"></span>
                    </div>
                </div>
            </div>

            <div class="rb-card-list rb-card-list--dense">
                @foreach($dashboard['status_changes']->take(3) as $employee)
                    <div class="rb-list-card">
                        <div>
                            <span class="rb-list-card__badge rb-list-card__badge--muted">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</span>
                            <strong>{{ $employee->full_name }}</strong>
                            <p>{{ $employee->designation ?: 'No designation' }} &middot; {{ $employee->department?->name ?? 'Unassigned' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Calendar',
                'title' => 'Upcoming Leave',
                'subtitle' => 'Approved leave already scheduled on the near-term calendar.'
            ])

            @if($dashboard['upcoming_leaves']->isEmpty())
                @include('dashboard.partials.shared.rb-empty', [
                    'icon' => 'calendar-range',
                    'title' => 'No upcoming leave scheduled',
                    'message' => 'Approved leave requests for upcoming dates will appear here.'
                ])
            @else
                <div class="rb-timeline">
                    @foreach($dashboard['upcoming_leaves'] as $leave)
                        <div class="rb-timeline__item">
                            <span class="rb-timeline__dot rb-timeline__dot--orange"></span>
                            <div class="rb-timeline__content">
                                <strong>{{ $leave->employee?->full_name ?? 'Unknown Employee' }}</strong>
                                <p>{{ $leave->leaveType?->name ?? 'Leave' }} &middot; {{ $leave->start_date?->format('d M') }} to {{ $leave->end_date?->format('d M') }} &middot; {{ $leave->days_count }} day{{ $leave->days_count === 1 ? '' : 's' }}</p>
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
            'subtitle' => 'Most recent notices relevant to HR operations.'
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
                            <p>{{ $announcement->audienceLabel() }} &middot; {{ $announcement->published_at?->format('d M Y') ?? 'N/A' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </article>
</section>
