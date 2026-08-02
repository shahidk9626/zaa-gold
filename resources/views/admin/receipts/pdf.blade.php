@php
    // Get cumulative calculations for the customer receipt
    $allPayments = \App\Models\BookingPayment::where('booking_id', $booking->id)
        ->where('status', 'Paid')
        ->where('payment_date', '<=', $payment->payment_date)
        ->get();
    
    $totalPaidTillDate = $allPayments->sum('amount_paid');
    $remainingBalance = max(0, $booking->grand_total - $totalPaidTillDate);
    
    $completedPaymentsCount = \App\Models\BookingPayment::where('booking_id', $booking->id)
        ->where('status', 'Paid')
        ->where('payment_date', '<=', $payment->payment_date)
        ->count();
    
    $completionPercentage = $booking->grand_total > 0 ? ($totalPaidTillDate / $booking->grand_total) * 100 : 0;
    
    $nextEmi = \App\Models\BookingEmiSchedule::where('booking_id', $booking->id)
        ->where('status', 'Pending')
        ->orderBy('due_date', 'asc')
        ->first();

    $currentInstallmentNumber = $payment->emiSchedule->installment_number ?? 1;

    // Masked PAN & Aadhaar details safely
    $panMasked = 'N/A';
    if (!empty($customer->customerDetail->pan_number)) {
        $pan = trim($customer->customerDetail->pan_number);
        if (strlen($pan) >= 4) {
            $panMasked = substr($pan, 0, 2) . '******' . substr($pan, -2);
        } else {
            $panMasked = $pan;
        }
    }
    
    $aadharMasked = 'N/A';
    if (!empty($customer->customerDetail->aadhar_number)) {
        $aadhar = trim($customer->customerDetail->aadhar_number);
        if (strlen($aadhar) >= 4) {
            $aadharMasked = '********' . substr($aadhar, -4);
        } else {
            $aadharMasked = $aadhar;
        }
    }

    $addressParts = [];
    if (!empty($customer->customerDetail->address)) $addressParts[] = $customer->customerDetail->address;
    if (!empty($customer->customerDetail->city)) $addressParts[] = $customer->customerDetail->city;
    if (!empty($customer->customerDetail->state)) $addressParts[] = $customer->customerDetail->state;
    if (!empty($customer->customerDetail->pincode)) $addressParts[] = $customer->customerDetail->pincode;
    $fullAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';

    $verificationCode = strtoupper(substr(md5($payment->receipt_number . ($customer->email ?? '')), 0, 8));
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt_{{ $payment->receipt_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 12mm 10mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #1e1b15;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .outer-frame {
            border: 2px solid #b4831b;
            border-radius: 8px;
            padding: 15px;
            background-color: #ffffff;
            position: relative;
        }
        table {
            border-collapse: collapse;
        }
        .header-table {
            width: 100%;
            margin-bottom: 12px;
        }
        .logo-img {
            height: 48px;
            width: auto;
        }
        .header-title-container {
            text-align: center;
        }
        .header-divider {
            color: #b4831b;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 2px;
        }
        .receipt-main-title {
            font-size: 15px;
            font-weight: bold;
            color: #1e1b15;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .receipt-subtitle {
            font-size: 8.5px;
            color: #555555;
            margin: 2px 0 0 0;
            font-style: italic;
            line-height: 1.25;
        }
        
        /* Summary Grid */
        .summary-grid {
            width: 100%;
            margin-bottom: 12px;
        }
        .summary-card {
            border: 1px solid #e8e2d2;
            padding: 6px 8px;
            background-color: #fcfaf5;
            border-radius: 4px;
        }
        .card-label {
            font-size: 7.5px;
            color: #777777;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .card-value {
            font-size: 10px;
            color: #1e1b15;
            font-weight: bold;
        }
        .status-badge {
            color: #24b47e;
            font-weight: bold;
        }

        /* Detail Blocks styling */
        .section-table {
            width: 100%;
            margin-bottom: 12px;
        }
        .section-header-bar {
            background-color: #b4831b;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 3px;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .details-box {
            border: 1px solid #e8e2d2;
            border-radius: 4px;
            padding: 8px;
            height: 125px; /* Fixed height for clean alignment */
            background-color: #ffffff;
        }
        .details-subtable {
            width: 100%;
            font-size: 9px;
        }
        .details-subtable td {
            padding: 2.5px 0;
            vertical-align: top;
        }

        /* Notice Bar */
        .notice-bar {
            background-color: #fffdf5;
            border: 1px solid #e8dfc4;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 9px;
            text-align: center;
            color: #8a6d1c;
            font-weight: bold;
            margin-bottom: 12px;
        }

        /* Financial summary columns */
        .financial-box {
            border: 1px solid #e8e2d2;
            border-radius: 4px;
            padding: 8px;
            height: 135px; /* Fixed height for clean alignment */
            background-color: #ffffff;
        }
        .financial-table {
            width: 100%;
            font-size: 8.5px;
        }
        .financial-table td {
            padding: 3.5px 0;
        }
        .highlight-row {
            background-color: #faf5eb;
            font-weight: bold;
            color: #78350f;
            border-top: 1px solid #b4831b;
            border-bottom: 1px solid #b4831b;
        }
        .highlight-row td {
            padding: 4px 4px;
        }

        /* Progress Panel */
        .progress-box {
            border: 1px solid #e8e2d2;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 12px;
            background-color: #ffffff;
        }
        .progress-bar-container {
            background-color: #e9ecef;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }
        .progress-bar-fill {
            background: #24b47e;
            height: 100%;
            border-radius: 4px;
        }

        /* Footer columns */
        .info-footer-box {
            border: 1px solid #e8e2d2;
            border-radius: 4px;
            padding: 8px;
            height: 100px;
            background-color: #ffffff;
        }
        .notes-list {
            padding-left: 10px;
            margin: 0;
            font-size: 8px;
            color: #555555;
        }
        .notes-list li {
            margin-bottom: 3.5px;
        }
        .seal-stamp {
            position: absolute;
            top: -12px;
            right: 4px;
            width: 48px;
            height: 48px;
            border: 1px dashed #b4831b;
            border-radius: 50%;
            text-align: center;
        }
        .seal-text-small {
            font-size: 4px;
            color: #b4831b;
            font-weight: bold;
            margin-top: 8px;
        }
        .seal-text-large {
            font-size: 9px;
            color: #b4831b;
            font-weight: bold;
            margin: 1px 0;
        }

        /* Contact strip */
        .contact-strip {
            width: 100%;
            border-top: 1px solid #e8e2d2;
            padding-top: 6px;
            margin-top: 12px;
            font-size: 8px;
            color: #555555;
            text-align: center;
        }
        .contact-strip td {
            padding: 2px;
        }

        /* Bottom strip */
        .bottom-banner {
            margin-top: 8px;
            background-color: #1e1b15;
            border-top: 2px solid #b4831b;
            border-bottom: 2px solid #b4831b;
            padding: 5px;
            font-size: 9.5px;
            color: #d4af37;
            text-align: center;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="outer-frame">
        <!-- Header -->
        <table class="header-table" width="100%">
            <tr>
                <td width="30%" align="left" valign="middle">
                    @if(file_exists(public_path('assets/images/logo.png')))
                        <img src="{{ public_path('assets/images/logo.png') }}" class="logo-img" alt="AurOnGold">
                    @else
                        <span style="font-size: 20px; font-weight: bold; color: #b4831b;">AurOnGold</span>
                    @endif
                </td>
                <td width="50%" align="center" valign="middle">
                    <div class="header-title-container">
                        <div class="header-divider">❖ ─── ✦ ─── ❖</div>
                        <h1 class="receipt-main-title">EASY MONTHLY ADVANCE PAYMENT RECEIPT</h1>
                        <p class="receipt-subtitle">Thank you for your payment. This receipt confirms the successful receipt of your EMAP installment towards your AurOnGold Gold Purchase Plan.</p>
                    </div>
                </td>
                <td width="20%" align="right" valign="middle">
                    <!-- Gold trust badge ribbon seal SVG -->
                    <svg width="65" height="85" viewBox="0 0 120 160" style="display: block;">
                        <path d="M 40 100 L 25 150 L 50 135 L 60 150 L 50 100 Z" fill="#b4831b" />
                        <path d="M 80 100 L 95 150 L 70 135 L 60 150 L 70 100 Z" fill="#b4831b" />
                        <circle cx="60" cy="60" r="50" fill="#a47214" stroke="#cfa643" stroke-width="2" />
                        <circle cx="60" cy="60" r="44" fill="#1b1a18" />
                        <circle cx="60" cy="60" r="41" fill="none" stroke="#d4af37" stroke-width="1" stroke-dasharray="3,3" />
                        <text x="60" y="44" fill="#d4af37" font-size="7.5" font-family="'DejaVu Sans', sans-serif" font-weight="bold" text-anchor="middle">TRUSTED</text>
                        <text x="60" y="56" fill="#d4af37" font-size="6.5" font-family="'DejaVu Sans', sans-serif" font-weight="bold" text-anchor="middle">TRANSPARENT</text>
                        <text x="60" y="68" fill="#d4af37" font-size="7.5" font-family="'DejaVu Sans', sans-serif" font-weight="bold" text-anchor="middle">SECURE</text>
                        <text x="60" y="85" fill="#d4af37" font-size="9" font-family="'DejaVu Sans', sans-serif" text-anchor="middle">★★★</text>
                    </svg>
                </td>
            </tr>
        </table>

        <!-- Top summary bar -->
        <table class="summary-grid" width="100%" cellspacing="6">
            <tr>
                <td width="25%">
                    <div class="summary-card">
                        <div class="card-label">📄 EMAP Payment No.</div>
                        <div class="card-value">{{ $currentInstallmentNumber }} of {{ $booking->duration_months }} Payments</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="summary-card">
                        <div class="card-label">🔖 Receipt No.</div>
                        <div class="card-value">{{ $payment->receipt_number }}</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="summary-card">
                        <div class="card-label">📌 Booking ID</div>
                        <div class="card-value">{{ $booking->booking_number }}</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="summary-card">
                        <div class="card-label">📅 Payment Date & Time</div>
                        <div class="card-value" style="font-size: 8.5px;">{{ $payment->payment_date->format('d M Y | h:i A') }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="33.3%">
                    <div class="summary-card">
                        <div class="card-label">💳 Transaction ID</div>
                        <div class="card-value" style="font-size: 9px;">{{ $payment->transaction_reference ?? 'TXNAG' . $payment->id . time() }}</div>
                    </div>
                </td>
                <td width="33.3%">
                    <div class="summary-card">
                        <div class="card-label">💼 Payment Method</div>
                        <div class="card-value">{{ $payment->payment_mode }}</div>
                    </div>
                </td>
                <td width="33.3%" colspan="2">
                    <div class="summary-card">
                        <div class="card-label">✔ Payment Status</div>
                        <div class="card-value status-badge">
                            <!-- Success Checkmark SVG -->
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#24b47e" stroke-width="4" style="display: inline-block; vertical-align: middle; margin-right: 2px;">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Successful
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Row 1 Details -->
        <table class="section-table" width="100%" cellspacing="0">
            <tr>
                <!-- Customer details -->
                <td width="48%" valign="top">
                    <div class="details-box">
                        <div class="section-header-bar">👤 1. CUSTOMER DETAILS</div>
                        <table class="details-subtable">
                            <tr>
                                <td width="32%"><strong>Customer Name</strong></td>
                                <td width="5%">:</td>
                                <td>{{ $customer->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Customer ID</strong></td>
                                <td>:</td>
                                <td>{{ $customer->customerDetail->customer_code ?? 'AGCUST' . str_pad($customer->id, 6, '0', STR_PAD_LEFT) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Mobile Number</strong></td>
                                <td>:</td>
                                <td>{{ $customer->customerDetail->phone_number ?? $customer->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Email Address</strong></td>
                                <td>:</td>
                                <td style="font-size: 8px; word-break: break-all;">{{ $customer->email }}</td>
                            </tr>
                            <tr>
                                <td><strong>Billing Address</strong></td>
                                <td>:</td>
                                <td style="font-size: 8px; line-height: 1.2;">{{ $fullAddress }}</td>
                            </tr>
                            <tr>
                                <td><strong>PAN (Masked)</strong></td>
                                <td>:</td>
                                <td>{{ $panMasked }}</td>
                            </tr>
                            <tr>
                                <td><strong>Aadhaar (Masked)</strong></td>
                                <td>:</td>
                                <td>{{ $aadharMasked }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="4%">&nbsp;</td>
                <!-- Plan details -->
                <td width="48%" valign="top">
                    <div class="details-box">
                        <div class="section-header-bar">📦 2. GOLD PLAN & PURCHASE DETAILS</div>
                        <table class="details-subtable">
                            <tr>
                                <td width="45%"><strong>Gold Product</strong></td>
                                <td width="5%">:</td>
                                <td>{{ $product->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gold Purity</strong></td>
                                <td>:</td>
                                <td>{{ $product->gold_purity ?? '24KT (999.9) Fine Gold' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gold Weight Purchased</strong></td>
                                <td>:</td>
                                <td>{{ number_format($booking->gold_weight, 3) }} g</td>
                            </tr>
                            <tr>
                                <td><strong>Locked Gold Price (per g)</strong></td>
                                <td>:</td>
                                <td>₹{{ number_format($booking->locked_price_per_gram, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Gold Value</strong></td>
                                <td>:</td>
                                <td>₹{{ number_format($booking->locked_gold_value, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Booking Date</strong></td>
                                <td>:</td>
                                <td>{{ $booking->booking_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Plan Type</strong></td>
                                <td>:</td>
                                <td>{{ $booking->duration_months }} Months EMAP Plan</td>
                            </tr>
                            <tr>
                                <td><strong>Plan Completion Date</strong></td>
                                <td>:</td>
                                <td>{{ $booking->booking_date->copy()->addMonths($booking->duration_months)->format('d M Y') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Notice Bar -->
        <div class="notice-bar">
            ✔ Your gold price remains locked as per your selected plan and is protected from future market price fluctuations.
        </div>

        <!-- Row 2 Financials -->
        <table class="section-table" width="100%" cellspacing="0">
            <tr>
                <!-- Payment Summary -->
                <td width="48%" valign="top">
                    <div class="financial-box">
                        <div class="section-header-bar">₹ 3. PAYMENT SUMMARY</div>
                        <table class="financial-table">
                            <tr>
                                <td width="65%"><strong>EMAP Amount ({{ $currentInstallmentNumber }} of {{ $booking->duration_months }})</strong></td>
                                <td width="5%">:</td>
                                <td align="right"><strong>₹{{ number_format($payment->amount_paid, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Total Gold Value</td>
                                <td>:</td>
                                <td align="right">₹{{ number_format($booking->locked_gold_value, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Total Applicable Charges (All Included)</td>
                                <td>:</td>
                                <td align="right">₹{{ number_format($booking->grand_total - $booking->locked_gold_value, 2) }}</td>
                            </tr>
                            <tr class="highlight-row">
                                <td>TOTAL PLAN VALUE (GOLD + CHARGES)</td>
                                <td>:</td>
                                <td align="right">₹{{ number_format($booking->grand_total, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="color: #24b47e;"><strong>Amount Received This Month</strong></td>
                                <td style="color: #24b47e;">:</td>
                                <td align="right" style="color: #24b47e;"><strong>₹{{ number_format($payment->amount_paid, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td style="color: #24b47e;"><strong>Total Amount Paid Till Date</strong></td>
                                <td style="color: #24b47e;">:</td>
                                <td align="right" style="color: #24b47e;"><strong>₹{{ number_format($totalPaidTillDate, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td style="color: #dc3545;"><strong>Balance Amount Remaining</strong></td>
                                <td style="color: #dc3545;">:</td>
                                <td align="right" style="color: #dc3545;"><strong>₹{{ number_format($remainingBalance, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="4%">&nbsp;</td>
                <!-- Plan Value Breakdown -->
                <td width="48%" valign="top">
                    <div class="financial-box">
                        <div class="section-header-bar">📋 4. PLAN VALUE BREAKDOWN (ALL CHARGES INCLUDED)</div>
                        <table class="financial-table">
                            <tr>
                                <td width="65%">Gold Value ({{ number_format($booking->gold_weight, 3) }} g @ ₹{{ number_format($booking->locked_price_per_gram, 2) }})</td>
                                <td width="5%">:</td>
                                <td align="right">₹{{ number_format($booking->locked_gold_value, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Price Locking Charges ({{ number_format($booking->finance_charge_percent, 1) }}%)</td>
                                <td>:</td>
                                <td align="right">₹{{ number_format($booking->finance_charge_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Secure Storage Charges ({{ number_format($booking->storage_charge_percent, 1) }}%)</td>
                                <td>:</td>
                                <td align="right">₹{{ number_format($booking->storage_charge_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Processing Fee</td>
                                <td>:</td>
                                <td align="right">₹{{ number_format($booking->emiPlan->processing_fee ?? 0.00, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Platform Convenience Fee</td>
                                <td>:</td>
                                <td align="right">₹0.00</td>
                            </tr>
                            <tr>
                                <td>Insured Delivery Charges</td>
                                <td>:</td>
                                <td align="right">₹0.00</td>
                            </tr>
                            <tr>
                                <td>Tax on Service Charges ({{ number_format($booking->gst_on_charges_percent, 1) }}%)</td>
                                <td>:</td>
                                <td align="right">₹{{ number_format($booking->gst_on_charges_amount, 2) }}</td>
                            </tr>
                            <tr class="highlight-row">
                                <td>TOTAL APPLICABLE CHARGES</td>
                                <td>:</td>
                                <td align="right">₹{{ number_format($booking->grand_total - $booking->locked_gold_value, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Row 3 Progress -->
        <div class="progress-box">
            <div class="section-header-bar">📈 5. PAYMENT PROGRESS</div>
            <table width="100%" style="font-size: 9px; margin-bottom: 2px;">
                <tr>
                    <td><strong>{{ $completedPaymentsCount }} of {{ $booking->duration_months }} Payments Completed</strong></td>
                    <td align="center"><strong>{{ number_format($completionPercentage, 2) }}% Completed</strong></td>
                    <td align="right"><strong>Remaining Payments : {{ max(0, $booking->duration_months - $completedPaymentsCount) }}</strong></td>
                </tr>
            </table>
            <!-- Progress Bar -->
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: {{ $completionPercentage }}%;"></div>
            </div>
            <table width="100%" style="font-size: 8.5px; color: #555555;">
                <tr>
                    <td>Paid Installments : {{ $completedPaymentsCount }}</td>
                    <td align="center">Total Installments : {{ $booking->duration_months }}</td>
                    <td align="right">Next Installment Due On : {{ $nextEmi ? $nextEmi->due_date->format('d M Y') : 'Fully Paid' }}</td>
                </tr>
            </table>
        </div>

        <!-- Row 4 Notes, Verification and Signature -->
        <table class="section-table" width="100%" cellspacing="0">
            <tr>
                <!-- Notes -->
                <td width="35%" valign="top">
                    <div class="info-footer-box">
                        <div class="section-header-bar">⚠ 6. IMPORTANT NOTES</div>
                        <ul class="notes-list">
                            <li>This receipt confirms only the successful receipt of the above EMAP payment.</li>
                            <li>Timely payments ensure uninterrupted gold allocation and secure your locked price.</li>
                            <li>In case of any payment failure, please contact customer support immediately.</li>
                            <li>Please refer to the Terms & Conditions for complete details.</li>
                            <li>Final GST Invoice will be provided once payment is completed in full with all applicable charges.</li>
                        </ul>
                    </div>
                </td>
                <td width="3%">&nbsp;</td>
                <!-- Verification QR -->
                <td width="30%" valign="top">
                    <div class="info-footer-box" style="text-align: center;">
                        <div class="section-header-bar" style="text-align: left;">🛡 7. VERIFY THIS RECEIPT</div>
                        <div style="margin-top: 4px;">
                            @if(!empty($qrImageSrc))
                                <img src="{{ $qrImageSrc }}" style="width: 58px; height: 58px; border: 1px solid #e8e2d2; padding: 2px;" alt="QR Code"><br>
                            @else
                                <div style="border: 1px dashed #ccc; width: 58px; height: 58px; line-height: 58px; margin: 0 auto; color: #999; font-size: 8px;">No QR</div>
                            @endif
                            <div style="color: #666; font-size: 7.5px; margin-top: 3px; line-height: 1.1;">
                                Code: <strong>{{ $verificationCode }}</strong><br>
                                Scan QR code to verify receipt
                            </div>
                        </div>
                    </div>
                </td>
                <td width="3%">&nbsp;</td>
                <!-- Authorized Signature -->
                <td width="29%" valign="top">
                    <div class="info-footer-box" style="text-align: center; position: relative;">
                        <div class="section-header-bar" style="text-align: left;">✍ 8. AUTHORISED SIGNATURE</div>
                        
                        <div style="height: 40px; margin-top: 10px; position: relative;">
                            <!-- Stylish digital signature handwriting representation -->
                            <span style="font-family: Georgia, serif; font-style: italic; font-size: 15px; color: #2e40e2; font-weight: bold; display: inline-block; margin-top: 10px;">AurOnGold Official</span>
                            
                            <!-- Stamp Seal CSS -->
                            <div class="seal-stamp">
                                <div class="seal-text-small">AURONGOLD</div>
                                <div class="seal-text-large">AG</div>
                                <div class="seal-text-small" style="margin-top: 0px;">SECURE</div>
                            </div>
                        </div>
                        
                        <div style="border-top: 1px solid #999999; width: 85%; margin: 4px auto 0; padding-top: 2px; font-weight: bold; color: #222222;">Authorised Signatory</div>
                        <div style="font-size: 8px; color: #666666;">AurOnGold Bullion Trading LLC</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Contact Strip -->
        <table class="contact-strip" width="100%">
            <tr>
                <td width="22%">🌐 www.aurongold.in</td>
                <td width="26%">✉ support@aurongold.in</td>
                <td width="22%">📞 +91 73376 16333</td>
                <td width="30%" align="right">📍 #73, BKC District, Mumbai, MH - 400051</td>
            </tr>
        </table>

        <!-- Bottom Banner -->
        <div class="bottom-banner">
            ❖ Thank you for choosing AurOnGold. Invest Smart. Grow Secure. ❖
        </div>
    </div>
</body>
</html>
