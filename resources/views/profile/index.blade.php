@extends('layouts.app')

@section('title', 'My Profile')

@php
    $formatMoney = fn ($value) => 'PKR ' . number_format((float) ($value ?? 0), 2);
    $employeeTabs = ['profile' => 'Employee Profile', 'salary' => 'Salary History', 'security' => 'Security Fund', 'tax' => 'Tax Records', 'attendance' => 'Attendance', 'leave' => 'Leave', 'performance' => 'Performance'];
    $showProfileSummary = $activeTab === 'account';
    $formatTime = function ($value) {
        if (! $value) {
            return '--:--';
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return \Illuminate\Support\Carbon::createFromFormat($format, $value)->format('g:i A');
            } catch (\Throwable $exception) {
                continue;
            }
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('g:i A');
        } catch (\Throwable $exception) {
            return $value;
        }
    };
@endphp

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>My Profile</h1>
        <p>Account settings and employee self-service information from one place.</p>
    </div>
    <div class="header-right">
        <button onclick="openChangePasswordModal()" class="btn btn-primary">
            <i data-lucide="key"></i> Change Password
        </button>
    </div>
</div>

@if($showProfileSummary)
    <div class="profile-hero table-card">
        <div class="profile-identity">
            <div class="avatar-lg" id="profileAvatarContainer">
                @if($user->avatar)
                    <img id="profileAvatarImg" src="{{ asset('storage/' . $user->avatar) }}" alt="Profile">
                @else
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                @endif
                <button type="button" class="avatar-upload-button" onclick="document.getElementById('avatarInput').click()">
                    <i data-lucide="camera"></i>
                </button>
                <input type="file" id="avatarInput" style="display: none;" accept="image/*" onchange="uploadAvatar(this)">
            </div>
            <div>
                <div class="hero-title-row">
                    <h2>{{ $user->name }}</h2>
                    <span class="role-pill">{{ $user->role }}</span>
                </div>
                <p>{{ $user->email }}</p>
                @if($employee)
                    <div class="hero-meta">
                        <span>{{ $employee->employee_id ?: 'Pending ID' }}</span>
                        <span>{{ $employee->designation ?: 'No designation' }}</span>
                        <span>{{ $employee->department?->name ?? 'Unassigned Department' }}</span>
                    </div>
                @endif
            </div>
        </div>
        <div class="profile-meta-card">
            <span>Account Status</span>
            <strong>{{ $user->is_active ? 'Active' : 'Inactive' }}</strong>
            <small>Two-factor authentication: {{ $user->two_factor_enabled ? 'Enabled' : 'Disabled' }}</small>
        </div>
    </div>
@endif

@if(session('status') === 'password-updated')
    <div class="portal-alert success"><i data-lucide="circle-check-big"></i><span>Password updated successfully.</span></div>
@endif

@if($employee && $showProfileSummary)
    <div class="stats-grid self-service-stats">
        <div class="stat-card"><div class="stat-content"><span class="stat-label">Latest Net Salary</span><span class="stat-value">{{ $portalStats['latestNetSalary'] !== null ? $formatMoney($portalStats['latestNetSalary']) : 'N/A' }}</span></div></div>
        <div class="stat-card"><div class="stat-content"><span class="stat-label">Security Balance</span><span class="stat-value">{{ $portalStats['securityBalance'] !== null ? $formatMoney($portalStats['securityBalance']) : 'N/A' }}</span></div></div>
        <div class="stat-card"><div class="stat-content"><span class="stat-label">{{ $currentMonthLabel }} Attendance</span><span class="stat-value">{{ $portalStats['currentMonthAttendanceRows'] }}</span></div></div>
        <div class="stat-card"><div class="stat-content"><span class="stat-label">Finalized Reviews</span><span class="stat-value">{{ $portalStats['finalizedReviews'] }}</span></div></div>
    </div>
@endif

<div class="tabs-container profile-tabs">
    <a href="{{ route('profile.index', ['tab' => 'account']) }}" class="tab-item {{ $activeTab === 'account' ? 'active' : '' }}">Account</a>
    @if($employee)
        @foreach($employeeTabs as $tabKey => $label)
            <a href="{{ route('profile.index', ['tab' => $tabKey]) }}" class="tab-item {{ $activeTab === $tabKey ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    @endif
</div>

<section class="table-card profile-account-section {{ $activeTab === 'account' ? '' : 'hidden-tab' }}">
    <div class="section-head">
        <h2>Account Settings</h2>
        <p>Profile, security, and role information for this login account.</p>
    </div>
    <div class="profile-account-grid">
        <div class="sub-card">
            <h3>Account Information</h3>
            <div class="info-grid two-col">
                <div class="info-card"><span class="label">User ID</span><strong>{{ $user->user_id }}</strong></div>
                <div class="info-card"><span class="label">Email</span><strong>{{ $user->email }}</strong></div>
                <div class="info-card"><span class="label">Role</span><strong>{{ $user->role }}</strong></div>
                <div class="info-card"><span class="label">Created</span><strong>{{ $user->created_at->format('d M Y') }}</strong></div>
            </div>
        </div>
        <div class="sub-card">
            <h3>Security Settings</h3>
            <div class="security-row">
                <div>
                    <strong>Two-Factor Authentication</strong>
                    <p>{{ $user->two_factor_enabled ? 'Enabled for this account.' : 'Disabled for this account.' }}</p>
                </div>
                <label class="switch-toggle">
                    <input type="checkbox" id="twoFactorToggle" {{ $user->two_factor_enabled ? 'checked' : '' }} onchange="toggleTwoFactor(this)">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="info-grid two-col" style="margin-top: 1rem;">
                <div class="info-card"><span class="label">Last Login</span><strong>{{ now()->format('d M Y, h:i A') }}</strong></div>
                <div class="info-card"><span class="label">Status</span><strong>{{ $user->is_active ? 'Active' : 'Inactive' }}</strong></div>
            </div>
        </div>
    </div>

    @if(! $employee && $user->role !== 'Super Admin')
        <div class="table-card self-service-empty inner-empty">
            <div class="empty-icon"><i data-lucide="shield-alert"></i></div>
            <h2>No Linked Employee Profile</h2>
            <p>This account can use employee self-service features after HR links it to an employee record.</p>
        </div>
    @endif
</section>

@if($employee)
    <div class="employee-tab-shell {{ in_array($activeTab, ['profile', 'salary', 'security', 'tax', 'attendance', 'leave', 'performance']) ? '' : 'hidden-tab' }}">
        @include('self-service.partials.profile')
        @include('self-service.partials.salary')
        @include('self-service.partials.security')
        @include('self-service.partials.tax')
        @include('self-service.partials.attendance')
        @include('self-service.partials.leave')
        @include('self-service.partials.performance')
    </div>
@endif

<div id="changePasswordModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 450px;">
        <div class="modal-header">
            <div>
                <h2>Change Password</h2>
                <p class="modal-desc">Update your login password to keep your account secure.</p>
            </div>
            <button onclick="closeChangePasswordModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <form id="changePasswordForm" style="display: flex; flex-direction: column; gap: 20px;">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter current password" required style="background: #f9fafb;">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="Enter new password" required style="background: #f9fafb;">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="password_confirmation" placeholder="Confirm new password" required style="background: #f9fafb;">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button onclick="closeChangePasswordModal()" class="btn btn-outline">Cancel</button>
            <button type="submit" form="changePasswordForm" class="btn btn-primary">Update Password</button>
        </div>
    </div>
</div>

<script>
    function openChangePasswordModal() {
        document.getElementById('changePasswordModal').style.display = 'flex';
    }

    function closeChangePasswordModal() {
        document.getElementById('changePasswordModal').style.display = 'none';
        document.getElementById('changePasswordForm').reset();
    }

    document.getElementById('changePasswordForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        fetch('{{ route('profile.password.update') }}', {
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
            const payload = await res.json();
            if (!res.ok) throw new Error(payload.message || 'Validation failed');
            return payload;
        })
        .then(payload => {
            if (window.showToast) window.showToast(payload.message || 'Password updated successfully.', 'success');
            closeChangePasswordModal();
        })
        .catch(err => {
            if (window.showToast) window.showToast(err.message, 'error');
        });
    };

    function uploadAvatar(input) {
        if (!input.files || !input.files[0]) return;

        const file = input.files[0];
        if (!file.type.startsWith('image/')) {
            if (window.showToast) window.showToast('Please select an image file.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);

        fetch('{{ route('profile.avatar.update') }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async res => {
            const payload = await res.json();
            if (!res.ok) throw new Error(payload.message || 'Upload failed');
            return payload;
        })
        .then(() => location.reload())
        .catch(err => {
            if (window.showToast) window.showToast(err.message, 'error');
        });
    }

    function toggleTwoFactor(checkbox) {
        const enabled = checkbox.checked;

        fetch('{{ route('profile.two-factor.toggle') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(async res => {
            const payload = await res.json();
            if (!res.ok) throw new Error(payload.message || 'Update failed');
            return payload;
        })
        .then(payload => {
            if (window.showToast) window.showToast(payload.message, 'success');
        })
        .catch(err => {
            checkbox.checked = !enabled;
            if (window.showToast) window.showToast(err.message, 'error');
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeChangePasswordModal();
        }
    });
</script>

<style>
    .profile-hero,.employee-summary-card,.portal-alert{margin-bottom:1.25rem}
    .profile-account-section{padding:1.5rem}
    .profile-hero{padding:1.5rem;display:grid;grid-template-columns:minmax(0,1.7fr) minmax(280px,.8fr);gap:1rem}
    .profile-identity{display:flex;align-items:center;gap:1rem}
    .avatar-lg{width:100px;height:100px;border-radius:28px;background:linear-gradient(135deg,#ff4d00 0%,#ff8c00 100%);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;position:relative;overflow:hidden;flex-shrink:0}
    .avatar-lg img{width:100%;height:100%;object-fit:cover}
    .avatar-upload-button{position:absolute;right:8px;bottom:8px;width:34px;height:34px;border-radius:999px;border:1px solid #e5e7eb;background:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
    .avatar-upload-button i{width:16px;height:16px;color:#6b7280}
    .hero-title-row{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:.35rem}
    .hero-title-row h2{margin:0;font-size:1.9rem;color:#111827}
    .profile-identity p{margin:0;color:#6b7280}
    .role-pill,.hero-meta span,.status-pill{display:inline-flex;align-items:center;padding:.4rem .8rem;border-radius:999px;border:1px solid #fed7aa;background:#fff7ed;color:#c2410c;font-size:.82rem;font-weight:600;white-space:nowrap}
    .hero-meta{display:flex;flex-wrap:wrap;gap:.65rem;margin-top:.8rem}
    .profile-meta-card,.employee-summary-card,.mini-stat,.sub-card,.performance-card,.feedback-card,.info-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:1.25rem;box-shadow:0 10px 24px rgba(15,23,42,.04)}
    .profile-meta-card{display:flex;flex-direction:column;justify-content:center;gap:.35rem}
    .profile-meta-card span,.summary-label,.summary-shift span{color:#6b7280;font-size:.82rem;text-transform:uppercase;letter-spacing:.04em}
    .profile-meta-card strong,.summary-shift strong{font-size:1.2rem;color:#111827}
    .self-service-stats,.profile-tabs{margin-bottom:1.25rem}
    .profile-account-grid,.self-service-two-col,.info-grid,.attendance-summary-grid,.metric-grid{display:grid;gap:1rem}
    .profile-account-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .profile-account-grid .sub-card{padding:1.35rem}
    .profile-account-grid .info-card{padding:1.2rem 1.35rem}
    .security-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem;border:1px solid #e5e7eb;border-radius:14px;background:#fafafa}
    .security-row strong{display:block;margin-bottom:.25rem;color:#111827}
    .security-row p{margin:0;color:#6b7280}
    .info-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
    .info-grid.two-col{grid-template-columns:repeat(2,minmax(0,1fr))}
    .info-card .label{display:block;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin-bottom:.45rem}
    .info-card strong{color:#111827;font-size:1rem;line-height:1.5}
    .inner-empty{margin-top:1rem}
    .employee-tab-shell{display:flex;flex-direction:column;gap:1.25rem}
    .employee-tab-shell>.table-card{padding:1.5rem;overflow:hidden}
    .employee-tab-shell>.table-card .table-scroll{margin-top:1rem;border:1px solid #eef2f7;border-radius:16px;background:#fff}
    .employee-tab-shell>.table-card .section-head+.table-scroll{margin-top:1rem}
    .employee-tab-shell>.table-card .data-table{margin:0}
    .employee-tab-shell>.table-card .data-table thead th:first-child,
    .employee-tab-shell>.table-card .data-table tbody td:first-child{padding-left:1.5rem}
    .employee-tab-shell>.table-card .data-table thead th:last-child,
    .employee-tab-shell>.table-card .data-table tbody td:last-child{padding-right:1.5rem}
    .employee-summary-card{display:flex;align-items:center;justify-content:space-between;gap:1rem}
    .employee-summary-card h2{margin:.35rem 0;color:#111827}
    .employee-summary-card p{margin:0;color:#6b7280}
    .summary-shift{text-align:right}
    .section-head{margin-bottom:1rem}
    .section-head h2{margin:0 0 .35rem;font-size:1.125rem;color:#111827}
    .section-head p,.muted-text,.empty-copy{margin:0;color:#6b7280}
    .attendance-summary-grid{grid-template-columns:repeat(6,minmax(0,1fr));margin-bottom:1rem}
    .leave-summary-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
    .mini-stat span{display:block;color:#6b7280;font-size:.84rem;margin-bottom:.4rem}
    .mini-stat strong{font-size:1.4rem;color:#111827}
    .self-service-two-col{grid-template-columns:repeat(2,minmax(0,1fr))}
    .sub-card h3,.performance-card h3{margin:0 0 .85rem;font-size:1rem;color:#111827}
    .table-card + .table-card{margin-top:1.25rem}
    .hidden-tab{display:none}
    .stack-list{display:flex;flex-direction:column;gap:.9rem}
    .stack-item{border:1px solid #e5e7eb;border-radius:14px;padding:1rem;background:#fafafa}
    .stack-item-head,.performance-card-head,.performance-footer{display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start}
    .stack-item p,.performance-card p{margin:.2rem 0 0;color:#6b7280}
    .review-note{margin-top:.75rem;padding:.75rem;border-radius:12px;background:#fff;color:#475467;font-size:.92rem}
    .metric-grid{grid-template-columns:repeat(5,minmax(0,1fr));margin:1rem 0}
    .metric-chip{padding:.9rem;border-radius:14px;background:#f8fafc;border:1px solid #e2e8f0}
    .metric-chip span{display:block;color:#6b7280;font-size:.82rem;margin-bottom:.35rem}
    .metric-chip strong{color:#111827;font-size:1rem}
    .feedback-grid{margin-top:1rem}
    .feedback-card{background:#fcfcfd}
    .feedback-card span{display:block;font-size:.82rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin-bottom:.45rem}
    .feedback-card p{margin:0;color:#111827;line-height:1.6}
    .performance-footer{margin-top:1rem;padding-top:1rem;border-top:1px solid #e5e7eb;color:#475467;font-size:.92rem}
    .portal-form{display:flex;flex-direction:column;gap:1rem}
    .portal-form .form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
    .portal-form .form-group{display:flex;flex-direction:column;gap:.5rem}
    .portal-form label{font-weight:600;color:#374151}
    .portal-form input,.portal-form select,.portal-form textarea{width:100%;padding:.9rem 1rem;border:1px solid #dbe2ea;border-radius:14px;background:#fff;color:#111827;outline:none}
    .portal-form textarea{resize:vertical}
    .portal-form input:focus,.portal-form select:focus,.portal-form textarea:focus{border-color:#fb923c;box-shadow:0 0 0 4px rgba(251,146,60,.12)}
    .portal-alert{display:flex;align-items:center;gap:.75rem;padding:.95rem 1rem;border-radius:14px;border:1px solid #bbf7d0;background:#f0fdf4;color:#166534}
    .portal-alert i{width:18px;height:18px}
    .self-service-empty{padding:3rem 2rem;text-align:center}
    .empty-icon{width:64px;height:64px;margin:0 auto 1rem;border-radius:20px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 100%);color:#f97316;border:1px solid #fed7aa}
    .empty-icon i{width:28px;height:28px}
    .switch-toggle{position:relative;display:inline-block;width:50px;height:28px}
    .switch-toggle input{opacity:0;width:0;height:0}
    .slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;transition:.2s;border-radius:999px}
    .slider:before{position:absolute;content:'';height:22px;width:22px;left:3px;top:3px;background:white;transition:.2s;border-radius:50%}
    .switch-toggle input:checked+.slider{background:#10b981}
    .switch-toggle input:checked+.slider:before{transform:translateX(22px)}
    .status-pill.active,.status-pill.approved,.status-pill.present,.status-pill.finalized{background:#ecfdf3;border-color:#bbf7d0;color:#15803d}
    .status-pill.pending,.status-pill.late{background:#fff7ed;border-color:#fed7aa;color:#c2410c}
    .status-pill.rejected,.status-pill.cancelled,.status-pill.absent,.status-pill.inactive,.status-pill.terminated,.status-pill.resigned{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
    .status-pill.holiday,.status-pill.weekend,.status-pill.incomplete{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
    @media (max-width:1200px){.profile-hero,.profile-account-grid,.self-service-two-col{grid-template-columns:1fr}.employee-summary-card{flex-direction:column;align-items:flex-start}.summary-shift{text-align:left}.info-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.attendance-summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.metric-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media (max-width:768px){.profile-account-section,.employee-tab-shell>.table-card{padding:1rem}.employee-tab-shell>.table-card .data-table thead th:first-child,.employee-tab-shell>.table-card .data-table tbody td:first-child{padding-left:1rem}.employee-tab-shell>.table-card .data-table thead th:last-child,.employee-tab-shell>.table-card .data-table tbody td:last-child{padding-right:1rem}.profile-identity{flex-direction:column;align-items:flex-start}.info-grid,.info-grid.two-col,.attendance-summary-grid,.leave-summary-grid,.metric-grid,.portal-form .form-row{grid-template-columns:1fr}}
</style>
@endsection
