@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="dashboard-header">
    <div class="header-title">
        <h1>My Profile</h1>
        <p>View and manage your account information</p>
    </div>
</div>

<div class="profile-container" style="display: flex; flex-direction: column; gap: 24px;">
    <!-- Profile Header Card -->
    <div class="table-card" style="padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 24px;">
                <div class="avatar-lg" id="profileAvatarContainer" style="width: 100px; height: 100px; border-radius: 50%; background: {{ $user->avatar ? 'none' : 'linear-gradient(135deg, #ff4d00 0%, #ff8c00 100%)' }}; color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; position: relative; overflow: hidden; border: 2px solid {{ $user->avatar ? '#f3f4f6' : 'transparent' }};">
                    @if($user->avatar)
                        <img id="profileAvatarImg" src="{{ asset('storage/' . $user->avatar) }}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    @endif
                    <div onclick="document.getElementById('avatarInput').click()" style="position: absolute; bottom: 0; right: 0; background: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid #e5e7eb; cursor: pointer; z-index: 10;">
                        <i data-lucide="camera" style="width: 16px; height: 16px; color: #6b7280;"></i>
                    </div>
                    <input type="file" id="avatarInput" style="display: none;" accept="image/*" onchange="uploadAvatar(this)">
                </div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <h2 style="margin: 0; font-size: 24px; font-weight: 600; color: #111827;">{{ $user->name }}</h2>
                    <span style="color: #6b7280; font-size: 16px;">{{ $user->email }}</span>
                    <div style="margin-top: 8px;">
                        <span style="background: #f3e8ff; color: #7e22ce; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 500;">
                            {{ $user->role }}
                        </span>
                    </div>
                </div>
            </div>
            <button onclick="openChangePasswordModal()" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
                <i data-lucide="key" style="width: 18px; height: 18px;"></i>
                Change Password
            </button>
        </div>
    </div>

    <!-- Info Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Account Information -->
        <div class="table-card" style="padding: 24px;">
            <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 600; color: #374151;">Account Information</h3>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="background: #fff7ed; padding: 10px; border-radius: 10px;">
                        <i data-lucide="user" style="width: 20px; height: 20px; color: #f97316;"></i>
                    </div>
                    <div>
                        <span style="display: block; font-size: 13px; color: #6b7280;">User ID</span>
                        <span style="font-weight: 600; color: #111827;">{{ $user->user_id }}</span>
                    </div>
                </div>
                <div style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="background: #eff6ff; padding: 10px; border-radius: 10px;">
                        <i data-lucide="mail" style="width: 20px; height: 20px; color: #3b82f6;"></i>
                    </div>
                    <div>
                        <span style="display: block; font-size: 13px; color: #6b7280;">Email Address</span>
                        <span style="font-weight: 600; color: #111827;">{{ $user->email }}</span>
                    </div>
                </div>
                <div style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="background: #fef2f2; padding: 10px; border-radius: 10px;">
                        <i data-lucide="calendar" style="width: 20px; height: 20px; color: #ef4444;"></i>
                    </div>
                    <div>
                        <span style="display: block; font-size: 13px; color: #6b7280;">Account Created</span>
                        <span style="font-weight: 600; color: #111827;">{{ $user->created_at->format('F j, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="table-card" style="padding: 24px;">
            <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 600; color: #374151;">Security Settings</h3>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="background: #fdf2f8; padding: 10px; border-radius: 10px;">
                        <i data-lucide="shield-check" style="width: 20px; height: 20px; color: #db2777;"></i>
                    </div>
                    <div>
                        <span style="display: block; font-size: 13px; color: #6b7280;">Two-Factor Authentication</span>
                        @if($user->two_factor_enabled)
                        <span style="font-weight: 600; color: #059669;">Enabled</span>
                        @else
                        <span style="font-weight: 600; color: #6b7280;">Disabled</span>
                        @endif
                    </div>
                </div>
                <div style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="background: #fffbeb; padding: 10px; border-radius: 10px;">
                        <i data-lucide="key" style="width: 20px; height: 20px; color: #d97706;"></i>
                    </div>
                    <div>
                        <span style="display: block; font-size: 13px; color: #6b7280;">Last Login</span>
                        <span style="font-weight: 600; color: #111827;">{{ date('M j, Y, h:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div class="table-card" style="padding: 24px;">
            <h3 style="margin: 0 0 16px 0; font-size: 15px; font-weight: 600; color: #374151;">Role & Permissions</h3>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <span style="font-size: 13px; color: #6b7280;">Your Role</span>
                <span style="font-weight: 500; color: #111827;">{{ $user->role }}</span>
            </div>
        </div>
        <div class="table-card" style="padding: 24px;">
            <h3 style="margin: 0 0 16px 0; font-size: 15px; font-weight: 600; color: #374151;">Account Status</h3>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <span style="font-size: 13px; color: #6b7280;">Status</span>
                <span style="font-weight: 500; color: #059669;">Active</span>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
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
                    <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">At least 8 characters long.</small>
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
            method: 'POST', // Use POST with _method spoofing
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}' 
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Validation failed');
            }
            return data;
        })
        .then(data => {
            alert(data.message);
            closeChangePasswordModal();
        })
        .catch(err => {
            alert(err.message);
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeChangePasswordModal();
        }
    });

    function uploadAvatar(input) {
        if (!input.files || !input.files[0]) return;

        const file = input.files[0];
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file.');
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);

        // Show loading state
        const container = document.getElementById('profileAvatarContainer');
        const originalContent = container.innerHTML;
        container.innerHTML = '<div style="color: white; font-size: 14px;">Uploading...</div>';
        container.style.background = '#6b7280';

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
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Upload failed');
            return data;
        })
        .then(data => {
            // Update the image without full page reload
            location.reload(); // Simplest way to reflect changes everywhere (header/sidebar)
        })
        .catch(err => {
            alert(err.message);
            container.innerHTML = originalContent;
            // Restore original background if was initials
            container.style.background = '{{ $user->avatar ? 'none' : 'linear-gradient(135deg, #ff4d00 0%, #ff8c00 100%)' }}';
        });
    }

    if (window.lucide) window.lucide.createIcons();
</script>

<style>
    .avatar-lg:hover div {
        background: #f3f4f6 !important;
        cursor: pointer;
    }
</style>
@endsection
