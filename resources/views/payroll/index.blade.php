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
    $previewRowsOnPage = $previewRowsPagination->getCollection();
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

<div class="payroll-section-stack">
    <div class="card payroll-inputs-card">
        <div class="section-header" style="margin-bottom: 16px;">
            <div>
                <h2>Monthly Payroll Inputs</h2>
                <p>Review attendance and salary inputs for {{ $selectedMonthLabel }}. Employees are shown in compact cards so payroll stays readable without horizontal scrolling.</p>
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
                <input type="hidden" name="page" value="{{ $previewRowsPagination->currentPage() }}">
                <input type="hidden" name="run_page" value="{{ request('run_page', 1) }}">
                @if($selectedRun)
                    <input type="hidden" name="run" value="{{ $selectedRun->id }}">
                @endif

                <div class="payroll-card-grid">
                    @foreach($previewRowsOnPage as $row)
                        @php
                            $employee = $row['employee'];
                            $adjustment = $row['adjustment'];
                        @endphp
                        <div class="payroll-employee-card">
                            <div class="payroll-employee-header">
                                <div>
                                    <h3>{{ $employee->full_name }}</h3>
                                    <p>{{ $employee->employee_id }} | {{ $employee->designation ?? 'Not specified' }}</p>
                                </div>
                                <div class="projected-pay-chip">
                                    <span>Projected Net</span>
                                    <strong>PKR {{ number_format($row['net_salary'], 2) }}</strong>
                                    <small>Tax {{ number_format($row['income_tax'], 2) }}</small>
                                </div>
                            </div>

                            <div class="payroll-summary-grid">
                                <div class="summary-box">
                                    <span>Base</span>
                                    <strong>PKR {{ number_format($row['basic_salary'], 2) }}</strong>
                                </div>
                                <div class="summary-box">
                                    <span>Increment</span>
                                    <strong>PKR {{ number_format($row['last_increment'], 2) }}</strong>
                                </div>
                                <div class="summary-box">
                                    <span>Absent Days</span>
                                    <strong>{{ $row['days_absent'] }}</strong>
                                </div>
                                <div class="summary-box">
                                    <span>Short Hours</span>
                                    <strong>{{ $row['short_hours_days'] }}</strong>
                                </div>
                                <div class="summary-box">
                                    <span>Security Balance</span>
                                    <strong>PKR {{ number_format($row['security_balance'], 2) }}</strong>
                                </div>
                                <div class="summary-box">
                                    <span>Gross</span>
                                    <strong>PKR {{ number_format($row['gross_salary'], 2) }}</strong>
                                </div>
                            </div>

                            <div class="payroll-adjustment-grid">
                                <div class="form-group" style="margin: 0;">
                                    <label>Incentives</label>
                                    <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][incentives_bonus]" value="{{ old("adjustments.{$employee->id}.incentives_bonus", $adjustment?->incentives_bonus ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Punctuality</label>
                                    <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][punctuality_bonus]" value="{{ old("adjustments.{$employee->id}.punctuality_bonus", $adjustment?->punctuality_bonus ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Penalty</label>
                                    <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][attendance_penalty]" value="{{ old("adjustments.{$employee->id}.attendance_penalty", $adjustment?->attendance_penalty ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Arrears</label>
                                    <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][arrears_adjustment]" value="{{ old("adjustments.{$employee->id}.arrears_adjustment", $adjustment?->arrears_adjustment ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label>Other</label>
                                    <input type="number" step="0.01" name="adjustments[{{ $employee->id }}][other_adjustment]" value="{{ old("adjustments.{$employee->id}.other_adjustment", $adjustment?->other_adjustment ?? 0) }}" @if(!$canEditPayroll) disabled @endif>
                                </div>
                                <div class="form-group payroll-note-field" style="margin: 0;">
                                    <label>Remarks</label>
                                    <input type="text" name="adjustments[{{ $employee->id }}][remarks]" value="{{ old("adjustments.{$employee->id}.remarks", $adjustment?->remarks) }}" placeholder="Optional note for this month" @if(!$canEditPayroll) disabled @endif>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($canEditPayroll)
                    <div class="table-footer-actions">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save"></i> Save Adjustments
                        </button>
                    </div>
                @endif

                <div class="pagination-wrapper" style="margin-top: 20px;">
                    {{ $previewRowsPagination->links() }}
                </div>
            </form>
        @else
            <div class="empty-state-panel">
                No payroll-eligible employees were found for {{ $selectedMonthLabel }}. Make sure employee salary data and attendance exist before generating payroll.
            </div>
        @endif
    </div>

    <div class="payroll-support-grid">
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
                    @foreach($selectedRunRecords as $record)
                        <div class="timeline-card" style="margin-bottom: 12px;">
                            <div class="timeline-header">
                                <div>
                                    <h4>{{ $record->employee->full_name }}</h4>
                                    <p>{{ $record->employee->employee_id }} | Net PKR {{ number_format($record->net_salary, 2) }}</p>
                                </div>
                                <div class="timeline-date">
                                    <strong>Gross PKR {{ number_format($record->gross_salary, 2) }}</strong>
                                    <span>Tax PKR {{ number_format($record->income_tax, 2) }}</span>
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
                <div class="pagination-wrapper" style="margin-top: 16px;">
                    {{ $selectedRunRecords->links() }}
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

    .payroll-section-stack {
        display: grid;
        gap: 24px;
    }

    .payroll-inputs-card {
        overflow: hidden;
    }

    .payroll-card-grid {
        display: grid;
        gap: 18px;
    }

    .payroll-employee-card {
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 20px;
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
    }

    .payroll-employee-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .payroll-employee-header h3 {
        margin: 0 0 4px;
        font-size: 18px;
        color: #111827;
    }

    .payroll-employee-header p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }

    .projected-pay-chip {
        min-width: 180px;
        padding: 12px 14px;
        border-radius: 14px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        text-align: right;
    }

    .projected-pay-chip span,
    .projected-pay-chip small {
        display: block;
        color: #9a3412;
    }

    .projected-pay-chip strong {
        display: block;
        font-size: 18px;
        color: #111827;
        margin: 3px 0;
    }

    .payroll-summary-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .summary-box {
        padding: 12px 14px;
        border-radius: 14px;
        background: #f9fafb;
        border: 1px solid #eef2f7;
    }

    .summary-box span {
        display: block;
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
    }

    .summary-box strong {
        font-size: 14px;
        color: #111827;
    }

    .payroll-adjustment-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14px;
        align-items: end;
    }

    .payroll-adjustment-grid input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
    }

    .payroll-adjustment-grid input:disabled {
        background: #f9fafb;
        color: #6b7280;
    }

    .payroll-note-field {
        grid-column: span 1;
    }

    .payroll-support-grid {
        display: grid;
        grid-template-columns: minmax(280px, 0.9fr) minmax(420px, 1.1fr);
        gap: 24px;
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

        .payroll-summary-grid,
        .payroll-adjustment-grid,
        .payroll-support-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .payroll-month-form,
        .payroll-generate-form {
            grid-template-columns: 1fr;
        }

        .payroll-summary-grid,
        .payroll-adjustment-grid,
        .payroll-support-grid {
            grid-template-columns: 1fr;
        }

        .projected-pay-chip {
            width: 100%;
            text-align: left;
        }
    }
</style>
@endsection
