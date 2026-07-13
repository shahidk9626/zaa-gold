<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt_{{ $payment->receipt_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #3f50f6;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #3f50f6;
        }
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            color: #555;
        }
        .info-table {
            width: 100%;
            margin-bottom: 25px;
            border-spacing: 0;
        }
        .info-table td {
            vertical-align: top;
            width: 50%;
            padding: 5px 0;
        }
        .section-header {
            font-size: 13px;
            font-weight: bold;
            color: #3f50f6;
            text-transform: uppercase;
            margin-bottom: 8px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            width: 90%;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .details-table th {
            background-color: #f8fafc;
            border: 1px solid #dee2e6;
            padding: 10px;
            font-weight: bold;
            text-align: left;
            font-size: 12px;
        }
        .details-table td {
            border: 1px solid #dee2e6;
            padding: 10px;
            font-size: 12px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .summary-table td {
            padding: 8px 10px;
            font-size: 12px;
        }
        .summary-label {
            text-align: right;
            font-weight: bold;
            color: #555;
            width: 80%;
            border-top: 1px solid #dee2e6;
        }
        .summary-value {
            text-align: right;
            font-weight: bold;
            border-top: 1px solid #dee2e6;
        }
        .summary-grand-total-label {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            color: #3f50f6;
            background-color: #f1f3f9;
            border-top: 2px solid #3f50f6;
            border-bottom: 2px solid #3f50f6;
        }
        .summary-grand-total-value {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            color: #3f50f6;
            background-color: #f1f3f9;
            border-top: 2px solid #3f50f6;
            border-bottom: 2px solid #3f50f6;
        }
        .footer-table {
            width: 100%;
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .signature-box {
            text-align: right;
            padding-top: 40px;
            font-weight: bold;
            color: #555;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px solid #777;
            float: right;
            margin-bottom: 5px;
        }
        .qr-code-box {
            text-align: left;
        }
        .qr-code-img {
            width: 90px;
            height: 90px;
            border: 1px solid #ddd;
            padding: 3px;
        }
        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #777;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo and Invoice Header -->
        <table class="header-table" width="100%" cellspacing="0">
            <tr>
                <td>
                    <span class="company-name">ZAA GOLD</span><br>
                    <small style="color: #777; font-size: 11px;">Enterprise Bullion & Accumulation Engine</small>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <span class="receipt-title">PAYMENT RECEIPT</span><br>
                    <small>Receipt No: <strong>{{ $payment->receipt_number }}</strong></small>
                </td>
            </tr>
        </table>

        <!-- Details Grid -->
        <table class="info-table">
            <tr>
                <td>
                    <div class="section-header">Customer Details</div>
                    <strong>Name:</strong> {{ $customer->name }}<br>
                    <strong>Email:</strong> {{ $customer->email }}<br>
                    <strong>Phone:</strong> {{ $customer->customerDetail->phone_number ?? $customer->phone ?? 'N/A' }}<br>
                    <strong>Address:</strong> {{ $customer->customerDetail->address ?? 'N/A' }}, {{ $customer->customerDetail->city ?? '' }}
                </td>
                <td>
                    <div class="section-header">Booking Details</div>
                    <strong>Booking Ref:</strong> {{ $booking->booking_number }}<br>
                    <strong>Plan:</strong> {{ $plan->plan_name }}<br>
                    <strong>Gold Product:</strong> {{ $product->name }}<br>
                    <strong>Locked Rate:</strong> ₹{{ number_format($booking->locked_price_per_gram, 2) }} / g ({{ $product->gold_type }})
                </td>
            </tr>
        </table>

        <!-- Payment Breakdown details -->
        <table class="details-table" cellspacing="0">
            <thead>
                <tr>
                    <th>EMI Installment Description</th>
                    <th>Payment Mode</th>
                    <th>Reference Code</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>EMI Installment #{{ $schedule->installment_number ?? 'N/A' }}</strong><br>
                        Scheduled Due Date: {{ $schedule ? $schedule->due_date->format('d M Y') : 'N/A' }}
                    </td>
                    <td>{{ $payment->payment_mode }}</td>
                    <td>{{ $payment->transaction_reference ?? 'N/A' }}</td>
                    <td><span style="color: #24b47e; font-weight: bold;">SUCCESSFUL</span></td>
                </tr>
            </tbody>
        </table>

        <!-- Receipt Financials -->
        <table class="summary-table" cellspacing="0">
            <tr>
                <td class="summary-label">Principal Portion:</td>
                <td class="summary-value">₹{{ number_format($payment->principal_paid, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Interest / Charge Portion:</td>
                <td class="summary-value">₹{{ number_format($payment->interest_paid, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Tax / GST (Proportional):</td>
                <td class="summary-value">₹{{ number_format($payment->gst_paid, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Overdue Late Fee:</td>
                <td class="summary-value" style="color: #dc3545;">
                    {{ $payment->late_fee_paid > 0 ? '₹' . number_format($payment->late_fee_paid, 2) : '₹0.00' }}
                </td>
            </tr>
            <tr>
                <td class="summary-grand-total-label">Total Amount Paid:</td>
                <td class="summary-grand-total-value">₹{{ number_format($payment->amount_paid, 2) }}</td>
            </tr>
        </table>

        <!-- Footer Grid: Signature & QR Code -->
        <table class="footer-table" width="100%">
            <tr>
                <td width="50%" style="vertical-align: top;">
                    @if($qrImageSrc)
                        <div class="qr-code-box">
                            <img src="{{ $qrImageSrc }}" class="qr-code-img" alt="Verification QR"><br>
                            <small style="color: #777; font-size: 10px;">Scan to verify receipt authenticity</small>
                        </div>
                    @endif
                </td>
                <td width="50%" style="vertical-align: bottom;">
                    <div class="signature-box">
                        <div class="signature-line"></div><br>
                        Authorized Signatory<br>
                        <small style="font-weight: normal; color: #888;">Paid Date: {{ $payment->payment_date->format('d M Y, h:i A') }}</small>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            This is a computer generated tax receipt and requires no physical signature under the Gold Accumulation Plan.<br>
            Thank you for choosing ZAA GOLD.
        </div>
    </div>
</body>
</html>
