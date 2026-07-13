<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery_Challan_{{ $delivery->delivery_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 10px;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #24b47e;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #24b47e;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            color: #444;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-spacing: 0;
        }
        .info-table td {
            vertical-align: top;
            width: 50%;
            padding: 4px 0;
        }
        .section-header {
            font-size: 12px;
            font-weight: bold;
            color: #24b47e;
            text-transform: uppercase;
            margin-bottom: 6px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
            width: 90%;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .details-table th {
            background-color: #f8fafc;
            border: 1px solid #dee2e6;
            padding: 8px;
            font-weight: bold;
            text-align: left;
            font-size: 11px;
        }
        .details-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            font-size: 11px;
            vertical-align: top;
        }
        .footer-table {
            width: 100%;
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .signature-box {
            text-align: right;
            padding-top: 40px;
            font-weight: bold;
            color: #555;
        }
        .signature-line {
            width: 180px;
            border-bottom: 1px solid #777;
            float: right;
            margin-bottom: 4px;
        }
        .qr-code-box {
            text-align: left;
        }
        .qr-code-img {
            width: 80px;
            height: 80px;
            border: 1px solid #ddd;
            padding: 2px;
        }
        .receiver-box {
            background-color: #f9f9f9;
            border: 1px dashed #24b47e;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo and Challan Header -->
        <table class="header-table" width="100%" cellspacing="0">
            <tr>
                <td>
                    <span class="company-name">ZAA GOLD</span><br>
                    <span style="font-size: 11px; color: #555;">
                        Regd. Address: 121 Bullion Chambers, BKC, Mumbai, MH - 400051<br>
                        Email: delivery@zaagold.com | Phone: +91 22 8888 7777
                    </span>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <span class="title">DELIVERY CHALLAN</span><br>
                    <span style="font-size: 11px;">
                        Challan No: <strong>{{ $delivery->delivery_number }}</strong><br>
                        Issue Date: <strong>{{ now()->format('d M Y') }}</strong><br>
                        Delivery Method: <strong>{{ $delivery->delivery_method }}</strong>
                    </span>
                </td>
            </tr>
        </table>

        <!-- Details Grid -->
        <table class="info-table">
            <tr>
                <td>
                    <div class="section-header">Customer Details (Consignee)</div>
                    <strong>Name:</strong> {{ $customer->name }}<br>
                    <strong>Email:</strong> {{ $customer->email }}<br>
                    <strong>Phone:</strong> {{ $delivery->receiver_mobile ?? $customer->phone ?? 'N/A' }}<br>
                    <strong>Address:</strong> {{ $delivery->delivery_address ?? $customer->customerDetail->address ?? 'N/A' }}
                </td>
                <td>
                    <div class="section-header">Booking References</div>
                    <strong>Booking Ref:</strong> {{ $booking->booking_number }}<br>
                    <strong>Certificate Number:</strong> {{ $booking->certificate->certificate_number ?? 'N/A' }}<br>
                    <strong>GST Invoice Number:</strong> {{ $invoice->invoice_number ?? 'N/A' }}<br>
                    <strong>Status:</strong> <span style="text-transform: uppercase;">{{ $delivery->delivery_status }}</span>
                </td>
            </tr>
        </table>

        <!-- Receiver Details Block (If Delivered) -->
        @if($delivery->receiver_name)
        <div class="receiver-box">
            <strong style="color: #24b47e;">Handover Receipt Details:</strong><br>
            Gold accumulated has been successfully verified and picked up / received by:<br>
            <strong>Receiver's Name:</strong> {{ $delivery->receiver_name }}<br>
            <strong>Receiver's Mobile:</strong> {{ $delivery->receiver_mobile }}<br>
            <strong>Receiver's ID Proof Reference:</strong> {{ $delivery->receiver_id_proof ?? 'N/A' }}<br>
            <strong>Date of Handover:</strong> {{ $delivery->delivered_date ? $delivery->delivered_date->format('d M Y, h:i A') : '—' }}
        </div>
        @endif

        <!-- Dispatch Tracking details -->
        @if($delivery->delivery_method === 'Courier' && $delivery->tracking_number)
        <div class="receiver-box" style="border-color: #3f50f6; background-color: #f1f3f9;">
            <strong style="color: #3f50f6;">Courier Dispatch Details:</strong><br>
            <strong>Courier Partner:</strong> {{ $delivery->courier_partner }}<br>
            <strong>AWB Tracking Number:</strong> {{ $delivery->tracking_number }}<br>
            <strong>Dispatch Date:</strong> {{ $delivery->dispatch_date ? $delivery->dispatch_date->format('d M Y, h:i A') : '—' }}
        </div>
        @endif

        <!-- Items Table -->
        <table class="details-table" cellspacing="0">
            <thead>
                <tr>
                    <th width="50%">Description of Gold Supplies</th>
                    <th width="15%" class="text-right">Weight (grams)</th>
                    <th width="15%" class="text-right">Gold Purity</th>
                    <th width="20%">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $product->name }}</strong><br>
                        Gold type: {{ $product->gold_type }} (Purity Guarantee Certificate Ref: {{ $booking->certificate->certificate_number ?? 'N/A' }})
                    </td>
                    <td style="text-align: right;">{{ number_format($booking->gold_weight, 2) }}g</td>
                    <td style="text-align: right;">{{ number_format($booking->gold_purity, 2) }}%</td>
                    <td>{{ $delivery->delivery_status }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Signatures and QR -->
        <table class="footer-table" width="100%">
            <tr>
                <td width="50%" style="vertical-align: top;">
                    @if($qrImageSrc)
                        <div class="qr-code-box">
                            <img src="{{ $qrImageSrc }}" class="qr-code-img" alt="Booking Verification QR"><br>
                            <small style="color: #777; font-size: 9px;">Scan booking QR to verify certificate details</small>
                        </div>
                    @endif
                </td>
                <td width="50%" style="vertical-align: bottom;">
                    <div class="signature-box">
                        <div class="signature-line"></div><br>
                        Authorized Signatory for ZAA GOLD<br>
                        <small style="font-weight: normal; color: #888;">Issued By: {{ $generatedBy }}</small>
                    </div>
                </td>
            </tr>
        </table>

        <div style="text-align: center; font-size: 10px; color: #777; margin-top: 35px;">
            This challan serves as confirmation of physical gold handover. ZAA GoldBKC Mumbai office.
        </div>
    </div>
</body>
</html>
