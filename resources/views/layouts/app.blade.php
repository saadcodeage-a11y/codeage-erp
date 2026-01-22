<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - CodeAge ERP</title>
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
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-grid"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
                    <i data-lucide="file-text"></i>
                    <span>Reports</span>
                </a>
                <a href="{{ route('employees.index') }}" class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                    <i data-lucide="users"></i>
                    <span>Employees</span>
                </a>
                <a href="{{ route('templates.index') }}" class="nav-item {{ request()->routeIs('templates.*') ? 'active' : '' }}">
                    <i data-lucide="mail"></i>
                    <span>Templates</span>
                </a>
                <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i data-lucide="user-cog"></i>
                    <span>User Management</span>
                </a>
                <a href="{{ route('activity-logs.index') }}" class="nav-item {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                    <i data-lucide="activity"></i>
                    <span>Activity Logs</span>
                </a>
                <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i data-lucide="settings"></i>
                    <span>Settings</span>
                </a>
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
            toast.style.cssText = `
                background: white;
                border-left: 4px solid ${type === 'success' ? '#10b981' : '#ef4444'};
                padding: 16px;
                border-radius: 4px;
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
            
            const iconColor = type === 'success' ? '#10b981' : '#ef4444';
            const icon = type === 'success' ? 'check-circle' : 'alert-circle';
            
            toast.innerHTML = `
                <i data-lucide="${icon}" style="color: ${iconColor}; width: 20px; height: 20px;"></i>
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
