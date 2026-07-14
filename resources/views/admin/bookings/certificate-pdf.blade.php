<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Price Lock Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #2d3748;
            margin: 0;
            padding: 20px;
            background-color: #fff;
            font-size: 10px;
        }
        .border-container {
            border: 10px solid #d4af37; /* Metallic Gold border */
            padding: 20px;
            min-height: 93%;
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px double #d4af37;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1a202c;
            letter-spacing: 2px;
        }
        .company-subtitle {
            font-size: 9px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 3px;
        }
        .certificate-title {
            font-size: 18px;
            font-weight: bold;
            color: #b7791f; /* Gold text */
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin: 10px 0;
        }
        .certificate-subtitle {
            text-align: center;
            font-size: 10px;
            color: #4a5568;
            margin-bottom: 15px;
            font-style: italic;
        }
        .section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .section-table td {
            padding: 4px 10px;
            vertical-align: top;
            width: 50%;
        }
        .label {
            font-size: 8px;
            color: #718096;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 1px;
        }
        .value {
            font-size: 10px;
            font-weight: 500;
            color: #1a202c;
        }
        .gold-box {
            background-color: #fefcbf; /* Light yellow box */
            border: 1px solid #ecc94b;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .gold-box-table {
            width: 100%;
            border-collapse: collapse;
        }
        .gold-box-table td {
            padding: 2px 5px;
            width: 25%;
            text-align: center;
        }
        .gold-box-label {
            font-size: 8px;
            color: #b7791f;
            text-transform: uppercase;
            font-weight: bold;
        }
        .gold-box-value {
            font-size: 11px;
            font-weight: bold;
            color: #744210;
            margin-top: 2px;
        }
        .details-header {
            font-size: 11px;
            font-weight: bold;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .summary-table td {
            padding: 4px 10px;
            border-bottom: 1px solid #edf2f7;
            font-size: 9px;
        }
        .summary-table tr.total-row td {
            background-color: #f7fafc;
            border-top: 1px solid #cbd5e0;
            border-bottom: 2px solid #cbd5e0;
            font-weight: bold;
            font-size: 10px;
        }
        .terms {
            font-size: 7.5px;
            color: #718096;
            line-height: 1.35;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 15px;
            width: 75%;
        }
        .qr-section {
            position: absolute;
            bottom: 20px;
            right: 20px;
            text-align: center;
            width: 100px;
        }
        .qr-image {
            width: 70px;
            height: 70px;
            border: 1px solid #cbd5e0;
            padding: 2px;
            background-color: #fff;
        }
        .qr-label {
            font-size: 7px;
            color: #718096;
            text-transform: uppercase;
            margin-top: 3px;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .footer {
            font-size: 7.5px;
            color: #a0aec0;
            text-align: center;
            margin-top: 10px;
        }
        .signature-table {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 5px;
        }
        .signature-line {
            border-top: 1px solid #cbd5e0;
            text-align: center;
            padding-top: 3px;
            font-size: 8px;
            color: #718096;
            width: 180px;
        }
    </style>
</head>
<body>

    <div class="border-container">
        
        <!-- Header -->
        <div class="header">
            <div class="company-name">ZAA GOLD</div>
            <div class="company-subtitle">Premium Bullion Trading & Gold Price Lock Services</div>
        </div>

        <!-- Title -->
        <div class="certificate-title">Price Lock Certificate</div>
        <div class="certificate-subtitle">This certificate guarantees the lock-in of gold price for the transaction detailed below.</div>

        <!-- Meta Grid -->
        <table class="section-table">
            <tr>
                <td>
                    <div class="label">Certificate Number</div>
                    <div class="value" style="font-weight: bold; color: #b7791f;">{{ $certificate->certificate_number }}</div>
                </td>
                <td>
                    <div class="label">Booking Number</div>
                    <div class="value" style="font-weight: bold;">{{ $booking->booking_number }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Date of Issue</div>
                    <div class="value">{{ $certificate->issued_at->format('d M Y, h:i A') }}</div>
                </td>
                <td>
                    <div class="label">Estimated Plan Completion</div>
                    <div class="value">{{ $booking->estimated_completion_date->format('d M Y') }}</div>
                </td>
            </tr>
        </table>

        <!-- Locked Parameters Box -->
        <div class="gold-box">
            <table class="gold-box-table">
                <tr>
                    <td>
                        <div class="gold-box-label">Gold Purity</div>
                        <div class="gold-box-value">{{ number_format($booking->gold_purity, 2) }}% [{{ $product->gold_type }}]</div>
                    </td>
                    <td>
                        <div class="gold-box-label">Locked Price / Gram</div>
                        <div class="gold-box-value">&#8377;{{ number_format($certificate->locked_price, 2) }}</div>
                    </td>
                    <td>
                        <div class="gold-box-label">Gold Weight</div>
                        <div class="gold-box-value">{{ number_format($certificate->gold_weight, 2) }}g</div>
                    </td>
                    <td>
                        <div class="gold-box-label">Monthly EMI</div>
                        <div class="gold-box-value">&#8377;{{ number_format($booking->monthly_emi, 2) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Details Tables -->
        <div class="details-header">Customer & Product Details</div>
        <table class="section-table" style="margin-bottom: 10px;">
            <tr>
                <td>
                    <div class="label">Customer Name</div>
                    <div class="value">{{ $customer->name }}</div>
                    
                    <div class="label" style="margin-top: 8px;">Customer Email</div>
                    <div class="value">{{ $customer->email }}</div>

                    <div class="label" style="margin-top: 8px;">Customer Phone</div>
                    <div class="value">{{ $customer->customerDetail->phone_number ?? $customer->phone ?? 'N/A' }}</div>
                </td>
                <td>
                    <div class="label">Product Name</div>
                    <div class="value">{{ $product->name }}</div>
                    
                    <div class="label" style="margin-top: 8px;">Product SKU</div>
                    <div class="value">{{ $product->sku }}</div>

                    <div class="label" style="margin-top: 8px;">EMI Plan Name</div>
                    <div class="value">{{ $plan->plan_name }} ({{ $plan->duration_months }} Months)</div>
                </td>
            </tr>
        </table>

        <!-- Financial Summary -->
        <div class="details-header">Locked Financial Summary</div>
        <table class="summary-table">
            <tr>
                <td>Base Gold/Product Value</td>
                <td style="text-align: right;">&#8377;{{ number_format($calculations['gold_value'], 2) }}</td>
            </tr>
            @if($calculations['use_financial_engine'])
                @if($booking->gst_on_gold_amount > 0)
                <tr>
                    <td>GST on Gold ({{ number_format($booking->gst_on_gold_percent, 2) }}%)</td>
                    <td style="text-align: right;">&#8377;{{ number_format($booking->gst_on_gold_amount, 2) }}</td>
                </tr>
                @endif
                @if($booking->finance_charge_amount > 0)
                <tr>
                    <td>Finance Charge ({{ number_format($booking->finance_charge_percent, 2) }}%)</td>
                    <td style="text-align: right;">&#8377;{{ number_format($booking->finance_charge_amount, 2) }}</td>
                </tr>
                @endif
                @if($booking->storage_charge_amount > 0)
                <tr>
                    <td>Storage / Insurance / Price Lock Charge ({{ number_format($booking->storage_charge_percent, 2) }}%)</td>
                    <td style="text-align: right;">&#8377;{{ number_format($booking->storage_charge_amount, 2) }}</td>
                </tr>
                @endif
                @if($booking->gst_on_charges_amount > 0)
                <tr>
                    <td>GST on Charges ({{ number_format($booking->gst_on_charges_percent, 2) }}%)</td>
                    <td style="text-align: right;">&#8377;{{ number_format($booking->gst_on_charges_amount, 2) }}</td>
                </tr>
                @endif
            @else
                <tr>
                    <td>Calculated Processing Fee</td>
                    <td style="text-align: right;">&#8377;{{ number_format($booking->processing_fee, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Plan Interest ({{ number_format($plan->interest_rate, 2) }}% {{ strtoupper($plan->interest_type) }})</td>
                    <td style="text-align: right;">&#8377;{{ number_format($booking->grand_total - $booking->locked_gold_value, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td>Grand Total (Price Locked)</td>
                <td style="text-align: right; color: #b7791f;">&#8377;{{ number_format($certificate->grand_total, 2) }}</td>
            </tr>
        </table>

        <!-- Signatures Area -->
        <table class="signature-table">
            <tr>
                <td>
                    <div style="height: 40px;"></div>
                    <div class="signature-line">Authorized Signatory<br>ZAA Gold Bullion LLC</div>
                </td>
                <td style="text-align: right; width: 50%;">
                    <div style="height: 40px;"></div>
                    <div class="signature-line" style="margin-left: auto;">Verified Customer Signature<br>{{ $customer->name }}</div>
                </td>
            </tr>
        </table>

        <!-- Terms and Conditions -->
        <div class="terms">
            <strong>Terms & Conditions:</strong><br>
            1. This certificate guarantees that the gold price of &#8377;{{ number_format($certificate->locked_price, 2) }} per gram is locked permanently for this transaction.<br>
            2. The locked gold price is valid for the entire plan duration of {{ $booking->duration_months }} months and cannot change regardless of subsequent market movements.<br>
            3. The customer agrees to pay the monthly EMI installment of &#8377;{{ number_format($booking->monthly_emi, 2) }} before the scheduled due dates.<br>
            4. This certificate is valid only when verified through the authorized ZAA Gold system using the embedded QR verification token.
        </div>

        <!-- QR Code Section -->
        @if(!empty($qrImageSrc))
        <div class="qr-section">
            <img class="qr-image" src="{{ $qrImageSrc }}" alt="QR Code">
            <div class="qr-label">Scan to Verify<br>Price Lock Certificate</div>
        </div>
        @endif

    </div>

    <!-- Footer -->
    <div class="footer">
        Generated At: {{ $generatedAt }} | Generated By: {{ $generatedBy }} | Token: {{ $certificate->verification_token }}
    </div>

</body>
</html>
