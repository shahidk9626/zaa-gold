<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .header {
            background: linear-gradient(135deg, #d4af37 0%, #aa7c11 100%);
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .content {
            padding: 40px 35px;
            color: #334155;
            line-height: 1.7;
        }
        .content h2 {
            font-size: 20px;
            color: #1e293b;
            margin-top: 0;
            font-weight: 700;
        }
        .welcome-msg {
            font-size: 15px;
            color: #475569;
            margin-bottom: 25px;
        }
        .otp-container {
            background-color: #faf9f6;
            border: 1px solid #e8e2d5;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 800;
            color: #aa7c11;
            letter-spacing: 6px;
            margin: 10px 0;
            font-family: 'Courier New', Courier, monospace;
        }
        .otp-expiry {
            font-size: 13px;
            color: #8a7355;
            font-weight: 500;
            margin-top: 10px;
        }
        .security-note {
            background-color: #fff7ed;
            border-left: 4px solid #f97316;
            padding: 15px;
            margin-top: 30px;
            font-size: 13px;
            color: #9a3412;
            border-radius: 4px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 8px 0;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin-top: 0;
                margin-bottom: 0;
                border-radius: 0;
            }
            .content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ZAA GOLD</h1>
        </div>
        <div class="content">
            <h2>Hello {{ $user->name }},</h2>
            
            @if ($purpose === 'registration')
                <p class="welcome-msg">Thank you for registering with ZAA GOLD! To complete your account activation, please use the following 6-digit One-Time Password (OTP):</p>
            @else
                <p class="welcome-msg">We received a request to reset your password. Please use the following 6-digit One-Time Password (OTP) to proceed:</p>
            @endif

            <div class="otp-container">
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-expiry">This OTP is valid for 10 minutes and can only be used once.</div>
            </div>

            <div class="security-note">
                <strong>Important Security Notice:</strong> If you did not initiate this request, please ignore this email or contact support immediately. Do not share this OTP with anyone.
            </div>

            <p style="margin-top: 30px;">Regards,<br><strong>Team ZAA GOLD</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} ZAA GOLD. All rights reserved.</p>
            <p>Support Contact: support@zaagold.com | +91 98765 43210</p>
        </div>
    </div>
</body>
</html>
