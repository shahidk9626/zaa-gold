<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ZAA GOLD</title>
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
            padding: 50px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
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
            font-size: 22px;
            color: #1e293b;
            margin-top: 0;
            font-weight: 700;
        }
        .welcome-msg {
            font-size: 16px;
            color: #475569;
            margin-bottom: 25px;
        }
        .info-card {
            background-color: #faf9f6;
            border: 1px solid #e8e2d5;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        .info-item {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #e8e2d5;
            padding-bottom: 10px;
        }
        .info-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .label {
            font-weight: 600;
            color: #8a7355;
            font-size: 14px;
            text-transform: uppercase;
        }
        .value {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
        }
        .button-container {
            text-align: center;
            margin-top: 35px;
            margin-bottom: 20px;
        }
        .button {
            background: linear-gradient(135deg, #d4af37 0%, #aa7c11 100%);
            color: #ffffff !important;
            padding: 16px 35px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
            font-size: 16px;
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
            <p class="welcome-msg">Welcome to <strong>ZAA GOLD</strong>! We are thrilled to have you onboard as a valued customer.</p>
            
            <p>Your customer account has been registered successfully. You can log in to your portal using the details below:</p>

            <div class="info-card">
                <div class="info-item">
                    <span class="label">Registered Email</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Customer ID</span>
                    <span class="value">{{ $customerId }}</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $loginUrl }}" class="button">Log In to Customer Portal</a>
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
