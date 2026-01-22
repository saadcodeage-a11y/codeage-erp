@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>User Management</h1>
        <p>Manage system users, roles, and permissions</p>
    </div>
    <div class="header-right">
        <button onclick="openAddUserModal()" class="btn btn-primary">
            <i data-lucide="plus"></i> Add New User
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Total Users</span>
            <span class="stat-value">{{ $counts['total'] }}</span>
        </div>
        <div class="stat-icon-wrapper orange">
            <i data-lucide="users"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Super Admins</span>
            <span class="stat-value">{{ $counts['super_admins'] }}</span>
        </div>
        <div class="stat-icon-wrapper purple">
            <i data-lucide="shield-check"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">HR Managers</span>
            <span class="stat-value">{{ $counts['hr_managers'] }}</span>
        </div>
        <div class="stat-icon-wrapper blue">
            <i data-lucide="user-cog"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-label">Accounts Managers</span>
            <span class="stat-value">{{ $counts['accounts_managers'] }}</span>
        </div>
        <div class="stat-icon-wrapper green">
            <i data-lucide="briefcase"></i>
        </div>
    </div>
</div>

<!-- Search -->
<div class="search-container">
    <form action="{{ route('users.index') }}" method="GET" class="search-form">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or user ID..." class="search-input">
    </form>
</div>

<!-- Users Table -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>User ID</th>
                <th>User</th>
                <th>Role</th>
                <th>2FA Status</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->user_id }}</td>
                <td>
                    <div class="employee-cell">
                        <div class="avatar-sm {{ $user->is_active ? 'orange' : 'inactive' }}">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div class="employee-info">
                            <span class="emp-name">{{ $user->name }}</span>
                            <span class="emp-email">{{ $user->email }}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="background: #f3e8ff; color: #7e22ce; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                        {{ $user->role }}
                    </span>
                </td>
                <td>
                    @if($user->two_factor_enabled)
                    <span style="color: #059669; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Enabled
                    </span>
                    @else
                    <span style="color: #6b7280; font-size: 13px;">Disabled</span>
                    @endif
                </td>
                <td>
                    <span class="status-badge {{ $user->is_active ? 'active' : 'inactive' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button onclick='editUser(@json($user))' class="btn-action outline">
                            <i data-lucide="edit-2"></i> Edit
                        </button>
                        <button onclick='openResetPasswordModal(@json($user))' class="btn-action outline">
                            <i data-lucide="key"></i> Reset
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination-wrapper">
        {{ $users->links() }}
    </div>
</div>

<!-- Modals Component -->
<div id="addUserModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <div>
                <h2>Add New User</h2>
                <p class="modal-desc" style="margin-bottom: 0;">Create a new user account. They will receive login credentials via email.</p>
            </div>
            <button onclick="closeAddUserModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <form id="addUserForm" style="display: flex; flex-direction: column; gap: 20px;">
                @csrf
                <div class="form-group" style="padding-bottom: 5px; border-bottom: 1px solid #f3f4f6; margin-bottom: 5px;">
                    <label>Assign to Employee (Optional)</label>
                    <div class="custom-select-wrapper" id="addUserEmployeeSelect">
                        <div class="custom-select" onclick="toggleCustomDropdown(this)">
                            <span class="selected-value">Select Employee</span>
                            <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                        </div>
                        <div class="custom-select-options" style="display: none;">
                            <div class="search-box">
                                <i data-lucide="search" style="width: 14px; height: 14px;"></i>
                                <input type="text" placeholder="Search employee..." onkeyup="filterCustomOptions(this)">
                            </div>
                            <div class="options-list">
                                <div class="option" data-id="" data-name="Select Employee" data-email="" onclick="selectCustomOption(this, null)">None</div>
                                @foreach($employees as $employee)
                                <div class="option" data-id="{{ $employee->id }}" data-name="{{ $employee->full_name }}" data-email="{{ $employee->email }}" onclick="selectCustomOption(this, '{{ $employee->id }}')">
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 500;">{{ $employee->full_name }}</span>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <small style="color: #6b7280; font-size: 11px;">{{ $employee->employee_id }}</small>
                                            <small style="color: #9ca3af; font-size: 10px;">{{ $employee->email }}</small>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="employee_id" class="custom-select-input">
                    </div>
                </div>

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" id="addUser_name" placeholder="Enter full name" required style="background: #f9fafb;">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="addUser_email" placeholder="Enter email address" required style="background: #f9fafb;">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required style="background: #f9fafb;">
                        <option value="Super Admin">Super Admin</option>
                        <option value="HR Manager">HR Manager</option>
                        <option value="Accounts Manager">Accounts Manager</option>
                        <option value="Employee">Employee</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Initial Password *</label>
                    <input type="password" name="password" placeholder="Enter initial password" required style="background: #f9fafb;">
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 10px;">
                    <div>
                        <span style="font-size: 13px; font-weight: 600; color: #111827; display: block;">Enable Two-Factor Authentication</span>
                        <p style="font-size: 12px; color: #6b7280; margin: 4px 0 0 0;">User will receive OTP via email on login</p>
                    </div>
                    <label class="switch-toggle" style="margin: 0;">
                        <input type="checkbox" name="two_factor_enabled" value="1">
                        <span class="slider"></span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button onclick="closeAddUserModal()" class="btn btn-outline">Cancel</button>
            <button type="submit" form="addUserForm" class="btn btn-primary">Create User</button>
        </div>
    </div>
</div>

<div id="editUserModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <div>
                <h2>Edit User</h2>
                <p class="modal-desc" style="margin-bottom: 0;">Update user information and permissions.</p>
            </div>
            <button onclick="closeEditUserModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <form id="editUserForm" style="display: flex; flex-direction: column; gap: 20px;">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_user_id">
                <div class="form-group" style="padding-bottom: 5px; border-bottom: 1px solid #f3f4f6; margin-bottom: 5px;">
                    <label>Assign to Employee</label>
                    <div class="custom-select-wrapper" id="editUserEmployeeSelect">
                        <div class="custom-select" onclick="toggleCustomDropdown(this)">
                            <span class="selected-value">Select Employee</span>
                            <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                        </div>
                        <div class="custom-select-options" style="display: none;">
                            <div class="search-box">
                                <i data-lucide="search" style="width: 14px; height: 14px;"></i>
                                <input type="text" placeholder="Search employee..." onkeyup="filterCustomOptions(this)">
                            </div>
                            <div class="options-list">
                                <div class="option" data-id="" data-name="Select Employee" data-email="" onclick="selectCustomOption(this, null)">None</div>
                                @foreach($employees as $employee)
                                <div class="option" data-id="{{ $employee->id }}" data-name="{{ $employee->full_name }}" data-email="{{ $employee->email }}" onclick="selectCustomOption(this, '{{ $employee->id }}')">
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 500;">{{ $employee->full_name }}</span>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <small style="color: #6b7280; font-size: 11px;">{{ $employee->employee_id }}</small>
                                            <small style="color: #9ca3af; font-size: 10px;">{{ $employee->email }}</small>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="employee_id" class="custom-select-input" id="edit_user_employee_id">
                    </div>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit_user_name" placeholder="Enter full name" required style="background: #f9fafb;">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_user_email" placeholder="Enter email address" required style="background: #f9fafb;">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_user_role" required style="background: #f9fafb;">
                        <option value="Super Admin">Super Admin</option>
                        <option value="HR Manager">HR Manager</option>
                        <option value="Accounts Manager">Accounts Manager</option>
                        <option value="Employee">Employee</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 10px;">
                    <div>
                        <span style="font-size: 13px; font-weight: 600; color: #111827; display: block;">Two-Factor Authentication</span>
                        <p style="font-size: 12px; color: #6b7280; margin: 4px 0 0 0;">Require OTP via email on login</p>
                    </div>
                    <label class="switch-toggle" style="margin: 0;">
                        <input type="checkbox" name="two_factor_enabled" id="edit_user_2fa" value="1">
                        <span class="slider"></span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button onclick="closeEditUserModal()" class="btn btn-outline">Cancel</button>
            <button type="submit" form="editUserForm" class="btn btn-primary">Save Changes</button>
        </div>
    </div>
</div>

<div id="resetPasswordModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 450px;">
        <div class="modal-header">
            <div>
                <h2>Reset Password</h2>
                <p class="modal-desc" style="margin-bottom: 0;">Set a new password for <span id="reset_user_display_name" style="font-weight: 600; color: #111827;"></span>. They will receive the new credentials via email.</p>
            </div>
            <button onclick="closeResetPasswordModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="resetPasswordForm">
                @csrf
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="Enter new password" required style="background: #f9fafb;">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button onclick="closeResetPasswordModal()" class="btn btn-outline">Cancel</button>
            <button type="submit" form="resetPasswordForm" class="btn btn-primary">Reset Password</button>
        </div>
    </div>
</div>

<style>
    .switch-toggle { position: relative; display: inline-block; width: 44px; height: 22px; }
    .switch-toggle input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; inset: 0; background-color: #e5e7eb; transition: .3s; border-radius: 22px; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    input:checked + .slider { background-color: #22c55e; }
    input:checked + .slider:before { transform: translateX(22px); }
    
    .avatar-sm.inactive { background-color: #9ca3af; }
    .status-badge.inactive { background-color: #f3f4f6; color: #6b7280; }

    /* Fix clipping issue */
    #addUserModal .modal-body, #editUserModal .modal-body { overflow: visible !important; }
    .modal-container { overflow: visible !important; }

    /* Custom Searchable Select */
    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; font-size: 14px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; }
    .custom-select:hover { border-color: #d1d5db; }
    .custom-select-options { position: absolute; top: calc(100% + 4px); left: 0; width: 100%; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); z-index: 10; max-height: 250px; display: flex; flex-direction: column; overflow: hidden; animation: fadeIn 0.1s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
    .search-box { padding: 8px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px; position: sticky; top: 0; background: white; }
    .search-box input { border: none; outline: none; width: 100%; font-size: 13px; background: transparent; }
    .options-list { overflow-y: auto; flex: 1; }
    .option { padding: 8px 12px; cursor: pointer; transition: background 0.2s; font-size: 13px; }
    .option:hover { background: #f9fafb; }
    .option.selected { background: #fff7ed; color: #FF4A00; }
</style>

<script>
    function openAddUserModal() {
        document.getElementById('addUserModal').style.display = 'flex';
        document.getElementById('addUserForm').reset();
        
        // Reset custom select
        const wrapper = document.getElementById('addUserEmployeeSelect');
        if (wrapper) {
            wrapper.querySelector('.selected-value').innerText = 'Select Employee';
            wrapper.querySelector('.custom-select-input').value = '';
            wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        }
    }
    function closeAddUserModal() { document.getElementById('addUserModal').style.display = 'none'; }

    function editUser(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_user_name').value = user.name;
        document.getElementById('edit_user_email').value = user.email;
        document.getElementById('edit_user_role').value = user.role;
        document.getElementById('edit_user_2fa').checked = user.two_factor_enabled;
        
        // Handle employee selection
        const empIdInput = document.getElementById('edit_user_employee_id');
        const wrapper = empIdInput.closest('.custom-select-wrapper');
        const display = wrapper.querySelector('.selected-value');
        const optionsList = wrapper.querySelector('.options-list');
        
        // Clear previous custom option if it exists (the one we might have added last time)
        const customOpt = optionsList.querySelector('.option.current-assignment');
        if (customOpt) customOpt.remove();

        empIdInput.value = user.employee_id || '';
        if (user.employee_id) {
            let option = wrapper.querySelector(`.option[data-id="${user.employee_id}"]`);
            
            // If the employee is not in the list (because they are assigned), add them temporarily
            if (!option && user.employee) {
                const newOpt = document.createElement('div');
                newOpt.className = 'option current-assignment';
                newOpt.dataset.id = user.employee.id;
                newOpt.dataset.name = user.employee.full_name;
                newOpt.onclick = function() { selectCustomOption(this, user.employee.id); };
                newOpt.innerHTML = `
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-weight: 500;">${user.employee.full_name}</span>
                        <small style="color: #6b7280; font-size: 11px;">${user.employee.employee_id || ''}</small>
                    </div>
                `;
                optionsList.insertBefore(newOpt, optionsList.children[1]); // Insert after "None"
                option = newOpt;
            }

            if (option) {
                display.innerText = option.dataset.name;
                wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
                option.classList.add('selected');
            }
        } else {
            display.innerText = 'Select Employee';
            wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
            wrapper.querySelector('.option[data-id=""]').classList.add('selected');
        }

        document.getElementById('editUserModal').style.display = 'flex';
        if (window.lucide) window.lucide.createIcons();
    }
    function closeEditUserModal() { document.getElementById('editUserModal').style.display = 'none'; }

    function openResetPasswordModal(user) {
        document.getElementById('reset_user_id').value = user.id;
        document.getElementById('reset_user_display_name').innerText = user.name;
        document.getElementById('resetPasswordModal').style.display = 'flex';
    }
    function closeResetPasswordModal() { document.getElementById('resetPasswordModal').style.display = 'none'; }

    // Form Submissions
    document.getElementById('addUserForm').onsubmit = function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        data.two_factor_enabled = this.two_factor_enabled.checked ? 1 : 0;
        
        fetch('/users', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}' 
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.message || 'Validation failed');
            }
            return res.json();
        })
        .then(data => {
            location.reload();
        })
        .catch(err => {
            alert(err.message);
        });
    }

    document.getElementById('editUserForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = formData.get('id');
        const data = Object.fromEntries(formData.entries());
        data.two_factor_enabled = this.two_factor_enabled.checked ? 1 : 0;
        
        // Use POST with _method spoofing for maximum compatibility
        data._method = 'PUT';
        fetch(`/users/${id}`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}' 
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.message || 'Validation failed');
            }
            return res.json();
        })
        .then(data => {
            location.reload();
        })
        .catch(err => {
            alert(err.message);
        });
    }

    document.getElementById('resetPasswordForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = formData.get('user_id');
        const data = Object.fromEntries(formData.entries());

        fetch(`/users/${id}/reset-password`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}' 
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.message || 'Validation failed');
            }
            return res.json();
        })
        .then(data => {
            alert(data.message);
            closeResetPasswordModal();
        })
        .catch(err => {
            alert(err.message);
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeAddUserModal();
            closeEditUserModal();
            closeResetPasswordModal();
        }
        if (!e.target.closest('.custom-select-wrapper')) {
            document.querySelectorAll('.custom-select-options').forEach(opt => opt.style.display = 'none');
        }
    });

    // Custom Select Functions
    function toggleCustomDropdown(el) {
        const options = el.nextElementSibling;
        const allOptions = document.querySelectorAll('.custom-select-options');
        allOptions.forEach(opt => { if(opt !== options) opt.style.display = 'none'; });
        options.style.display = options.style.display === 'none' ? 'flex' : 'none';
        if(options.style.display === 'flex') {
            options.querySelector('input').focus();
        }
    }

    function filterCustomOptions(input) {
        const filter = input.value.toLowerCase();
        const optionsList = input.closest('.custom-select-options').querySelector('.options-list');
        const options = optionsList.querySelectorAll('.option');
        options.forEach(opt => {
            const text = opt.innerText.toLowerCase();
            opt.style.display = text.includes(filter) ? 'block' : 'none';
        });
    }

    function selectCustomOption(el, id) {
        const wrapper = el.closest('.custom-select-wrapper');
        const display = wrapper.querySelector('.selected-value');
        const input = wrapper.querySelector('.custom-select-input');
        const options = wrapper.querySelector('.custom-select-options');
        
        display.innerText = el.querySelector('span') ? el.querySelector('span').innerText : el.innerText;
        input.value = id || '';
        options.style.display = 'none';
        
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');

        // Auto-fill logic for Add/Edit User modals
        if (wrapper.id === 'addUserEmployeeSelect' || wrapper.id === 'editUserEmployeeSelect') {
            const prefix = wrapper.id === 'addUserEmployeeSelect' ? 'addUser' : 'edit_user';
            const nameInput = document.getElementById(`${prefix}_name`);
            const emailInput = document.getElementById(`${prefix}_email`);
            if (id) {
                if (nameInput) nameInput.value = el.dataset.name || '';
                if (emailInput) emailInput.value = el.dataset.email || '';
            } else if (wrapper.id === 'addUserEmployeeSelect') { 
                // Only clear on Add modal, on Edit we might want to keep current values if unlinking
                if (nameInput) nameInput.value = '';
                if (emailInput) emailInput.value = '';
            }
        }
    }

    if (window.lucide) window.lucide.createIcons();
</script>

@endsection
