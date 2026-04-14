<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    @php
        $loginCssVersion = file_exists(public_path('css/login.css'))
            ? filemtime(public_path('css/login.css'))
            : time();
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ $loginCssVersion }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card" id="login-card">
            <div class="logo-container">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="logo">
            </div>

            <h1 class="welcome-title">Welcome to {{ config('app.name') }}</h1>
            <p class="welcome-subtitle">Sign in to continue to your dashboard</p>

            @if(session('success') || session('status') || $errors->any())
                <div class="login-alerts">
                    @if(session('success'))
                        <div class="login-alert login-alert--success" role="status" aria-live="polite">
                            <span class="login-alert__icon" aria-hidden="true">✓</span>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('status'))
                        <div class="login-alert login-alert--success" role="status" aria-live="polite">
                            <span class="login-alert__icon" aria-hidden="true">✓</span>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="login-alert login-alert--error" role="alert" aria-live="assertive">
                            <span class="login-alert__icon" aria-hidden="true">!</span>
                            <div class="login-alert__content">
                                <strong>Login failed</strong>
                                <ul class="login-alert__list">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="login-form" novalidate>
                @csrf

                <div class="form-group">
                    <label for="email">Username / Email</label>
                    <input
                        type="text"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your username or email"
                        required
                        autofocus
                        autocomplete="username"
                        class="@error('email') is-invalid @enderror"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                        class="@error('password') is-invalid @enderror"
                    >
                </div>

                <div class="forgot-password">
                    <a href="#">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn" id="login-submit-btn">
                    <span class="login-btn__loader" aria-hidden="true"></span>
                    <span class="login-btn__label" id="login-submit-label">Login</span>
                </button>
            </form>

            <div class="footer">
                &copy; {{ date('Y') }} CodeAge Private Limited
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('login-form');
            const submitButton = document.getElementById('login-submit-btn');
            const submitLabel = document.getElementById('login-submit-label');
            const loginCard = document.getElementById('login-card');

            if (!form || !submitButton || !submitLabel || !loginCard) {
                return;
            }

            const resetLoginButton = function () {
                submitButton.disabled = false;
                submitButton.classList.remove('is-loading');
                submitButton.removeAttribute('aria-busy');
                submitLabel.textContent = 'Login';
                loginCard.classList.remove('is-loading');
            };

            resetLoginButton();
            window.addEventListener('pageshow', resetLoginButton);

            form.addEventListener('submit', function () {
                if (!form.checkValidity()) {
                    return;
                }

                submitButton.disabled = true;
                submitButton.classList.add('is-loading');
                submitButton.setAttribute('aria-busy', 'true');
                submitLabel.textContent = 'Signing in...';
                loginCard.classList.add('is-loading');
            });
        });
    </script>
</body>
</html>
