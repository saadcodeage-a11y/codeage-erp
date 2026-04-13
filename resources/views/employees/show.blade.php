@extends('layouts.app')

@section('title', $employee->full_name . ' - Employee Details')

@section('content')
@php
    $canEditEmployees = Auth::user()->canAccessModule('employees', 'edit');
    $formatMoney = fn ($amount) => 'PKR ' . number_format((float) ($amount ?? 0), 2);
    $latestPayrollRecord = $employee->payrollRecords->first();
@endphp
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <a href="{{ route('employees.index') }}" class="btn btn-outline" style="padding: 8px 12px; text-decoration: none;">
            <i data-lucide="arrow-left"></i> Back to List
        </a>
    </div>
    @if($canEditEmployees)
        <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
            <button type="button" onclick="editEmployee({{ $employee->id }})" class="btn btn-primary" style="background-color: #FF4A00; text-decoration: none;">
                <i data-lucide="edit"></i> Edit Details
            </button>
            <button type="button" onclick="deleteEmployee({{ $employee->id }})" class="btn btn-primary" style="background-color: #dc2626; text-decoration: none;">
                <i data-lucide="trash-2"></i> Delete Employee
            </button>
            @if($employee->status == 'pending_approval')
                <button type="button" onclick="approveEmployee({{ $employee->id }})" class="btn btn-primary" style="background-color: #10B981; text-decoration: none;">
                    <i data-lucide="check"></i> Approve Application
                </button>
                <button type="button" onclick="disapproveEmployee({{ $employee->id }})" class="btn btn-outline" style="color: #dc2626; border-color: #dc2626; text-decoration: none;">
                    <i data-lucide="x"></i> Disapprove
                </button>
            @elseif($employee->status == 'active')
                <button type="button" onclick="openInactiveModal({{ $employee->id }})" class="btn btn-outline" style="text-decoration: none;">
                    <i data-lucide="user-minus"></i> Mark as Inactive
                </button>
                <button type="button" onclick="updateStatus({{ $employee->id }}, 'resigned')" class="btn btn-outline" style="text-decoration: none;">
                    <i data-lucide="log-out"></i> Mark as Resigned
                </button>
                <button type="button" onclick="updateStatus({{ $employee->id }}, 'terminated')" class="btn btn-outline" style="text-decoration: none;">
                    <i data-lucide="shield-x"></i> Mark as Terminated
                </button>
            @else
                <button type="button" onclick="updateStatus({{ $employee->id }}, 'active')" class="btn btn-outline" style="text-decoration: none;">
                    <i data-lucide="user-check"></i> Mark as Active
                </button>
            @endif
        </div>
    @endif
</div>

<!-- Employee Header Card -->
<div class="card" style="margin-bottom: 24px; display: flex; align-items: center; gap: 24px;">
    <div class="avatar-lg" style="width: 80px; height: 80px; border-radius: 50%; background-color: #FF4A00; color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 600; flex-shrink: 0; background-image: url('{{ $employee->profile_picture ? asset('storage/'.ltrim($employee->profile_picture, '/')) : '' }}'); background-size: cover; background-position: center;">
        @if(!$employee->profile_picture)
            {{ substr($employee->full_name, 0, 2) }}
        @endif
    </div>
    <div style="flex: 1;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
            <h1 style="font-size: 24px; font-weight: 600; color: #111827; margin: 0;">{{ $employee->full_name }}</h1>
            <span class="status-badge {{ $employee->status }}" style="padding: 2px 10px;">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</span>
        </div>
        <p style="color: #6b7280; font-size: 16px; margin: 0 0 8px 0;">{{ $employee->designation }}</p>
        <div style="display: flex; align-items: center; gap: 8px; color: #9ca3af; font-size: 14px;">
            <i data-lucide="briefcase" style="width: 14px; height: 14px;"></i> {{ $employee->employee_id ?? 'N/A' }}
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="tabs-container" style="width: 100%; margin-bottom: 24px; padding: 0; background: transparent; border: none; border-bottom: 1px solid #e5e7eb; border-radius: 0;">
    <button class="tab-btn active" onclick="switchTab('personal', this)">Personal Information</button>
    <button class="tab-btn" onclick="switchTab('employment', this)">Employment Summary</button>
    <button class="tab-btn" onclick="switchTab('job', this)">Job Details</button>
    <button class="tab-btn" onclick="switchTab('documents', this)">Documents</button>
    <button class="tab-btn" onclick="switchTab('leave', this)">Leave History</button>
    <button class="tab-btn" onclick="switchTab('attendance', this)">Attendance</button>
    <button class="tab-btn" onclick="switchTab('payroll', this)">Payroll</button>
    <button class="tab-btn" onclick="switchTab('letters', this)">HR Letters</button>
    <button class="tab-btn" onclick="switchTab('activity', this)">Activity Logs</button>
</div>

<!-- Tab Contents -->
<div class="card" style="min-height: 400px;">
    
    <!-- Personal Information Tab -->
    <div id="personal" class="tab-content active">
        <h3 class="section-title">Contact Information</h3>
        <div class="info-grid two-col">
            <div class="info-item">
                <label>Email</label>
                <div class="value-with-icon">
                    <i data-lucide="mail"></i> {{ $employee->email }}
                </div>
            </div>
            <div class="info-item">
                <label>Phone</label>
                <div class="value-with-icon">
                    <i data-lucide="phone"></i> {{ $employee->phone ?? 'Not provided' }}
                </div>
            </div>
        </div>

        <h3 class="section-title" style="margin-top: 32px;">Personal Details</h3>
        <div class="info-grid two-col">
            <div class="info-item">
                <label>CNIC</label>
                <p>{{ $employee->cnic ?? 'Not provided' }}</p>
            </div>
            <div class="info-item">
                <label>Gender</label>
                <p>{{ $employee->gender ?? 'Not provided' }}</p>
            </div>
            <div class="info-item">
                <label>Date of Birth</label>
                <p>{{ $employee->dob ? $employee->dob->format('d/m/Y') : 'Not provided' }}</p>
            </div>
            <div class="info-item">
                <label>Father's Name</label>
                <p>{{ $employee->father_name ?? 'Not provided' }}</p>
            </div>
            <div class="info-item">
                <label>Guardian Contact</label>
                <p>{{ $employee->guardian_contact ?? 'Not provided' }}</p>
            </div>
        </div>

        <div class="info-grid heading-only" style="margin-top: 20px;">
             <div class="info-item full-width">
                <label>Current Address</label>
                <p>{{ $employee->current_address ?? 'Not provided' }}</p>
            </div>
             <div class="info-item full-width">
                <label>Permanent Address</label>
                <p>{{ $employee->permanent_address ?? 'Not provided' }}</p>
            </div>
        </div>
    </div>

    <!-- Employment Summary Tab -->
    <div id="employment" class="tab-content" style="display: none;">
        <h3 class="section-title">Current Employment Snapshot</h3>
        <div class="info-grid two-col">
            <div class="info-item">
                <label>Position</label>
                <p>{{ $employee->designation }}</p>
            </div>
            <div class="info-item">
                <label>Department</label>
                <p>{{ $employee->department?->name ?? 'Not assigned' }}</p>
            </div>
             <div class="info-item">
                <label>Payroll Status</label>
                <p>{{ $employee->payroll_status ?? 'Not specified' }}</p>
            </div>
              <div class="info-item">
                <label>Location</label>
                <p>{{ $employee->job_location ?? 'Not specified' }}</p>
            </div>
            <div class="info-item">
                <label>Employee Status</label>
                <p>{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</p>
            </div>
            <div class="info-item">
                <label>Hiring Date</label>
                <p>{{ $employee->hiring_date ? $employee->hiring_date->format('d F, Y') : 'Not specified' }}</p>
            </div>
            @if($employee->inactive_reason)
                <div class="info-item full-width">
                    <label>Inactive Reason</label>
                    <p>{{ $employee->inactive_reason }}</p>
                </div>
            @endif
        </div>

        <h3 class="section-title" style="margin-top: 32px;">Employment Timeline</h3>
        <div class="timeline-list">
            @forelse($employee->employmentHistories as $history)
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-card">
                        <div class="timeline-header">
                            <div>
                                <h4>{{ $history->designation ?? 'Position not specified' }}</h4>
                                <p>
                                    {{ $history->department?->name ?? 'Department not assigned' }}
                                    •
                                    {{ $history->employment_status ? ucfirst(str_replace('_', ' ', $history->employment_status)) : 'Status not specified' }}
                                </p>
                            </div>
                            <div class="timeline-date">
                                <strong>{{ $history->effective_from->format('d M Y, h:i A') }}</strong>
                                <span>
                                    @if($history->effective_to)
                                        to {{ $history->effective_to->format('d M Y, h:i A') }}
                                    @else
                                        Current
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="timeline-meta">
                            <span>Payroll: {{ $history->payroll_status ?? 'Not specified' }}</span>
                            <span>Location: {{ $history->job_location ?? 'Not specified' }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state-panel">
                    No employment history has been recorded yet.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Job Details Tab (Placeholder mostly same as employment for now, user screenshot shows 'Position & Dates') -->
    <div id="job" class="tab-content" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <h3 class="section-title" style="margin-bottom: 4px;">Position & Dates</h3>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Employee-specific working hours are managed here and used by attendance imports.</p>
            </div>
            @if($canEditEmployees)
                <button type="button" class="btn btn-outline" onclick="openShiftTimingModal()">
                    <i data-lucide="clock-3"></i> Adjust Working Hours
                </button>
            @endif
        </div>
        <div class="info-grid two-col">
             <div class="info-item">
                <label>Department</label>
                <p>{{ $employee->department?->name ?? 'Not assigned' }}</p>
            </div>
            <div class="info-item">
                <label>Hiring Date</label>
                <p>{{ $employee->hiring_date ? $employee->hiring_date->format('d F, Y') : 'Not specified' }}</p>
            </div>
            <div class="info-item">
                <label>Shift Timing</label>
                <p>
                    {{ $employee->shift_start_time ? \Illuminate\Support\Carbon::parse($employee->shift_start_time)->format('H:i') : '--:--' }}
                    to
                    {{ $employee->shift_end_time ? \Illuminate\Support\Carbon::parse($employee->shift_end_time)->format('H:i') : '--:--' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Documents Tab -->
    <div id="documents" class="tab-content" style="display: none;">
        <h3 class="section-title"><i data-lucide="file-text"></i> Identity Documents</h3>
        <div class="doc-grid">
            <div class="doc-card">
                <p class="doc-label">CNIC Front</p>
                <p class="doc-status">{{ $employee->cnic_front_path ? 'Uploaded' : 'Not uploaded' }}</p>
                @if($employee->cnic_front_path)
                    <a href="{{ asset('storage/'.$employee->cnic_front_path) }}" target="_blank" class="doc-link">View</a>
                @endif
            </div>
             <div class="doc-card">
                <p class="doc-label">CNIC Back</p>
                <p class="doc-status">{{ $employee->cnic_back_path ? 'Uploaded' : 'Not uploaded' }}</p>
                 @if($employee->cnic_back_path)
                    <a href="{{ asset('storage/'.$employee->cnic_back_path) }}" target="_blank" class="doc-link">View</a>
                @endif
            </div>
        </div>

        <h3 class="section-title" style="margin-top: 32px;"><i data-lucide="briefcase"></i> Professional Documents</h3>
         <div class="doc-card full-width">
            <p class="doc-label">CV/Resume</p>
            <p class="doc-status">{{ $employee->cv_path ? 'Uploaded' : 'Not uploaded' }}</p>
             @if($employee->cv_path)
                <a href="{{ asset('storage/'.$employee->cv_path) }}" target="_blank" class="doc-link">View</a>
            @endif
        </div>
        <div class="doc-card full-width" style="margin-top: 16px;">
            <p class="doc-label">Educational Documents</p>
            <p class="doc-status">{{ $employee->transcript_path ? 'Uploaded' : 'Not uploaded' }}</p>
             @if($employee->transcript_path)
                <a href="{{ asset('storage/'.$employee->transcript_path) }}" target="_blank" class="doc-link">View</a>
            @endif
        </div>
    </div>

    <div id="leave" class="tab-content" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <h3 class="section-title" style="margin-bottom: 4px;">Leave History</h3>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Requests submitted for this employee across all leave types.</p>
            </div>
            @if(Auth::user()->canAccessModule('leave_management', 'create'))
                <a href="{{ route('leaves.index') }}" class="btn btn-outline" style="text-decoration: none;">
                    <i data-lucide="calendar-range"></i> Open Leave Management
                </a>
            @endif
        </div>

        <div class="stacked-list">
            @forelse($employee->leaveRequests as $leaveRequest)
                <div class="timeline-card" style="margin-bottom: 14px;">
                    <div class="timeline-header">
                        <div>
                            <h4>{{ $leaveRequest->leaveType?->name ?? 'Leave Request' }}</h4>
                            <p>
                                {{ $leaveRequest->start_date->format('d M Y') }} to {{ $leaveRequest->end_date->format('d M Y') }}
                                • {{ $leaveRequest->days_count }} day{{ $leaveRequest->days_count === 1 ? '' : 's' }}
                            </p>
                        </div>
                        <span class="status-badge {{ $leaveRequest->status }}">{{ ucfirst($leaveRequest->status) }}</span>
                    </div>
                    <div class="activity-log-meta" style="margin-bottom: 12px;">
                        <span>Requested by {{ $leaveRequest->requestedBy?->name ?? 'System' }}</span>
                        @if($leaveRequest->reviewed_at)
                            <span>Reviewed {{ $leaveRequest->reviewed_at->format('d M Y, h:i A') }}</span>
                        @endif
                    </div>
                    <p style="margin: 0; color: #374151; line-height: 1.6;">{{ $leaveRequest->reason }}</p>
                    @if($leaveRequest->reviewer_notes)
                        <div class="note-panel" style="margin-top: 12px;">
                            <strong>Reviewer Notes:</strong> {{ $leaveRequest->reviewer_notes }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-state-panel">
                    No leave requests have been recorded for this employee yet.
                </div>
            @endforelse
        </div>
    </div>

    <div id="attendance" class="tab-content" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <h3 class="section-title" style="margin-bottom: 4px;">Recent Attendance</h3>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Latest imported attendance rows for this employee, including the assigned shift timings and global late policy.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                @if($canEditEmployees)
                    <button type="button" class="btn btn-outline" onclick="openShiftTimingModal()">
                        <i data-lucide="clock-3"></i> Adjust Working Hours
                    </button>
                @endif
                @if(Auth::user()->canAccessModule('attendance_management'))
                    <a href="{{ route('attendance.index', ['search' => $employee->employee_id, 'month' => now()->format('Y-m')]) }}" class="btn btn-outline" style="text-decoration: none;">
                        <i data-lucide="fingerprint"></i> Open Attendance Module
                    </a>
                @endif
            </div>
        </div>

        <div class="stacked-list">
            @forelse($employee->attendanceRecords as $attendanceRecord)
                <div class="timeline-card" style="margin-bottom: 14px;">
                    <div class="timeline-header">
                        <div>
                            <h4>{{ $attendanceRecord->attendance_date->format('d M Y') }}</h4>
                            <p>
                                Shift
                                {{ $attendanceRecord->shift_start_time ? \Illuminate\Support\Carbon::parse($attendanceRecord->shift_start_time)->format('H:i') : '--:--' }}
                                to
                                {{ $attendanceRecord->shift_end_time ? \Illuminate\Support\Carbon::parse($attendanceRecord->shift_end_time)->format('H:i') : '--:--' }}
                            </p>
                        </div>
                        <span class="status-badge {{ $attendanceRecord->status }}">{{ ucfirst(str_replace('_', ' ', $attendanceRecord->status)) }}</span>
                    </div>
                    <div class="timeline-meta">
                        <span>Clock In: {{ $attendanceRecord->clock_in ? \Illuminate\Support\Carbon::parse($attendanceRecord->clock_in)->format('H:i') : '--:--' }}</span>
                        <span>Clock Out: {{ $attendanceRecord->clock_out ? \Illuminate\Support\Carbon::parse($attendanceRecord->clock_out)->format('H:i') : '--:--' }}</span>
                        <span>Late: {{ $attendanceRecord->late_duration ?? '--:--' }}</span>
                        <span>Early: {{ $attendanceRecord->early_duration ?? '--:--' }}</span>
                        <span>Absent: {{ $attendanceRecord->absent_duration ?? '--:--' }}</span>
                        <span>Work Time: {{ $attendanceRecord->work_duration ?? '--:--' }}</span>
                    </div>
                </div>
            @empty
                <div class="empty-state-panel">
                    No attendance records have been imported for this employee yet.
                </div>
            @endforelse
        </div>
    </div>

    <div id="payroll" class="tab-content" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <h3 class="section-title" style="margin-bottom: 4px;">Payroll Overview</h3>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Current salary settings, latest payroll breakdown, and security-fund balances for this employee.</p>
            </div>
        </div>

        <div class="info-grid two-col">
            <div class="info-item">
                <label>Current Salary</label>
                <p>{{ $employee->current_salary !== null ? $formatMoney($employee->current_salary) : 'Not specified' }}</p>
            </div>
            <div class="info-item">
                <label>Payment Mode</label>
                <p>{{ $employee->payment_mode ?? 'Not specified' }}</p>
            </div>
            <div class="info-item">
                <label>Payroll Status</label>
                <p>{{ $employee->payroll_status ?? 'Not specified' }}</p>
            </div>
            <div class="info-item">
                <label>Last Increment</label>
                <p>{{ $employee->last_increment !== null ? $formatMoney($employee->last_increment) : 'Not specified' }}</p>
            </div>
        </div>

        <h3 class="section-title" style="margin-top: 32px;">Latest Payroll Run</h3>
        @if($latestPayrollRecord)
            <div class="info-grid two-col" style="margin-bottom: 20px;">
                <div class="info-item">
                    <label>Pay Period</label>
                    <p>{{ optional($latestPayrollRecord->payrollRun?->pay_period_month)->format('F Y') ?? 'Not specified' }}</p>
                </div>
                <div class="info-item">
                    <label>Payment Date</label>
                    <p>{{ optional($latestPayrollRecord->payrollRun?->payment_date)->format('d F, Y') ?? 'Not specified' }}</p>
                </div>
                <div class="info-item">
                    <label>Gross Salary</label>
                    <p>{{ $formatMoney($latestPayrollRecord->gross_salary) }}</p>
                </div>
                <div class="info-item">
                    <label>Net Salary</label>
                    <p>{{ $formatMoney($latestPayrollRecord->net_salary) }}</p>
                </div>
            </div>

            <div class="info-grid two-col" style="margin-bottom: 20px;">
                <div class="timeline-card">
                    <div class="timeline-header" style="margin-bottom: 12px;">
                        <div>
                            <h4>Earnings</h4>
                            <p>Amounts contributing positively to payroll.</p>
                        </div>
                    </div>
                    <div class="timeline-meta" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                        <span>Basic: {{ $formatMoney($latestPayrollRecord->basic_salary) }}</span>
                        <span>Increment: {{ $formatMoney($latestPayrollRecord->last_increment) }}</span>
                        <span>Incentives: {{ $formatMoney($latestPayrollRecord->incentives_bonus) }}</span>
                        <span>Punctuality: {{ $formatMoney($latestPayrollRecord->punctuality_bonus) }}</span>
                        <span>Arrears: {{ $formatMoney($latestPayrollRecord->positive_arrears) }}</span>
                        <span>Other: {{ $formatMoney($latestPayrollRecord->positive_other) }}</span>
                    </div>
                </div>
                <div class="timeline-card">
                    <div class="timeline-header" style="margin-bottom: 12px;">
                        <div>
                            <h4>Deductions</h4>
                            <p>Amounts reducing the payable salary.</p>
                        </div>
                    </div>
                    <div class="timeline-meta" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                        <span>Security: {{ $formatMoney($latestPayrollRecord->security_deduction) }}</span>
                        <span>Security Total: {{ $formatMoney($latestPayrollRecord->security_total_deducted) }}</span>
                        <span>Non-Paid Leave: {{ $formatMoney($latestPayrollRecord->non_paid_leave_deduction) }}</span>
                        <span>Attendance Penalty: {{ $formatMoney($latestPayrollRecord->attendance_penalty) }}</span>
                        <span>Arrears: {{ $formatMoney($latestPayrollRecord->arrears_deduction) }}</span>
                        <span>Other: {{ $formatMoney($latestPayrollRecord->other_deduction) }}</span>
                        <span>Income Tax: {{ $formatMoney($latestPayrollRecord->income_tax) }}</span>
                        <span>Annual Tax Total: {{ $formatMoney($latestPayrollRecord->annual_tax_total) }}</span>
                    </div>
                </div>
            </div>

            <div class="info-grid two-col">
                <div class="info-item">
                    <label>Actual Absent Days</label>
                    <p>{{ $latestPayrollRecord->days_absent }}</p>
                </div>
                <div class="info-item">
                    <label>Late Arrivals</label>
                    <p>{{ $latestPayrollRecord->late_count }}</p>
                </div>
                <div class="info-item">
                    <label>Absent by 3 Late Arrivals</label>
                    <p>{{ $latestPayrollRecord->late_absent_equivalent }}</p>
                </div>
                <div class="info-item">
                    <label>Total Unpaid Leave Days</label>
                    <p>{{ $latestPayrollRecord->unpaid_leave_days }}</p>
                </div>
                <div class="info-item">
                    <label>Short Hours Days</label>
                    <p>{{ $latestPayrollRecord->short_hours_days }}</p>
                </div>
                <div class="info-item">
                    <label>Beneficiary Name</label>
                    <p>{{ $latestPayrollRecord->beneficiary_name ?? 'Not specified' }}</p>
                </div>
                <div class="info-item">
                    <label>Beneficiary Account</label>
                    <p>{{ $latestPayrollRecord->beneficiary_account_no ?? 'Not specified' }}</p>
                </div>
            </div>
        @else
            <div class="empty-state-panel">
                No payroll records are available for this employee yet.
            </div>
        @endif

        <h3 class="section-title" style="margin-top: 32px;">Payroll History</h3>
        <div class="stacked-list">
            @forelse($employee->payrollRecords as $payrollRecord)
                <div class="timeline-card" style="margin-bottom: 14px;">
                    <div class="timeline-header">
                        <div>
                            <h4>{{ optional($payrollRecord->payrollRun?->pay_period_month)->format('F Y') ?? 'Payroll Run' }}</h4>
                            <p>{{ $payrollRecord->payrollRun?->name ?? 'Imported payroll record' }}</p>
                        </div>
                        <div class="timeline-date">
                            <strong>{{ $formatMoney($payrollRecord->net_salary) }}</strong>
                            <span>Gross {{ $formatMoney($payrollRecord->gross_salary) }}</span>
                        </div>
                    </div>
                    <div class="timeline-meta">
                        <span>Basic: {{ $formatMoney($payrollRecord->basic_salary) }}</span>
                        <span>Tax: {{ $formatMoney($payrollRecord->income_tax) }}</span>
                        <span>Annual Tax Total: {{ $formatMoney($payrollRecord->annual_tax_total) }}</span>
                        <span>Security: {{ $formatMoney($payrollRecord->security_deduction) }}</span>
                        <span>Security Total: {{ $formatMoney($payrollRecord->security_total_deducted) }}</span>
                        <span>Actual Absence Days: {{ $payrollRecord->days_absent }}</span>
                        <span>Late Arrivals: {{ $payrollRecord->late_count }}</span>
                        <span>Absent by 3 Late Arrivals: {{ $payrollRecord->late_absent_equivalent }}</span>
                    </div>
                </div>
            @empty
                <div class="empty-state-panel">
                    No payroll history has been recorded for this employee yet.
                </div>
            @endforelse
        </div>

        <h3 class="section-title" style="margin-top: 32px;">Security Fund</h3>
        <div class="stacked-list">
            @forelse($employee->securityFundSnapshots as $snapshot)
                <div class="timeline-card" style="margin-bottom: 14px;">
                    <div class="timeline-header">
                        <div>
                            <h4>{{ $snapshot->fiscal_year_label }}</h4>
                            <p>Snapshot month {{ $snapshot->snapshot_month->format('F Y') }}</p>
                        </div>
                        <div class="timeline-date">
                            <strong>Balance {{ $formatMoney($snapshot->balance_in_account) }}</strong>
                            <span>Paid {{ $formatMoney($snapshot->paid_amount) }}</span>
                        </div>
                    </div>
                    <div class="timeline-meta">
                        <span>Opening Arrears: {{ $formatMoney($snapshot->opening_arrears) }}</span>
                        <span>July: {{ $formatMoney($snapshot->july_amount) }}</span>
                        <span>August: {{ $formatMoney($snapshot->august_amount) }}</span>
                        <span>September: {{ $formatMoney($snapshot->september_amount) }}</span>
                        <span>October: {{ $formatMoney($snapshot->october_amount) }}</span>
                        <span>November: {{ $formatMoney($snapshot->november_amount) }}</span>
                        <span>December: {{ $formatMoney($snapshot->december_amount) }}</span>
                    </div>
                    @if($snapshot->remarks)
                        <div class="note-panel" style="margin-top: 12px;">
                            <strong>Remarks:</strong> {{ $snapshot->remarks }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-state-panel">
                    No security-fund snapshots are available for this employee yet.
                </div>
            @endforelse
        </div>
    </div>

    <div id="letters" class="tab-content" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <h3 class="section-title" style="margin-bottom: 4px;">HR Letters</h3>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Generate, store, and download employee letters from one place.</p>
            </div>
            @if($canEditEmployees)
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline" onclick="generateLetter('offer')">
                        <i data-lucide="file-plus"></i> Offer Letter
                    </button>
                    <button type="button" class="btn btn-outline" onclick="generateLetter('experience')">
                        <i data-lucide="badge-check"></i> Experience Letter
                    </button>
                    <button type="button" class="btn btn-outline" onclick="generateLetter('termination')">
                        <i data-lucide="file-warning"></i> Termination Letter
                    </button>
                </div>
            @endif
        </div>

        <div class="stacked-list">
            @forelse($employee->hrLetters as $letter)
                <div class="timeline-card" style="margin-bottom: 14px;">
                    <div class="timeline-header">
                        <div>
                            <h4>{{ $letter->title }}</h4>
                            <p>{{ ucfirst($letter->type) }} letter</p>
                        </div>
                        <div class="timeline-date">
                            <strong>{{ optional($letter->generated_at)->format('d M Y, h:i A') ?? 'Generated' }}</strong>
                            <span>{{ $letter->generatedBy?->name ? 'By ' . $letter->generatedBy->name : 'Generated by system' }}</span>
                        </div>
                    </div>
                    <div class="action-buttons" style="margin-top: 14px;">
                        <a href="{{ route('employees.letters.download', [$employee, $letter]) }}" class="btn btn-outline" style="text-decoration: none;">
                            <i data-lucide="download"></i> Download
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state-panel">
                    No HR letters have been generated for this employee yet.
                </div>
            @endforelse
        </div>
    </div>
    
    <!-- Activity Logs -->
    <div id="activity" class="tab-content" style="display: none;">
        <h3 class="section-title">Employee Activity</h3>
        <div class="activity-log-list">
            @forelse($employeeActivityLogs as $log)
                @php
                    $changedFields = collect(array_keys($log->properties['old'] ?? []))
                        ->map(fn ($field) => \Illuminate\Support\Str::headline($field))
                        ->implode(', ');
                @endphp
                <div class="activity-log-card">
                    <div class="activity-log-header">
                        <div>
                            <h4>{{ $log->description }}</h4>
                            <p>
                                {{ $log->created_at->format('d M Y, h:i A') }}
                                @if($log->user)
                                    • By {{ $log->user->name }}
                                @endif
                            </p>
                        </div>
                        <span class="log-type {{ $log->type }}">{{ ucfirst($log->type) }}</span>
                    </div>
                    <div class="activity-log-meta">
                        <span>Subject: {{ \Illuminate\Support\Str::headline(class_basename($log->subject_type ?? 'Employee')) }}</span>
                        @if($changedFields !== '')
                            <span>Changed: {{ $changedFields }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state-panel">
                    No activity logs recorded for this employee yet.
                </div>
            @endforelse
        </div>
    </div>

</div>

<div id="inactiveEmployeeModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <div>
                <h2>Mark Employee as Inactive</h2>
                <p class="modal-desc">A reason is required before this employee can be marked inactive.</p>
            </div>
            <button onclick="closeInactiveModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <form id="inactiveEmployeeForm">
                @csrf
                <input type="hidden" id="inactive_employee_id" value="{{ $employee->id }}">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Reason for Inactivation *</label>
                    <textarea id="inactive_employee_reason" rows="4" required placeholder="Explain why this employee is being marked inactive" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;"></textarea>
                    <span id="inactiveEmployeeError" class="text-red-500 text-xs" style="display: none; margin-top: 6px;"></span>
                </div>
                <div class="modal-footer" style="margin-top: 20px; padding: 0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeInactiveModal()" class="btn btn-outline">Cancel</button>
                    <button type="button" onclick="submitInactiveStatus()" class="btn btn-primary" style="background-color: #6b7280; color: white;">Save Reason & Mark Inactive</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="shiftTimingModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <div>
                <h2>Adjust Working Hours</h2>
                <p class="modal-desc">Update the employee-specific shift timing used for attendance review and late calculation.</p>
            </div>
            <button type="button" onclick="closeShiftTimingModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <form method="POST" action="{{ route('employees.shift-timing.update', $employee) }}">
                @csrf
                @method('PATCH')
                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
                    <div class="form-group" style="margin: 0;">
                        <label>Shift Start Time</label>
                        <input type="time" name="shift_start_time" value="{{ old('shift_start_time', $employee->shift_start_time ? \Illuminate\Support\Carbon::parse($employee->shift_start_time)->format('H:i') : '') }}">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Shift End Time</label>
                        <input type="time" name="shift_end_time" value="{{ old('shift_end_time', $employee->shift_end_time ? \Illuminate\Support\Carbon::parse($employee->shift_end_time)->format('H:i') : '') }}">
                    </div>
                </div>
                <div class="modal-footer" style="margin-top: 20px; padding: 0;">
                    <button type="button" onclick="closeShiftTimingModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #FF4A00; color: white;">Save Working Hours</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal (same as index) -->
<div id="editEmployeeModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2>Edit Employee</h2>
                <p class="modal-desc" style="margin-bottom: 0;">Update the employee's information. Fields marked with * are required.</p>
            </div>
            <button onclick="closeEditModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <div id="editErrorSummary" class="alert alert-danger" style="display: none; background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <strong style="display: block; margin-bottom: 4px;">Please check the following errors:</strong>
                <ul id="editErrorList" style="margin: 0; padding-left: 20px;"></ul>
            </div>

            <form id="editEmployeeForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <!-- Profile Picture -->
                <div class="form-section profile-section" style="border-bottom: none; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 10px; border: none;">Employee Profile Picture</h3>
                    <div class="profile-upload">
                        <div id="edit_profilePreview" class="profile-placeholder" style="width: 80px; height: 80px; font-size: 32px; overflow: hidden; background-position: center; background-size: cover;">
                            <i data-lucide="user"></i>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <div class="upload-btn-wrapper">
                                <button type="button" class="btn btn-outline">
                                    <i data-lucide="upload"></i> Change Photo
                                </button>
                                <input type="file" name="profile_picture" accept="image/*" id="edit_profilePhotoInput" onchange="previewEditProfilePhoto(this)">
                            </div>
                            <span class="hint">Recommended: Square image, max 2MB</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="form-group">
                            <label>CNIC</label>
                            <input type="text" name="cnic" id="edit_cnic">
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" id="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" id="edit_phone">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" id="edit_gender">
                                <option value="">Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" id="edit_dob">
                        </div>
                        <div class="form-group full-width">
                            <label>Current Address</label>
                            <input type="text" name="current_address" id="edit_current_address">
                        </div>
                        <div class="form-group full-width">
                            <label>Permanent Address</label>
                            <input type="text" name="permanent_address" id="edit_permanent_address">
                        </div>
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" id="edit_father_name">
                        </div>
                        <div class="form-group">
                            <label>Father/Guardian Contact</label>
                            <input type="text" name="guardian_contact" id="edit_guardian_contact">
                        </div>
                        <div class="form-group">
                            <label>Education Level</label>
                            <select name="education_level" id="edit_education_level">
                                <option value="">Select education level</option>
                                <option value="Bachelors">Bachelors</option>
                                <option value="Masters">Masters</option>
                                <option value="PhD">PhD</option>
                                <option value="Intermediate">Intermediate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Field of Study / Major</label>
                            <input type="text" name="field_of_study" id="edit_field_of_study">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Job Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hiring Date</label>
                            <input type="date" name="hiring_date" id="edit_hiring_date">
                        </div>
                        <div class="form-group">
                            <label>Hiring Position</label>
                            <input type="text" name="designation" id="edit_designation" required>
                        </div>
                        <div class="form-group">
                            <label>Job Location</label>
                            <select name="job_location" id="edit_job_location">
                                <option value="">Select location</option>
                                <option value="On-site">On-site</option>
                                <option value="Remote">Remote</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Shift Start Time</label>
                            <input type="time" name="shift_start_time" id="edit_shift_start_time">
                        </div>
                        <div class="form-group">
                            <label>Department *</label>
                            <select name="department_id" id="edit_department_id" required>
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Shift End Time</label>
                            <input type="time" name="shift_end_time" id="edit_shift_end_time">
                        </div>
                        <div class="form-group full-width">
                            <label>Payroll Status</label>
                            <select name="payroll_status" id="edit_payroll_status">
                                <option value="">Select payroll status</option>
                                <option value="Paid">Paid</option>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Internship">Internship</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Employee Status</label>
                            <select name="status" id="edit_status" onchange="toggleEditInactiveReasonField()">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="invited">Invited</option>
                                <option value="pending_approval">Pending Approval</option>
                                <option value="resigned">Resigned</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                        <div class="form-group full-width" id="editInactiveReasonGroup" style="display: none;">
                            <label>Reason for Inactivation *</label>
                            <textarea name="inactive_reason" id="edit_inactive_reason" rows="3" placeholder="Explain why this employee is being marked inactive" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>HR Manager Comments</h3>
                    <div class="form-group">
                        <textarea name="hr_comments" id="edit_hr_comments" rows="3" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;"></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Document Uploads</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>CNIC Front</label>
                            <input type="file" name="cnic_front" class="file-input">
                            <div id="edit_cnic_front_status"></div>
                        </div>
                        <div class="form-group">
                            <label>CNIC Back</label>
                            <input type="file" name="cnic_back" class="file-input">
                            <div id="edit_cnic_back_status"></div>
                        </div>
                        <div class="form-group full-width">
                            <label>CV/Resume Upload</label>
                            <input type="file" name="cv" class="file-input">
                            <div id="edit_cv_status"></div>
                        </div>
                        <div class="form-group full-width">
                            <label>Educational Documents / Transcript</label>
                            <input type="file" name="transcript" class="file-input">
                            <div id="edit_transcript_status"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Banking Information</h3>
                    <div class="form-group">
                        <label>Do they have a Bank Account?</label>
                        <select id="edit_bankToggle" onchange="toggleEditBankFields()">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div id="edit_bankFields" class="form-grid" style="display: none; margin-top: 15px;">
                        <div class="form-group">
                            <label>Bank</label>
                            <select name="bank_id" id="edit_bank_id">
                                <option value="">Select Bank</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Account Title</label>
                            <input type="text" name="bank_account_title" id="edit_bank_account_title">
                        </div>
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" name="bank_account_number" id="edit_bank_account_number">
                        </div>
                        <div class="form-group">
                            <label>IBAN</label>
                            <input type="text" name="iban" id="edit_iban">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Banking Comments</label>
                        <textarea name="banking_comments" id="edit_banking_comments" rows="3" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #FF4A00; color: white;">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function switchTab(tabId, button) {
        document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
        document.getElementById(tabId).style.display = 'block';
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
    }

    function editEmployee(id) {
        fetch(`/employees/${id}/edit`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            const employee = data.employee;
            const departments = data.departments;
            const banks = data.banks;
            document.getElementById('editEmployeeForm').action = `/employees/${id}`;
            const deptSelect = document.getElementById('edit_department_id');
            deptSelect.innerHTML = '<option value="">Select Department</option>';
            departments.forEach(dept => {
                const opt = document.createElement('option');
                opt.value = dept.id;
                opt.textContent = dept.name;
                if (employee.department_id == dept.id) opt.selected = true;
                deptSelect.appendChild(opt);
            });

            const bankSelect = document.getElementById('edit_bank_id');
            bankSelect.innerHTML = '<option value="">Select Bank</option>';
            banks.forEach(bank => {
                const opt = document.createElement('option');
                opt.value = bank.id;
                opt.textContent = bank.code ? `${bank.name} (${bank.code})` : bank.name;
                if (employee.bank_id == bank.id) opt.selected = true;
                bankSelect.appendChild(opt);
            });

            document.getElementById('edit_full_name').value = employee.full_name || '';
            document.getElementById('edit_cnic').value = employee.cnic || '';
            document.getElementById('edit_email').value = employee.email || '';
            document.getElementById('edit_phone').value = employee.phone || '';
            document.getElementById('edit_gender').value = employee.gender || '';
            document.getElementById('edit_dob').value = employee.dob ? employee.dob.split('T')[0] : '';
            document.getElementById('edit_current_address').value = employee.current_address || '';
            document.getElementById('edit_permanent_address').value = employee.permanent_address || '';
            document.getElementById('edit_father_name').value = employee.father_name || '';
            document.getElementById('edit_guardian_contact').value = employee.guardian_contact || '';
            document.getElementById('edit_education_level').value = employee.education_level || '';
            document.getElementById('edit_field_of_study').value = employee.field_of_study || '';
            document.getElementById('edit_hiring_date').value = employee.hiring_date ? employee.hiring_date.split('T')[0] : '';
            document.getElementById('edit_designation').value = employee.designation || '';
            document.getElementById('edit_job_location').value = employee.job_location || '';
            document.getElementById('edit_shift_start_time').value = employee.shift_start_time ? employee.shift_start_time.substring(0, 5) : '';
            document.getElementById('edit_shift_end_time').value = employee.shift_end_time ? employee.shift_end_time.substring(0, 5) : '';
            document.getElementById('edit_payroll_status').value = employee.payroll_status || '';
            document.getElementById('edit_status').value = employee.status || '';
            document.getElementById('edit_inactive_reason').value = employee.inactive_reason || '';
            document.getElementById('edit_hr_comments').value = employee.hr_comments || '';
            
            if (employee.bank_id || employee.bank_name) {
                document.getElementById('edit_bankToggle').value = 'Yes';
                document.getElementById('edit_bankFields').style.display = 'grid';
            } else {
                document.getElementById('edit_bankToggle').value = 'No';
                document.getElementById('edit_bankFields').style.display = 'none';
            }
            document.getElementById('edit_bank_account_title').value = employee.bank_account_title || '';
            document.getElementById('edit_bank_account_number').value = employee.bank_account_number || '';
            document.getElementById('edit_iban').value = employee.iban || '';
            document.getElementById('edit_banking_comments').value = employee.banking_comments || '';

            const preview = document.getElementById('edit_profilePreview');
            if (employee.profile_picture) {
                preview.innerHTML = '';
                const storagePath = "{{ asset('storage') }}";
                preview.style.backgroundImage = `url('${storagePath}/${employee.profile_picture.replace(/^\//, '')}')`;
                preview.style.border = '1px solid #e5e7eb';
            } else {
                preview.style.backgroundImage = 'none';
                preview.innerHTML = '<i data-lucide="user"></i>';
                preview.style.border = 'none';
                if (window.lucide) window.lucide.createIcons();
            }

            document.getElementById('edit_cnic_front_status').innerHTML = employee.cnic_front_path ? '<small style="color: green">Uploaded</small>' : '';
            document.getElementById('edit_cnic_back_status').innerHTML = employee.cnic_back_path ? '<small style="color: green">Uploaded</small>' : '';
            document.getElementById('edit_cv_status').innerHTML = employee.cv_path ? '<small style="color: green">Uploaded</small>' : '';
            document.getElementById('edit_transcript_status').innerHTML = employee.transcript_path ? '<small style="color: green">Uploaded</small>' : '';

            toggleEditInactiveReasonField();
            openEditModal();
        });
    }

    function openEditModal() {
        document.getElementById('editEmployeeModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeEditModal() {
        document.getElementById('editEmployeeModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function openShiftTimingModal() {
        document.getElementById('shiftTimingModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeShiftTimingModal() {
        document.getElementById('shiftTimingModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function toggleEditBankFields() {
        var val = document.getElementById('edit_bankToggle').value;
        var fields = document.getElementById('edit_bankFields');
        var shouldShow = val === 'Yes';
        fields.style.display = shouldShow ? 'grid' : 'none';

        if (!shouldShow) {
            document.getElementById('edit_bank_id').value = '';
            document.getElementById('edit_bank_account_title').value = '';
            document.getElementById('edit_bank_account_number').value = '';
            document.getElementById('edit_iban').value = '';
        }
    }

    function toggleEditInactiveReasonField() {
        const status = document.getElementById('edit_status').value;
        const group = document.getElementById('editInactiveReasonGroup');
        const field = document.getElementById('edit_inactive_reason');
        const isInactive = status === 'inactive';

        group.style.display = isInactive ? 'block' : 'none';
        field.required = isInactive;

        if (!isInactive) {
            field.value = '';
        }
    }

    function previewEditProfilePhoto(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('edit_profilePreview');
                preview.innerHTML = '';
                preview.style.backgroundImage = 'url(' + e.target.result + ')';
                preview.style.backgroundColor = 'transparent';
                preview.style.border = '1px solid #e5e7eb';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openInactiveModal(id) {
        document.getElementById('inactive_employee_id').value = id;
        document.getElementById('inactive_employee_reason').value = '';
        document.getElementById('inactiveEmployeeError').textContent = '';
        document.getElementById('inactiveEmployeeError').style.display = 'none';
        document.getElementById('inactiveEmployeeModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeInactiveModal() {
        document.getElementById('inactiveEmployeeModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function submitInactiveStatus() {
        const id = document.getElementById('inactive_employee_id').value;
        const reason = document.getElementById('inactive_employee_reason').value.trim();
        const error = document.getElementById('inactiveEmployeeError');

        if (!reason) {
            error.textContent = 'Reason is required.';
            error.style.display = 'block';
            return;
        }

        updateStatus(id, 'inactive', reason);
    }

    function updateStatus(id, status, inactiveReason = null) {
        if (status !== 'inactive' && !confirm(`Are you sure you want to mark this employee as ${status}?`)) return;

        fetch(`/employees/${id}/status`, {
            method: 'PATCH',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                status: status,
                inactive_reason: inactiveReason
            })
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 422 && data.errors?.inactive_reason?.[0]) {
                    const error = document.getElementById('inactiveEmployeeError');

                    if (error) {
                        error.textContent = data.errors.inactive_reason[0];
                        error.style.display = 'block';
                    }
                } else {
                    alert(data.message || 'Failed to update status.');
                }

                return null;
            }

            return data;
        })
        .then(data => {
            if (data?.success) {
                closeInactiveModal();
                location.reload();
            }
        });
    }

    function deleteEmployee(id) {
        if (!confirm('Are you sure you want to delete this employee? This action cannot be undone.')) return;

        fetch(`/employees/${id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = "{{ route('employees.index') }}";
            } else {
                alert('Failed to delete employee.');
            }
        });
    }

    function approveEmployee(id) {
        if (!confirm('Are you sure you want to approve this employee?')) return;
        fetch(`/employees/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => location.reload());
    }

    function disapproveEmployee(id) {
        if (!confirm('Are you sure you want to disapprove this application?')) return;
        fetch(`/employees/${id}/disapprove`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => location.reload());
    }

    function generateLetter(type) {
        fetch(`/employees/{{ $employee->id }}/letters`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ type })
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                alert(data.message || 'Failed to generate the letter.');
                return null;
            }

            return data;
        })
        .then(data => {
            if (!data?.success) {
                return;
            }

            if (data.download_url) {
                window.open(data.download_url, '_blank');
            }

            location.reload();
        });
    }

    // Close on click outside
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('editEmployeeModal')) {
            closeEditModal();
        }
        if (event.target == document.getElementById('shiftTimingModal')) {
            closeShiftTimingModal();
        }
        if (event.target == document.getElementById('inactiveEmployeeModal')) {
            closeInactiveModal();
        }
    });
    toggleEditInactiveReasonField();
    @if($errors->has('shift_start_time') || $errors->has('shift_end_time'))
        openShiftTimingModal();
    @endif
</script>

<style>
    .tab-btn {
        background: none;
        border: none;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
    }
    .tab-btn.active {
        color: #111827;
        border-bottom-color: #000;
    }
    .tab-btn:hover {
        color: #111827;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { width: 18px; height: 18px; }

    .info-grid {
        display: grid;
        gap: 24px;
    }
    .info-grid.two-col {
        grid-template-columns: 1fr 1fr;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .info-item label {
        font-size: 13px;
        color: #9ca3af;
    }
    .info-item p, .value-with-icon {
        font-size: 15px;
        color: #111827;
        font-weight: 500;
    }
    .value-with-icon {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .value-with-icon i { width: 16px; height: 16px; color: #9ca3af; }

    .doc-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    .doc-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
    }
    .stacked-list {
        display: flex;
        flex-direction: column;
    }
    .note-panel {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px 14px;
        color: #4b5563;
        font-size: 14px;
    }
    .status-badge.resigned,
    .status-badge.terminated {
        background: #fef2f2;
        color: #b91c1c;
    }
    .status-badge.holiday,
    .status-badge.weekend {
        background: #e0f2fe;
        color: #0f766e;
    }
    .doc-label {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 4px 0;
    }
    .doc-status {
        font-size: 14px;
        color: #9ca3af;
        margin: 0;
    }
    .doc-link {
        font-size: 13px;
        color: #FF4A00;
        text-decoration: none;
        margin-top: 8px;
        display: inline-block;
    }
    .doc-link:hover { text-decoration: underline; }

    .timeline-list,
    .activity-log-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .timeline-item {
        display: grid;
        grid-template-columns: 18px 1fr;
        gap: 16px;
        align-items: stretch;
    }

    .timeline-marker {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #FF4A00;
        margin-top: 18px;
        box-shadow: 0 0 0 4px #fff7ed;
    }

    .timeline-card,
    .activity-log-card,
    .empty-state-panel {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px 18px;
        background: #fff;
    }

    .timeline-header,
    .activity-log-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
    }

    .timeline-header h4,
    .activity-log-header h4 {
        margin: 0 0 4px 0;
        font-size: 15px;
        color: #111827;
    }

    .timeline-header p,
    .activity-log-header p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }

    .timeline-date {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
        color: #6b7280;
        font-size: 12px;
        white-space: nowrap;
    }

    .timeline-meta,
    .activity-log-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 12px;
        color: #4b5563;
        font-size: 13px;
    }

    .timeline-meta span,
    .activity-log-meta span {
        background: #f9fafb;
        border: 1px solid #f3f4f6;
        border-radius: 999px;
        padding: 6px 10px;
    }

    .log-type {
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .log-type.success {
        background: #ecfdf5;
        color: #047857;
    }

    .log-type.info {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .log-type.warning {
        background: #fff7ed;
        color: #c2410c;
    }

    .log-type.error {
        background: #fef2f2;
        color: #b91c1c;
    }

    .empty-state-panel {
        color: #6b7280;
        font-style: italic;
    }

    @media (max-width: 900px) {
        .timeline-header,
        .activity-log-header,
        .info-grid.two-col,
        .doc-grid {
            grid-template-columns: 1fr;
            display: grid;
        }

        .timeline-date {
            align-items: flex-start;
            white-space: normal;
        }
    }
</style>
@endsection
