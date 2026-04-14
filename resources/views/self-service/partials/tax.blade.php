<section class="table-card {{ $activeTab === 'tax' ? '' : 'hidden-tab' }}">
    <div class="section-head">
        <h2>Tax Records</h2>
        <p>Month-wise tax deductions captured from saved payroll payouts.</p>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Gross Salary</th>
                    <th>Monthly Tax</th>
                    <th>Annual Tax Total</th>
                    <th>Net Salary</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrollRecords as $record)
                    <tr>
                        <td>{{ optional($record->payrollRun?->pay_period_month)->format('F Y') ?? 'Unknown period' }}</td>
                        <td>{{ $formatMoney($record->gross_salary) }}</td>
                        <td>{{ $formatMoney($record->income_tax) }}</td>
                        <td>{{ $formatMoney($record->annual_tax_total) }}</td>
                        <td>{{ $formatMoney($record->net_salary) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No tax records are available yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
