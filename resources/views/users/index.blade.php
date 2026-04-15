@extends('layouts.app')

@section('title', 'User Management')

@section('content')
@php
    $moduleLabels = \App\Models\Role::availableModules();
@endphp
<div class="page-header">
    <div class="header-left">
        <h1>User Management</h1>
        <p>Manage users, roles, and module permissions</p>
    </div>
    <div class="header-right">
        <button id="addUserAction" class="btn btn-primary" onclick="openAddUserModal()">
            <i data-lucide="plus"></i> Add User
        </button>
        <button id="addRoleAction" class="btn btn-primary" style="display: none;" onclick="openModal('addRoleModal')">
            <i data-lucide="shield-plus"></i> Add Role
        </button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Total Users</span><span class="stat-value">{{ $counts['total_users'] }}</span></div><div class="stat-icon-wrapper orange"><i data-lucide="users"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Active Users</span><span class="stat-value">{{ $counts['active_users'] }}</span></div><div class="stat-icon-wrapper green"><i data-lucide="user-check"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">2FA Enabled</span><span class="stat-value">{{ $counts['two_factor_enabled'] }}</span></div><div class="stat-icon-wrapper blue"><i data-lucide="shield-check"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Roles</span><span class="stat-value">{{ $counts['total_roles'] }}</span></div><div class="stat-icon-wrapper purple"><i data-lucide="key-round"></i></div></div>
</div>

<div class="tabs-container">
    <button type="button" class="tab-item active" onclick="switchTab('users', this)">Users</button>
    <button type="button" class="tab-item" onclick="switchTab('roles', this)">Roles</button>
</div>

<div id="usersTab">
    <div class="search-container">
        <form action="{{ route('users.index') }}" method="GET" class="search-form">
            <i data-lucide="search" class="search-icon"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or user ID..." class="search-input">
        </form>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>2FA</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->user_id }}</td>
                        <td>
                            <div class="employee-cell">
                                <div class="avatar-sm {{ $user->is_active ? 'orange' : 'inactive' }}">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                                <div class="employee-info">
                                    <span class="emp-name">{{ $user->name }}</span>
                                    <span class="emp-email">{{ $user->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td><span class="role-badge">{{ $user->role }}</span></td>
                        <td>{{ $user->two_factor_enabled ? 'Enabled' : 'Disabled' }}</td>
                        <td><span class="status-badge {{ $user->is_active ? 'active' : 'inactive' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action outline" onclick='openEditUser(@json($user))'><i data-lucide="edit-2"></i> Edit</button>
                                <button class="btn-action outline" onclick='openResetPassword(@json($user))'><i data-lucide="key"></i> Reset</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrapper">{{ $users->links() }}</div>
    </div>
</div>

<div id="rolesTab" style="display: none;">
    <div class="roles-overview">
        <div>
            <h2>Role Permissions</h2>
            <p>Each role controls module-level read, create, and edit access across the system.</p>
        </div>
        <div class="roles-overview-metrics">
            <div class="overview-pill">
                <strong>{{ $roles->count() }}</strong>
                <span>Roles</span>
            </div>
            <div class="overview-pill">
                <strong>{{ count($moduleLabels) }}</strong>
                <span>Modules</span>
            </div>
        </div>
    </div>

    <div class="role-grid">
        @forelse($roles as $role)
            @php
                $permissions = $role->permissionsByModule();
                $allowedCount = collect($permissions)->reduce(function ($total, $permission) {
                    return $total
                        + ($permission['read'] ? 1 : 0)
                        + ($permission['create'] ? 1 : 0)
                        + ($permission['edit'] ? 1 : 0);
                }, 0);
                $moduleCount = count($permissions);
                $grantedModuleCount = collect($permissions)->filter(fn ($permission) => $permission['read'] || $permission['create'] || $permission['edit'])->count();
                $coveragePercent = $moduleCount > 0 ? (int) round(($grantedModuleCount / $moduleCount) * 100) : 0;
                $rolePayload = ['id' => $role->id, 'name' => $role->name, 'permissions' => $permissions];
            @endphp
            <div class="role-card">
                <div class="role-card-header">
                    <div class="role-card-identity">
                        <div class="role-avatar">{{ strtoupper(substr($role->name, 0, 2)) }}</div>
                        <div>
                            <h3>{{ $role->name }}</h3>
                            <p>{{ $role->users_count }} assigned {{ \Illuminate\Support\Str::plural('user', $role->users_count) }}</p>
                            <div class="role-card-metrics">
                                <span class="metric-chip">{{ $allowedCount }} permissions allowed</span>
                                <span class="metric-chip muted">{{ $moduleCount }} modules covered</span>
                                <span class="metric-chip neutral">{{ $grantedModuleCount }} modules active</span>
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn-action outline" onclick='openEditRole(@json($rolePayload))'><i data-lucide="edit-2"></i> Edit</button>
                        <button class="btn-action outline-red" onclick="deleteRole({{ $role->id }}, @json($role->name))"><i data-lucide="trash-2"></i> Delete</button>
                    </div>
                </div>
                <div class="role-card-summary">
                    <div class="role-summary-stat">
                        <span>Coverage</span>
                        <strong>{{ $coveragePercent }}%</strong>
                    </div>
                    <div class="role-summary-progress">
                        <div class="role-summary-progress-track">
                            <div class="role-summary-progress-bar" style="width: {{ $coveragePercent }}%;"></div>
                        </div>
                        <p>{{ $grantedModuleCount }} of {{ $moduleCount }} modules currently available to this role.</p>
                    </div>
                </div>
                <div class="role-permission-list">
                    @foreach($permissions as $permission)
                        <div class="module-permission-card">
                            <div class="module-permission-header">
                                <div>
                                    <strong>{{ $permission['label'] }}</strong>
                                    <small>{{ collect([$permission['read'], $permission['create'], $permission['edit']])->filter()->count() }}/3 permissions enabled</small>
                                </div>
                                <span class="module-status {{ ($permission['read'] || $permission['create'] || $permission['edit']) ? 'active' : 'inactive' }}">
                                    {{ ($permission['read'] || $permission['create'] || $permission['edit']) ? 'Access Granted' : 'No Access' }}
                                </span>
                            </div>
                            <div class="permission-matrix">
                                <div class="permission-matrix-item {{ $permission['read'] ? 'allowed' : 'denied' }}">
                                    <span>Read</span>
                                    <strong>{{ $permission['read'] ? 'Allowed' : 'Blocked' }}</strong>
                                </div>
                                <div class="permission-matrix-item {{ $permission['create'] ? 'allowed' : 'denied' }}">
                                    <span>Create</span>
                                    <strong>{{ $permission['create'] ? 'Allowed' : 'Blocked' }}</strong>
                                </div>
                                <div class="permission-matrix-item {{ $permission['edit'] ? 'allowed' : 'denied' }}">
                                    <span>Edit</span>
                                    <strong>{{ $permission['edit'] ? 'Allowed' : 'Blocked' }}</strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="empty-state-panel">No roles found. Create a role to start assigning module permissions.</div>
        @endforelse
    </div>
</div>

<div id="addUserModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 520px;">
        <div class="modal-header"><div><h2>Add User</h2><p class="modal-desc" style="margin-bottom:0;">Create a new user account.</p></div><button class="close-btn" onclick="closeModal('addUserModal')"><i data-lucide="x"></i></button></div>
        <div class="modal-body" style="padding:24px;">
            <form id="addUserForm" class="modal-form">
                @csrf
                <div class="form-group">
                    <label>Assign to Employee</label>
                    <div class="employee-picker" data-picker="add">
                        <input type="hidden" name="employee_id" id="add_user_employee_id">
                        <div class="employee-picker-input">
                            <i data-lucide="search"></i>
                            <input
                                type="text"
                                id="add_user_employee_search"
                                placeholder="Search employee by name, ID, or email"
                                autocomplete="off"
                                onfocus="openEmployeePicker('add')"
                                oninput="filterEmployeePicker('add')"
                            >
                            <button type="button" class="employee-picker-clear" id="add_user_employee_clear" onclick="clearEmployeePicker('add')" aria-label="Clear employee selection" style="display:none;">
                                <i data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="employee-picker-dropdown" id="add_user_employee_dropdown" style="display:none;">
                            <button type="button" class="employee-picker-option employee-picker-option--empty" onclick="selectEmployeePicker('add', null)">
                                <strong>No employee assignment</strong>
                                <span>Leave this user account unlinked.</span>
                            </button>
                            @foreach($employees as $employee)
                                <button
                                    type="button"
                                    class="employee-picker-option"
                                    data-id="{{ $employee['id'] }}"
                                    data-label="{{ $employee['full_name'] }} ({{ $employee['employee_id'] ?: $employee['email'] }})"
                                    data-search="{{ strtolower($employee['full_name'] . ' ' . ($employee['employee_id'] ?: '') . ' ' . ($employee['email'] ?: '')) }}"
                                    data-assigned-user-id="{{ $employee['assigned_user_id'] ?? '' }}"
                                    data-assigned-user-name="{{ $employee['assigned_user_name'] ?? '' }}"
                                    onclick="selectEmployeePicker('add', this)"
                                >
                                    <strong>{{ $employee['full_name'] }}</strong>
                                    <span>{{ $employee['employee_id'] ?: $employee['email'] }}{{ !empty($employee['assigned_user_id']) ? ' · Assigned to ' . $employee['assigned_user_name'] : '' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Role</label><select name="role" required>@foreach($roleOptions as $roleName)<option value="{{ $roleName }}">{{ $roleName }}</option>@endforeach</select></div>
                <div class="form-group"><label>Initial Password</label><input type="password" name="password" required></div>
                <label class="two-factor-card">
                    <span class="two-factor-card__icon" aria-hidden="true"><i data-lucide="shield-check"></i></span>
                    <span class="two-factor-card__copy">
                        <strong>Enable Two-Factor Authentication</strong>
                        <small>Require an additional verification step after password login.</small>
                    </span>
                    <span class="switch-toggle compact">
                        <input type="checkbox" name="two_factor_enabled" value="1">
                        <span class="slider"></span>
                    </span>
                </label>
            </form>
        </div>
        <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button><button class="btn btn-primary" form="addUserForm" type="submit">Create User</button></div>
    </div>
</div>

<div id="editUserModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 520px;">
        <div class="modal-header"><div><h2>Edit User</h2><p class="modal-desc" style="margin-bottom:0;">Update user information and role.</p></div><button class="close-btn" onclick="closeModal('editUserModal')"><i data-lucide="x"></i></button></div>
        <div class="modal-body" style="padding:24px;">
            <form id="editUserForm" class="modal-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_user_id">
                <div class="form-group">
                    <label>Assign to Employee</label>
                    <div class="employee-picker" data-picker="edit">
                        <input type="hidden" name="employee_id" id="edit_user_employee_id">
                        <div class="employee-picker-input">
                            <i data-lucide="search"></i>
                            <input
                                type="text"
                                id="edit_user_employee_search"
                                placeholder="Search employee by name, ID, or email"
                                autocomplete="off"
                                onfocus="openEmployeePicker('edit')"
                                oninput="filterEmployeePicker('edit')"
                            >
                            <button type="button" class="employee-picker-clear" id="edit_user_employee_clear" onclick="clearEmployeePicker('edit')" aria-label="Clear employee selection" style="display:none;">
                                <i data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="employee-picker-dropdown" id="edit_user_employee_dropdown" style="display:none;">
                            <button type="button" class="employee-picker-option employee-picker-option--empty" onclick="selectEmployeePicker('edit', null)">
                                <strong>No employee assignment</strong>
                                <span>Unlink this user account from an employee.</span>
                            </button>
                            @foreach($employees as $employee)
                                <button
                                    type="button"
                                    class="employee-picker-option"
                                    data-id="{{ $employee['id'] }}"
                                    data-label="{{ $employee['full_name'] }} ({{ $employee['employee_id'] ?: $employee['email'] }})"
                                    data-search="{{ strtolower($employee['full_name'] . ' ' . ($employee['employee_id'] ?: '') . ' ' . ($employee['email'] ?: '')) }}"
                                    data-assigned-user-id="{{ $employee['assigned_user_id'] ?? '' }}"
                                    data-assigned-user-name="{{ $employee['assigned_user_name'] ?? '' }}"
                                    onclick="selectEmployeePicker('edit', this)"
                                >
                                    <strong>{{ $employee['full_name'] }}</strong>
                                    <span>{{ $employee['employee_id'] ?: $employee['email'] }}{{ !empty($employee['assigned_user_id']) ? ' · Assigned to ' . $employee['assigned_user_name'] : '' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="form-group"><label>Name</label><input type="text" name="name" id="edit_user_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_user_email" required></div>
                <div class="form-group"><label>Role</label><select name="role" id="edit_user_role" required>@foreach($roleOptions as $roleName)<option value="{{ $roleName }}">{{ $roleName }}</option>@endforeach</select></div>
                <label class="two-factor-card">
                    <span class="two-factor-card__icon" aria-hidden="true"><i data-lucide="shield-check"></i></span>
                    <span class="two-factor-card__copy">
                        <strong>Enable Two-Factor Authentication</strong>
                        <small>Require an additional verification step after password login.</small>
                    </span>
                    <span class="switch-toggle compact">
                        <input type="checkbox" name="two_factor_enabled" id="edit_user_2fa" value="1">
                        <span class="slider"></span>
                    </span>
                </label>
            </form>
        </div>
        <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button><button class="btn btn-primary" form="editUserForm" type="submit">Save Changes</button></div>
    </div>
</div>

<div id="resetPasswordModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 420px;">
        <div class="modal-header"><div><h2>Reset Password</h2><p class="modal-desc" style="margin-bottom:0;">Set a new password for <span id="reset_user_display_name" style="font-weight:600;color:#111827;"></span>.</p></div><button class="close-btn" onclick="closeModal('resetPasswordModal')"><i data-lucide="x"></i></button></div>
        <div class="modal-body" style="padding:24px;">
            <form id="resetPasswordForm" class="modal-form">
                @csrf
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="form-group"><label>New Password</label><input type="password" name="password" required></div>
            </form>
        </div>
        <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('resetPasswordModal')">Cancel</button><button class="btn btn-primary" form="resetPasswordForm" type="submit">Reset Password</button></div>
    </div>
</div>

<div id="addRoleModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 860px;">
        <div class="modal-header"><div><h2>Create Role</h2><p class="modal-desc" style="margin-bottom:0;">Set separate read, create, and edit access for each module.</p></div><button class="close-btn" onclick="closeModal('addRoleModal')"><i data-lucide="x"></i></button></div>
        <div class="modal-body" style="padding:24px;">
            <form id="addRoleForm" class="modal-form">
                @csrf
                <div class="form-group"><label>Role Name</label><input type="text" name="name" required></div>
                <div class="permission-editor">
                    @foreach($modules as $moduleKey => $moduleLabel)
                        <div class="permission-row" data-module="{{ $moduleKey }}">
                            <strong>{{ $moduleLabel }}</strong>
                            <label><input type="checkbox" data-permission="read"> Read</label>
                            <label><input type="checkbox" data-permission="create"> Create</label>
                            <label><input type="checkbox" data-permission="edit"> Edit</label>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
        <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('addRoleModal')">Cancel</button><button class="btn btn-primary" form="addRoleForm" type="submit">Create Role</button></div>
    </div>
</div>

<div id="editRoleModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 860px;">
        <div class="modal-header"><div><h2>Edit Role</h2><p class="modal-desc" style="margin-bottom:0;">Update module permissions for this role.</p></div><button class="close-btn" onclick="closeModal('editRoleModal')"><i data-lucide="x"></i></button></div>
        <div class="modal-body" style="padding:24px;">
            <form id="editRoleForm" class="modal-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_role_id">
                <div class="form-group"><label>Role Name</label><input type="text" name="name" id="edit_role_name" required></div>
                <div class="permission-editor">
                    @foreach($modules as $moduleKey => $moduleLabel)
                        <div class="permission-row" data-module="{{ $moduleKey }}">
                            <strong>{{ $moduleLabel }}</strong>
                            <label><input type="checkbox" data-permission="read"> Read</label>
                            <label><input type="checkbox" data-permission="create"> Create</label>
                            <label><input type="checkbox" data-permission="edit"> Edit</label>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
        <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('editRoleModal')">Cancel</button><button class="btn btn-primary" form="editRoleForm" type="submit">Save Role</button></div>
    </div>
</div>

<style>
    .avatar-sm.inactive { background: #9ca3af; }
    .role-badge { background: #fff1e7; color: #b45309; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .roles-overview {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #f8fafc 100%);
        border: 1px solid #fed7aa;
        border-radius: 18px;
    }
    .roles-overview h2 { margin: 0 0 4px; font-size: 22px; color: #111827; }
    .roles-overview p { margin: 0; color: #6b7280; font-size: 14px; }
    .roles-overview-metrics { display: flex; gap: 12px; }
    .overview-pill {
        min-width: 96px;
        padding: 12px 14px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        text-align: center;
    }
    .overview-pill strong { display: block; font-size: 20px; color: #111827; }
    .overview-pill span { font-size: 12px; color: #6b7280; }
    .role-grid { display: grid; gap: 20px; }
    .role-card { background: white; border: 1px solid #e5e7eb; border-radius: 18px; overflow: hidden; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05); }
    .role-card-header { display: flex; justify-content: space-between; gap: 16px; padding: 20px; border-bottom: 1px solid #f3f4f6; }
    .role-card-identity { display: flex; align-items: flex-start; gap: 14px; }
    .role-avatar {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        color: #c2410c;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .role-card-header h3 { margin: 0 0 4px; }
    .role-card-header p { margin: 0; color: #6b7280; font-size: 13px; }
    .role-card-metrics { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
    .metric-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #166534;
        font-size: 12px;
        font-weight: 600;
    }
    .metric-chip.muted {
        background: #f3f4f6;
        color: #6b7280;
    }
    .metric-chip.neutral {
        background: #eff6ff;
        color: #1d4ed8;
    }
    .role-card-summary {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: 16px;
        align-items: center;
        padding: 18px 20px;
        background: #fcfcfd;
        border-bottom: 1px solid #f3f4f6;
    }
    .role-summary-stat {
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: white;
        text-align: center;
    }
    .role-summary-stat span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        margin-bottom: 6px;
    }
    .role-summary-stat strong {
        display: block;
        color: #111827;
        font-size: 24px;
        line-height: 1;
    }
    .role-summary-progress-track {
        height: 10px;
        border-radius: 999px;
        background: #e5e7eb;
        overflow: hidden;
        margin-bottom: 10px;
    }
    .role-summary-progress-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #ff7a18 0%, #ff4a00 100%);
    }
    .role-summary-progress p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }
    .role-permission-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
        padding: 18px;
        background: #fcfcfd;
    }
    .module-permission-card {
        padding: 16px;
        border: 1px solid #e9eef5;
        border-radius: 16px;
        background: white;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
    }
    .module-permission-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 14px;
    }
    .module-permission-header strong {
        display: block;
        font-size: 15px;
        color: #111827;
        margin-bottom: 4px;
    }
    .module-permission-header small {
        color: #6b7280;
        font-size: 12px;
    }
    .module-status {
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }
    .module-status.active {
        background: #dcfce7;
        color: #166534;
    }
    .module-status.inactive {
        background: #f3f4f6;
        color: #6b7280;
    }
    .permission-matrix {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }
    .permission-matrix-item {
        border: 1px solid transparent;
        border-radius: 12px;
        padding: 10px 8px;
        text-align: center;
    }
    .permission-matrix-item span {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 5px;
    }
    .permission-matrix-item strong {
        display: block;
        font-size: 12px;
        font-weight: 600;
    }
    .permission-matrix-item.allowed {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .permission-matrix-item.denied {
        background: #f9fafb;
        border-color: #e5e7eb;
        color: #6b7280;
    }
    .modal-form { display: flex; flex-direction: column; gap: 16px; }
    .modal-form input, .modal-form select { width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb; }
    .employee-picker {
        position: relative;
    }
    .employee-picker-input {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 46px;
        padding: 0 14px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
        transition: all .2s ease;
    }
    .employee-picker-input:focus-within {
        border-color: #fdba74;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(255, 122, 24, 0.12);
    }
    .employee-picker-input i {
        width: 18px;
        height: 18px;
        color: #6b7280;
        flex-shrink: 0;
    }
    .employee-picker-input input {
        flex: 1;
        min-width: 0;
        border: none;
        background: transparent;
        padding: 0;
        outline: none;
        box-shadow: none;
    }
    .employee-picker-clear {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border: none;
        border-radius: 999px;
        background: #fff;
        color: #6b7280;
        cursor: pointer;
        flex-shrink: 0;
    }
    .employee-picker-clear:hover {
        background: #f3f4f6;
        color: #111827;
    }
    .employee-picker-clear i {
        width: 16px;
        height: 16px;
    }
    .employee-picker-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        z-index: 30;
        max-height: 280px;
        overflow: auto;
        padding: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
    }
    .employee-picker-option {
        width: 100%;
        border: none;
        background: #fff;
        border-radius: 10px;
        padding: 12px 14px;
        text-align: left;
        cursor: pointer;
        transition: all .2s ease;
    }
    .employee-picker-option:hover,
    .employee-picker-option.active {
        background: #fff7ed;
    }
    .employee-picker-option strong {
        display: block;
        font-size: 13px;
        color: #111827;
        margin-bottom: 4px;
    }
    .employee-picker-option span {
        display: block;
        font-size: 12px;
        color: #6b7280;
        line-height: 1.45;
    }
    .employee-picker-option--empty {
        border-bottom: 1px solid #f3f4f6;
        margin-bottom: 6px;
        padding-bottom: 14px;
    }
    .employee-picker-option.is-disabled {
        opacity: .58;
        cursor: not-allowed;
        background: #f9fafb;
    }
    .employee-picker-option.is-disabled:hover {
        background: #f9fafb;
    }
    .employee-picker-empty {
        padding: 14px;
        text-align: center;
        color: #6b7280;
        font-size: 13px;
    }
    .two-factor-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
        cursor: pointer;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .two-factor-card:hover {
        border-color: #fed7aa;
        background: linear-gradient(180deg, #fffaf5 0%, #ffffff 100%);
    }
    .two-factor-card:focus-within {
        border-color: #fb923c;
        box-shadow: 0 0 0 4px rgba(255, 122, 24, 0.12);
    }
    .two-factor-card__icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: #fff7ed;
        color: #ea580c;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .two-factor-card__copy {
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex: 1;
    }
    .two-factor-card__copy strong {
        font-size: 14px;
        color: #111827;
    }
    .two-factor-card__copy small {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
    }
    .switch-toggle {
        position: relative;
        display: inline-block;
        width: 52px;
        height: 30px;
        flex-shrink: 0;
    }
    .switch-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .switch-toggle .slider {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #d1d5db;
        transition: .2s ease;
    }
    .switch-toggle .slider::before {
        content: '';
        position: absolute;
        left: 3px;
        top: 3px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.18);
        transition: .2s ease;
    }
    .switch-toggle input:checked + .slider {
        background: linear-gradient(90deg, #ff7a18 0%, #ff4a00 100%);
    }
    .switch-toggle input:checked + .slider::before {
        transform: translateX(22px);
    }
    .switch-toggle input:focus-visible + .slider {
        box-shadow: 0 0 0 4px rgba(255, 74, 0, 0.15);
    }
    .switch-toggle.compact {
        width: 48px;
        height: 28px;
    }
    .switch-toggle.compact .slider::before {
        width: 22px;
        height: 22px;
    }
    .switch-toggle.compact input:checked + .slider::before {
        transform: translateX(20px);
    }
    .permission-editor { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
    .permission-row { display: grid; grid-template-columns: minmax(180px, 1fr) 120px 120px 120px; gap: 12px; align-items: center; padding: 14px 16px; border-bottom: 1px solid #f3f4f6; }
    .permission-row:last-child { border-bottom: none; }
    .permission-row label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; }
    @media (max-width: 900px) {
        .roles-overview { flex-direction: column; align-items: flex-start; }
        .role-card-header, .permission-row { grid-template-columns: 1fr; display: grid; }
        .role-card-identity,
        .role-card-summary,
        .permission-matrix { grid-template-columns: 1fr; }
        .module-permission-header { flex-direction: column; align-items: flex-start; }
        .two-factor-card { align-items: flex-start; }
        .employee-picker-dropdown {
            position: static;
            margin-top: 8px;
        }
    }
</style>

<script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; if (window.lucide) window.lucide.createIcons(); }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function getEmployeePicker(mode) {
        return {
            root: document.querySelector(`.employee-picker[data-picker="${mode}"]`),
            hidden: document.getElementById(`${mode}_user_employee_id`),
            search: document.getElementById(`${mode}_user_employee_search`),
            clear: document.getElementById(`${mode}_user_employee_clear`),
            dropdown: document.getElementById(`${mode}_user_employee_dropdown`),
        };
    }

    function configureEmployeePicker(mode, currentUserId = null, selectedEmployeeId = '') {
        const picker = getEmployeePicker(mode);
        picker.root.dataset.currentUserId = currentUserId ? String(currentUserId) : '';

        picker.dropdown.querySelectorAll('.employee-picker-option[data-id]').forEach(option => {
            const assignedUserId = option.dataset.assignedUserId || '';
            const isAssignedElsewhere = assignedUserId && String(assignedUserId) !== String(currentUserId || '');
            option.classList.toggle('is-disabled', Boolean(isAssignedElsewhere));
        });

        if (selectedEmployeeId) {
            const selectedOption = picker.dropdown.querySelector(`.employee-picker-option[data-id="${selectedEmployeeId}"]`);
            if (selectedOption) {
                selectEmployeePicker(mode, selectedOption, false);
                return;
            }
        }

        clearEmployeePicker(mode, false);
    }

    function filterEmployeePicker(mode) {
        const picker = getEmployeePicker(mode);
        const query = (picker.search.value || '').trim().toLowerCase();
        const selectedLabel = picker.root.dataset.selectedLabel || '';

        if ((picker.search.value || '') !== selectedLabel) {
            picker.hidden.value = '';
            picker.root.dataset.selectedLabel = '';
            picker.dropdown.querySelectorAll('.employee-picker-option').forEach(item => item.classList.remove('active'));
            picker.clear.style.display = picker.search.value ? 'inline-flex' : 'none';
        }

        let visibleCount = 0;

        picker.dropdown.querySelectorAll('.employee-picker-option[data-id]').forEach(option => {
            const matches = !query || (option.dataset.search || '').includes(query);
            option.style.display = matches ? 'block' : 'none';
            if (matches) {
                visibleCount += 1;
            }
        });

        let emptyState = picker.dropdown.querySelector('.employee-picker-empty');
        if (!visibleCount) {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'employee-picker-empty';
                emptyState.textContent = 'No employees match this search.';
                picker.dropdown.appendChild(emptyState);
            }
        } else if (emptyState) {
            emptyState.remove();
        }

        openEmployeePicker(mode);
    }

    function openEmployeePicker(mode) {
        const picker = getEmployeePicker(mode);
        picker.dropdown.style.display = 'block';
    }

    function closeEmployeePicker(mode) {
        const picker = getEmployeePicker(mode);
        picker.dropdown.style.display = 'none';
    }

    function selectEmployeePicker(mode, option, closeAfterSelect = true) {
        const picker = getEmployeePicker(mode);

        if (!option) {
            clearEmployeePicker(mode, closeAfterSelect);
            return;
        }

        if (option.classList.contains('is-disabled')) {
            return;
        }

        picker.hidden.value = option.dataset.id;
        picker.search.value = option.dataset.label;
        picker.root.dataset.selectedLabel = option.dataset.label;
        picker.clear.style.display = 'inline-flex';
        picker.dropdown.querySelectorAll('.employee-picker-option').forEach(item => item.classList.remove('active'));
        option.classList.add('active');

        if (closeAfterSelect) {
            closeEmployeePicker(mode);
        }
    }

    function clearEmployeePicker(mode, closeAfterClear = true) {
        const picker = getEmployeePicker(mode);
        picker.hidden.value = '';
        picker.search.value = '';
        picker.root.dataset.selectedLabel = '';
        picker.clear.style.display = 'none';
        picker.dropdown.querySelectorAll('.employee-picker-option').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.id) {
                item.style.display = 'block';
            }
        });
        const emptyState = picker.dropdown.querySelector('.employee-picker-empty');
        if (emptyState) {
            emptyState.remove();
        }
        if (closeAfterClear) {
            closeEmployeePicker(mode);
        }
    }

    function switchTab(tab, button) {
        document.getElementById('usersTab').style.display = tab === 'users' ? 'block' : 'none';
        document.getElementById('rolesTab').style.display = tab === 'roles' ? 'block' : 'none';
        document.getElementById('addUserAction').style.display = tab === 'users' ? 'inline-flex' : 'none';
        document.getElementById('addRoleAction').style.display = tab === 'roles' ? 'inline-flex' : 'none';
        document.querySelectorAll('.tabs-container .tab-item').forEach(item => item.classList.remove('active'));
        button.classList.add('active');
    }

    function openEditUser(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_user_name').value = user.name;
        document.getElementById('edit_user_email').value = user.email;
        document.getElementById('edit_user_role').value = user.role;
        configureEmployeePicker('edit', user.id, user.employee_id || '');
        document.getElementById('edit_user_2fa').checked = user.two_factor_enabled;
        openModal('editUserModal');
    }

    function openResetPassword(user) {
        document.getElementById('reset_user_id').value = user.id;
        document.getElementById('reset_user_display_name').textContent = user.name;
        openModal('resetPasswordModal');
    }

    function openAddUserModal() {
        const form = document.getElementById('addUserForm');
        form.reset();
        configureEmployeePicker('add');
        openModal('addUserModal');
    }

    function resetPermissionEditor(form, permissions = {}) {
        form.querySelectorAll('.permission-row').forEach(row => {
            const module = row.dataset.module;
            ['read', 'create', 'edit'].forEach(permission => {
                row.querySelector(`[data-permission="${permission}"]`).checked = Boolean(permissions[module]?.[permission]);
            });
        });
    }

    function openEditRole(role) {
        document.getElementById('edit_role_id').value = role.id;
        document.getElementById('edit_role_name').value = role.name;
        resetPermissionEditor(document.getElementById('editRoleForm'), role.permissions || {});
        openModal('editRoleModal');
    }

    function serializePermissions(form) {
        const permissions = {};
        form.querySelectorAll('.permission-row').forEach(row => {
            const module = row.dataset.module;
            permissions[module] = {
                read: row.querySelector('[data-permission="read"]').checked,
                create: row.querySelector('[data-permission="create"]').checked,
                edit: row.querySelector('[data-permission="edit"]').checked
            };
        });
        return permissions;
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

    document.getElementById('addUserForm').onsubmit = async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        data.two_factor_enabled = this.two_factor_enabled.checked ? 1 : 0;
        try { await submitJson('/users', 'POST', data); location.reload(); } catch (error) { alert(error.message); }
    };

    document.getElementById('editUserForm').onsubmit = async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        const id = data.id;
        data.two_factor_enabled = this.two_factor_enabled.checked ? 1 : 0;
        data._method = 'PUT';
        try { await submitJson(`/users/${id}`, 'POST', data); location.reload(); } catch (error) { alert(error.message); }
    };

    document.getElementById('resetPasswordForm').onsubmit = async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        try { const result = await submitJson(`/users/${data.user_id}/reset-password`, 'POST', data); alert(result.message); closeModal('resetPasswordModal'); } catch (error) { alert(error.message); }
    };

    document.getElementById('addRoleForm').onsubmit = async function(e) {
        e.preventDefault();
        const data = { name: this.querySelector('[name="name"]').value, permissions: serializePermissions(this) };
        try { await submitJson('{{ route('roles.store') }}', 'POST', data); location.reload(); } catch (error) { alert(error.message); }
    };

    document.getElementById('editRoleForm').onsubmit = async function(e) {
        e.preventDefault();
        const id = document.getElementById('edit_role_id').value;
        const data = { name: document.getElementById('edit_role_name').value, permissions: serializePermissions(this) };
        try { await submitJson(`/roles/${id}`, 'PUT', data); location.reload(); } catch (error) { alert(error.message); }
    };

    async function deleteRole(id, name) {
        if (!confirm(`Delete the role "${name}"?`)) return;
        try { await submitJson(`/roles/${id}`, 'DELETE', {}); location.reload(); } catch (error) { alert(error.message); }
    }

    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            document.querySelectorAll('.modal-overlay').forEach(modal => modal.style.display = 'none');
        }

        if (!event.target.closest('.employee-picker')) {
            closeEmployeePicker('add');
            closeEmployeePicker('edit');
        }
    });

    configureEmployeePicker('add');
    configureEmployeePicker('edit');
    resetPermissionEditor(document.getElementById('addRoleForm'));
    if (window.lucide) window.lucide.createIcons();
</script>

@endsection
