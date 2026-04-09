<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Completed - {{ config('app.name') }}</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        
        .card { background: white; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); padding: 48px; text-align: center; max-width: 500px; width: 100%; border: 1px solid rgba(0,0,0,0.05); }
        
        .icon-circle { width: 80px; height: 80px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; border: 4px solid #f0fdf4; }
        .icon-circle i { color: #10b981; }
        
        h1 { font-size: 24px; color: #111827; margin-bottom: 16px; font-weight: 700; }
        p { color: #6b7280; line-height: 1.6; margin-bottom: 32px; }
        
        .divider { height: 1px; background: #f3f4f6; margin-bottom: 32px; }
        
        .info-box { background: #f9fafb; border-radius: 12px; padding: 20px; text-align: left; border: 1px solid #f1f5f9; }
        .info-box h3 { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .info-box p { font-size: 13px; color: #6b7280; margin-bottom: 0; }
        
        .logo { margin-bottom: 40px; font-weight: 800; font-size: 24px; color: #111827; letter-spacing: -0.5px; }
        .logo span { color: #f97316; }

        @media (max-width: 640px) {
            .card { padding: 32px 24px; }
            h1 { font-size: 20px; }
            p { font-size: 14px; margin-bottom: 24px; }
            .icon-circle { width: 60px; height: 60px; margin-bottom: 24px; }
            .icon-circle i { width: 30px !important; height: 30px !important; }
            .logo img { height: 35px !important; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <img src="{{ asset('images/logo.png') }}" alt="CodeAge Logo" style="height: 45px; width: auto;">
        </div>
        
        <div class="icon-circle">
            <i data-lucide="check-circle" style="width: 40px; height: 40px;"></i>
        </div>
        
        <h1>Details Submitted Successfully</h1>
        <p>Thank you for completing your onboarding profile. Your information has been successfully received and is now pending review by our HR department.</p>
        
        <div class="divider"></div>
        
        <div class="info-box">
            <h3>Next Steps</h3>
            <p>1. Our HR team will review your documents.<br>
            2. You will receive an email once your account is activated.<br>
            3. Detailed login instructions will be provided at that time.</p>
        </div>
    </div>
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
