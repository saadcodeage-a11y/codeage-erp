@extends('layouts.app')

@section('content')
<div class="dashboard-header">
    <h1>Dashboard</h1>
    <p>Welcome back, {{ Auth::user()->name }}</p>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <!-- Total Employees -->
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Total Employees</span>
            <div class="stat-value">{{ $totalEmployees }}</div>
            <div class="stat-trend positive">
                <i data-lucide="trending-up"></i>
                <span>+12% from last month</span>
            </div>
        </div>
        <div class="stat-icon-wrapper orange">
            <i data-lucide="users"></i>
        </div>
    </div>

    <!-- Active Employees -->
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Active Employees</span>
            <div class="stat-value">{{ $activeEmployees }}</div>
        </div>
        <div class="stat-icon-wrapper green">
            <i data-lucide="user-check"></i>
        </div>
    </div>

    <!-- Pending Approval -->
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Pending Approval</span>
            <div class="stat-value">{{ $pendingApproval }}</div>
        </div>
        <div class="stat-icon-wrapper yellow">
            <i data-lucide="clock"></i>
        </div>
    </div>

    <!-- Invited -->
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Invited</span>
            <div class="stat-value">{{ $invited }}</div>
        </div>
        <div class="stat-icon-wrapper blue">
            <i data-lucide="user-plus"></i>
        </div>
    </div>
</div>

<!-- Second Row Stats -->
<div class="stats-grid second-row">
    <!-- Total Users -->
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Total Users</span>
            <div class="stat-value">{{ $totalUsers }}</div>
        </div>
        <div class="stat-icon-wrapper purple">
            <i data-lucide="shield"></i>
        </div>
    </div>

    <!-- Active Users -->
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Active Users</span>
            <div class="stat-value">{{ $activeUsers }}</div>
        </div>
        <div class="stat-icon-wrapper blue-dark">
            <i data-lucide="activity"></i>
        </div>
    </div>

    <!-- System Status -->
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">System Status</span>
            <div class="stat-value text-md">Operational</div>
        </div>
        <div class="stat-icon-wrapper green-solid">
            <i data-lucide="check-circle-2"></i>
        </div>
    </div>
</div>

<!-- Activity & Departments -->
<div class="content-grid">
    <!-- Recent Activity -->
    <div class="card activity-card">
        <div class="card-header">
            <h3>Recent Activity</h3>
            <button class="icon-btn"><i data-lucide="activity"></i></button>
        </div>
        <div class="activity-list">
            @forelse($activities as $activity)
                <div class="activity-item">
                    <div class="activity-icon {{ $activity->type }}">
                        @if($activity->type == 'info') <i data-lucide="info"></i> @endif
                        @if($activity->type == 'success') <i data-lucide="user-plus"></i> @endif
                        @if($activity->type == 'warning') <i data-lucide="mail"></i> @endif
                    </div>
                    <div class="activity-details">
                        <p class="activity-desc">{{ $activity->description }}</p>
                        <span class="activity-time">{{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="activity-item">
                    <p>No recent activity.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Department Overview -->
    <div class="card department-card">
        <div class="card-header">
            <h3>Department Overview</h3>
            <button class="icon-btn"><i data-lucide="file-text"></i></button>
        </div>
        <div class="department-list">
            @foreach($departments as $dept)
                <div class="dept-item">
                    <div class="dept-info">
                        <span class="dept-name">{{ $dept->name }}</span>
                        <span class="dept-count">{{ $dept->employees_count }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ ($dept->employees_count / $maxDeptEmployees) * 100 }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions-section">
    <h3>Quick Actions</h3>
    <div class="quick-actions-grid">
        <div class="action-card">
            <div class="action-icon-wrapper orange-light">
                <i data-lucide="user-plus"></i>
            </div>
            <div class="action-details">
                <h4>Add Employee</h4>
                <p>Invite new team member</p>
            </div>
        </div>

        <div class="action-card">
            <div class="action-icon-wrapper blue-light">
                <i data-lucide="file-text"></i>
            </div>
            <div class="action-details">
                <h4>View Reports</h4>
                <p>Generate analytics</p>
            </div>
        </div>

        <div class="action-card">
            <div class="action-icon-wrapper purple-light">
                <i data-lucide="shield"></i>
            </div>
            <div class="action-details">
                <h4>Manage Users</h4>
                <p>User permissions</p>
            </div>
        </div>
    </div>
</div>
@endsection
