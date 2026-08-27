<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Gold Inspection Enquiry</title>
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
            font-size: 24px;
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
        .info-card {
            background-color: #fafafa;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }
        .info-item {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 10px;
        }
        .info-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .label {
            font-weight: 600;
            color: #64748b;
            font-size: 13px;
            text-transform: uppercase;
        }
        .value {
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
            text-align: right;
            max-width: 70%;
        }
        .button-container {
            text-align: center;
            margin-top: 30px;
        }
        .button {
            background: linear-gradient(135deg, #d4af37 0%, #aa7c11 100%);
            color: #ffffff !important;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            display: inline-block;
            font-size: 15px;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.2);
        }
        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gold Inspection Request</h1>
        </div>
        <div class="content">
            <h2>Hello Admin,</h2>
            <p>A new "Sell Gold / Gold Inspection Enquiry" request has been submitted on the landing page. Here are the details:</p>

            <div class="info-card">
                <div class="info-item">
                    <span class="label">Customer Name</span>
                    <span class="value">{{ $enquiry->name }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Phone</span>
                    <span class="value">{{ $enquiry->phone }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Address</span>
                    <span class="value">{{ $enquiry->address }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Approx. Weight</span>
                    <span class="value">{{ $enquiry->approx_grams }} Grams</span>
                </div>
                <div class="info-item">
                    <span class="label">Gold Type</span>
                    <span class="value">{{ $enquiry->gold_type }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Storage Location</span>
                    <span class="value">{{ $enquiry->gold_location }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Inspection Date</span>
                    <span class="value">{{ $enquiry->preferred_date ? $enquiry->preferred_date->format('Y-m-d') : 'N/A' }}</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ url('/admin/inspection/' . $enquiry->id) }}" class="button">View Inspection Details</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} AurOnGold Admin Portal.</p>
        </div>
    </div>
</body>
</html>
