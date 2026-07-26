<!DOCTYPE html>
<html dir="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Code</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 12px; padding: 32px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .otp { font-size: 36px; letter-spacing: 8px; font-weight: 700; color: #1a1a1a; margin: 24px 0; }
        .hint { color: #666; font-size: 14px; margin-top: 16px; }
        .footer { color: #999; font-size: 12px; margin-top: 24px; border-top: 1px solid #eee; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verification Code</h2>
        <p style="color: #555;">Use the code below to verify your email:</p>
        <div class="otp">{{ $otp }}</div>
        <p class="hint">This code expires in 5 minutes.</p>
        <p style="color: #888; font-size: 13px;">If you did not request this, please ignore this email.</p>
        <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
    </div>
</body>
</html>
