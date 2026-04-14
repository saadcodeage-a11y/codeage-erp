<div class="dashboard-section-grid dashboard-section-grid-wide">
    <section class="card dashboard-panel dashboard-panel--feature">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Latest Payout Summary',
            'subtitle' => 'Most recent saved payout month and its financial totals.'
        ])
        @if(! $dashboard['latest_run'])
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'wallet-cards',
                'title' => 'No payout month available',
                'message' => 'Generate a payout month to start building payroll summaries here.'
            ])
        @else
            <div class="dashboard-metric-grid">
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Month</span>
                    <strong>{{ $dashboard['latest_run']->pay_period_month?->format('F Y') }}</strong>
                    <p>Most recent saved payout month.</p>
                </div>
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Gross</span>
                    <strong>PKR {{ number_format($dashboard['latest_run_totals']['gross'], 2) }}</strong>
                    <p>Total gross salary in the latest run.</p>
                </div>
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Tax</span>
                    <strong>PKR {{ number_format($dashboard['latest_run_totals']['tax'], 2) }}</strong>
                    <p>Total tax deducted in the latest run.</p>
                </div>
                <div class="dashboard-metric-card">
                    <span class="dashboard-metric-label">Net</span>
                    <strong>PKR {{ number_format($dashboard['latest_run_totals']['net'], 2) }}</strong>
                    <p>Total net salary in the latest run.</p>
                </div>
            </div>
        @endif
    </section>

    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Payroll Exceptions',
            'subtitle' => 'Latest payout records with leave, security, or short-hour impact.'
        ])
        @if($dashboard['payroll_exceptions']->isEmpty())
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'triangle-alert',
                'title' => 'No payroll exceptions',
                'message' => 'The latest payout month does not contain flagged deductions or short-hour cases.'
            ])
        @else
            <div class="dashboard-list">
                @foreach($dashboard['payroll_exceptions'] as $record)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $record->employee?->full_name ?? 'Unknown Employee' }}</strong>
                            <p>Unpaid leave: PKR {{ number_format((float) $record->non_paid_leave_deduction, 2) }} &middot; Security: PKR {{ number_format((float) $record->security_deduction, 2) }}</p>
                        </div>
                        <span class="dashboard-status-chip muted">{{ $record->short_hours_days }} short</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

<div class="dashboard-section-grid">
    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Recent Payroll Runs',
            'subtitle' => 'Latest payout months and their current run status.'
        ])
        @if($dashboard['recent_runs']->isEmpty())
            @include('dashboard.partials.shared.empty-state', [
                'icon' => 'calendar-days',
                'title' => 'No payroll runs generated',
                'message' => 'Saved payout months will appear here once payroll is generated.'
            ])
        @else
            <div class="dashboard-list">
                @foreach($dashboard['recent_runs'] as $run)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $run->pay_period_month?->format('F Y') ?? 'Unknown month' }}</strong>
                            <p>{{ $run->records_count }} employee records &middot; Payment {{ $run->payment_date?->format('d M Y') ?? 'not scheduled' }}</p>
                        </div>
                        <span class="dashboard-status-chip {{ $run->status === 'finalized' ? '' : 'muted' }}">{{ ucfirst($run->status) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Reporting Shortcuts',
            'subtitle' => 'Jump directly into payroll and tax reporting views.'
        ])
        <div class="dashboard-list">
            @foreach($dashboard['report_shortcuts'] as $shortcut)
                <a href="{{ $shortcut['href'] }}" class="dashboard-link-row">
                    <div>
                        <strong>{{ $shortcut['title'] }}</strong>
                        <p>{{ $shortcut['description'] }}</p>
                    </div>
                    <i data-lucide="arrow-right"></i>
                </a>
            @endforeach
        </div>
    </section>
</div>

@if($dashboard['announcements']->isNotEmpty())
    <section class="card dashboard-panel dashboard-panel--support">
        @include('dashboard.partials.shared.panel-header', [
            'title' => 'Latest Announcements',
            'subtitle' => 'Recent notices relevant to payroll and finance operations.'
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
