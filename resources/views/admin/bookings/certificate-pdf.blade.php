@php
    $booking = $certificate->goldBooking ?? $booking;
    $customer = $booking->customer;
    $product = $booking->product;
    $plan = $booking->emiPlan;

    // Customer Detail Strings
    $customerCode = $customer->customerDetail->customer_code ?? ('AGCUST' . str_pad($customer->id, 6, '0', STR_PAD_LEFT));
    $customerMobile = $customer->customerDetail->phone_number ?? $customer->phone ?? 'N/A';

    // Calculation breakups
    $goldValue = (float) $booking->locked_gold_value;
    $totalPrice = (float) $booking->grand_total;

    $financeCharge = (float) ($booking->finance_charge_amount ?? 0);
    $storageCharge = (float) ($booking->storage_charge_amount ?? 0);
    $gstOnGold = (float) ($booking->gst_on_gold_amount ?? 0);
    $gstOnCharges = (float) ($booking->gst_on_charges_amount ?? 0);
    $savingsAmount = (float) ($booking->savings_amount ?? 0);

    $rawCharges = $financeCharge + $storageCharge + $gstOnGold + $gstOnCharges;
    if ($rawCharges <= 0) {
        $rawCharges = max(0, $totalPrice - $goldValue + $savingsAmount);
    }

    $logoPath = public_path('assets/images/logo.png');
    $logoExists = file_exists($logoPath);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Price Lock Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 4mm 5mm 4mm 5mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            color: #111827;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #FFFFFF;
        }
        .outer-frame {
            border: 2px solid #C59B27;
            padding: 12px 14px;
            background-color: #FFFFFF;
            position: relative;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .company-title {
            font-size: 20px;
            font-weight: bold;
            color: #0B1E36;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .company-subtitle {
            font-size: 9.5px;
            font-weight: bold;
            color: #B4831B;
            letter-spacing: 2.5px;
            line-height: 1.1;
            margin-top: 3px;
        }
        .contact-bar {
            border-top: 1.5px solid #C59B27;
            border-bottom: 1.5px solid #C59B27;
            padding: 5px 0;
            margin: 8px 0 12px 0;
            font-size: 8.8px;
            color: #222222;
            text-align: center;
        }
        .main-title-block {
            text-align: center;
            margin-bottom: 6px;
        }
        .ornament-line {
            color: #C59B27;
            font-size: 10px;
            margin: 2px 0;
        }
        .main-title {
            font-size: 22px;
            font-weight: bold;
            color: #0B1E36;
            letter-spacing: 2px;
            margin: 3px 0;
            text-transform: uppercase;
        }
        .cert-desc {
            font-size: 9.5px;
            color: #333333;
            text-align: center;
            margin-bottom: 12px;
            line-height: 1.35;
        }
        .section-pill {
            background-color: #0B1E36;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 9.5px;
            padding: 4px 10px;
            border-radius: 4px 4px 0 0;
            display: inline-block;
            letter-spacing: 0.5px;
        }
        .card-box-pill {
            border: 1.5px solid #E2D7C1;
            border-radius: 0 5px 5px 5px;
            padding: 6px 10px;
            background-color: #FFFFFF;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 3px 4px;
            font-size: 9.5px;
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
        .price-details-header {
            background-color: #FFFDF7;
            border-bottom: 1.5px solid #E2D7C1;
            padding: 6px 10px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 4px 10px;
            font-size: 9.5px;
            border-bottom: 1px solid #F0EAD9;
        }
        .summary-table tr:last-child td {
            border-bottom: none;
        }
        .terms-box {
            border: 1.5px solid #0B1E36;
            border-radius: 0 0 5px 5px;
            padding: 6px 10px;
            background-color: #FFFFFF;
            margin-bottom: 10px;
        }
        .terms-list td {
            font-size: 8.2px;
            color: #222222;
            padding: 2.2px 3px;
            vertical-align: top;
            line-height: 1.3;
        }
    </style>
</head>
<body>
    <div class="outer-frame">
        <!-- TOP-LEFT CORNER DECORATIVE ACCENT -->
        <svg width="170" height="110" style="position: absolute; top: -1px; left: -1px; z-index: 1; pointer-events: none;">
            <path d="M 0 0 L 160 0 C 100 20 20 100 0 130 Z" fill="#0B1E36" />
            <path d="M 165 0 C 105 25 25 105 0 135" fill="none" stroke="#C59B27" stroke-width="2.5" />
        </svg>

        <!-- BOTTOM-RIGHT CORNER DECORATIVE ACCENT -->
        <svg width="170" height="110" style="position: absolute; bottom: -1px; right: -1px; z-index: 1; pointer-events: none;">
            <path d="M 170 110 L 10 110 C 70 90 150 10 170 -20 Z" fill="#0B1E36" />
            <path d="M 170 -25 C 145 20 65 95 -5 110" fill="none" stroke="#C59B27" stroke-width="2.5" />
        </svg>

        <!-- TOP HEADER AREA -->
        <table width="100%" style="margin-bottom: 6px; position: relative; z-index: 2;">
            <tr>
                <td width="28%" align="left" valign="top">
                    <!-- Left Corner space for Ribbon -->
                </td>
                <td width="44%" align="center" valign="top">
                    @if($logoExists)
                        <img src="{{ $logoPath }}" style="height: 48px; width: auto; display: block; margin: 0 auto 3px auto;" alt="AurOnGold">
                    @endif
                    <div class="company-title">Auron Gold Private Limited</div>
                    <div class="company-subtitle">— SMART WAY TO BUY GOLD —</div>
                </td>
                <td width="28%" align="right" valign="top">
                    <div style="font-size: 8.5px; color: #555555;">Certificate No.</div>
                    <div style="font-size: 11px; font-weight: bold; color: #0B1E36; margin-top: 1px;">{{ $certificate->certificate_number }}</div>
                    <div style="color: #C59B27; font-size: 8px; margin-top: 2px;">❖ ─── ✦ ─── ❖</div>
                </td>
            </tr>
        </table>

        <!-- COMPANY CONTACT BAR -->
        <div class="contact-bar" style="position: relative; z-index: 2;">
            📍 73, 1st Floor, Samathawoods Layout, Bhogadi, Gaddige Main Road, Mysuru - 570026
            &nbsp;&nbsp;|&nbsp;&nbsp;
            📞 7337616333
            &nbsp;&nbsp;|&nbsp;&nbsp;
            ✉ support@aurongold.in
            &nbsp;&nbsp;|&nbsp;&nbsp;
            🌐 www.aurongold.in
        </div>

        <!-- MAIN TITLE & DESCRIPTION AREA -->
        <table width="100%" style="position: relative; z-index: 2;">
            <tr>
                <td width="76%" valign="top">
                    <div class="main-title-block">
                        <div class="ornament-line">❖ ──────────────────────── ✦ ──────────────────────── ❖</div>
                        <div class="main-title">PRICE LOCK CERTIFICATE</div>
                        <div class="ornament-line">❖ ──────────────────────── ✦ ──────────────────────── ❖</div>
                    </div>
                    <div class="cert-desc">
                        This is to certify that the gold price for the below mentioned purchase has been locked as per the details given below.
                    </div>
                </td>
                <td width="24%" align="center" valign="middle">
                    <!-- PRICE LOCKED SEAL BADGE -->
                    <div style="display: inline-block; border: 2.5px solid #C59B27; border-radius: 50%; width: 92px; height: 92px; text-align: center; background-color: #FFFDF7; padding: 3px;">
                        <div style="border: 1.5px dashed #C59B27; border-radius: 50%; width: 84px; height: 84px; text-align: center;">
                            <div style="font-size: 6.5px; color: #B4831B; margin-top: 7px;">★★★</div>
                            <div style="font-size: 10px; font-weight: bold; color: #0B1E36; margin: 0;">PRICE</div>
                            <div style="font-size: 10px; font-weight: bold; color: #0B1E36; margin: 0;">LOCKED</div>
                            <div style="font-size: 6.5px; color: #B4831B; margin: 1px 0;">★★★</div>
                            <div style="font-size: 10px; color: #B4831B; margin-top: 1px;">🔒</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- CUSTOMER DETAILS SECTION -->
        <div style="position: relative; z-index: 2;">
            <div class="section-pill">👤 CUSTOMER DETAILS</div>
            <div class="card-box-pill">
                <table width="100%" class="info-table">
                    <tr>
                        <td width="50%">
                            <table class="info-table">
                                <tr>
                                    <td class="info-label" style="width: 35%;">Customer Name</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value">{{ $customer->name }}</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Customer ID</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value" style="font-weight: normal;">{{ $customerCode }}</td>
                                </tr>
                            </table>
                        </td>
                        <td width="50%">
                            <table class="info-table">
                                <tr>
                                    <td class="info-label" style="width: 35%;">Mobile Number</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value" style="font-weight: normal;">{{ $customerMobile }}</td>
                                </tr>
                                <tr>
                                    <td class="info-label">Email Address</td>
                                    <td class="info-colon">:</td>
                                    <td class="info-value" style="font-weight: normal; font-size: 9px; word-break: break-all;">{{ $customer->email }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- PRICE LOCK DETAILS SECTION -->
        <div style="position: relative; z-index: 2;">
            <div class="section-pill">🔒 PRICE LOCK DETAILS</div>
            <div class="card-box-pill" style="padding: 0;">
                <div class="price-details-header">
                    <table width="100%">
                        <tr>
                            <td>
                                <strong style="color: #111111; font-size: 10px;">🪙 Today's Gold Price 24KT (1 Gm 999)</strong><br>
                                <span style="font-size: 9px; color: #555555;">(Date: {{ $booking->booking_date->format('d/m/Y') }}) &nbsp;:&nbsp; <strong>₹{{ number_format($booking->locked_price_per_gram, 2) }}</strong></span>
                            </td>
                            <td align="right" valign="middle">
                                <span style="font-weight: bold; color: #B4831B; font-size: 10px; letter-spacing: 0.5px;">PRICE LOCKED FOR THIS</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="summary-table">
                    <tr>
                        <td style="color: #333333;">Gold Value ({{ number_format($booking->gold_weight, 3) }} g)</td>
                        <td align="right" style="font-weight: bold; color: #111111;">₹{{ number_format($goldValue, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="color: #333333;">All Other Charges</td>
                        <td align="right" style="font-weight: bold; color: #111111;">₹{{ number_format($rawCharges, 2) }}</td>
                    </tr>
                    @if($savingsAmount > 0)
                    <tr>
                        <td style="color: #c53030;">Promo Savings / Discount</td>
                        <td align="right" style="font-weight: bold; color: #c53030;">-₹{{ number_format($savingsAmount, 2) }}</td>
                    </tr>
                    @endif
                    <tr style="background-color: #FFFDF5;">
                        <td style="font-weight: bold; color: #0B1E36; font-size: 10.5px;">Total Price (All Inclusive)</td>
                        <td align="right" style="font-weight: bold; color: #A66E14; font-size: 11.5px;">₹{{ number_format($totalPrice, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- PAYMENT PLAN SECTION -->
        <div style="position: relative; z-index: 2;">
            <div class="section-pill">🗓 PAYMENT PLAN</div>
            <div class="card-box-pill">
                <table width="100%">
                    <tr>
                        <td valign="middle">
                            <div style="font-size: 10.5px; font-weight: bold; color: #111111;">
                                🗓 EMAP (Easy Monthly Advance Payment)
                            </div>
                            <div style="font-size: 9.5px; color: #444444; margin-top: 3px;">
                                <strong>₹{{ number_format($booking->monthly_emi, 2) }}</strong> X <strong>{{ $booking->duration_months }} Months</strong>
                            </div>
                        </td>
                        <td align="right" valign="middle">
                            <div style="font-size: 16px; font-weight: bold; color: #A66E14; font-family: 'DejaVu Sans', sans-serif;">
                                ₹{{ number_format($booking->monthly_emi, 2) }}
                            </div>
                            <div style="font-size: 7.5px; color: #666666;">(Per Month)</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- TERMS & CONDITIONS SECTION -->
        <div style="position: relative; z-index: 2;">
            <div class="section-pill" style="border-radius: 4px 4px 0 0;">📜 TERMS & CONDITIONS</div>
            <div class="terms-box">
                <table width="100%" class="terms-list">
                    <tr>
                        <td width="50%" valign="top">
                            <table width="100%">
                                <tr>
                                    <td width="14" style="color: #A66E14; font-weight: bold; font-size: 9px;">✔</td>
                                    <td>The above gold price is locked as on {{ $booking->booking_date->format('d/m/Y') }} and is valid for the particular duration as per the terms of the selected plan.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold; font-size: 9px;">✔</td>
                                    <td>The locked price is applicable for the mentioned quantity and plan only.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold; font-size: 9px;">✔</td>
                                    <td>All other charges are calculated as per company policy and are subject to change if plan is modified.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold; font-size: 9px;">✔</td>
                                    <td>Customer can cancel their plan at any time, but cancellation charges will be applicable and refund will be processed within 7 – 10 working days as per our refund policy.</td>
                                </tr>
                            </table>
                        </td>
                        <td width="50%" valign="top">
                            <table width="100%">
                                <tr>
                                    <td width="14" style="color: #A66E14; font-weight: bold; font-size: 9px;">✔</td>
                                    <td>Missing payments as per terms may lead to cancellation and we will provide refund as per our refund policy.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold; font-size: 9px;">✔</td>
                                    <td>Gold will be delivered after completion of full payment as per plan terms.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold; font-size: 9px;">✔</td>
                                    <td>Company reserves the right to modify the plan terms & conditions without prior notice.</td>
                                </tr>
                                <tr>
                                    <td style="color: #A66E14; font-weight: bold; font-size: 9px;">✔</td>
                                    <td>This certificate is system generated and does not require any physical signature.</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- FOOTER & SIGNATURE AREA -->
        <table width="100%" style="margin-top: 8px; position: relative; z-index: 2;">
            <tr>
                <!-- Date of Issue -->
                <td width="28%" valign="bottom" align="left">
                    <div style="font-size: 8.5px; color: #555555;">Date of Issue</div>
                    <div style="font-size: 10.5px; font-weight: bold; color: #111111; margin-top: 1px;">{{ $certificate->issued_at->format('d/m/Y') }}</div>
                    <div style="border-top: 1.5px solid #888888; width: 120px; margin-top: 3px;"></div>
                </td>
                <!-- Center Emblem Logo -->
                <td width="44%" align="center" valign="bottom">
                    <div style="display: inline-block; border: 2px solid #C59B27; border-radius: 50%; width: 48px; height: 48px; text-align: center; background-color: #FFFDF7;">
                        <div style="font-size: 5.5px; color: #A66E14; font-weight: bold; margin-top: 6px; letter-spacing: 0.5px;">AURONGOLD</div>
                        <div style="font-size: 12px; color: #0B1E36; font-weight: bold; margin: -1px 0;">AG</div>
                        <div style="font-size: 4.5px; color: #A66E14; font-weight: bold;">CERTIFIED</div>
                    </div>
                </td>
                <!-- Authorised Signatory -->
                <td width="28%" valign="bottom" align="right">
                    <div style="font-size: 8.5px; color: #555555; margin-bottom: 2px;">For Auron Gold Private Limited</div>
                    @if(!empty($signatureImageSrc))
                        <img src="{{ $signatureImageSrc }}" style="max-height: 40px; max-width: 130px; object-fit: contain; margin-bottom: 2px;" alt="Signature">
                    @else
                        <div style="font-family: Georgia, serif; font-style: italic; font-size: 16px; color: #0B1E36; font-weight: bold; padding: 2px 0 0 5px;">
                            Harshith
                        </div>
                    @endif
                    <div style="font-size: 9.5px; font-weight: bold; color: #111111; margin-top: 2px;">Authorised Signatory</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
