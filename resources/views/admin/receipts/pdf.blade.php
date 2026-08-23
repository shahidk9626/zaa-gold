@php
    $booking = $payment->booking;
    $customer = $booking->customer;
    $product = $booking->product;
    $plan = $booking->emiPlan;
    $schedule = $payment->emiSchedule;

    // Get cumulative calculations for the customer receipt
    $allPayments = \App\Models\BookingPayment::where('booking_id', $booking->id)
        ->where('status', 'Paid')
        ->where('payment_date', '<=', $payment->payment_date)
        ->get();

    $financialService = app(\App\Services\FinancialCalculationService::class);
    $rawPaidTillDate = (float) $allPayments->sum('amount_paid');
    $totalPaidTillDate = $financialService->displayPaidTotal($booking, $rawPaidTillDate);
    $remainingBalance = $financialService->outstanding($booking, $rawPaidTillDate);

    $completedPaymentsCount = \App\Models\BookingPayment::where('booking_id', $booking->id)
        ->where('status', 'Paid')
        ->where('payment_date', '<=', $payment->payment_date)
        ->count();

    $currentInstallmentNumber = $schedule->installment_number ?? 1;
    $totalDurationMonths = $booking->duration_months ?? 12;
    $remainingPaymentsCount = max(0, $totalDurationMonths - $completedPaymentsCount);

    // Customer Detail Strings
    $customerCode = $customer->customerDetail->customer_code ?? ('AGCUST' . str_pad($customer->id, 6, '0', STR_PAD_LEFT));
    $customerMobile = $customer->customerDetail->phone_number ?? $customer->phone ?? 'N/A';

    // Financial Breakdown Values
    $goldValue = (float) $booking->locked_gold_value;
    $totalCost = (float) $booking->grand_total;
    $otherCharges = max(0, $totalCost - $goldValue);
    $discountAmount = (float) ($booking->savings_amount ?? 0);
    $finalPayableAmount = $totalCost;

    // Monthly Calculation Formula String
    $monthlyCalcFormula = '₹' . number_format($totalCost, 2) . ' ÷ ' . $totalDurationMonths . ' = ₹' . number_format($payment->amount_paid, 2);

    $logoPath = public_path('assets/images/logo.png');
    $logoExists = file_exists($logoPath);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt_{{ $payment->receipt_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 5mm 6mm 5mm 6mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #1A1A1A;
            line-height: 1.25;
            margin: 0;
            padding: 0;
            background-color: #FFFFFF;
        }
        .outer-frame {
            border: 1.5px solid #C59B27;
            padding: 8px 10px;
            background-color: #FFFFFF;
            position: relative;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .company-title {
            font-size: 15px;
            font-weight: bold;
            color: #0B1E36;
            letter-spacing: 1px;
            line-height: 1.1;
        }
        .company-subtitle {
            font-size: 8px;
            font-weight: bold;
            color: #B4831B;
            letter-spacing: 2px;
            line-height: 1.1;
        }
        .company-tagline {
            font-size: 6.5px;
            color: #555555;
            letter-spacing: 0.8px;
            margin-top: 1px;
        }
        .header-contact {
            font-size: 7.5px;
            color: #222222;
            text-align: right;
            line-height: 1.35;
        }
        .header-divider {
            border: 0;
            border-top: 1.5px solid #C59B27;
            margin: 5px 0 7px 0;
        }
        .banner-box {
            background-color: #0B1E36;
            border: 1.5px solid #C59B27;
            border-radius: 4px;
            padding: 4px 8px;
            text-align: center;
            margin-bottom: 7px;
        }
        .banner-title {
            color: #FFFFFF;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1.2px;
            font-family: 'DejaVu Sans', sans-serif;
        }
        .banner-symbol {
            color: #D4AF37;
            font-size: 8px;
        }
        .section-pill {
            background-color: #A66E14;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 7.5px;
            padding: 2.5px 7px;
            border-radius: 3px 3px 0 0;
            display: inline-block;
            letter-spacing: 0.3px;
        }
        .card-box {
            border: 1px solid #E2D7C1;
            border-radius: 4px;
            padding: 4px 6px;
            background-color: #FFFFFF;
            margin-bottom: 6px;
        }
        .card-box-pill {
            border: 1px solid #E2D7C1;
            border-radius: 0 4px 4px 4px;
            padding: 4px 6px;
            background-color: #FFFFFF;
            margin-bottom: 6px;
        }
        .info-table td {
            padding: 1.8px 2px;
            font-size: 7.8px;
            vertical-align: top;
        }
        .info-label {
            color: #333333;
            width: 38%;
        }
        .info-colon {
            width: 4%;
            text-align: center;
        }
        .info-value {
            color: #111111;
            font-weight: bold;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 2.5px 8px;
            font-size: 7.8px;
            border-bottom: 1px solid #F0EAD9;
        }
        .summary-table tr:last-child td {
            border-bottom: none;
        }
        .highlight-row {
            background-color: #FFFDF7;
            font-weight: bold;
        }
        .progress-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        .progress-table th {
            font-size: 7px;
            color: #555555;
            font-weight: normal;
            padding-bottom: 2px;
        }
        .progress-table td {
            font-size: 8.5px;
            font-weight: bold;
            color: #111111;
            padding: 2px 4px;
            border-right: 1px solid #E2D7C1;
        }
        .progress-table td:last-child {
            border-right: none;
        }
        .ack-text {
            font-size: 7.2px;
            color: #222222;
            text-align: center;
            line-height: 1.3;
            margin: 4px 15px;
        }
        .notice-box {
            border: 1px solid #E2D7C1;
            background-color: #FFFDF9;
            border-radius: 4px;
            padding: 4px 6px;
            font-size: 6.8px;
            color: #444444;
            line-height: 1.25;
        }
        .terms-banner {
            background-color: #0B1E36;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 7.5px;
            padding: 2.5px 7px;
            border-radius: 3px 3px 0 0;
            letter-spacing: 0.3px;
        }
        .terms-box {
            border: 1px solid #0B1E36;
            border-radius: 0 0 4px 4px;
            padding: 4px 6px;
            background-color: #FFFFFF;
            margin-bottom: 5px;
        }
        .terms-list td {
            font-size: 6.8px;
            color: #222222;
            padding: 1.2px 2px;
            vertical-align: top;
            line-height: 1.2;
        }
        .footer-banner {
            background-color: #0B1E36;
            color: #FFFFFF;
            text-align: center;
            padding: 3.5px 6px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 3px;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    <div class="outer-frame">
        <!-- HEADER -->
        <table class="header-table">
            <tr>
                <td width="55%" align="left">
                    <table width="100%">
                        <tr>
                            @if($logoExists)
                                <td width="55" valign="middle">
                                    <img src="{{ $logoPath }}" style="height: 40px; width: auto; display: block;" alt="AurOnGold">
                                </td>
                            @endif
                            <td valign="middle">
                                <div class="company-title">AURON GOLD</div>
                                <div class="company-subtitle">PRIVATE LIMITED</div>
                                <div class="company-tagline">— SMART WAY TO BUY GOLD —</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="45%" align="right" valign="middle">
                    <div class="header-contact">
                        📍 73, 1st Floor, Samathawoods Layout, Bhogadi,<br>
                        Gaddige Main Road, Mysuru - 570026<br>
                        📞 7337616333 &nbsp;|&nbsp; ✉ support@aurongold.in<br>
                        🌐 www.aurongold.in
                    </div>
                </td>
            </tr>
        </table>

        <hr class="header-divider">

        <!-- DOCUMENT TITLE BANNER -->
        <div class="banner-box">
            <span class="banner-symbol">❖</span>
            <span class="banner-title">ADVANCE PAYMENT RECEIPT & ACKNOWLEDGEMENT</span>
            <span class="banner-symbol">❖</span>
        </div>

        <!-- RECEIPT INFO & CUSTOMER DETAILS (2 Columns) -->
        <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 6px;">
            <tr>
                <!-- Left: Receipt Information -->
                <td width="49%" valign="top">
                    <table class="info-table" style="margin-top: 4px;">
                        <tr>
                            <td class="info-label">Receipt No.</td>
                            <td class="info-colon">:</td>
                            <td class="info-value">{{ $payment->receipt_number }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Booking ID</td>
                            <td class="info-colon">:</td>
                            <td class="info-value">{{ $booking->booking_number }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Payment Date & Time</td>
                            <td class="info-colon">:</td>
                            <td class="info-value" style="font-weight: normal;">{{ $payment->payment_date->format('d M Y | h:i A') }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Payment Method</td>
                            <td class="info-colon">:</td>
                            <td class="info-value" style="font-weight: normal;">{{ $payment->payment_mode ?? 'Online Gateway (UPI)' }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Payment Status</td>
                            <td class="info-colon">:</td>
                            <td class="info-value" style="color: #1E7E34;">Successful</td>
                        </tr>
                    </table>
                </td>
                <td width="2%">&nbsp;</td>
                <!-- Right: Customer Details Card -->
                <td width="49%" valign="top">
                    <div style="border: 1px solid #E2D7C1; border-radius: 4px; overflow: hidden; background-color: #FFFFFF;">
                        <div style="background-color: #A66E14; color: #FFFFFF; font-weight: bold; font-size: 7.5px; padding: 2.5px 7px;">
                            👤 CUSTOMER DETAILS
                        </div>
                        <div style="padding: 4px 6px;">
                            <table class="info-table">
                                <tr>
                                    <td class="info-label" style="width: 42%;">Customer Name</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value">{{ $customer->name }}</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Customer ID</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value" style="font-weight: normal;">{{ $customerCode }}</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Mobile Number</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value" style="font-weight: normal;">{{ $customerMobile }}</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Email Address</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value" style="font-weight: normal; font-size: 7.2px; word-break: break-all;">{{ $customer->email }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- SECTION 1: GOLD PURCHASE / ORDER DETAILS -->
        <div>
            <div class="section-pill">🏷 1. GOLD PURCHASE / ORDER DETAILS</div>
            <div class="card-box-pill">
                <table width="100%" class="info-table">
                    <tr>
                        <td width="48%" valign="top">
                            <table class="info-table">
                                <tr>
                                    <td class="info-label" style="width: 42%;">Gold Product</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value">{{ $product->name }}</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Gold Purity</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value" style="font-weight: normal;">{{ $product->gold_purity ?? '24KT (999.9) Fine Gold' }}</td>
                                </tr>
                            </table>
                        </td>
                        <td width="4%">&nbsp;</td>
                        <td width="48%" valign="top">
                            <table class="info-table">
                                <tr>
                                    <td class="info-label" style="width: 52%;">Gold Weight / Quantity</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value">{{ number_format($booking->gold_weight, 3) }} g</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Today's Gold Price (per g)</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value" style="font-weight: normal;">₹{{ number_format($booking->locked_price_per_gram, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Gold Value</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value">₹{{ number_format($goldValue, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- SECTION 2: PAYMENT SUMMARY -->
        <div>
            <div class="section-pill">🧾 2. PAYMENT SUMMARY</div>
            <div class="card-box-pill" style="padding: 0;">
                <table class="summary-table">
                    <tr>
                        <td style="color: #333333;">Gold Value</td>
                        <td align="right" style="font-weight: bold; color: #111111;">₹{{ number_format($goldValue, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="color: #333333;">All Other Charges</td>
                        <td align="right" style="font-weight: bold; color: #111111;">₹{{ number_format($otherCharges, 2) }}</td>
                    </tr>
                    <tr class="highlight-row">
                        <td style="font-weight: bold; color: #A66E14;">Total Cost (All Inclusive)</td>
                        <td align="right" style="font-weight: bold; color: #A66E14; font-size: 8.5px;">₹{{ number_format($totalCost, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="color: #333333;">Discount (If Any)</td>
                        <td align="right" style="font-weight: bold; color: {{ $discountAmount > 0 ? '#1E7E34' : '#111111' }};">
                            {{ $discountAmount > 0 ? '- ₹' . number_format($discountAmount, 2) : '₹0.00' }}
                        </td>
                    </tr>
                    <tr class="highlight-row" style="background-color: #FFF9EC;">
                        <td style="font-weight: bold; color: #0B1E36; font-size: 8.5px;">Final Payable Amount</td>
                        <td align="right" style="font-weight: bold; color: #0B1E36; font-size: 9px;">₹{{ number_format($finalPayableAmount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- SECTION 3: PLAN & INSTALLMENT DETAILS -->
        <div>
            <div class="section-pill">📅 3. PLAN & INSTALLMENT DETAILS</div>
            <div class="card-box-pill">
                <table width="100%" class="info-table" style="margin-bottom: 4px;">
                    <tr>
                        <td width="50%">
                            <span style="color: #333333;">Plan Type</span> : <strong>{{ $totalDurationMonths }} Months Advance Plan</strong>
                        </td>
                        <td width="50%" align="right">
                            <span style="color: #333333;">Total Installments</span> : <strong>{{ $totalDurationMonths }} Months</strong>
                        </td>
                    </tr>
                </table>

                <div style="border: 1px solid #E2D7C1; background-color: #FFFDF9; border-radius: 4px; padding: 5px 8px;">
                    <table width="100%">
                        <tr>
                            <td valign="middle">
                                <div style="font-size: 8.5px; font-weight: bold; color: #111111;">
                                    🗓 {{ $currentInstallmentNumber }}/{{ $totalDurationMonths }} Advance Payment
                                </div>
                                <div style="font-size: 7.5px; color: #555555; margin-top: 1px;">
                                    {{ $monthlyCalcFormula }}
                                </div>
                            </td>
                            <td align="right" valign="middle">
                                <div style="font-size: 13px; font-weight: bold; color: #A66E14; font-family: 'DejaVu Sans', sans-serif;">
                                    ₹{{ number_format($payment->amount_paid, 2) }}
                                </div>
                                <div style="font-size: 6.5px; color: #666666;">(Per Month)</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECTION 4: PAYMENT PROGRESS -->
        <div>
            <div class="section-pill">📈 4. PAYMENT PROGRESS</div>
            <div class="card-box-pill" style="padding: 5px 2px;">
                <table class="progress-table">
                    <tr>
                        <th>Payment No.</th>
                        <th>This Payment (Advance)</th>
                        <th>Total Paid Till Date</th>
                        <th>Remaining Amount</th>
                        <th>Remaining Payments</th>
                    </tr>
                    <tr>
                        <td>{{ $currentInstallmentNumber }} of {{ $totalDurationMonths }}</td>
                        <td>₹{{ number_format($payment->amount_paid, 2) }}</td>
                        <td>₹{{ number_format($totalPaidTillDate, 2) }}</td>
                        <td style="color: #0B1E36;">₹{{ number_format($remainingBalance, 2) }}</td>
                        <td>{{ $remainingPaymentsCount }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ACKNOWLEDGEMENT & SIGNATORY SECTION -->
        <div style="margin-top: 4px; margin-bottom: 6px;">
            <div style="text-align: center; color: #A66E14; font-weight: bold; font-size: 8px; letter-spacing: 0.5px;">
                ❖ ACKNOWLEDGEMENT ❖
            </div>
            <div class="ack-text">
                Received advance payment from the customer towards the mentioned gold purchase or order and the same is acknowledged.<br>
                This receipt is issued as an acknowledgement of advance payment received and is subject to the terms & conditions of the purchase plan/order.
            </div>

            <table width="100%" style="margin-top: 4px;">
                <tr>
                    <!-- Signatory -->
                    <td width="35%" valign="bottom" align="left">
                        <div style="font-size: 7px; color: #555555; margin-bottom: 2px;">For Auron Gold Private Limited</div>
                        <div style="font-family: Georgia, serif; font-style: italic; font-size: 13px; color: #0B1E36; font-weight: bold; padding: 2px 0 0 5px;">
                            Harshith
                        </div>
                        <div style="border-top: 1px solid #888888; width: 140px; margin-top: 2px;"></div>
                        <div style="font-size: 7.5px; font-weight: bold; color: #111111; margin-top: 1px;">Authorised Signatory</div>
                    </td>
                    <!-- Center Seal -->
                    <td width="30%" align="center" valign="middle">
                        <div style="display: inline-block; border: 1.5px solid #C59B27; border-radius: 50%; width: 44px; height: 44px; text-align: center; background-color: #FFFDF7;">
                            <div style="font-size: 5px; color: #A66E14; font-weight: bold; margin-top: 6px; letter-spacing: 0.5px;">AURONGOLD</div>
                            <div style="font-size: 11px; color: #0B1E36; font-weight: bold; margin: -1px 0;">AG</div>
                            <div style="font-size: 4.5px; color: #A66E14; font-weight: bold;">VERIFIED</div>
                        </div>
                    </td>
                    <!-- Notice Box -->
                    <td width="35%" valign="middle" align="right">
                        <div class="notice-box" style="text-align: left;">
                            <table width="100%">
                                <tr>
                                    <td width="16" valign="top" style="font-size: 10px; color: #A66E14;">📝</td>
                                    <td valign="top">
                                        This receipt will be shown only in website. If anyone provides any other receipt in other ways, it is not an original receipt.
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- TERMS & CONDITIONS -->
        <div>
            <div class="terms-banner">✔ TERMS & CONDITIONS</div>
            <div class="terms-box">
                <table width="100%" class="terms-list">
                    <tr>
                        <td width="50%" valign="top">
                            <table width="100%">
                                <tr>
                                    <td width="12" style="color: #A66E14; font-weight: bold;">✔</td>
                                    <td>The above gold price is locked as on {{ $booking->booking_date->format('d/m/Y') }} and is valid for the particular duration as per the terms of the selected plan.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold;">✔</td>
                                    <td>The locked price is applicable for the mentioned quantity and plan only.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold;">✔</td>
                                    <td>All other charges are calculated as per company policy and are subject to change if plan is modified.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold;">✔</td>
                                    <td>Customer can cancel their plan at any time, but cancellation charges will be applicable and refund will be processed within 7 – 10 working days as per our refund policy.</td>
                                </tr>
                            </table>
                        </td>
                        <td width="50%" valign="top">
                            <table width="100%">
                                <tr>
                                    <td width="12" style="color: #A66E14; font-weight: bold;">✔</td>
                                    <td>Missing payments as per terms may lead to cancellation and we will provide refund as per our refund policy.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold;">✔</td>
                                    <td>Gold will be delivered after completion of full payment as per plan terms.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold;">✔</td>
                                    <td>Company reserves the right to modify the plan terms & conditions without prior notice.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold;">✔</td>
                                    <td>This certificate is system generated and does not require any physical signature.</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer-banner">
            🌐 For complete Terms & Conditions, please visit our website <span style="color: #D4AF37; text-decoration: underline;">www.aurongold.in/terms-conditions</span>
        </div>
    </div>
</body>
</html>
