<section class="table-card {{ $activeTab === 'salary' ? '' : 'hidden-tab' }}">
    <div class="section-head">
        <h2>Salary History</h2>
        <p>Saved monthly payroll records with gross, deductions, net salary, and payslip access.</p>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Gross</th>
                    <th>Total Deductions</th>
                    <th>Net</th>
                    <th>Payslip</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrollRecords as $record)
                    @php
                        $deductions = (float) $record->security_deduction + (float) $record->non_paid_leave_deduction + (float) $record->attendance_penalty + (float) $record->arrears_deduction + (float) $record->other_deduction + (float) $record->income_tax;
                    @endphp
                    <tr>
                        <td>{{ optional($record->payrollRun?->pay_period_month)->format('F Y') ?? 'Unknown period' }}</td>
                        <td>{{ $formatMoney($record->gross_salary) }}</td>
                        <td>{{ $formatMoney($deductions) }}</td>
                        <td><strong>{{ $formatMoney($record->net_salary) }}</strong></td>
                        <td>
                            @if($record->payrollRun)
                                <a href="{{ route('profile.self-service.payroll.payslip', $record) }}" class="btn btn-outline small" style="text-decoration: none;">Download</a>
                            @else
                                <span class="muted-text">Unavailable</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No salary history is available yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
