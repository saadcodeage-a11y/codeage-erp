@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')
@php
    $canImportAttendance = Auth::user()->canAccessModule('attendance_management', 'create');
    $canManageAttendance = Auth::user()->canAccessModule('attendance_management', 'edit');
    $hasAttendanceRecords = $attendanceRecords->count() > 0;
    $selectedMonthLabel = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y');
    $selectedYear = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('Y');
@endphp
<div class="page-header">
    <div class="header-left">
        <h1>Attendance Management</h1>
        <p>Import monthly fingerprint machine reports and review daily attendance summaries.</p>
    </div>
    <div class="header-right">
        @if($canImportAttendance)
            <button class="btn btn-primary" onclick="openModal('attendanceImportModal')">
                <i data-lucide="upload"></i> Import Attendance
            </button>
        @endif
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Records This Month</span><span class="stat-value">{{ $stats['records'] }}</span></div><div class="stat-icon-wrapper orange"><i data-lucide="calendar-days"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Present</span><span class="stat-value">{{ $stats['present'] }}</span></div><div class="stat-icon-wrapper green"><i data-lucide="badge-check"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Late</span><span class="stat-value">{{ $stats['late'] }}</span></div><div class="stat-icon-wrapper yellow"><i data-lucide="clock-3"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Absent / Incomplete</span><span class="stat-value">{{ $stats['absent'] + $stats['incomplete'] }}</span></div><div class="stat-icon-wrapper red"><i data-lucide="triangle-alert"></i></div></div>
</div>

<div class="attendance-policy-grid">
    <div class="card">
        <div class="section-header" style="margin-bottom: 16px;">
            <div>
                <h2>Attendance Rules</h2>
                <p>Global rules for attendance marking. Individual working hours stay on each employee profile.</p>
            </div>
        </div>
        <div class="attendance-rule-notes">
            <span class="summary-pill muted">Weekends: Saturday & Sunday</span>
            <span class="summary-pill muted">Late after: {{ $lateGraceMinutes }} minute{{ $lateGraceMinutes === 1 ? '' : 's' }}</span>
        </div>
        @if($canManageAttendance)
            <form method="POST" action="{{ route('attendance.settings.update') }}" class="attendance-rule-form">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="form-group" style="margin: 0;">
                    <label>Late Grace Period (Minutes)</label>
                    <input type="number" name="late_grace_minutes" min="0" max="240" value="{{ old('late_grace_minutes', $lateGraceMinutes) }}" required>
                    <span class="hint">If an employee checks in after shift start time plus this grace period, the row is counted late.</span>
                </div>
                <div class="policy-form-actions">
                    <button type="submit" class="btn btn-primary">Save Attendance Rule</button>
                </div>
            </form>
        @else
            <div class="empty-state-panel" style="margin-top: 12px;">Only users with attendance edit rights can change the global late threshold.</div>
        @endif
    </div>

    <div class="card">
        <div class="section-header" style="margin-bottom: 16px;">
            <div>
                <h2>Official Holidays</h2>
                <p>Working-day holidays for {{ $selectedYear }}. Weekends are already handled automatically.</p>
            </div>
        </div>
        @if($canManageAttendance)
            <form method="POST" action="{{ route('attendance.holidays.store') }}" class="holiday-form">
                @csrf
                <div class="form-group" style="margin: 0;">
                    <label>Holiday Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Eid ul Fitr" required>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Holiday Date</label>
                    <input type="date" name="holiday_date" value="{{ old('holiday_date') }}" required>
                </div>
                <div class="form-group holiday-form-description" style="margin: 0;">
                    <label>Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" placeholder="Optional note">
                </div>
                <div class="policy-form-actions">
                    <button type="submit" class="btn btn-primary">Add Holiday</button>
                </div>
            </form>
        @endif
        <div class="holiday-list">
            @forelse($officialHolidays as $holiday)
                <div class="holiday-card">
                    <div>
                        <strong>{{ $holiday->name }}</strong>
                        <p>{{ $holiday->holiday_date->format('d M Y') }} | {{ $holiday->holiday_date->format('l') }}</p>
                        @if($holiday->description)
                            <span>{{ $holiday->description }}</span>
                        @endif
                    </div>
                    @if($canManageAttendance)
                        <form method="POST" action="{{ route('attendance.holidays.destroy', $holiday) }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="month" value="{{ $month }}">
                            <button type="submit" class="btn btn-outline">Remove</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="empty-state-panel">No official working-day holidays have been added for {{ $selectedYear }}.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="{{ route('attendance.index') }}" style="display: grid; grid-template-columns: minmax(180px, 220px) minmax(220px, 1fr) auto; gap: 16px; align-items: end;">
        <div class="form-group" style="margin: 0;">
            <label>Month</label>
            <input type="month" name="month" value="{{ $month }}">
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Search Employee</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by employee name or ID">
        </div>
        <button type="submit" class="btn btn-outline">
            <i data-lucide="filter"></i> Apply Filters
        </button>
    </form>
</div>

@if(session('success') || session('warning') || $errors->any())
    <div class="attendance-feedback-stack">
        @if(session('success'))
            <div class="status-banner success">
                <i data-lucide="circle-check-big"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('warning'))
            <div class="status-banner warning">
                <i data-lucide="triangle-alert"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="status-banner danger">
                <i data-lucide="octagon-alert"></i>
                <div>
                    <strong>Attendance import could not be completed.</strong>
                    <p>{{ $errors->first() }}</p>
                </div>
            </div>
        @endif
    </div>
@endif

<div class="two-column-layout">
    <div class="table-card attendance-records-card">
        <div class="section-header attendance-records-header">
            <div>
                <h2>Attendance Records</h2>
                <p>Daily summaries generated from the imported fingerprint machine report for {{ $selectedMonthLabel }}.</p>
            </div>
            <div class="section-badge-row">
                <span class="summary-pill">{{ $stats['records'] }} rows</span>
                <span class="summary-pill muted">{{ $selectedMonthLabel }}</span>
            </div>
        </div>
        @if($hasAttendanceRecords)
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Late</th>
                            <th>Early</th>
                            <th>Absent</th>
                            <th>Work Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendanceRecords as $record)
                            <tr>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <strong>{{ $record->employee->full_name }}</strong>
                                        <span style="font-size: 12px; color: #6b7280;">{{ $record->employee->employee_id }}</span>
                                    </div>
                                </td>
                                <td>{{ $record->attendance_date->format('d M Y') }}</td>
                                <td>{{ $record->shift_start_time ? \Illuminate\Support\Carbon::parse($record->shift_start_time)->format('H:i') : '--:--' }} to {{ $record->shift_end_time ? \Illuminate\Support\Carbon::parse($record->shift_end_time)->format('H:i') : '--:--' }}</td>
                                <td>{{ $record->clock_in ? \Illuminate\Support\Carbon::parse($record->clock_in)->format('H:i') : '--:--' }}</td>
                                <td>{{ $record->clock_out ? \Illuminate\Support\Carbon::parse($record->clock_out)->format('H:i') : '--:--' }}</td>
                                <td>{{ $record->late_duration ?? '--:--' }}</td>
                                <td>{{ $record->early_duration ?? '--:--' }}</td>
                                <td>{{ $record->absent_duration ?? '--:--' }}</td>
                                <td>{{ $record->work_duration ?? '--:--' }}</td>
                                <td><span class="status-badge {{ $record->status }}">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrapper">{{ $attendanceRecords->links() }}</div>
        @else
            <div class="attendance-empty-shell">
                <div class="attendance-empty-state">
                    <div class="attendance-empty-icon">
                        <i data-lucide="calendar-search"></i>
                    </div>
                    <h3>No Attendance Records Yet</h3>
                    <p>No attendance records were found for {{ $selectedMonthLabel }}. Import the fingerprint machine report for this month to populate the summary table.</p>
                    <div class="attendance-empty-meta">
                        <span class="summary-pill muted">Expected file: .xls / .xlsx</span>
                        <span class="summary-pill muted">Employee match: first column</span>
                        <span class="summary-pill muted">Month: {{ $selectedMonthLabel }}</span>
                    </div>
                    @if($canImportAttendance)
                        <button class="btn btn-primary" onclick="openModal('attendanceImportModal')">
                            <i data-lucide="upload"></i> Import Attendance File
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="side-panel-stack">
        <div class="card">
            <div class="section-header" style="margin-bottom: 16px;">
                <div>
                    <h2>Recent Imports</h2>
                    <p>Uploaded fingerprint-machine attendance files.</p>
                </div>
            </div>
            <div class="import-list">
                @forelse($recentImports as $import)
                    <a href="{{ route('attendance.index', ['month' => $month, 'import' => $import->id]) }}" class="import-card {{ $selectedImport?->id === $import->id ? 'active' : '' }}">
                        <div>
                            <strong>{{ $import->source_file_name }}</strong>
                            <p>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $import->attendance_month)->format('F Y') }} | {{ optional($import->imported_at)->format('d M Y, h:i A') ?? 'Not imported yet' }}</p>
                        </div>
                        <div class="import-card-metrics">
                            <span>{{ $import->imported_rows }} imported</span>
                            <span>{{ $import->errors_count }} errors</span>
                        </div>
                    </a>
                @empty
                    <div class="empty-state-panel">No attendance files imported yet.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="section-header" style="margin-bottom: 16px;">
                <div>
                    <h2>Import Errors</h2>
                    <p>
                        @if($selectedImport)
                            Errors recorded for {{ $selectedImport->source_file_name }}.
                        @else
                            Select an import to review row-level issues.
                        @endif
                    </p>
                </div>
            </div>

            @if($selectedImport && $selectedImport->errors->isNotEmpty())
                <div class="error-list">
                    @foreach($selectedImport->errors as $error)
                        <div class="error-card">
                            <div class="error-card-header">
                                <strong>Row {{ $error->row_number }}</strong>
                                @if($error->employee_code)
                                    <span>{{ $error->employee_code }}</span>
                                @endif
                            </div>
                            <p>{{ $error->reason }}</p>
                            <div class="error-meta">
                                @if($error->employee_name)
                                    <span>Name: {{ $error->employee_name }}</span>
                                @endif
                                @if($error->attendance_date)
                                    <span>Date: {{ $error->attendance_date }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($selectedImport)
                <div class="empty-state-panel">No import errors were recorded for this file.</div>
            @else
                <div class="empty-state-panel">No import selected.</div>
            @endif
        </div>
    </div>
</div>

@if($canImportAttendance)
    <div id="attendanceImportModal" class="modal-overlay" style="display: none;">
        <div class="modal-container" style="max-width: 560px;">
            <div class="modal-header">
                <div>
                    <h2>Import Attendance File</h2>
                    <p class="modal-desc" style="margin-bottom: 0;">Upload the monthly `.xls` fingerprint-machine report. The first column will be matched against each employee's ID.</p>
                </div>
                <button class="close-btn" onclick="closeModal('attendanceImportModal')"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <form method="POST" action="{{ route('attendance.import') }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 16px;">
                    @csrf
                    <div class="form-group" style="margin: 0;">
                        <label>Attendance Month</label>
                        <input type="month" name="attendance_month" value="{{ old('attendance_month', $month) }}" required>
                        <span class="hint">Select the month this attendance file belongs to. Rows from a different month will be flagged as errors.</span>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Attendance File</label>
                        <input type="file" name="attendance_file" accept=".xls,.xlsx" required>
                        <span class="hint">Expected columns: No./Employee ID, Name, Date, Clock In, Clock Out, Late, Early, Absent, Work Time.</span>
                    </div>
                    <div class="modal-footer" style="padding: 0; margin-top: 8px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('attendanceImportModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Import File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<style>
    .attendance-policy-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }
    .attendance-rule-notes {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .attendance-rule-form,
    .holiday-form {
        display: grid;
        gap: 16px;
    }
    .holiday-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: end;
        margin-bottom: 18px;
    }
    .holiday-form-description,
    .policy-form-actions {
        grid-column: 1 / -1;
    }
    .holiday-list {
        display: grid;
        gap: 12px;
    }
    .holiday-card {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        padding: 14px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
    }
    .holiday-card strong {
        display: block;
        margin-bottom: 4px;
        color: #111827;
    }
    .holiday-card p,
    .holiday-card span {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.6;
    }
    .two-column-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.9fr);
        gap: 24px;
        align-items: start;
    }
    .side-panel-stack {
        display: grid;
        gap: 24px;
    }
    .attendance-feedback-stack {
        display: grid;
        gap: 12px;
        margin-bottom: 24px;
    }
    .status-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 18px;
        border-radius: 12px;
        border: 1px solid;
        background: #fff;
    }
    .status-banner i {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .status-banner strong,
    .status-banner span,
    .status-banner p {
        color: inherit;
    }
    .status-banner p {
        margin: 4px 0 0;
        font-size: 13px;
        line-height: 1.5;
    }
    .status-banner.success {
        color: #166534;
        border-color: #bbf7d0;
        background: #f0fdf4;
    }
    .status-banner.warning {
        color: #9a3412;
        border-color: #fed7aa;
        background: #fff7ed;
    }
    .status-banner.danger {
        color: #991b1b;
        border-color: #fecaca;
        background: #fef2f2;
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
        margin-bottom: 18px;
    }
    .section-header h2 {
        margin: 0 0 4px;
        font-size: 18px;
        color: #111827;
    }
    .section-header p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.6;
    }
    .attendance-records-header {
        padding: 24px 24px 18px;
        margin-bottom: 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .section-badge-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .summary-pill {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        background: #fff7ed;
        color: #9a3412;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #fed7aa;
    }
    .summary-pill.muted {
        background: #f8fafc;
        color: #475569;
        border-color: #e2e8f0;
    }
    .table-scroll {
        overflow-x: auto;
    }
    .attendance-records-card .pagination-wrapper {
        padding: 18px 24px 24px;
    }
    .import-list,
    .error-list {
        display: grid;
        gap: 12px;
    }
    .attendance-empty-shell {
        padding: 24px;
    }
    .attendance-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 14px;
        min-height: 280px;
        padding: 36px 28px;
        background:
            radial-gradient(circle at top, rgba(251, 146, 60, 0.14), transparent 42%),
            linear-gradient(180deg, #fffaf5 0%, #ffffff 62%);
        border: 1px dashed #fdba74;
        border-radius: 18px;
    }
    .attendance-empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 22px;
        background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 16px 30px rgba(249, 115, 22, 0.22);
    }
    .attendance-empty-icon i {
        width: 32px;
        height: 32px;
    }
    .attendance-empty-state h3 {
        margin: 0;
        font-size: 24px;
        color: #111827;
    }
    .attendance-empty-state p {
        max-width: 620px;
        margin: 0;
        color: #6b7280;
        line-height: 1.75;
        font-size: 14px;
    }
    .attendance-empty-meta {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .import-card {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        text-decoration: none;
        color: inherit;
        background: #fff;
        transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
    }
    .import-card:hover {
        border-color: #fdba74;
        transform: translateY(-1px);
    }
    .import-card.active {
        border-color: #fdba74;
        background: #fff7ed;
    }
    .import-card p {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 12px;
    }
    .import-card-metrics {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
        font-size: 12px;
        color: #6b7280;
        white-space: nowrap;
    }
    .error-card {
        border: 1px solid #fecaca;
        background: #fef2f2;
        border-radius: 14px;
        padding: 14px 16px;
    }
    .error-card-header,
    .error-meta {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .error-card p {
        margin: 8px 0;
        color: #991b1b;
        font-size: 13px;
        line-height: 1.5;
    }
    .error-meta {
        font-size: 12px;
        color: #7f1d1d;
    }
    .status-badge.early_leave,
    .status-badge.incomplete {
        background: #fef3c7;
        color: #92400e;
    }
    .status-badge.holiday,
    .status-badge.weekend {
        background: #e0f2fe;
        color: #0f766e;
    }
    @media (max-width: 1180px) {
        .attendance-policy-grid,
        .two-column-layout {
            grid-template-columns: 1fr;
        }
        .section-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .attendance-records-header {
            padding-bottom: 16px;
        }
    }
    @media (max-width: 768px) {
        .holiday-form {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    window.addEventListener('click', function (event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->has('attendance_file') || $errors->has('attendance_month'))
            openModal('attendanceImportModal');
        @endif
    });
</script>
@endsection
