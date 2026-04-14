@extends('layouts.app')

@section('title', 'My Team')

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>My Team</h1>
        <p>Review assigned employees and manage monthly performance feedback.</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Assigned Employees</span><span class="stat-value">{{ $stats['assigned'] }}</span></div><div class="stat-icon-wrapper orange"><i data-lucide="users-round"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Active Team Members</span><span class="stat-value">{{ $stats['active'] }}</span></div><div class="stat-icon-wrapper green"><i data-lucide="user-check"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Inactive / Left</span><span class="stat-value">{{ $stats['inactive'] }}</span></div><div class="stat-icon-wrapper yellow"><i data-lucide="user-minus"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Reviewed This Month</span><span class="stat-value">{{ $stats['reviewed_this_month'] }}</span></div><div class="stat-icon-wrapper blue"><i data-lucide="clipboard-check"></i></div></div>
</div>

<div class="search-container">
    <form action="{{ route('team.index') }}" method="GET" class="search-form">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by employee name, ID, designation, or email..." class="search-input">
    </form>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Status</th>
                <th>Team Manager</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
                <tr>
                    <td>
                        <div class="employee-cell">
                            <div class="avatar-sm orange">{{ strtoupper(substr($employee->full_name, 0, 2)) }}</div>
                            <div class="employee-info">
                                <span class="emp-name">{{ $employee->full_name }}</span>
                                <span class="emp-email">{{ $employee->employee_id ?: $employee->email }}</span>
                            </div>
                        </div>
                    </td>
                    <td>{{ $employee->department?->name ?? 'Unassigned' }}</td>
                    <td><span class="status-badge {{ $employee->status }}">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</span></td>
                    <td>{{ $employee->teamManager?->name ?? 'Not assigned' }}</td>
                    <td>
                        <a href="{{ route('team.show', $employee) }}" class="btn-action outline">
                            <i data-lucide="star"></i> Review
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">No assigned employees found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination-wrapper">{{ $employees->links() }}</div>
</div>
@endsection
