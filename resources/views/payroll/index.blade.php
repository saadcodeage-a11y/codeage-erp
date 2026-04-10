@extends('layouts.app')

@section('title', 'Payroll')

@section('content')
@php
    $canGeneratePayroll = Auth::user()->canAccessModule('payroll_management', 'create');
    $canEditPayroll = Auth::user()->canAccessModule('payroll_management', 'edit');
    $selectedMonth = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month);
    $selectedMonthLabel = $selectedMonth->format('F Y');
    $selectedRunTotals = $selectedRun
        ? [
            'gross_salary' => round($selectedRun->records->sum('gross_salary'), 2),
            'income_tax' => round($selectedRun->records->sum('income_tax'), 2),
            'net_salary' => round($selectedRun->records->sum('net_salary'), 2),
        ]
        : null;
@endphp

<div class="page-header">
    <div class="header-left">
        <h1>Payroll</h1>
        <p>Generate month-wise payroll from employee salary setup, attendance records, security balances, and manual monthly adjustments.</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Eligible Employees</span>
            <span class="stat-value">{{ $previewRows->count() }}</span>
        </div>
        <div class="stat-icon-wrapper orange"><i data-lucide="users-round"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Projected Gross</span>
            <span class="stat-value">PKR {{ number_format($totals['gross_salary'], 2) }}</span>
        </div>
        <div class="stat-icon-wrapper yellow"><i data-lucide="landmark"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Projected Tax</span>
            <span class="stat-value">PKR {{ number_format($totals['income_tax'], 2) }}</span>
        </div>
        <div class="stat-icon-wrapper red"><i data-lucide="receipt-text"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Projected Net</span>
            <span class="stat-value">PKR {{ number_format($totals['net_salary'], 2) }}</span>
        </div>
        <div class="stat-icon-wrapper green"><i data-lucide="wallet"></i></div>
    </div>
</div>

@if(session('success') || $errors->any())
    <div class="attendance-feedback-stack" style="margin-bottom: 24px;">
        @if(session('success'))
            <div class="status-banner success">
                <i data-lucide="circle-check-big"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="status-banner danger">
                <i data-lucide="octagon-alert"></i>
                <div>
                    <strong>Payroll action could not be completed.</strong>
                    <p>{{ $errors->first() }}</p>
                </div>
            </div>
        @endif
    </div>
@endif

<div class="payroll-toolbar-grid">
    <div class="card">
        <div class="section-header" style="margin-bottom: 16px;">
            <div>
                <h2>Payroll Month</h2>
                <p>Choose the month to review inputs, save adjustments, and generate payroll.</p>
            </div>
        </div>
        <form method="GET" action="{{ route('payroll.index') }}" class="payroll-month-form">
            <div class="form-group" style="margin: 0;">
                <label>Selected Month</label>
                <input type="month" name="month" value="{{ $month }}">
            </div>
            <button type="submit" class="btn btn-outline">
                <i data-lucide="calendar-search"></i> Load Month
            </button>
        </form>
    </div>

    <div class="card">
        <div class="section-header" style="margin-bottom: 16px;">
            <div>
                <h2>Payroll Run</h2>
                <p>Generate a draft payroll run for {{ $selectedMonthLabel }} and finalize it when reviewed.</p>
            </div>
        </div>
        @if($canGeneratePayroll)
            <form method="POST" action="{{ route('payroll.generate') }}" class="payroll-generate-form">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="form-group" style="margin: 0;">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', $selectedMonth->copy()->addMonth()->startOfMonth()->toDateString()) }}">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional payroll notes">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="play"></i> Generate Payroll
                </button>
            </form>
        @else
            <div class="empty-state-panel">You can review payroll, but only payroll creators can generate a run.</div>
        @endif
    </div>
</div>

<div class="two-column-layout payroll-layout">
    <div class="table-card">
        <div class="section-header" style="margin-bottom: 16px;">
            <div>
                <h2>Monthly Payroll Inputs</h2>
                <p>Review attendance and salary inputs for {{ $selectedMonthLabel }}. Save manual adjustments before generating payroll.</p>
            </div>
            <div class="section-badge-row">
                <span class="summary-pill">{{ $previewRows->count() }} employees</span>
                <span class="summary-pill muted">{{ $selectedMonthLabel }}</span>
            </div>
        </div>

        @if($previewRows->isNotEmpty())
            <form method="POST" action="{{ route('payroll.adjustments.update') }}">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="table-scroll">
                    <table class="data-table payroll-input-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Base</th>
                                <th>Increment</th>
                                <th>Absent</th>
                                <th>Short Hours</th>
                                <th>Security Balance</th>
                                <th>Incentives</th>
                                <th>Punctuality</th>
                                <th>Penalty</th>
                                <th>Arrears</th>
                                <th>Other</th>
                                <th>Projected Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewRows as $row)
                                @php
                                    $employee = $row['employee'];
                                    $adjustment = $row['adjustment'];
                                @endphp
                                <tr>
                                    <td>
                                        <div style="display: flex; flex-direction: column;">
                                            <strong>{{ $employee->full_name }}</strong>
                                            <span style="font-size: 12px; color: #6b7280;">{{ $employee->employee_id }}</span>
                                        </div>
                                    </td>
                                    <td>PKR {{ number_format($row['basic_salary'], 2) }}</td>
                                    <td>PKR {{ number_format($row['last_increment'], 2) }}</td>
                                    <td>{{ $row['days_absent'] }}</td>
                                    <td>{{ $row['short_hours_days'] }}</td>
                                    <td>PKR {{ number_format($row['security_balance'], 2) }}</td>
                                    <td>
                                        <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][incentives_bonus]" value="{{ old("adjustments.{$employee->id}.incentives_bonus", $adjustment?->incentives_bonus ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][punctuality_bonus]" value="{{ old("adjustments.{$employee->id}.punctuality_bonus", $adjustment?->punctuality_bonus ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][attendance_penalty]" value="{{ old("adjustments.{$employee->id}.attendance_penalty", $adjustment?->attendance_penalty ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][arrears_adjustment]" value="{{ old("adjustments.{$employee->id}.arrears_adjustment", $adjustment?->arrears_adjustment ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][other_adjustment]" value="{{ old("adjustments.{$employee->id}.other_adjustment", $adjustment?->other_adjustment ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column;">
                                            <strong>PKR {{ number_format($row['net_salary'], 2) }}</strong>
                                            <span style="font-size: 12px; color: #6b7280;">Tax {{ number_format($row['income_tax'], 2) }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @if($canEditPayroll)
                                    <tr class="remarks-row">
                                        <td colspan="12">
                                            <div class="form-group" style="margin: 0;">
                                                <label style="font-size: 12px;">Remarks for {{ $employee->full_name }}</label>
                                                <input type="text" name="adjustments[{{ $employee->id }}][remarks]" value="{{ old("adjustments.{$employee->id}.remarks", $adjustment?->remarks) }}" placeholder="Optional note for this month">
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($canEditPayroll)
                    <div class="table-footer-actions">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save"></i> Save Adjustments
                        </button>
                    </div>
                @endif
            </form>
        @else
            <div class="empty-state-panel">
                No payroll-eligible employees were found for {{ $selectedMonthLabel }}. Make sure employee salary data and attendance exist before generating payroll.
            </div>
        @endif
    </div>

    <div class="side-panel-stack">
        <div class="card">
            <div class="section-header" style="margin-bottom: 16px;">
                <div>
                    <h2>Recent Payroll Runs</h2>
                    <p>Draft and finalized runs saved in the system.</p>
                </div>
            </div>
            <div class="import-list">
                @forelse($runs as $run)
                    <a href="{{ route('payroll.index', ['month' => $run->pay_period_month->format('Y-m'), 'run' => $run->id]) }}" class="import-card {{ $selectedRun?->id === $run->id ? 'active' : '' }}">
                        <div>
                            <strong>{{ $run->name }}</strong>
                            <p>{{ $run->pay_period_month->format('F Y') }} | {{ ucfirst($run->status) }}</p>
                        </div>
                        <div class="import-card-metrics">
                            <span>{{ $run->records_count }} records</span>
                            <span>{{ optional($run->generatedBy)->name ?? 'System' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="empty-state-panel">No payroll runs have been generated yet.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="section-header" style="margin-bottom: 16px;">
                <div>
                    <h2>Selected Run</h2>
                    <p>Review the generated payroll run and download payslips.</p>
                </div>
            </div>
            @if($selectedRun)
                <div class="run-summary">
                    <div class="run-summary-row">
                        <span>Run</span>
                        <strong>{{ $selectedRun->name }}</strong>
                    </div>
                    <div class="run-summary-row">
                        <span>Status</span>
                        <strong>{{ ucfirst($selectedRun->status) }}</strong>
                    </div>
                    <div class="run-summary-row">
                        <span>Gross</span>
                        <strong>PKR {{ number_format($selectedRunTotals['gross_salary'], 2) }}</strong>
                    </div>
                    <div class="run-summary-row">
                        <span>Tax</span>
                        <strong>PKR {{ number_format($selectedRunTotals['income_tax'], 2) }}</strong>
                    </div>
                    <div class="run-summary-row">
                        <span>Net</span>
                        <strong>PKR {{ number_format($selectedRunTotals['net_salary'], 2) }}</strong>
                    </div>
                </div>

                @if($canEditPayroll && $selectedRun->status !== 'finalized')
                    <form method="POST" action="{{ route('payroll.finalize', $selectedRun) }}" style="margin-top: 16px;">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i data-lucide="badge-check"></i> Finalize Payroll Run
                        </button>
                    </form>
                @endif

                <div class="stacked-list" style="margin-top: 18px;">
                    @foreach($selectedRun->records->sortBy('employee.employee_id') as $record)
                        <div class="timeline-card" style="margin-bottom: 12px;">
                            <div class="timeline-header">
                                <div>
                                    <h4>{{ $record->employee->full_name }}</h4>
                                    <p>{{ $record->employee->employee_id }} | Net PKR {{ number_format($record->net_salary, 2) }}</p>
                                </div>
                            </div>
                            <div class="action-buttons" style="margin-top: 12px;">
                                <a href="{{ route('payroll.payslip.download', [$selectedRun, $record->employee]) }}" class="btn btn-outline" style="text-decoration: none;">
                                    <i data-lucide="file-down"></i> Download Payslip
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state-panel">Select or generate a payroll run to review totals and download payslips.</div>
            @endif
        </div>
    </div>
</div>

<style>
    .payroll-toolbar-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .payroll-month-form,
    .payroll-generate-form {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) auto;
        gap: 16px;
        align-items: end;
    }

    .payroll-generate-form {
        grid-template-columns: repeat(2, minmax(160px, 1fr)) auto;
    }

    .payroll-layout {
        align-items: start;
    }

    .payroll-input-table input {
        min-width: 110px;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
    }

    .payroll-input-table input:disabled {
        background: #f9fafb;
        color: #6b7280;
    }

    .remarks-row td {
        background: #fafafa;
    }

    .table-footer-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 18px;
    }

    .run-summary {
        display: grid;
        gap: 10px;
    }

    .run-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: 14px;
        color: #374151;
    }

    @media (max-width: 1200px) {
        .payroll-toolbar-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .payroll-month-form,
        .payroll-generate-form {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
