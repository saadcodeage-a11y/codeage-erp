<!DOCTYPE html>
<html>
<head>
    <title>Your Two-Factor Authentication Code</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; color: #111827; margin: 0; padding: 0; }
        .container { max-width: 500px; margin: 40px auto; background: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: 800; color: #FF4A00; text-decoration: none; }
        .code-container { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; margin: 24px 0; }
        .code { font-size: 32px; font-weight: 700; letter-spacing: 4px; color: #111827; }
        .footer { text-align: center; font-size: 13px; color: #6b7280; margin-top: 30px; }
        p { line-height: 1.6; color: #374151; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo">{{ config('app.name') }}</a>
        </div>
        <p>Hello,</p>
        <p>You have requested to log in. Please use the verification code below to complete your sign-in process.</p>
        
        <div class="code-container">
            <div class="code">{{ $code }}</div>
        </div>

        <p>This code will expire in 10 minutes. If you did not attempt to sign in, please contact your administrator immediately.</p>
        
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
