<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/dashboard.css'])
    @else
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="{{ asset('images/logo.png') }}" alt="CodeAge" class="sidebar-logo">
            </div>
            
            <nav class="sidebar-nav">
                @if(Auth::user()->canAccessModule('dashboard'))
                    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-grid"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('employees'))
                    <a href="{{ route('employees.index') }}" class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <i data-lucide="users"></i>
                        <span>Employees</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('team_management'))
                    <a href="{{ route('team.index') }}" class="nav-item {{ request()->routeIs('team.*') ? 'active' : '' }}">
                        <i data-lucide="user-round-search"></i>
                        <span>My Team</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('performance_management'))
                    <a href="{{ route('performance.index') }}" class="nav-item {{ request()->routeIs('performance.*') ? 'active' : '' }}">
                        <i data-lucide="chart-column-big"></i>
                        <span>Performance</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('leave_management'))
                    <a href="{{ route('leaves.index') }}" class="nav-item {{ request()->routeIs('leaves.*') || request()->routeIs('leave-types.*') ? 'active' : '' }}">
                        <i data-lucide="calendar-range"></i>
                        <span>Leave Management</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('attendance_management'))
                    <a href="{{ route('attendance.index') }}" class="nav-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                        <i data-lucide="fingerprint"></i>
                        <span>Attendance</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('payroll_management'))
                    <a href="{{ route('payroll.index') }}" class="nav-item {{ request()->routeIs('payroll.*') ? 'active' : '' }}">
                        <i data-lucide="wallet-cards"></i>
                        <span>Payroll</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('reports'))
                    <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i data-lucide="files"></i>
                        <span>Reports</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('announcements'))
                    <a href="{{ route('announcements.index') }}" class="nav-item {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                        <i data-lucide="megaphone"></i>
                        <span>Announcements</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('templates'))
                    <a href="{{ route('templates.index') }}" class="nav-item {{ request()->routeIs('templates.*') ? 'active' : '' }}">
                        <i data-lucide="mail"></i>
                        <span>Templates</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('user_management'))
                    <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'active' : '' }}">
                        <i data-lucide="user-cog"></i>
                        <span>User Management</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('activity_logs'))
                    <a href="{{ route('activity-logs.index') }}" class="nav-item {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                        <i data-lucide="activity"></i>
                        <span>Activity Logs</span>
                    </a>
                @endif
                @if(Auth::user()->canAccessModule('settings'))
                    <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i data-lucide="settings"></i>
                        <span>Settings</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="avatar" style="overflow: hidden; background: {{ Auth::user()->avatar ? 'none' : '' }}; border: 1px solid {{ Auth::user()->avatar ? '#f3f4f6' : 'transparent' }};">
                        @if(Auth::user()->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        @endif
                    </div>
                    <div class="user-info">
                        <span class="user-name">{{ Auth::user()->name }}</span>
                        <span class="user-email">{{ Auth::user()->email }}</span>
                    </div>
                </div>
                
                <a href="{{ route('profile.index') }}" class="nav-item mt-4 {{ request()->is('profile*') ? 'active' : '' }}">
                    <i data-lucide="user"></i>
                    <span>My Profile</span>
                </a>
                
                <!-- Logout Form -->
                <form method="POST" action="/logout" id="logout-form" style="display: none;">
                   @csrf 
                </form>
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="nav-item">
                    <i data-lucide="log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <script>
        lucide.createIcons();

        // Toast Notification System
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            const styles = type === 'success'
                ? {
                    border: '#10b981',
                    icon: '#10b981',
                    iconName: 'circle-check-big',
                    background: '#ffffff',
                  }
                : {
                    border: '#ef4444',
                    icon: '#ef4444',
                    iconName: 'alert-circle',
                    background: '#ffffff',
                  };
            toast.style.cssText = `
                background: ${styles.background};
                border-left: 4px solid ${styles.border};
                padding: 16px 18px;
                border-radius: 12px;
                border: 1px solid #e5e7eb;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 300px;
                transform: translateX(100%);
                transition: transform 0.3s ease-out;
                margin-bottom: 10px;
                pointer-events: auto;
            `;
            
            toast.innerHTML = `
                <i data-lucide="${styles.iconName}" style="color: ${styles.icon}; width: 20px; height: 20px;"></i>
                <span style="font-size: 14px; font-weight: 500; color: #1f2937;">${message}</span>
            `;
            
            container.appendChild(toast);
            lucide.createIcons();
            
            // Animate in
            requestAnimationFrame(() => {
                toast.style.transform = 'translateX(0)';
            });

            // Remove after 3s
            setTimeout(() => {
                toast.style.transform = 'translateX(120%)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Check for session flash messages
        document.addEventListener('DOMContentLoaded', () => {
            const flashMessage = sessionStorage.getItem('flash_message');
            const flashType = sessionStorage.getItem('flash_type');
            
            if (flashMessage) {
                showToast(flashMessage, flashType || 'success');
                sessionStorage.removeItem('flash_message');
                sessionStorage.removeItem('flash_type');
            }
        });
    </script>
    
    <!-- Toast Container -->
    <div id="toast-container" style="
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    "></div>
</body>
</html>
