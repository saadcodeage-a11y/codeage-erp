<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - {{ $employee->full_name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            margin: 0;
            padding: 28px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 24px;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
        }

        .header-right {
            text-align: right;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        h2 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .muted {
            color: #6b7280;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 18px;
        }

        .two-col {
            display: table;
            width: 100%;
        }

        .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .meta-row {
            margin-bottom: 8px;
        }

        .meta-label {
            display: inline-block;
            min-width: 120px;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th:last-child,
        td:last-child {
            text-align: right;
        }

        .totals {
            margin-top: 16px;
            width: 280px;
            margin-left: auto;
        }

        .totals td {
            border-bottom: none;
            padding: 6px 0;
        }

        .totals tr:last-child td {
            font-weight: bold;
            font-size: 14px;
            padding-top: 10px;
        }

        .footer-note {
            margin-top: 24px;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>Pay Slip</h1>
            <div class="muted">{{ config('app.name') }}</div>
            <div class="muted">Salary Slip for {{ $payrollRun->pay_period_month->format('F Y') }}</div>
        </div>
        <div class="header-right">
            <div><strong>Payment Date</strong></div>
            <div>{{ optional($payrollRun->payment_date)->format('d F, Y') ?? 'Not specified' }}</div>
        </div>
    </div>

    <div class="card">
        <div class="two-col">
            <div class="col">
                <h2>Employee Details</h2>
                <div class="meta-row"><span class="meta-label">Employee</span>{{ $employee->full_name }}</div>
                <div class="meta-row"><span class="meta-label">Employee ID</span>{{ $employee->employee_id }}</div>
                <div class="meta-row"><span class="meta-label">Position</span>{{ $employee->designation ?? 'Not specified' }}</div>
                <div class="meta-row"><span class="meta-label">Department</span>{{ $employee->department?->name ?? 'Not assigned' }}</div>
                <div class="meta-row"><span class="meta-label">Email</span>{{ $employee->email }}</div>
            </div>
            <div class="col">
                <h2>Bank / Payment</h2>
                <div class="meta-row"><span class="meta-label">Payment Mode</span>{{ $employee->payment_mode ?? 'Not specified' }}</div>
                <div class="meta-row"><span class="meta-label">Beneficiary</span>{{ $record->beneficiary_name ?? 'Not specified' }}</div>
                <div class="meta-row"><span class="meta-label">Account</span>{{ $record->beneficiary_account_no ?? 'Not specified' }}</div>
                <div class="meta-row"><span class="meta-label">Bank Code</span>{{ $record->bank_code ?? 'Not specified' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Earnings and Deductions</h2>
        <table>
            <thead>
                <tr>
                    <th>Earnings</th>
                    <th>Amount</th>
                    <th>Deductions</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td>PKR {{ number_format($record->basic_salary, 2) }}</td>
                    <td>Security</td>
                    <td>PKR {{ number_format($record->security_deduction, 2) }}</td>
                </tr>
                <tr>
                    <td>Last Increment</td>
                    <td>PKR {{ number_format($record->last_increment, 2) }}</td>
                    <td>Non-Paid Leave</td>
                    <td>PKR {{ number_format($record->non_paid_leave_deduction, 2) }}</td>
                </tr>
                <tr>
                    <td>Incentives / Bonus</td>
                    <td>PKR {{ number_format($record->incentives_bonus, 2) }}</td>
                    <td>Attendance Penalty</td>
                    <td>PKR {{ number_format($record->attendance_penalty, 2) }}</td>
                </tr>
                <tr>
                    <td>Punctuality Bonus</td>
                    <td>PKR {{ number_format($record->punctuality_bonus, 2) }}</td>
                    <td>Arrears Deduction</td>
                    <td>PKR {{ number_format($record->arrears_deduction, 2) }}</td>
                </tr>
                <tr>
                    <td>Positive Arrears</td>
                    <td>PKR {{ number_format($record->positive_arrears, 2) }}</td>
                    <td>Other Deduction</td>
                    <td>PKR {{ number_format($record->other_deduction, 2) }}</td>
                </tr>
                <tr>
                    <td>Other Additions</td>
                    <td>PKR {{ number_format($record->positive_other, 2) }}</td>
                    <td>Income Tax</td>
                    <td>PKR {{ number_format($record->income_tax, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Gross Salary</td>
                <td>PKR {{ number_format($record->gross_salary, 2) }}</td>
            </tr>
            <tr>
                <td>Net Salary</td>
                <td>PKR {{ number_format($record->net_salary, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Attendance Summary</h2>
        <div class="two-col">
            <div class="col">
                <div class="meta-row"><span class="meta-label">Days Absent</span>{{ $record->days_absent }}</div>
                <div class="meta-row"><span class="meta-label">Short Hours Days</span>{{ $record->short_hours_days }}</div>
            </div>
            <div class="col">
                <div class="meta-row"><span class="meta-label">Payroll Run</span>{{ $payrollRun->name }}</div>
                <div class="meta-row"><span class="meta-label">Status</span>{{ ucfirst($payrollRun->status) }}</div>
            </div>
        </div>
    </div>

    <div class="footer-note">
        This payslip was generated by {{ config('app.name') }} for {{ $payrollRun->pay_period_month->format('F Y') }}.
        @if($payrollRun->notes)
            <br><br><strong>Notes:</strong> {{ $payrollRun->notes }}
        @endif
    </div>
</body>
</html>
