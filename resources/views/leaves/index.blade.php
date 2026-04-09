@extends('layouts.app')

@section('title', 'Leave Management')

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>Leave Management</h1>
        <p>{{ Auth::user()->role === 'Employee' ? 'Apply for leave and track your requests.' : 'Manage leave requests, approvals, and leave types.' }}</p>
    </div>
    <div class="header-right">
        @if(Auth::user()->canAccessModule('leave_management', 'create'))
            <button class="btn btn-outline" onclick="openModal('leaveTypeModal')" @if(!Auth::user()->canAccessModule('leave_management', 'edit')) style="display:none;" @endif>
                <i data-lucide="settings-2"></i> Manage Leave Types
            </button>
            <button class="btn btn-primary" onclick="openModal('leaveRequestModal')">
                <i data-lucide="calendar-plus"></i> New Leave Request
            </button>
        @endif
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-content"><span class="stat-label">All Requests</span><span class="stat-value">{{ $counts['all'] }}</span></div><div class="stat-icon-wrapper orange"><i data-lucide="folder-open"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Pending</span><span class="stat-value">{{ $counts['pending'] }}</span></div><div class="stat-icon-wrapper yellow"><i data-lucide="clock-3"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Approved</span><span class="stat-value">{{ $counts['approved'] }}</span></div><div class="stat-icon-wrapper green"><i data-lucide="badge-check"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Rejected / Cancelled</span><span class="stat-value">{{ $counts['rejected'] + $counts['cancelled'] }}</span></div><div class="stat-icon-wrapper red"><i data-lucide="ban"></i></div></div>
</div>

<div class="tabs-container">
    @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $key => $label)
        <a href="{{ route('leaves.index', ['status' => $key]) }}" class="tab-item {{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Leave Type</th>
                <th>Dates</th>
                <th>Days</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leaveRequests as $leaveRequest)
                <tr>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <strong>{{ $leaveRequest->employee->full_name }}</strong>
                            <span style="font-size: 12px; color: #6b7280;">{{ $leaveRequest->employee->employee_id ?: 'Not assigned' }}</span>
                        </div>
                    </td>
                    <td>{{ $leaveRequest->leaveType->name }}</td>
                    <td>{{ $leaveRequest->start_date->format('d M Y') }} to {{ $leaveRequest->end_date->format('d M Y') }}</td>
                    <td>{{ $leaveRequest->days_count }}</td>
                    <td><span class="status-badge {{ $leaveRequest->status }}">{{ ucfirst($leaveRequest->status) }}</span></td>
                    <td style="max-width: 260px;">{{ \Illuminate\Support\Str::limit($leaveRequest->reason, 80) }}</td>
                    <td>
                        <div class="action-buttons">
                            @if(Auth::user()->canAccessModule('leave_management', 'edit') && $leaveRequest->status === 'pending')
                                <button class="btn-action outline-green" onclick="approveLeave({{ $leaveRequest->id }})"><i data-lucide="check"></i> Approve</button>
                                <button class="btn-action outline-red" onclick="rejectLeave({{ $leaveRequest->id }})"><i data-lucide="x"></i> Reject</button>
                            @elseif(Auth::user()->role === 'Employee' && $leaveRequest->status === 'pending')
                                <button class="btn-action outline-red" onclick="cancelLeave({{ $leaveRequest->id }})"><i data-lucide="ban"></i> Cancel</button>
                            @else
                                <span style="font-size: 12px; color: #9ca3af;">{{ $leaveRequest->reviewer_notes ? 'Reviewed' : 'No actions' }}</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @if($leaveRequest->reviewer_notes)
                    <tr>
                        <td colspan="7" style="background: #fafafa; font-size: 13px; color: #6b7280;">
                            <strong>Notes:</strong> {{ $leaveRequest->reviewer_notes }}
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="7" class="text-center">No leave requests found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination-wrapper">{{ $leaveRequests->links() }}</div>
</div>

@if(Auth::user()->canAccessModule('leave_management', 'edit'))
    <div class="card" style="margin-top: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <div>
                <h3 style="margin: 0; font-size: 18px;">Leave Types</h3>
                <p style="margin: 4px 0 0; color: #6b7280; font-size: 13px;">Control available leave categories and limits.</p>
            </div>
            <button class="btn btn-outline" onclick="openModal('leaveTypeModal')"><i data-lucide="plus"></i> Add Leave Type</button>
        </div>
        <div class="leave-type-grid">
            @foreach($leaveTypes as $leaveType)
                <div class="leave-type-card">
                    <div>
                        <h4>{{ $leaveType->name }}</h4>
                        <p>{{ $leaveType->description ?: 'No description provided.' }}</p>
                        <span class="hint">Max days: {{ $leaveType->max_days ?: 'No limit' }}</span>
                    </div>
                    <div class="action-buttons">
                        <button class="btn-action outline" onclick='editLeaveType(@json($leaveType))'><i data-lucide="edit-2"></i> Edit</button>
                        <button class="btn-action outline-red" onclick="deleteLeaveType({{ $leaveType->id }})"><i data-lucide="trash-2"></i> Delete</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div id="leaveRequestModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 560px;">
        <div class="modal-header"><div><h2>New Leave Request</h2><p class="modal-desc" style="margin-bottom: 0;">Submit a leave request for review.</p></div><button class="close-btn" onclick="closeModal('leaveRequestModal')"><i data-lucide="x"></i></button></div>
        <div class="modal-body" style="padding: 24px;">
            <form id="leaveRequestForm" method="POST" action="{{ route('leaves.store') }}" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf
                @if(Auth::user()->role !== 'Employee')
                    <div class="form-group">
                        <label>Employee</label>
                        <select name="employee_id" required>
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_id ?: 'Pending ID' }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="form-group">
                    <label>Leave Type</label>
                    <select name="leave_type_id" required>
                        <option value="">Select Leave Type</option>
                        @foreach($leaveTypes->where('is_active', true) as $leaveType)
                            <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group"><label>Start Date</label><input type="date" name="start_date" required></div>
                    <div class="form-group"><label>End Date</label><input type="date" name="end_date" required></div>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" rows="4" required style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('leaveRequestModal')">Cancel</button><button class="btn btn-primary" form="leaveRequestForm" type="submit">Submit Request</button></div>
    </div>
</div>

@if(Auth::user()->canAccessModule('leave_management', 'edit'))
    <div id="leaveTypeModal" class="modal-overlay" style="display: none;">
        <div class="modal-container" style="max-width: 520px;">
            <div class="modal-header"><div><h2 id="leaveTypeModalTitle">Add Leave Type</h2><p class="modal-desc" style="margin-bottom: 0;">Create or update leave categories.</p></div><button class="close-btn" onclick="closeModal('leaveTypeModal')"><i data-lucide="x"></i></button></div>
            <div class="modal-body" style="padding: 24px;">
                <form id="leaveTypeForm" style="display: flex; flex-direction: column; gap: 16px;">
                    @csrf
                    <input type="hidden" id="leave_type_id">
                    <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Description</label><input type="text" name="description"></div>
                    <div class="form-group"><label>Max Days</label><input type="number" name="max_days" min="1"></div>
                    <label class="checkbox-row"><input type="checkbox" name="is_active" value="1" checked> Active leave type</label>
                </form>
            </div>
            <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('leaveTypeModal')">Cancel</button><button class="btn btn-primary" form="leaveTypeForm" type="submit">Save Leave Type</button></div>
        </div>
    </div>
@endif

<style>
    .leave-type-grid { display: grid; gap: 16px; }
    .leave-type-card { border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; }
    .leave-type-card h4 { margin: 0 0 6px; font-size: 15px; }
    .leave-type-card p { margin: 0 0 8px; font-size: 13px; color: #6b7280; }
    .checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; }
</style>

<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    async function submitJson(url, method, data) {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || 'Request failed.');
        return payload;
    }

    async function approveLeave(id) {
        const reviewer_notes = prompt('Optional approval notes:') || '';
        try {
            await submitJson(`/leaves/${id}/approve`, 'POST', { reviewer_notes });
            location.reload();
        } catch (error) {
            alert(error.message);
        }
    }

    async function rejectLeave(id) {
        const reviewer_notes = prompt('Reason for rejection:');
        if (!reviewer_notes) return;
        try {
            await submitJson(`/leaves/${id}/reject`, 'POST', { reviewer_notes });
            location.reload();
        } catch (error) {
            alert(error.message);
        }
    }

    async function cancelLeave(id) {
        if (!confirm('Cancel this leave request?')) return;
        try {
            await submitJson(`/leaves/${id}/cancel`, 'POST', {});
            location.reload();
        } catch (error) {
            alert(error.message);
        }
    }

    function editLeaveType(leaveType) {
        document.getElementById('leaveTypeModalTitle').textContent = 'Edit Leave Type';
        document.getElementById('leave_type_id').value = leaveType.id;
        const form = document.getElementById('leaveTypeForm');
        form.name.value = leaveType.name || '';
        form.description.value = leaveType.description || '';
        form.max_days.value = leaveType.max_days || '';
        form.is_active.checked = !!leaveType.is_active;
        openModal('leaveTypeModal');
    }

    async function deleteLeaveType(id) {
        if (!confirm('Delete this leave type?')) return;
        try {
            await submitJson(`/leave-types/${id}`, 'DELETE', {});
            location.reload();
        } catch (error) {
            alert(error.message);
        }
    }

    @if(Auth::user()->canAccessModule('leave_management', 'edit'))
    document.getElementById('leaveTypeForm').onsubmit = async function(e) {
        e.preventDefault();
        const id = document.getElementById('leave_type_id').value;
        const data = {
            name: this.name.value,
            description: this.description.value,
            max_days: this.max_days.value || null,
            is_active: this.is_active.checked ? 1 : 0
        };

        try {
            await submitJson(id ? `/leave-types/${id}` : '/leave-types', id ? 'PUT' : 'POST', data);
            location.reload();
        } catch (error) {
            alert(error.message);
        }
    };
    @endif

    window.addEventListener('click', function (event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    });
</script>
@endsection
