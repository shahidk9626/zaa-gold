<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Staff Credentials</title>
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
            background: linear-gradient(135deg, #3f50f6 0%, #ff3ca6 100%);
            padding: 50px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
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
        .credential-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        .credential-item {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 10px;
        }
        .credential-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .label {
            font-weight: 600;
            color: #64748b;
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
            background: linear-gradient(135deg, #3f50f6 0%, #ff3ca6 100%);
            color: #ffffff !important;
            padding: 16px 35px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(63, 80, 246, 0.3);
            font-size: 16px;
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
            <p class="welcome-msg">We are excited to welcome you to our team as a <strong>{{ $roleName }}</strong>.</p>
            
            <p>Your account has been created successfully and you can now access the staff portal using the credentials below.</p>
            <p>We are happy to onboard you and look forward to your contribution and success with us.</p>

            <div class="credential-card">
                <div class="credential-item">
                    <span class="label">Login Email</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                <div class="credential-item">
                    <span class="label">Temporary Password</span>
                    <span class="value" style="color: #3f50f6; font-family: monospace;">{{ $password }}</span>
                </div>
                <div class="credential-item">
                    <span class="label">Staff ID</span>
                    <span class="value">{{ $user->staffDetail->emp_code ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $loginUrl }}" class="button">Login to Portal</a>
            </div>

            <div class="security-note">
                <strong>Important Security Note:</strong> For your security, please change your password immediately after your first login. Do not share these credentials with anyone.
            </div>

            <p style="margin-top: 30px;">Regards,<br><strong>Team ZAA GOLD</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} ZAA GOLD. All rights reserved.</p>
            <p>If you have any issues logging in, please contact IT Support.</p>
        </div>
    </div>
</body>
</html>
