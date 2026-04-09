<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/login.css'])
    @else
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Fallback if CSS file loading issues occur during dev */
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="logo">
            </div>
            
            <h1 class="welcome-title">Welcome to {{ config('app.name') }}</h1>
            <p class="welcome-subtitle">Sign in to continue to your dashboard</p>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <div class="form-group">
                    <label for="email">Username / Email</label>
                    <input type="text" id="email" name="email" placeholder="Enter your username or email" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="forgot-password">
                    <a href="#">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn">Login</button>
            </form>

            <div class="footer">
                &copy; {{ date('Y') }} CodeAge Private Limited
            </div>
        </div>
    </div>
</body>
</html>
