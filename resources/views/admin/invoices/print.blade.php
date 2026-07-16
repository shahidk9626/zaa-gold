<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print_Invoice_{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .no-print-bar {
            background-color: #f8fafc;
            border: 1px solid #dee2e6;
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 0;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-primary {
            color: #fff;
            background-color: #3f50f6;
            border-color: #2e40e2;
        }
        .btn-secondary {
            color: #333;
            background-color: #fff;
            border-color: #ccc;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #3f50f6;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #3f50f6;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            color: #444;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            vertical-align: top;
            width: 50%;
            padding: 4px 0;
        }
        .section-header {
            font-size: 12px;
            font-weight: bold;
            color: #3f50f6;
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
        }
        .details-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .summary-table td {
            padding: 6px 8px;
        }
        .summary-label {
            text-align: right;
            font-weight: bold;
            color: #555;
            width: 75%;
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
            font-size: 13px;
            color: #3f50f6;
            background-color: #f1f3f9;
            border-top: 2px solid #3f50f6;
            border-bottom: 2px solid #3f50f6;
        }
        .summary-grand-total-value {
            text-align: right;
            font-weight: bold;
            font-size: 13px;
            color: #3f50f6;
            background-color: #f1f3f9;
            border-top: 2px solid #3f50f6;
            border-bottom: 2px solid #3f50f6;
        }
        .footer-table {
            width: 100%;
            margin-top: 20px;
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
        .terms-box {
            font-size: 11px;
            color: #666;
            margin-top: 15px;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 4px;
            background-color: #fafafa;
        }
        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #777;
            margin-top: 25px;
        }
        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Header controls -->
        <div class="no-print-bar">
            <span>Tax Invoice Print Preview</span>
            <div>
                <button onclick="window.print()" class="btn btn-primary">Print Now</button>
                <button onclick="window.close()" class="btn btn-secondary">Close Window</button>
            </div>
        </div>

        <!-- Logo and Invoice Header -->
        <table class="header-table" width="100%" cellspacing="0">
            <tr>
                <td>
                    <span class="company-name">ZAA GOLD</span><br>
                    <span style="font-size: 11px; color: #555;">
                        GSTIN: <strong>27ZGOLD1234F1Z9</strong><br>
                        Regd. Address: 121 Bullion Chambers, BKC, Mumbai, MH - 400051<br>
                        Email: accounts@zaagold.com | Support: +91 22 8888 7777
                    </span>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <span class="invoice-title">TAX INVOICE</span><br>
                    <span style="font-size: 11px;">
                        Invoice No: <strong>{{ $invoice->invoice_number }}</strong><br>
                        Invoice Date: <strong>{{ $invoice->invoice_date->format('d M Y') }}</strong><br>
                        @if($invoice->invoice_status === 'Cancelled')
                            <span style="color: #dc3545; font-weight: bold; font-size: 13px;">CANCELLED</span>
                        @endif
                    </span>
                </td>
            </tr>
        </table>

        <!-- Details Grid -->
        <table class="info-table" width="100%">
            <tr>
                <td width="50%">
                    <div class="section-header">Billing Details (Customer)</div>
                    <strong>Name:</strong> {{ $invoice->customer_name }}<br>
                    <strong>Email:</strong> {{ $invoice->customer_email }}<br>
                    <strong>Phone:</strong> {{ $invoice->customer_phone }}<br>
                    <strong>Address:</strong> {{ $invoice->billing_address }}
                </td>
                <td width="50%">
                    <div class="section-header">Reference Specifications</div>
                    <strong>Booking Ref:</strong> {{ $invoice->booking->booking_number }}<br>
                    <strong>Plan:</strong> {{ $invoice->booking->emiPlan->plan_name }} ({{ $invoice->booking->duration_months }} Months)<br>
                    <strong>Gold Product:</strong> {{ $invoice->product_name }}<br>
                    <strong>Locked Price:</strong> ₹{{ number_format($invoice->locked_gold_price, 2) }} / g (Purity: {{ number_format($invoice->gold_purity, 2) }}%)
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="details-table" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th width="50%">Description of Supplies</th>
                    <th width="15%" class="text-right">Gold Weight</th>
                    <th width="15%" class="text-right">Taxable Subtotal</th>
                    <th width="20%" class="text-right">Tax Breakdown</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>EMI repayment installment portion</strong><br>
                        Includes proportional principal gold allocation and finance charges.<br>
                        <small style="color: #666;">Payment ID: {{ $invoice->payment->payment_number }} (Receipt: {{ $invoice->payment->receipt_number }})</small>
                    </td>
                    <td style="text-align: right;">{{ number_format($invoice->gold_weight, 2) }}g</td>
                    <td style="text-align: right;">₹{{ number_format($invoice->subtotal, 2) }}</td>
                    <td style="text-align: right;">
                        @if($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0)
                            CGST [{{ number_format($invoice->cgst_percent, 2) }}%]: ₹{{ number_format($invoice->cgst_amount, 2) }}<br>
                            SGST [{{ number_format($invoice->sgst_percent, 2) }}%]: ₹{{ number_format($invoice->sgst_amount, 2) }}
                        @else
                            IGST [{{ number_format($invoice->igst_percent, 2) }}%]: ₹{{ number_format($invoice->igst_amount, 2) }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Financial Summary -->
        <table class="summary-table" cellspacing="0" width="100%">
            <tr>
                <td class="summary-label">Gold Allocation Value:</td>
                <td class="summary-value">₹{{ number_format($invoice->gold_value, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Finance Charge (Non-Taxable):</td>
                <td class="summary-value">₹{{ number_format($invoice->finance_charge, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Storage Charge (Taxable):</td>
                <td class="summary-value">₹{{ number_format($invoice->storage_charge, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Taxable Subtotal (Before GST):</td>
                <td class="summary-value">₹{{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            
            <!-- GST Breakdown -->
            @if($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0)
                <tr>
                    <td class="summary-label">Central Tax (CGST):</td>
                    <td class="summary-value">₹{{ number_format($invoice->cgst_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="summary-label">State Tax (SGST):</td>
                    <td class="summary-value">₹{{ number_format($invoice->sgst_amount, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td class="summary-label">Integrated Tax (IGST):</td>
                    <td class="summary-value">₹{{ number_format($invoice->igst_amount, 2) }}</td>
                </tr>
            @endif

            <tr>
                <td class="summary-grand-total-label">Invoice Grand Total (Inclusive of Taxes):</td>
                <td class="summary-grand-total-value">₹{{ number_format($invoice->grand_total, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label" style="border-top: none; color: #24b47e;">Amount Paid / Received:</td>
                <td class="summary-value" style="border-top: none; color: #24b47e;">₹{{ number_format($invoice->payment_received, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label" style="border-top: none; color: #dc3545;">Balance Outstanding:</td>
                <td class="summary-value" style="border-top: none; color: #dc3545;">₹{{ number_format($invoice->balance_amount, 2) }}</td>
            </tr>
        </table>

        <!-- Amount In Words -->
        <div style="font-weight: bold; margin-bottom: 20px; font-size: 11px;">
            Amount in Words: <span style="text-transform: capitalize; color: #3f50f6;">{{ app(App\Services\InvoiceService::class)->convertAmountToWords($invoice->grand_total) }}</span>
        </div>

        <!-- Terms & Conditions -->
        <div class="terms-box">
            <strong>Terms & Conditions:</strong><br>
            1. This document acts as an official Tax Invoice for gold accumulation and processing fees under applicable GST laws.<br>
            2. Gold weight allocation is subject to price lock guarantees specified in the certificate.<br>
            3. All disputes are subject to BKC Mumbai jurisdiction.
        </div>

        <!-- Footer Grid: Signature & QR Code -->
        <table class="footer-table" width="100%">
            <tr>
                <td width="50%" style="vertical-align: top;">
                    @if($invoice->qr_code && Storage::disk('public')->exists($invoice->qr_code))
                        <div class="qr-code-box">
                            <img src="{{ asset('storage/' . $invoice->qr_code) }}" class="qr-code-img" alt="Verification QR"><br>
                            <small style="color: #777; font-size: 9px;">Scan to verify tax invoice details</small>
                        </div>
                    @endif
                </td>
                <td width="50%" style="vertical-align: bottom;">
                    <div class="signature-box">
                        <div class="signature-line"></div><br>
                        Authorized Signatory for ZAA GOLD<br>
                        <small style="font-weight: normal; color: #888;">Generated At: {{ now()->format('d M Y, h:i A') }}</small>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            This is a computer generated document and requires no physical signature.<br>
            Thank you for investing with ZAA GOLD.
        </div>
    </div>

    <script>
        // Trigger print dialog automatically after document is loaded
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
