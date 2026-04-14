<section class="table-card {{ $activeTab === 'security' ? '' : 'hidden-tab' }}">
    <div class="section-head">
        <h2>Security Fund</h2>
        <p>Monthly security deductions from payroll and imported security fund balances.</p>
    </div>

    <div class="section-head" style="margin-top: 1rem;">
        <h2 style="font-size: 1rem;">Payroll Security Deductions</h2>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Monthly Deduction</th>
                    <th>Cumulative Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrollRecords as $record)
                    <tr>
                        <td>{{ optional($record->payrollRun?->pay_period_month)->format('F Y') ?? 'Unknown period' }}</td>
                        <td>{{ $formatMoney($record->security_deduction) }}</td>
                        <td>{{ $formatMoney($record->security_total_deducted) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center">No security deductions are available yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-head" style="margin-top: 1.5rem;">
        <h2 style="font-size: 1rem;">Security Fund Snapshots</h2>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fiscal Year</th>
                    <th>Snapshot Month</th>
                    <th>Paid Out</th>
                    <th>Balance In Account</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($securitySnapshots as $snapshot)
                    <tr>
                        <td>{{ $snapshot->fiscal_year_label ?: 'N/A' }}</td>
                        <td>{{ $snapshot->snapshot_month?->format('d M Y') ?? 'N/A' }}</td>
                        <td>{{ $formatMoney($snapshot->paid_amount) }}</td>
                        <td>{{ $formatMoney($snapshot->balance_in_account) }}</td>
                        <td>{{ $snapshot->remarks ?: 'N/A' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No security fund snapshots are available yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
