@php
    $latestFinancialTotal = max(
        (float) ($dashboard['latest_run_totals']['gross'] ?? 0),
        (float) ($dashboard['latest_run_totals']['net'] ?? 0),
        (float) ($dashboard['latest_run_totals']['tax'] ?? 0),
        (float) ($dashboard['latest_run_totals']['security'] ?? 0),
        1
    );
    $recentRunMax = max(collect($dashboard['recent_run_series'])->max('gross') ?? 0, 1);
@endphp

<section class="rb-section">
    <div class="rb-grid rb-grid--hero">
        <article class="rb-panel rb-panel--spotlight">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Payroll',
                'title' => 'Payroll Composition',
                'subtitle' => 'Latest payout month broken into gross, tax, security, and net.'
            ])

            @if(! $dashboard['latest_run'])
                @include('dashboard.partials.shared.rb-empty', [
                    'icon' => 'wallet-cards',
                    'title' => 'No payout month available',
                    'message' => 'Generate a payout month to start seeing payroll composition here.'
                ])
            @else
                <div class="rb-bars">
                    <div class="rb-bar-row">
                        <div class="rb-bar-row__head">
                            <span>Gross Salary</span>
                            <strong>PKR {{ number_format($dashboard['latest_run_totals']['gross'], 2) }}</strong>
                        </div>
                        <div class="rb-bar-row__track"><span class="rb-bar-row__fill rb-bar-row__fill--orange" style="width: {{ ($dashboard['latest_run_totals']['gross'] / $latestFinancialTotal) * 100 }}%;"></span></div>
                    </div>
                    <div class="rb-bar-row">
                        <div class="rb-bar-row__head">
                            <span>Income Tax</span>
                            <strong>PKR {{ number_format($dashboard['latest_run_totals']['tax'], 2) }}</strong>
                        </div>
                        <div class="rb-bar-row__track"><span class="rb-bar-row__fill rb-bar-row__fill--blue" style="width: {{ ($dashboard['latest_run_totals']['tax'] / $latestFinancialTotal) * 100 }}%;"></span></div>
                    </div>
                    <div class="rb-bar-row">
                        <div class="rb-bar-row__head">
                            <span>Security Deduction</span>
                            <strong>PKR {{ number_format($dashboard['latest_run_totals']['security'], 2) }}</strong>
                        </div>
                        <div class="rb-bar-row__track"><span class="rb-bar-row__fill rb-bar-row__fill--purple" style="width: {{ ($dashboard['latest_run_totals']['security'] / $latestFinancialTotal) * 100 }}%;"></span></div>
                    </div>
                    <div class="rb-bar-row">
                        <div class="rb-bar-row__head">
                            <span>Net Salary</span>
                            <strong>PKR {{ number_format($dashboard['latest_run_totals']['net'], 2) }}</strong>
                        </div>
                        <div class="rb-bar-row__track"><span class="rb-bar-row__fill rb-bar-row__fill--green" style="width: {{ ($dashboard['latest_run_totals']['net'] / $latestFinancialTotal) * 100 }}%;"></span></div>
                    </div>
                </div>
            @endif
        </article>

        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Flags',
                'title' => 'Exception Watchlist',
                'subtitle' => 'Employees in the latest run with deductions or short-hour flags.'
            ])

            @if($dashboard['payroll_exceptions']->isEmpty())
                @include('dashboard.partials.shared.rb-empty', [
                    'icon' => 'triangle-alert',
                    'title' => 'No payroll exceptions',
                    'message' => 'The latest payout month does not contain flagged deductions or short-hour cases.'
                ])
            @else
                <div class="rb-card-list rb-card-list--dense">
                    @foreach($dashboard['payroll_exceptions'] as $record)
                        <div class="rb-list-card">
                            <div>
                                <span class="rb-list-card__badge rb-list-card__badge--muted">{{ $record->short_hours_days }} short</span>
                                <strong>{{ $record->employee?->full_name ?? 'Unknown Employee' }}</strong>
                                <p>Unpaid leave: PKR {{ number_format((float) $record->non_paid_leave_deduction, 2) }} &middot; Security: PKR {{ number_format((float) $record->security_deduction, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </div>
</section>

<section class="rb-section">
    <div class="rb-grid">
        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Trend',
                'title' => 'Recent Payroll Runs',
                'subtitle' => 'Latest payout months and their financial scale.'
            ])

            @if(collect($dashboard['recent_run_series'])->isEmpty())
                @include('dashboard.partials.shared.rb-empty', [
                    'icon' => 'calendar-days',
                    'title' => 'No payroll runs generated',
                    'message' => 'Saved payout months will appear here once payroll is generated.'
                ])
            @else
                <div class="rb-bars">
                    @foreach($dashboard['recent_run_series'] as $run)
                        <div class="rb-bar-row">
                            <div class="rb-bar-row__head">
                                <span>{{ $run['label'] }}</span>
                                <strong>{{ $run['records'] }} employees</strong>
                            </div>
                            <div class="rb-bar-row__track">
                                <span class="rb-bar-row__fill {{ $run['status'] === 'finalized' ? 'rb-bar-row__fill--green' : 'rb-bar-row__fill--orange' }}" style="width: {{ ($run['gross'] / $recentRunMax) * 100 }}%;"></span>
                            </div>
                            <p>Gross PKR {{ number_format($run['gross'], 2) }} &middot; Net PKR {{ number_format($run['net'], 2) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Reports',
                'title' => 'Report Shortcuts',
                'subtitle' => 'Open financial reports directly from the dashboard.'
            ])

            <div class="rb-card-list">
                @foreach($dashboard['report_shortcuts'] as $shortcut)
                    <a href="{{ $shortcut['href'] }}" class="rb-list-card rb-list-card--link">
                        <div>
                            <span class="rb-list-card__badge">Report</span>
                            <strong>{{ $shortcut['title'] }}</strong>
                            <p>{{ $shortcut['description'] }}</p>
                        </div>
                        <i data-lucide="arrow-up-right"></i>
                    </a>
                @endforeach
            </div>
        </article>
    </div>
</section>

@if($dashboard['announcements']->isNotEmpty())
    <section class="rb-section">
        <article class="rb-panel">
            @include('dashboard.partials.shared.rb-panel-header', [
                'eyebrow' => 'Announcements',
                'title' => 'Latest Announcements',
                'subtitle' => 'Recent notices relevant to payroll and finance operations.'
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
