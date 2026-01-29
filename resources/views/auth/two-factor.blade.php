<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - CodeAge ERP</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body>
    <div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
        <div class="table-card" style="padding: 40px; max-width: 400px; width: 100%; text-align: center; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <div style="margin-bottom: 24px;">
                <div style="background: #fff7ed; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i data-lucide="shield-check" style="width: 32px; height: 32px; color: #FF4A00;"></i>
                </div>
                <h1 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 8px;">Two-Factor Authentication</h1>
                <p style="color: #6b7280; font-size: 14px; margin: 0;">We've sent a 6-digit code to your email. Please enter it below to continue.</p>
            </div>

            <form method="POST" action="{{ route('verify.store') }}">
                @csrf

                <div class="form-group" style="margin-bottom: 24px;">
                    <input id="two_factor_code" type="text" 
                           class="form-control @error('two_factor_code') is-invalid @enderror" 
                           name="two_factor_code" required autofocus placeholder="123456"
                           style="text-align: center; letter-spacing: 4px; font-size: 24px; font-weight: 600; padding: 12px; width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; outline: none;"
                           maxlength="6">

                    @error('two_factor_code')
                        <span class="invalid-feedback" role="alert" style="display: block; color: #ef4444; font-size: 13px; margin-top: 8px;">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; background: #FF4A00; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Verify Code
                </button>
            </form>

            <div style="margin-top: 24px; font-size: 14px;">
                <span style="color: #6b7280;">Didn't receive the code?</span>
                <form method="POST" action="{{ route('verify.resend') }}" style="display: inline;">
                    @csrf
                    <button type="submit" style="background: none; border: none; color: #FF4A00; font-weight: 600; cursor: pointer; padding: 0; font-family: inherit;">
                        Resend
                    </button>
                </form>
            </div>
            
            @if(session('message'))
                <div style="margin-top: 16px; background: #ecfdf5; color: #059669; padding: 8px; border-radius: 6px; font-size: 13px; font-weight: 500;">
                    {{ session('message') }}
                </div>
            @endif
            
            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #f3f4f6;">
                <a href="{{ route('login') }}" style="color: #6b7280; text-decoration: none; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        if (window.lucide) window.lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('two_factor_code').focus();
        });
    </script>
</body>
</html>
