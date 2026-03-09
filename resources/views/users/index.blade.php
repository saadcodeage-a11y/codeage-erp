@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>User Management</h1>
        <p>Manage users, roles, and module permissions</p>
    </div>
    <div class="header-right">
        <button id="addUserAction" class="btn btn-primary" onclick="openModal('addUserModal')">
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
    <div class="role-grid">
        @forelse($roles as $role)
            @php
                $permissions = $role->permissionsByModule();
                $rolePayload = ['id' => $role->id, 'name' => $role->name, 'permissions' => $permissions];
            @endphp
            <div class="role-card">
                <div class="role-card-header">
                    <div>
                        <h3>{{ $role->name }}</h3>
                        <p>{{ $role->users_count }} assigned {{ \Illuminate\Support\Str::plural('user', $role->users_count) }}</p>
                    </div>
                    <div class="action-buttons">
                        <button class="btn-action outline" onclick='openEditRole(@json($rolePayload))'><i data-lucide="edit-2"></i> Edit</button>
                        <button class="btn-action outline-red" onclick="deleteRole({{ $role->id }}, @json($role->name))"><i data-lucide="trash-2"></i> Delete</button>
                    </div>
                </div>
                <table class="permissions-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Read</th>
                            <th>Create</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $permission)
                            <tr>
                                <td>{{ $permission['label'] }}</td>
                                <td>{{ $permission['read'] ? 'Yes' : 'No' }}</td>
                                <td>{{ $permission['create'] ? 'Yes' : 'No' }}</td>
                                <td>{{ $permission['edit'] ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                <div class="form-group"><label>Assign to Employee</label><select name="employee_id"><option value="">None</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_id ?: $employee->email }})</option>@endforeach</select></div>
                <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Role</label><select name="role" required>@foreach($roleOptions as $roleName)<option value="{{ $roleName }}">{{ $roleName }}</option>@endforeach</select></div>
                <div class="form-group"><label>Initial Password</label><input type="password" name="password" required></div>
                <label class="checkbox-row"><input type="checkbox" name="two_factor_enabled" value="1"> Enable two-factor authentication</label>
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
                <div class="form-group"><label>Assign to Employee</label><select name="employee_id" id="edit_user_employee_id"><option value="">None</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_id ?: $employee->email }})</option>@endforeach</select></div>
                <div class="form-group"><label>Name</label><input type="text" name="name" id="edit_user_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_user_email" required></div>
                <div class="form-group"><label>Role</label><select name="role" id="edit_user_role" required>@foreach($roleOptions as $roleName)<option value="{{ $roleName }}">{{ $roleName }}</option>@endforeach</select></div>
                <label class="checkbox-row"><input type="checkbox" name="two_factor_enabled" id="edit_user_2fa" value="1"> Enable two-factor authentication</label>
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
    .role-grid { display: grid; gap: 20px; }
    .role-card { background: white; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
    .role-card-header { display: flex; justify-content: space-between; gap: 16px; padding: 20px; border-bottom: 1px solid #f3f4f6; }
    .role-card-header h3 { margin: 0 0 4px; }
    .role-card-header p { margin: 0; color: #6b7280; font-size: 13px; }
    .permissions-table { width: 100%; border-collapse: collapse; }
    .permissions-table th, .permissions-table td { padding: 12px 20px; border-bottom: 1px solid #f3f4f6; text-align: left; font-size: 13px; }
    .permissions-table tr:last-child td { border-bottom: none; }
    .modal-form { display: flex; flex-direction: column; gap: 16px; }
    .modal-form input, .modal-form select { width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb; }
    .checkbox-row { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #374151; }
    .permission-editor { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
    .permission-row { display: grid; grid-template-columns: minmax(180px, 1fr) 120px 120px 120px; gap: 12px; align-items: center; padding: 14px 16px; border-bottom: 1px solid #f3f4f6; }
    .permission-row:last-child { border-bottom: none; }
    .permission-row label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; }
    @media (max-width: 900px) { .role-card-header, .permission-row { grid-template-columns: 1fr; display: grid; } }
</style>

<script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; if (window.lucide) window.lucide.createIcons(); }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

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
        document.getElementById('edit_user_employee_id').value = user.employee_id || '';
        document.getElementById('edit_user_2fa').checked = user.two_factor_enabled;
        openModal('editUserModal');
    }

    function openResetPassword(user) {
        document.getElementById('reset_user_id').value = user.id;
        document.getElementById('reset_user_display_name').textContent = user.name;
        openModal('resetPasswordModal');
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
    });

    resetPermissionEditor(document.getElementById('addRoleForm'));
    if (window.lucide) window.lucide.createIcons();
</script>

@endsection
