@if($rows->isNotEmpty())
    <div class="payout-preview-tools">
        <div class="payout-search-box">
            <input type="search" placeholder="Search employee by name, ID, designation, bank, or account" data-payout-search>
        </div>
        <span class="summary-pill muted" data-payout-search-count>{{ $rows->count() }} employees</span>
    </div>

    <div class="payout-preview-table-wrap">
        <table class="payout-preview-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Bank / Account</th>
                    <th>Base</th>
                    <th>Increment</th>
                    <th>Absent</th>
                    <th>Short</th>
                    <th>Security</th>
                    <th>Incentives</th>
                    <th>Punctuality</th>
                    <th>Penalty</th>
                    <th>Arrears</th>
                    <th>Other</th>
                    <th>Projected Net</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php
                        $employee = $row['employee'];
                        $adjustment = $row['adjustment'];
                        $bankLabel = $employee->bank?->name ?: ($row['bank_code'] ?: 'No linked bank');
                        $accountLabel = $row['beneficiary_account_no'] ?: 'No account';
                        $searchText = collect([
                            $employee->full_name,
                            $employee->employee_id,
                            $employee->designation,
                            $bankLabel,
                            $accountLabel,
                        ])->filter()->implode(' ');
                    @endphp
                    <tr data-payout-row data-search-text="{{ strtolower($searchText) }}">
                        <td>
                            <div class="payout-employee-cell">
                                <strong>{{ $employee->full_name }}</strong>
                                <span>{{ $employee->employee_id }} | {{ $employee->designation ?? 'Not specified' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="payout-bank-cell">
                                <strong>{{ $bankLabel }}</strong>
                                <span>{{ $accountLabel }}</span>
                            </div>
                        </td>
                        <td>PKR {{ number_format($row['basic_salary'], 2) }}</td>
                        <td>PKR {{ number_format($row['last_increment'], 2) }}</td>
                        <td>{{ $row['days_absent'] }}</td>
                        <td>{{ $row['short_hours_days'] }}</td>
                        <td>PKR {{ number_format($row['security_balance'], 2) }}</td>
                        <td>
                            <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][incentives_bonus]" value="{{ $adjustment?->incentives_bonus ?? 0 }}" @if(!$canEditPayroll) disabled @endif>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][punctuality_bonus]" value="{{ $adjustment?->punctuality_bonus ?? 0 }}" @if(!$canEditPayroll) disabled @endif>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][attendance_penalty]" value="{{ $adjustment?->attendance_penalty ?? 0 }}" @if(!$canEditPayroll) disabled @endif>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][arrears_adjustment]" value="{{ $adjustment?->arrears_adjustment ?? 0 }}" @if(!$canEditPayroll) disabled @endif>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][other_adjustment]" value="{{ $adjustment?->other_adjustment ?? 0 }}" @if(!$canEditPayroll) disabled @endif>
                        </td>
                        <td class="payout-net-cell">PKR {{ number_format($row['net_salary'], 2) }}</td>
                        <td>
                            <input type="text" name="adjustments[{{ $employee->id }}][remarks]" value="{{ $adjustment?->remarks }}" placeholder="Optional note" @if(!$canEditPayroll) disabled @endif>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="empty-state-panel" data-payout-empty-search style="display: none; margin-top: 12px;">
        No employees match the current search.
    </div>
@else
    <div class="empty-state-panel">
        No payroll-eligible employees were found for the selected month.
    </div>
@endif
