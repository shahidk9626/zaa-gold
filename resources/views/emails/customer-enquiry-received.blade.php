<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Contacting AurOnGold</title>
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
            text-align: right;
            max-width: 70%;
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
            <h1>AurOnGold</h1>
        </div>
        <div class="content">
            <h2>Hello {{ $enquiry->name }},</h2>
            <p class="welcome-msg">Thank you for contacting AurOnGold.</p>
            
            <p>We have successfully received your enquiry. Our relationship team will review your message and contact you within 24 hours.</p>

            <div class="info-card">
                <div class="info-item">
                    <span class="label">Subject</span>
                    <span class="value">{{ $enquiry->subject }}</span>
                </div>
                <div class="info-item" style="flex-direction: column; align-items: flex-start; border-bottom: none;">
                    <span class="label" style="margin-bottom: 5px;">Message</span>
                    <span class="value" style="font-weight: normal; text-align: left; max-width: 100%;">{{ $enquiry->message }}</span>
                </div>
            </div>

            <p style="margin-top: 30px;">Regards,<br><strong>Team AurOnGold</strong><br><small>Invest Smart. Grow Secure.</small></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} AurOnGold. All rights reserved.</p>
            <p>Support Contact: support.aurongold@gmail.com | +91 7337616333</p>
        </div>
    </div>
</body>
</html>
