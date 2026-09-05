@php
    $booking = $invoice->booking;
    $customer = $invoice->customer ?? $booking->customer;
    $payment = $invoice->payment ?? $booking->payments()->latest()->first();
    $product = $booking->product;
    $plan = $booking->emiPlan;

    // Customer details
    $customerName = $invoice->customer_name ?? $customer->name ?? 'N/A';
    $customerCode = $customer->customerDetail->customer_code ?? ('AGCUST' . str_pad($customer->id ?? 0, 6, '0', STR_PAD_LEFT));
    $customerPhone = $invoice->customer_phone ?? $customer->customerDetail->phone_number ?? $customer->phone ?? 'N/A';
    $customerEmail = $invoice->customer_email ?? $customer->email ?? 'N/A';

    // Billing address formatting
    $addressParts = [];
    if (!empty($customer->customerDetail->address)) $addressParts[] = $customer->customerDetail->address;
    if (!empty($customer->customerDetail->city)) $addressParts[] = $customer->customerDetail->city;
    if (!empty($customer->customerDetail->state)) $addressParts[] = $customer->customerDetail->state;
    if (!empty($customer->customerDetail->pincode)) $addressParts[] = $customer->customerDetail->pincode;
    $fullAddress = !empty($addressParts) ? implode(', ', $addressParts) : ($invoice->billing_address ?? 'N/A');

    // Customer state determination for GST (Karnataka vs Inter-state)
    $customerState = trim($customer->customerDetail->state ?? '');
    $isKarnataka = empty($customerState) || in_array(strtolower($customerState), ['karnataka', 'ka', '29', 'karnataka (29)']);
    $placeOfSupply = $isKarnataka ? 'Karnataka (29)' : ($customerState . ' (Inter-state)');

    // Page 1: Gold Tax Invoice Amounts
    $goldValue = (float)($invoice->gold_value ?? $booking->locked_gold_value ?? 0);
    $goldWeight = (float)($invoice->gold_weight ?? $booking->gold_weight ?? 0);
    $lockedPrice = (float)($invoice->locked_gold_price ?? $booking->locked_price_per_gram ?? 0);
    $gstOnGold = (float)($invoice->gst_on_gold_amount ?? $booking->gst_on_gold_amount ?? 0);

    $page1TaxableValue = $goldValue;
    $page1TotalTax = $gstOnGold;

    if ($isKarnataka) {
        $cgstAmount = round($page1TotalTax / 2, 2);
        $sgstAmount = $page1TotalTax - $cgstAmount;
        $igstAmount = 0.00;
    } else {
        $cgstAmount = 0.00;
        $sgstAmount = 0.00;
        $igstAmount = $page1TotalTax;
    }

    $page1GrandTotal = $page1TaxableValue + $page1TotalTax;

    // Helper for Amount in Words (Page 1)
    $invoiceService = app(\App\Services\InvoiceService::class);
    $page1AmountInWords = $amountInWords ?? $invoiceService->convertAmountToWords($page1GrandTotal);

    // Page 2: Receipt Breakdown Charges & Savings
    $financeCharge = (float)($invoice->finance_charge ?? $booking->finance_charge_amount ?? 0);
    $storageCharge = (float)($invoice->storage_charge ?? $booking->storage_charge_amount ?? 0);
    $gstOnCharges = (float)($invoice->gst_on_charges_amount ?? $booking->gst_on_charges_amount ?? 0);
    $savingsAmount = (float)($booking->savings_amount ?? 0);

    $totalReceiptCharges = max(0, $financeCharge + $storageCharge + $gstOnCharges - $savingsAmount);
    $page2AmountInWords = $invoiceService->convertAmountToWords($totalReceiptCharges);

    $logoPath = public_path('assets/images/logo.png');
    $logoExists = file_exists($logoPath);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice & Receipt - {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 4mm 5mm 4mm 5mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            color: #111827;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background-color: #FFFFFF;
        }
        .page-container {
            border: 2px solid #C59B27;
            padding: 10px 12px;
            background-color: #FFFFFF;
            position: relative;
            box-sizing: border-box;
        }
        .page-break {
            page-break-before: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .company-title {
            font-size: 18px;
            font-weight: bold;
            color: #A66E14;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .company-subtitle {
            font-size: 8.5px;
            font-weight: bold;
            color: #0B1E36;
            letter-spacing: 2px;
            line-height: 1.1;
            margin-top: 2px;
        }
        .company-tagline {
            font-size: 7.5px;
            font-weight: bold;
            color: #A66E14;
            letter-spacing: 1.5px;
            margin-top: 2px;
        }
        .contact-info-block {
            font-size: 8px;
            color: #333333;
            line-height: 1.35;
        }
        .header-divider {
            border-bottom: 1.5px solid #C59B27;
            margin: 6px 0 10px 0;
        }
        .section-pill {
            background-color: #8C6512;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 9px;
            padding: 3px 8px;
            border-radius: 3px 3px 0 0;
            display: inline-block;
            letter-spacing: 0.5px;
        }
        .card-box {
            border: 1.5px solid #E2D7C1;
            border-radius: 0 4px 4px 4px;
            padding: 6px 8px;
            background-color: #FFFFFF;
            min-height: 105px;
        }
        .info-table td {
            padding: 2px 3px;
            font-size: 8.5px;
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
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border: 1.5px solid #E2D7C1;
        }
        .data-table th {
            background-color: #8C6512;
            color: #FFFFFF;
            font-size: 8.5px;
            font-weight: bold;
            padding: 5px 6px;
            text-align: center;
            border: 1px solid #8C6512;
        }
        .data-table td {
            padding: 5px 6px;
            font-size: 8.5px;
            border: 1px solid #E2D7C1;
        }
        .summary-row td {
            padding: 4px 8px;
            font-size: 8.5px;
            border: 1px solid #E2D7C1;
        }
        .total-banner-row td {
            background-color: #8C6512;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 9.5px;
            padding: 5px 8px;
            border: 1px solid #8C6512;
        }
        .terms-box {
            border: 1.5px solid #8C6512;
            border-radius: 0 0 4px 4px;
            padding: 6px 8px;
            background-color: #FFFFFF;
        }
        .terms-list {
            padding-left: 12px;
            margin: 0;
            font-size: 7.5px;
            color: #333333;
            line-height: 1.35;
        }
        .terms-list li {
            margin-bottom: 2px;
        }
        .signatory-box {
            text-align: right;
            font-size: 8.5px;
        }
        .signature-font {
            font-family: Georgia, serif;
            font-style: italic;
            font-size: 16px;
            color: #0B1E36;
            font-weight: bold;
            margin: 4px 0 2px 0;
        }
        .footer-banner {
            border-top: 1px solid #E2D7C1;
            padding-top: 4px;
            margin-top: 8px;
            text-align: center;
            font-size: 8.5px;
            color: #8C6512;
            font-style: italic;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- ========================================================= -->
    <!-- PAGE 1: GST TAX INVOICE                                   -->
    <!-- ========================================================= -->
    <div class="page-container">
        <!-- HEADER -->
        <table width="100%">
            <tr>
                <td width="55%" align="left" valign="top">
                    <table width="100%">
                        <tr>
                            @if($logoExists)
                                <td width="55" valign="middle">
                                    <img src="{{ $logoPath }}" style="height: 46px; width: auto;" alt="AurOnGold">
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
                <td width="45%" align="right" valign="top" class="contact-info-block">
                    📍 73, 1st Floor, Samathawoods Layout,<br>
                    Bhogadi, Gaddige Main Road,<br>
                    Mysuru - 570026, Karnataka<br>
                    📞 7337616333 &nbsp;|&nbsp; ✉ support@aurongold.in<br>
                    🌐 www.aurongold.in
                </td>
            </tr>
        </table>

        <div class="header-divider"></div>

        <!-- TOP 2 CARDS: BILL TO & TAX INVOICE (GST) -->
        <table width="100%" style="margin-bottom: 10px;">
            <tr>
                <td width="49%" valign="top">
                    <div class="section-pill">BILL TO</div>
                    <div class="card-box">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Customer Name</td>
                                <td class="info-colon">:</td>
                                <td class="info-value">{{ $customerName }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Customer ID</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal;">{{ $customerCode }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Mobile Number</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal;">{{ $customerPhone }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Email Address</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal; font-size: 8px; word-break: break-all;">{{ $customerEmail }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Address</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal; font-size: 8px; line-height: 1.25;">{{ $fullAddress }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="2%"></td>
                <td width="49%" valign="top">
                    <div class="section-pill">TAX INVOICE (GST)</div>
                    <div class="card-box">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Invoice No.</td>
                                <td class="info-colon">:</td>
                                <td class="info-value">{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Invoice Date</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal;">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Place of Supply</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal;">{{ $placeOfSupply }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Reverse Charge</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal;">No</td>
                            </tr>
                            <tr>
                                <td class="info-label">Invoice Type</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal;">Tax Invoice</td>
                            </tr>
                            <tr>
                                <td class="info-label">GSTIN</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal;">{{ $customer->customerDetail->gstin ?? 'Unregistered' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- PRODUCT & TAX BREAKUP TABLE -->
        <table class="data-table">
            <thead>
                <tr>
                    <th width="8%">SR. NO.</th>
                    <th width="42%">DESCRIPTION</th>
                    <th width="12%">HSN CODE</th>
                    <th width="10%">QTY</th>
                    <th width="8%">UNIT</th>
                    <th width="10%">RATE (₹)</th>
                    <th width="10%">AMOUNT (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td align="center">1</td>
                    <td>
                        <strong>{{ $product->name ?? '1 g 24KT (999) Fine Gold' }}</strong><br>
                        <span style="font-size: 7.5px; color: #555555;">Gold Value ({{ $booking->gold_type ?? '24KT' }})</span>
                    </td>
                    <td align="center">7108</td>
                    <td align="center">{{ number_format($goldWeight, 3) }}</td>
                    <td align="center">Gram</td>
                    <td align="right">₹{{ number_format($lockedPrice > 0 ? $lockedPrice : ($goldValue / max(0.001, $goldWeight)), 2) }}</td>
                    <td align="right">₹{{ number_format($goldValue, 2) }}</td>
                </tr>

                <!-- TAXABLE VALUE -->
                <tr class="summary-row">
                    <td colspan="5" style="border: none;"></td>
                    <td align="right" style="font-weight: bold; background-color: #FAFAFA;">TAXABLE VALUE</td>
                    <td align="right" style="font-weight: bold; background-color: #FAFAFA;">₹{{ number_format($page1TaxableValue, 2) }}</td>
                </tr>

                <!-- GST BREAKUP: KARNATAKA (CGST+SGST) VS INTER-STATE (IGST) -->
                @if($isKarnataka)
                    <tr class="summary-row">
                        <td colspan="5" style="border: none;"></td>
                        <td align="right" style="color: #333333;">CGST @ 1.5%</td>
                        <td align="right">₹{{ number_format($cgstAmount, 2) }}</td>
                    </tr>
                    <tr class="summary-row">
                        <td colspan="5" style="border: none;"></td>
                        <td align="right" style="color: #333333;">SGST @ 1.5%</td>
                        <td align="right">₹{{ number_format($sgstAmount, 2) }}</td>
                    </tr>
                @else
                    <tr class="summary-row">
                        <td colspan="5" style="border: none;"></td>
                        <td align="right" style="color: #333333;">IGST @ 3.0%</td>
                        <td align="right">₹{{ number_format($igstAmount, 2) }}</td>
                    </tr>
                @endif

                <!-- TOTAL AMOUNT ROW -->
                <tr class="total-banner-row">
                    <td colspan="5" style="border: none; background-color: #FFFFFF;"></td>
                    <td align="right">TOTAL AMOUNT (INR)</td>
                    <td align="right">₹{{ number_format($page1GrandTotal, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- AMOUNT IN WORDS & INFO NOTICE -->
        <table width="100%" style="margin-top: 10px;">
            <tr>
                <td width="60%" valign="top">
                    <div style="font-size: 8.5px; font-weight: bold; color: #8C6512;">AMOUNT IN WORDS:</div>
                    <div style="font-size: 8px; color: #111111; margin-top: 2px; line-height: 1.3;">
                        {{ $page1AmountInWords }}
                    </div>
                </td>
                <td width="40%" valign="top" align="right">
                    <div style="border: 1.5px solid #E2D7C1; border-radius: 5px; padding: 6px 10px; background-color: #FFFDF7; display: inline-block; text-align: left;">
                        <table width="100%">
                            <tr>
                                <td width="20" valign="middle" style="font-size: 14px; color: #8C6512;">📝</td>
                                <td style="font-size: 7.5px; color: #333333; line-height: 1.25;">
                                    GST Invoice will be provided once payment is completed.
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- TERMS & CONDITIONS & SIGNATURE -->
        <table width="100%" style="margin-top: 12px;">
            <tr>
                <td width="65%" valign="top">
                    <div class="section-pill">TERMS & CONDITIONS</div>
                    <div class="terms-box">
                        <ul class="terms-list">
                            <li>Goods once sold will not be exchanged or returned.</li>
                            <li>Please ensure to verify the item before completing the purchase.</li>
                            <li>Company is not responsible for any loss after delivery.</li>
                            <li>All disputes are subject to Mysuru jurisdiction only.</li>
                            <li>All our terms and conditions available in website it's included in this.</li>
                        </ul>
                    </div>
                </td>
                <td width="35%" valign="bottom" class="signatory-box">
                    <div style="font-size: 8px; color: #555555; margin-bottom: 2px;">For Auron Gold Private Limited</div>
                    @if(!empty($signatureImageSrc))
                        <img src="{{ $signatureImageSrc }}" style="max-height: 38px; max-width: 130px; object-fit: contain; margin-bottom: 2px;" alt="Signature">
                    @else
                        <div class="signature-font">Harshith</div>
                    @endif
                    <div style="border-top: 1.5px solid #111111; width: 130px; margin-left: auto; margin-top: 2px;"></div>
                    <div style="font-size: 8.5px; font-weight: bold; color: #111111; margin-top: 2px;">Authorised Signatory</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ========================================================= -->
    <!-- PAGE 2: PAYMENT RECEIPT                                   -->
    <!-- ========================================================= -->
    <div class="page-break"></div>

    <div class="page-container">
        <!-- HEADER -->
        <table width="100%">
            <tr>
                <td width="55%" align="left" valign="top">
                    <table width="100%">
                        <tr>
                            @if($logoExists)
                                <td width="55" valign="middle">
                                    <img src="{{ $logoPath }}" style="height: 46px; width: auto;" alt="AurOnGold">
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
                <td width="45%" align="right" valign="top" class="contact-info-block">
                    📍 73, 1st Floor, Samathawoods Layout,<br>
                    Bhogadi, Gaddige Main Road,<br>
                    Mysuru - 570026, Karnataka<br>
                    📞 7337616333 &nbsp;|&nbsp; ✉ support@aurongold.in<br>
                    🌐 www.aurongold.in
                </td>
            </tr>
        </table>

        <div class="header-divider"></div>

        <!-- PAYMENT RECEIPT BANNER -->
        <div style="text-align: center; margin-bottom: 10px;">
            <div style="display: inline-block; background-color: #8C6512; color: #FFFFFF; font-weight: bold; font-size: 11px; padding: 4px 18px; border-radius: 4px; letter-spacing: 1px;">
                ❖ &nbsp; PAYMENT RECEIPT &nbsp; ❖
            </div>
        </div>

        <!-- TOP DETAILS: RECEIPT META & CUSTOMER DETAILS -->
        <table width="100%" style="margin-bottom: 10px;">
            <tr>
                <td width="49%" valign="top">
                    <table class="info-table">
                        <tr>
                            <td class="info-label" style="width: 42%;">Receipt No.</td>
                            <td class="info-colon">:</td>
                            <td class="info-value">AG-PR-{{ date('Y') }}-{{ str_pad($payment->id ?? $invoice->id, 6, '0', STR_PAD_LEFT) }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Booking ID</td>
                            <td class="info-colon">:</td>
                            <td class="info-value" style="font-weight: normal;">{{ $booking->booking_number }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Receipt Date</td>
                            <td class="info-colon">:</td>
                            <td class="info-value" style="font-weight: normal;">{{ optional($payment->created_at ?? $invoice->created_at)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Payment Date & Time</td>
                            <td class="info-colon">:</td>
                            <td class="info-value" style="font-weight: normal;">{{ optional($payment->payment_date ?? $payment->created_at)->format('d/m/Y | h:i A') }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Payment Method</td>
                            <td class="info-colon">:</td>
                            <td class="info-value" style="font-weight: normal;">{{ ucfirst($payment->payment_method ?? 'Online Payment (UPI)') }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Payment Status</td>
                            <td class="info-colon">:</td>
                            <td class="info-value" style="color: #2e7d32; font-weight: bold;">Successful</td>
                        </tr>
                    </table>
                </td>
                <td width="2%"></td>
                <td width="49%" valign="top">
                    <div class="section-pill">CUSTOMER DETAILS</div>
                    <div class="card-box" style="min-height: 100px;">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Customer Name</td>
                                <td class="info-colon">:</td>
                                <td class="info-value">{{ $customerName }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Customer ID</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal;">{{ $customerCode }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Mobile Number</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal;">{{ $customerPhone }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Email Address</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal; font-size: 8px; word-break: break-all;">{{ $customerEmail }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Address</td>
                                <td class="info-colon">:</td>
                                <td class="info-value" style="font-weight: normal; font-size: 8px; line-height: 1.25;">{{ $fullAddress }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- CHARGES BREAKDOWN TABLE -->
        <table class="data-table">
            <thead>
                <tr>
                    <th width="10%">SR. NO.</th>
                    <th width="65%">DESCRIPTION</th>
                    <th width="25%">AMOUNT (₹)</th>
                </tr>
            </thead>
            <tbody>
                @php $receiptSr = 1; @endphp
                @if($financeCharge > 0)
                <tr>
                    <td align="center">{{ $receiptSr++ }}</td>
                    <td>Gold Price Lock Charges</td>
                    <td align="right">₹{{ number_format($financeCharge, 2) }}</td>
                </tr>
                @endif

                @if($storageCharge > 0)
                <tr>
                    <td align="center">{{ $receiptSr++ }}</td>
                    <td>Service Charges</td>
                    <td align="right">₹{{ number_format($storageCharge, 2) }}</td>
                </tr>
                @endif

                @if($gstOnCharges > 0)
                <tr>
                    <td align="center">{{ $receiptSr++ }}</td>
                    <td>GST on Service Charges ({{ number_format($booking->gst_on_charges_percent ?? 18.00, 2) }}%)</td>
                    <td align="right">₹{{ number_format($gstOnCharges, 2) }}</td>
                </tr>
                @endif

                @if($savingsAmount > 0)
                <tr>
                    <td align="center">{{ $receiptSr++ }}</td>
                    <td style="color: #c53030;">Promo Savings / Discount</td>
                    <td align="right" style="color: #c53030;">-₹{{ number_format($savingsAmount, 2) }}</td>
                </tr>
                @endif

                @if($totalReceiptCharges <= 0 && $financeCharge <= 0 && $storageCharge <= 0)
                <tr>
                    <td align="center">1</td>
                    <td>Other Charges (All Inclusive)</td>
                    <td align="right">₹0.00</td>
                </tr>
                @endif

                <tr class="total-banner-row">
                    <td colspan="2" align="right">TOTAL CHARGES (ALL INCLUSIVE)</td>
                    <td align="right">₹{{ number_format($totalReceiptCharges, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- AMOUNT IN WORDS & INFO NOTICE -->
        <table width="100%" style="margin-top: 8px;">
            <tr>
                <td width="60%" valign="top">
                    <div style="font-size: 8.5px; font-weight: bold; color: #8C6512;">AMOUNT IN WORDS:</div>
                    <div style="font-size: 8px; color: #111111; margin-top: 2px; line-height: 1.3;">
                        {{ $page2AmountInWords }}
                    </div>
                </td>
                <td width="40%" valign="top" align="right">
                    <div style="border: 1.5px solid #E2D7C1; border-radius: 5px; padding: 6px 10px; background-color: #FFFDF7; display: inline-block; text-align: left;">
                        <table width="100%">
                            <tr>
                                <td width="20" valign="middle" style="font-size: 14px; color: #8C6512;">💳</td>
                                <td style="font-size: 7.5px; color: #333333; line-height: 1.25;">
                                    This is a payment receipt for advance charges received.
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- NOTE BOX -->
        <div style="border: 1.5px solid #E2D7C1; border-radius: 4px; padding: 5px 8px; background-color: #FFFDF7; margin-top: 8px;">
            <div style="font-size: 8.5px; font-weight: bold; color: #8C6512; margin-bottom: 2px;">NOTE:</div>
            <div style="font-size: 7.5px; color: #333333; line-height: 1.3;">
                • This receipt is issued for the advance payment received towards Gold Price Lock Charges, Service Charges and Other Charges (All Inclusive).
            </div>
        </div>

        <!-- TERMS, QR CODE & SIGNATURE -->
        <table width="100%" style="margin-top: 8px;">
            <tr>
                <td width="50%" valign="top">
                    <div class="section-pill">TERMS & CONDITIONS</div>
                    <div class="terms-box">
                        <ul class="terms-list">
                            <li>This receipt is valid subject to realisation of payment.</li>
                            <li>All our terms and conditions available in website it's included in this.</li>
                            <li>Company reserves the right to modify or change any charges/terms without prior notice.</li>
                            <li>This is a computer generated receipt and does not require any physical signature.</li>
                        </ul>
                    </div>
                </td>
                <td width="22%" align="center" valign="middle">
                    @if(!empty($qrImageSrc))
                        <img src="{{ $qrImageSrc }}" style="width: 55px; height: 55px; display: block; margin: 0 auto;" alt="QR Code"><br>
                        <span style="font-size: 6.5px; color: #555555;">Scan to visit our website</span>
                    @endif
                </td>
                <td width="28%" valign="bottom" class="signatory-box">
                    <div style="font-size: 8px; color: #555555; margin-bottom: 2px;">For Auron Gold Private Limited</div>
                    @if(!empty($signatureImageSrc))
                        <img src="{{ $signatureImageSrc }}" style="max-height: 38px; max-width: 130px; object-fit: contain; margin-bottom: 2px;" alt="Signature">
                    @else
                        <div class="signature-font">Harshith</div>
                    @endif
                    <div style="border-top: 1.5px solid #111111; width: 130px; margin-left: auto; margin-top: 2px;"></div>
                    <div style="font-size: 8.5px; font-weight: bold; color: #111111; margin-top: 2px;">Authorised Signatory</div>
                </td>
            </tr>
        </table>

        <!-- FOOTER -->
        <div class="footer-banner">
            Thank you for choosing Auron Gold Private Limited.
        </div>
    </div>

</body>
</html>
