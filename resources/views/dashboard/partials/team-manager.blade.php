@php
    $teamStatusTotal = collect($dashboard['team_status_mix'])->sum('value');
    $evaluationTotal = collect($dashboard['evaluation_status_mix'])->sum('value');
    $attendanceIssueTotal = collect($dashboard['attendance_issue_breakdown'])->sum('value');
@endphp

<section class="rb-section">
    <div class="rb-grid rb-grid--hero">
        <article class="rb-panel rb-panel--spotlight">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Team',
                'title' => 'Team Health',
                'subtitle' => 'Assigned team composition and current operational health.'
            ])

            <div class="rb-spotlight">
                <div class="rb-ring-grid">
                    @include('dashboard.partials.shared.rb-ring', [
                        'percentage' => $teamStatusTotal > 0 ? (($dashboard['team_status_mix'][0]['value'] ?? 0) / $teamStatusTotal) * 100 : 0,
                        'value' => $dashboard['team_status_mix'][0]['value'] ?? 0,
                        'label' => 'Active Team',
                        'color' => '#16a34a',
                        'meta' => 'Active assigned employees',
                    ])
                    @include('dashboard.partials.shared.rb-ring', [
                        'percentage' => $evaluationTotal > 0 ? (($dashboard['evaluation_status_mix'][0]['value'] ?? 0) / $evaluationTotal) * 100 : 0,
                        'value' => $evaluationTotal,
                        'label' => 'Evaluations',
                        'color' => '#2563eb',
                        'meta' => 'Current review pipeline',
                    ])
                </div>

                <div class="rb-legend-list">
                    @foreach($dashboard['team_status_mix'] as $item)
                        <div class="rb-legend-row">
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
                'eyebrow' => 'Pipeline',
                'title' => 'Evaluation Pipeline',
                'subtitle' => 'Status distribution of team evaluations.'
            ])

            <div class="rb-bars">
                @foreach($dashboard['evaluation_status_mix'] as $item)
                    <div class="rb-bar-row">
                        <div class="rb-bar-row__head">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $item['value'] }}</strong>
                        </div>
                        <div class="rb-bar-row__track">
                            <span class="rb-bar-row__fill" style="width: {{ $evaluationTotal > 0 ? ($item['value'] / $evaluationTotal) * 100 : 0 }}%; background: {{ $item['color'] }};"></span>
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
                'eyebrow' => 'Roster',
                'title' => 'Assigned Team Members',
                'subtitle' => 'Current team members mapped to your account.'
            ])

            @if($dashboard['team_roster']->isEmpty())
                @include('dashboard.partials.shared.rb-empty', [
                    'icon' => 'users-round',
                    'title' => 'No assigned employees yet',
                    'message' => 'Assigned team members will appear here once HR maps employees to your account.'
                ])
            @else
                <div class="rb-card-list rb-card-list--dense">
                    @foreach($dashboard['team_roster'] as $employee)
                        <div class="rb-list-card">
                            <div>
                                <span class="rb-list-card__badge {{ $employee->status === 'active' ? '' : 'rb-list-card__badge--muted' }}">{{ ucfirst($employee->status) }}</span>
                                <strong>{{ $employee->full_name }}</strong>
                                <p>{{ $employee->designation ?: 'No designation' }} &middot; {{ $employee->department?->name ?? 'Unassigned' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Attendance',
                'title' => 'Current Month Issues',
                'subtitle' => 'Team attendance exceptions for the current month.'
            ])

            <div class="rb-bars">
                @foreach($dashboard['attendance_issue_breakdown'] as $item)
                    <div class="rb-bar-row">
                        <div class="rb-bar-row__head">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $item['value'] }}</strong>
                        </div>
                        <div class="rb-bar-row__track">
                            <span class="rb-bar-row__fill" style="width: {{ $attendanceIssueTotal > 0 ? ($item['value'] / $attendanceIssueTotal) * 100 : 0 }}%; background: {{ $item['color'] }};"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </div>
</section>

<section class="rb-section">
    <div class="rb-grid">
        @if($dashboard['team_leaves']->isNotEmpty())
            <article class="rb-panel">
                @include('dashboard.partials.shared.rb-panel-header', [
                    'eyebrow' => 'Leave',
                    'title' => 'Team Leave Activity',
                    'subtitle' => 'Recent leave movement across employees assigned to you.'
                ])

                <div class="rb-timeline">
                    @foreach($dashboard['team_leaves'] as $leave)
                        <div class="rb-timeline__item">
                            <span class="rb-timeline__dot rb-timeline__dot--orange"></span>
                            <div class="rb-timeline__content">
                                <strong>{{ $leave->employee?->full_name ?? 'Unknown Employee' }}</strong>
                                <p>{{ $leave->leaveType?->name ?? 'Leave' }} &middot; {{ $leave->start_date?->format('d M') }} to {{ $leave->end_date?->format('d M') }} &middot; {{ ucfirst($leave->status) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        @endif

        @if($dashboard['announcements']->isNotEmpty())
            <article class="rb-panel">
                @include('dashboard.partials.shared.rb-panel-header', [
                    'eyebrow' => 'Announcements',
                    'title' => 'Latest Announcements',
                    'subtitle' => 'Latest office notices visible to your team role.'
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
        @endif
    </div>
</section>
