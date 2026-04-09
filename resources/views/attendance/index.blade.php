@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')
@php
    $canImportAttendance = Auth::user()->canAccessModule('attendance_management', 'create');
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

<div class="two-column-layout">
    <div class="table-card">
        <div class="section-header">
            <div>
                <h2>Attendance Records</h2>
                <p>Daily summaries generated from the imported fingerprint machine report.</p>
            </div>
        </div>
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
                @forelse($attendanceRecords as $record)
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
                @empty
                    <tr><td colspan="10" class="text-center">No attendance records found for the selected month.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrapper">{{ $attendanceRecords->links() }}</div>
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
                            <p>{{ optional($import->imported_at)->format('d M Y, h:i A') ?? 'Not imported yet' }}</p>
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
    .section-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
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
    }
    .import-list,
    .error-list {
        display: grid;
        gap: 12px;
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
    @media (max-width: 1180px) {
        .two-column-layout {
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
</script>
@endsection
