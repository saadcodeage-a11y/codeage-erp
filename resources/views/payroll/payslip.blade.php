@php
    use App\Models\Setting;

    $settings = Setting::query()
        ->whereIn('key', [
            'company_ntn',
            'company_corp_number',
            'company_website',
            'accounts_email',
            'company_phone',
            'office_location',
        ])
        ->pluck('value', 'key');

    $companyName = strtoupper(config('app.name'));
    $corpNumber = $settings['company_corp_number'] ?? '0135020';
    $ntnNumber = $settings['company_ntn'] ?? '5428797';
    $companyWebsite = $settings['company_website'] ?? 'codeagepk.com';
    $accountsEmail = $settings['accounts_email'] ?? 'accounts@codeagepk.com';
    $companyPhone = $settings['company_phone'] ?? '0518743211';
    $companyAddress = $settings['office_location'] ?? 'Office 1&2 , 2nd Floor Plaza 56 Spring North Commercial Bahria Town Phase 7 Islamabad';
    $bankName = $employee->bank?->name ?? $employee->bank_name ?? 'Not specified';
    $paymentMode = $employee->payment_mode ?? 'Bank Transfer';
    $paymentDate = optional($payrollRun->payment_date)->format('d-m-Y') ?? 'Not specified';
    $earningsSubtotal = $record->basic_salary
        + $record->last_increment
        + $record->incentives_bonus
        + $record->punctuality_bonus
        + $record->positive_arrears
        + $record->positive_other;
    $deductionsSubtotal = $record->security_deduction
        + $record->non_paid_leave_deduction
        + $record->attendance_penalty
        + $record->arrears_deduction
        + $record->other_deduction;
    $lateDerivedLeaveNote = $record->late_absent_equivalent > 0
        ? $record->late_absent_equivalent . ' unpaid leave day(s) came from every 3 late arrivals.'
        : 'No unpaid leave was derived from repeated late arrivals this month.';
    $money = function ($value, bool $showZero = false) {
        $value = round((float) $value, 2);

        if (! $showZero && $value == 0.0) {
            return '';
        }

        return 'PKR ' . number_format($value, 2);
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - {{ $employee->full_name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 11px;
            margin: 0;
            padding: 26px 30px 18px;
            line-height: 1.35;
        }

        .company-header {
            text-align: left;
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
        }

        .company-meta {
            color: #4b5563;
            font-size: 10.5px;
            margin-bottom: 2px;
        }

        .title {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            margin: 12px 0 20px;
        }

        .top-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .top-grid td {
            vertical-align: top;
            padding: 0;
        }

        .left-panel {
            width: 58%;
            padding-right: 18px;
        }

        .right-panel {
            width: 42%;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 12px;
        }

        .field-row {
            margin-bottom: 7px;
        }

        .field-label {
            display: inline-block;
            min-width: 118px;
            color: #4b5563;
        }

        .net-pay-box {
            text-align: right;
            margin-bottom: 10px;
        }

        .net-pay-label {
            color: #4b5563;
            font-size: 11px;
        }

        .net-pay-value {
            font-size: 28px;
            font-weight: 700;
            margin-top: 4px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .details-table td {
            padding: 0 0 7px;
            vertical-align: top;
        }

        .split-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .split-table td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .split-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .split-card.left {
            margin-right: 10px;
        }

        .split-card.right {
            margin-left: 10px;
        }

        .split-heading {
            padding: 10px 12px;
            background: #f9fafb;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .line-table {
            width: 100%;
            border-collapse: collapse;
        }

        .line-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: top;
        }

        .line-table tr:last-child td {
            border-bottom: none;
        }

        .line-table td:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .subtotal-row td {
            font-weight: 700;
            background: #fcfcfd;
        }

        .summary-box {
            width: 320px;
            margin-left: auto;
            margin-top: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .summary-row {
            display: table;
            width: 100%;
        }

        .summary-row > div {
            display: table-cell;
            padding: 10px 12px;
            border-bottom: 1px solid #eef2f7;
        }

        .summary-row > div:last-child {
            text-align: right;
            font-weight: 700;
            white-space: nowrap;
        }

        .summary-row:last-child > div {
            border-bottom: none;
        }

        .summary-row.highlight > div {
            background: #fff7ed;
            font-weight: 700;
        }

        .remarks-box {
            margin-top: 16px;
        }

        .remarks-title {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .remarks-list {
            margin: 0;
            padding-left: 14px;
            color: #374151;
        }

        .remarks-list li {
            margin-bottom: 5px;
        }

        .footer {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #4b5563;
            font-size: 10px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="company-header">
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-meta">Corp / NTN : {{ $corpNumber }} / {{ $ntnNumber }}</div>
        <div class="company-meta">{{ $companyWebsite }}</div>
    </div>

    <div class="title">Pay Slip</div>

    <table class="top-grid">
        <tr>
            <td class="left-panel">
                <div class="section-title">Employee Details</div>
                <table class="details-table">
                    <tr>
                        <td><span class="field-label">Employee name:</span> {{ $employee->full_name }}</td>
                    </tr>
                    <tr>
                        <td><span class="field-label">Employee id:</span> {{ $employee->employee_id }}</td>
                    </tr>
                    <tr>
                        <td><span class="field-label">Designation</span> {{ $employee->designation ?? 'Not specified' }}</td>
                    </tr>
                    <tr>
                        <td><span class="field-label">Email id:</span> {{ $employee->email }}</td>
                    </tr>
                </table>
            </td>
            <td class="right-panel">
                <div class="net-pay-box">
                    <div class="net-pay-label">Net Pay:</div>
                    <div class="net-pay-value">PKR {{ number_format($record->net_salary, 0) }}</div>
                </div>

                <table class="details-table">
                    <tr>
                        <td><span class="field-label">Pay Period:</span> {{ $payrollRun->pay_period_month->format('F Y') }}</td>
                    </tr>
                    <tr>
                        <td><span class="field-label">Payment Mode:</span> {{ $paymentMode }}</td>
                    </tr>
                    <tr>
                        <td><span class="field-label">Bank name:</span> {{ $bankName }}</td>
                    </tr>
                    <tr>
                        <td><span class="field-label">IBAN /Account:</span> {{ $record->beneficiary_account_no ?? 'Not specified' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="split-table">
        <tr>
            <td>
                <div class="split-card left">
                    <div class="split-heading">EARNINGS</div>
                    <table class="line-table">
                        <tr>
                            <td>Basic Salary</td>
                            <td>{{ $money($record->basic_salary, true) }}</td>
                        </tr>
                        <tr>
                            <td>Last Increment</td>
                            <td>{{ $money($record->last_increment) }}</td>
                        </tr>
                        <tr>
                            <td>Incentives / Bonus</td>
                            <td>{{ $money($record->incentives_bonus) }}</td>
                        </tr>
                        <tr>
                            <td>Punctuality Bonus</td>
                            <td>{{ $money($record->punctuality_bonus) }}</td>
                        </tr>
                        <tr>
                            <td>Arrears</td>
                            <td>{{ $money($record->positive_arrears) }}</td>
                        </tr>
                        <tr>
                            <td>Other</td>
                            <td>{{ $money($record->positive_other) }}</td>
                        </tr>
                        <tr class="subtotal-row">
                            <td>Sub Total</td>
                            <td>{{ $money($earningsSubtotal, true) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="split-card right">
                    <div class="split-heading">DEDUCTIONS</div>
                    <table class="line-table">
                        <tr>
                            <td>Security</td>
                            <td>{{ $money($record->security_deduction) }}</td>
                        </tr>
                        <tr>
                            <td>Non-Paid Leave ({{ $record->unpaid_leave_days }} day{{ $record->unpaid_leave_days === 1 ? '' : 's' }})</td>
                            <td>{{ $money($record->non_paid_leave_deduction) }}</td>
                        </tr>
                        <tr>
                            <td>Attendance Penalty</td>
                            <td>{{ $money($record->attendance_penalty) }}</td>
                        </tr>
                        <tr>
                            <td>Arrears</td>
                            <td>{{ $money($record->arrears_deduction) }}</td>
                        </tr>
                        <tr>
                            <td>Other</td>
                            <td>{{ $money($record->other_deduction) }}</td>
                        </tr>
                        <tr class="subtotal-row">
                            <td>Sub Total</td>
                            <td>{{ $money($deductionsSubtotal, true) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="summary-box">
        <div class="summary-row">
            <div>Gross Salary</div>
            <div>{{ $money($record->gross_salary, true) }}</div>
        </div>
        <div class="summary-row">
            <div>Income Tax</div>
            <div>{{ $money($record->income_tax) }}</div>
        </div>
        <div class="summary-row">
            <div>Annual Tax Total</div>
            <div>{{ $money($record->annual_tax_total, true) }}</div>
        </div>
        <div class="summary-row highlight">
            <div>Net Salary Payable</div>
            <div>{{ $money($record->net_salary, true) }}</div>
        </div>
    </div>

    <div class="remarks-box">
        <div class="remarks-title">Remarks :</div>
        <ul class="remarks-list">
            <li>Payment was deposited on {{ $paymentDate }} via {{ $paymentMode }}</li>
            <li>If you have any question email to {{ $accountsEmail }}</li>
            <li>Attendance summary: {{ $record->days_absent }} actual absent day(s), {{ $record->late_count }} late arrival(s), and {{ $record->late_absent_equivalent }} absent day(s) derived from every 3 late arrivals.</li>
            <li>Total unpaid leave deducted this month: {{ $record->unpaid_leave_days }} day(s) for {{ $money($record->non_paid_leave_deduction, true) }}. {{ $lateDerivedLeaveNote }}</li>
            <li>Security deducted this month: {{ $money($record->security_deduction, true) }}. Cumulative security held: {{ $money($record->security_total_deducted ?? 0, true) }}.</li>
            <li>Monthly tax deducted: {{ $money($record->income_tax, true) }}. Fiscal-year tax deducted till this month: {{ $money($record->annual_tax_total ?? 0, true) }}.</li>
            <li>This is a computer-generated statement. No signature is required.</li>
        </ul>
    </div>

    <div class="footer">
        Email: {{ $accountsEmail }} | Phone: {{ $companyPhone }}<br>
        Address: {{ $companyAddress }}
    </div>
</body>
</html>
