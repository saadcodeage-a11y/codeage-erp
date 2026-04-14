<div class="dashboard-section-grid">
    <section class="card">
        <div class="card-header">
            <h3>Recent Payroll Runs</h3>
        </div>
        @if($dashboard['recent_runs']->isEmpty())
            <div class="dashboard-empty-state">No payroll runs generated yet.</div>
        @else
            <div class="dashboard-list">
                @foreach($dashboard['recent_runs'] as $run)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $run->pay_period_month?->format('F Y') ?? 'Unknown month' }}</strong>
                            <p>{{ $run->records_count }} employee records · Payment {{ $run->payment_date?->format('d M Y') ?? 'not scheduled' }}</p>
                        </div>
                        <span class="dashboard-status-chip {{ $run->status === 'finalized' ? '' : 'muted' }}">{{ ucfirst($run->status) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card">
        <div class="card-header">
            <h3>Latest Payout Totals</h3>
        </div>
        @if(! $dashboard['latest_run'])
            <div class="dashboard-empty-state">No payout month available.</div>
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
</div>

<div class="dashboard-section-grid">
    <section class="card">
        <div class="card-header">
            <h3>Payroll Exceptions</h3>
        </div>
        @if($dashboard['payroll_exceptions']->isEmpty())
            <div class="dashboard-empty-state">No payroll exceptions found in the latest payout.</div>
        @else
            <div class="dashboard-list">
                @foreach($dashboard['payroll_exceptions'] as $record)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $record->employee?->full_name ?? 'Unknown Employee' }}</strong>
                            <p>
                                Unpaid leave: PKR {{ number_format((float) $record->non_paid_leave_deduction, 2) }}
                                · Security: PKR {{ number_format((float) $record->security_deduction, 2) }}
                            </p>
                        </div>
                        <span class="dashboard-status-chip muted">{{ $record->short_hours_days }} short</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card">
        <div class="card-header">
            <h3>Reporting Shortcuts</h3>
        </div>
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
