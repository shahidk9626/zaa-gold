@php
    $booking = $invoice->booking;
    $customer = $invoice->customer;
    $payment = $invoice->payment;
    $product = $booking->product;
    $plan = $booking->emiPlan;

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

    $verificationCode = strtoupper(substr(md5($invoice->invoice_number . ($customer->email ?? '')), 0, 8));

    // Calculate invoice breakups
    $goldValue = (float)$invoice->gold_value;
    $gstOnGold = (float)$invoice->gst_on_gold_amount;
    $storagePriceLockCharges = (float)($invoice->finance_charge + $invoice->storage_charge);
    $subtotalB = $goldValue + $gstOnGold + $storagePriceLockCharges;
    
    $processingFee = (float)($plan->processing_fee ?? 0.00);
    $gstOnServiceCharges = (float)$invoice->gst_on_charges_amount;
    $totalInvoiceAmount = $subtotalB + $processingFee + $gstOnServiceCharges;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice_{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 10mm 8mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #1e1b15;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .outer-frame {
            border: 2px solid #b4831b;
            border-radius: 8px;
            padding: 12px;
            background-color: #ffffff;
            position: relative;
        }
        
        /* Print header bar style */
        .no-print-bar {
            background-color: #fcfaf5;
            border: 1px solid #e8e2d2;
            padding: 8px 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            text-align: right;
        }
        .btn {
            display: inline-block;
            padding: 4px 8px;
            margin-left: 5px;
            font-size: 10px;
            font-weight: bold;
            cursor: pointer;
            border: 1px solid #b4831b;
            border-radius: 3px;
            text-decoration: none;
            background-color: #ffffff;
            color: #b4831b;
        }
        .btn-primary {
            background-color: #b4831b;
            color: #ffffff;
        }
        
        table {
            border-collapse: collapse;
        }
        .header-table {
            width: 100%;
            margin-bottom: 8px;
        }
        .logo-img {
            height: 42px;
            width: auto;
        }
        .company-details-header {
            font-size: 8px;
            color: #555555;
            line-height: 1.25;
            margin-top: 3px;
        }
        .invoice-title-banner {
            background-color: #b4831b;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 3px 10px;
            text-align: right;
            border-radius: 2px;
            display: inline-block;
            letter-spacing: 0.5px;
        }
        .invoice-meta-table {
            font-size: 8px;
            margin-top: 4px;
        }
        .invoice-meta-table td {
            padding: 1.5px 0;
        }

        /* Details side by side columns */
        .section-table {
            width: 100%;
            margin-bottom: 8px;
        }
        .section-header-bar {
            background-color: #b4831b;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 2px;
            margin-bottom: 4px;
        }
        .details-box {
            border: 1px solid #e8e2d2;
            border-radius: 4px;
            padding: 6px;
            height: 110px; /* Fixed height for clean A4 alignment */
            background-color: #ffffff;
        }
        .details-subtable {
            width: 100%;
            font-size: 8px;
        }
        .details-subtable td {
            padding: 1.5px 0;
            vertical-align: top;
        }

        /* Main Details Table Styles */
        .items-table {
            width: 100%;
            margin-bottom: 8px;
            border: 1px solid #e8e2d2;
            border-radius: 4px;
        }
        .items-table th {
            background-color: #fdfaf3;
            color: #78350f;
            font-weight: bold;
            border-bottom: 1px solid #e8e2d2;
            padding: 5px;
            text-align: left;
            font-size: 8.5px;
        }
        .items-table td {
            padding: 5px;
            border-bottom: 1px solid #e8e2d2;
            font-size: 8px;
        }
        .notes-text {
            font-size: 7.5px;
            color: #666666;
            line-height: 1.2;
        }
        .right-aligned-total {
            background-color: #fffdf5;
            border-left: 1px solid #e8e2d2;
            font-weight: bold;
            color: #78350f;
            font-size: 9px;
        }

        /* Invoice Breakup Table */
        .breakup-table {
            width: 100%;
            margin-bottom: 8px;
            border: 1px solid #e8e2d2;
            border-radius: 4px;
        }
        .breakup-table th {
            background-color: #fdfaf3;
            color: #78350f;
            font-weight: bold;
            border-bottom: 1px solid #e8e2d2;
            padding: 4px 6px;
            text-align: left;
            font-size: 8.5px;
        }
        .breakup-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #f3eedf;
            font-size: 8px;
        }
        .highlight-breakup-row {
            background-color: #faf5eb;
            font-weight: bold;
            color: #78350f;
            border-top: 1px solid #b4831b;
            border-bottom: 1px solid #b4831b;
        }

        /* Words & Total block */
        .words-total-table {
            width: 100%;
            margin-bottom: 8px;
        }
        .words-card {
            border: 1px solid #e8e2d2;
            border-radius: 4px;
            padding: 6px;
            background-color: #ffffff;
            height: 38px;
        }
        .total-card {
            border: 2px solid #b4831b;
            border-radius: 4px;
            padding: 6px;
            background-color: #fffdf5;
            text-align: center;
            height: 38px;
        }

        /* 5. Notes / Terms / Bank details grid cards */
        .info-cards-table {
            width: 100%;
            margin-bottom: 8px;
        }
        .info-card-box {
            border: 1px solid #e8e2d2;
            border-radius: 4px;
            padding: 5px;
            height: 105px; /* Fixed height to keep spacing clean */
            background-color: #ffffff;
        }
        .card-bullets {
            padding-left: 8px;
            margin: 0;
            font-size: 7.5px;
            color: #555555;
            line-height: 1.25;
        }
        .card-bullets li {
            margin-bottom: 2px;
        }
        
        .seal-stamp {
            position: absolute;
            top: -12px;
            right: 4px;
            width: 44px;
            height: 44px;
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
            font-size: 8px;
            color: #b4831b;
            font-weight: bold;
            margin: 1px 0;
        }

        /* Contact strip */
        .contact-strip {
            width: 100%;
            border-top: 1px solid #e8e2d2;
            padding-top: 4px;
            margin-top: 6px;
            font-size: 7.5px;
            color: #555555;
            text-align: center;
        }
        .contact-strip td {
            padding: 1px;
        }

        /* Bottom banner */
        .bottom-banner {
            margin-top: 6px;
            background-color: #1e1b15;
            border-top: 2px solid #b4831b;
            border-bottom: 2px solid #b4831b;
            padding: 4px;
            font-size: 9px;
            color: #d4af37;
            text-align: center;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        
        @media print {
            .no-print-bar {
                display: none !important;
            }
            .outer-frame {
                border: 2px solid #b4831b !important;
                padding: 12px !important;
            }
        }
    </style>
</head>
<body>
    <div class="outer-frame">
        <!-- Print Header controls -->
        @if(isset($isPrint) && $isPrint)
            <div class="no-print-bar">
                <span style="float: left; font-size: 11px; font-weight: bold; color: #b4831b; padding-top: 4px;">AurOnGold Final GST Invoice Preview</span>
                <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
                <button onclick="window.close()" class="btn">Close Preview</button>
                <div style="clear: both;"></div>
            </div>
        @endif

        <!-- Header -->
        <table class="header-table" width="100%">
            <tr>
                <td width="55%" align="left" valign="top">
                    @if(file_exists(public_path('assets/images/logo.png')))
                        <img src="{{ public_path('assets/images/logo.png') }}" class="logo-img" alt="AurOnGold">
                    @else
                        <span style="font-size: 20px; font-weight: bold; color: #b4831b;">AurOnGold</span>
                    @endif
                    <div style="font-size: 10px; color: #b4831b; font-weight: bold; letter-spacing: 1px; margin-top: 2px;">AurOnGold</div>
                    <table class="company-details-header" width="100%">
                        <tr>
                            <td width="15%"><strong>GSTIN</strong></td>
                            <td width="5%">:</td>
                            <td>29ABCDE1234F1Z5</td>
                        </tr>
                        <tr>
                            <td><strong>PAN</strong></td>
                            <td>:</td>
                            <td>ABCDE1234F</td>
                        </tr>
                        <tr>
                            <td valign="top"><strong>Regd. Office</strong></td>
                            <td valign="top">:</td>
                            <td>#73, First Floor, Sumatha Woods Layout, Martikyatanahalli Circle, Mysuru - 570026, Karnataka, India</td>
                        </tr>
                    </table>
                </td>
                <td width="25%" align="right" valign="top">
                    <div class="invoice-title-banner">FINAL GST TAX INVOICE</div>
                    <table class="invoice-meta-table" width="100%">
                        <tr>
                            <td width="50%" align="right"><strong>Invoice No.</strong></td>
                            <td width="8%" align="center">:</td>
                            <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        </tr>
                        <tr>
                            <td align="right"><strong>Invoice Date</strong></td>
                            <td align="center">:</td>
                            <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td align="right"><strong>Place of Supply</strong></td>
                            <td align="center">:</td>
                            <td>{{ $customer->customerDetail->state ?? 'Karnataka' }} ({{ $customer->customerDetail->state_code ?? '29' }})</td>
                        </tr>
                        <tr>
                            <td align="right"><strong>Reverse Charge</strong></td>
                            <td align="center">:</td>
                            <td>No</td>
                        </tr>
                        <tr>
                            <td align="right"><strong>State Code</strong></td>
                            <td align="center">:</td>
                            <td>{{ $customer->customerDetail->state_code ?? '29' }}</td>
                        </tr>
                    </table>
                </td>
                <td width="20%" align="right" valign="top">
                    <!-- Gold trust badge ribbon seal SVG -->
                    <svg width="60" height="80" viewBox="0 0 120 160" style="display: block;">
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

        <!-- Contact top strip -->
        <table width="100%" style="font-size: 8px; color: #666; border-top: 1px solid #e8e2d2; border-bottom: 1px solid #e8e2d2; padding: 3px 0; margin-bottom: 8px; text-align: center;">
            <tr>
                <td>🌐 www.aurongold.in</td>
                <td>✉ support@aurongold.in</td>
                <td>📞 +91 73376 16333</td>
            </tr>
        </table>

        <!-- Customer & Purchase details -->
        <table class="section-table" width="100%" cellspacing="0">
            <tr>
                <!-- Customer Details -->
                <td width="48%" valign="top">
                    <div class="details-box">
                        <div class="section-header-bar">👤 BILLING DETAILS (CUSTOMER)</div>
                        <table class="details-subtable">
                            <tr>
                                <td width="30%"><strong>Customer Name</strong></td>
                                <td width="5%">:</td>
                                <td>{{ $invoice->customer_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Customer ID</strong></td>
                                <td>:</td>
                                <td>{{ $customer->customerDetail->customer_code ?? 'AGCUST' . str_pad($customer->id, 6, '0', STR_PAD_LEFT) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Mobile Number</strong></td>
                                <td>:</td>
                                <td>{{ $invoice->customer_phone }}</td>
                            </tr>
                            <tr>
                                <td><strong>Email Address</strong></td>
                                <td>:</td>
                                <td>{{ $invoice->customer_email }}</td>
                            </tr>
                            <tr>
                                <td><strong>Billing Address</strong></td>
                                <td>:</td>
                                <td style="line-height: 1.2;">{{ $invoice->billing_address }}</td>
                            </tr>
                            <tr>
                                <td><strong>State</strong></td>
                                <td>:</td>
                                <td>{{ $customer->customerDetail->state ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>GSTIN</strong></td>
                                <td>:</td>
                                <td>{{ $customer->customerDetail->gst_number ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="4%">&nbsp;</td>
                <!-- Purchase Details -->
                <td width="48%" valign="top">
                    <div class="details-box">
                        <div class="section-header-bar">📌 REFERENCE & PURCHASE DETAILS</div>
                        <table class="details-subtable">
                            <tr>
                                <td width="40%"><strong>Booking Ref</strong></td>
                                <td width="5%">:</td>
                                <td>{{ $booking->booking_number }}</td>
                            </tr>
                            <tr>
                                <td><strong>Booking Date</strong></td>
                                <td>:</td>
                                <td>{{ $booking->booking_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Plan Type</strong></td>
                                <td>:</td>
                                <td>{{ $booking->duration_months }} Months Gold Purchase Plan (EMAP)</td>
                            </tr>
                            <tr>
                                <td><strong>Gold Product</strong></td>
                                <td>:</td>
                                <td>{{ $invoice->product_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gold Purity</strong></td>
                                <td>:</td>
                                <td>{{ $product->gold_purity ?? '24KT (999.9) Fine Gold' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gold Weight Purchased</strong></td>
                                <td>:</td>
                                <td>{{ number_format($invoice->gold_weight, 3) }} g</td>
                            </tr>
                            <tr>
                                <td><strong>Locked Gold Price</strong></td>
                                <td>:</td>
                                <td>₹{{ number_format($invoice->locked_gold_price, 2) }} per gram (Purity: 999.9%)</td>
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

        <!-- Product Table -->
        <table class="items-table" width="100%" cellspacing="0">
            <thead>
                <tr>
                    <th width="50%">Description</th>
                    <th width="15%" align="right">Gold Weight (g)</th>
                    <th width="15%" align="right">Locked Price (Per g)</th>
                    <th width="20%" align="right">Gold Value (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td valign="top">
                        <strong>{{ $invoice->product_name }}</strong><br>
                        Gold bars/coins locked as per selected plan and accumulated monthly.
                    </td>
                    <td valign="top" align="right">{{ number_format($invoice->gold_weight, 3) }}</td>
                    <td valign="top" align="right">₹{{ number_format($invoice->locked_gold_price, 2) }}</td>
                    <td valign="top" align="right">₹{{ number_format($invoice->gold_value, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="notes-text" valign="middle">
                        * Gold weight is in grams. Final product (Coin/Bar) will be delivered upon completion of the plan, subject to stock availability.
                    </td>
                    <td class="right-aligned-total" align="right" valign="middle">TOTAL GOLD VALUE (A)</td>
                    <td class="right-aligned-total" align="right" valign="middle">₹{{ number_format($invoice->gold_value, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Invoice Breakup Table -->
        <table class="breakup-table" width="100%" cellspacing="0">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="50%">Particulars</th>
                    <th width="25%">Calculation</th>
                    <th width="20%" align="right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Gold Value ({{ number_format($invoice->gold_weight, 3) }} g @ ₹{{ number_format($invoice->locked_gold_price, 2) }})</td>
                    <td>A</td>
                    <td align="right">₹{{ number_format($invoice->gold_value, 2) }}</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>GST on Gold @ {{ number_format($invoice->gst_on_gold_percent, 1) }}%</td>
                    <td>{{ number_format($invoice->gst_on_gold_percent, 1) }}% of A</td>
                    <td align="right">₹{{ number_format($invoice->gst_on_gold_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Gold Storage & Price Locking Charges @ {{ number_format($booking->finance_charge_percent + $booking->storage_charge_percent, 1) }}%</td>
                    <td>{{ number_format($booking->finance_charge_percent + $booking->storage_charge_percent, 1) }}% of A</td>
                    <td align="right">₹{{ number_format($storagePriceLockCharges, 2) }}</td>
                </tr>
                <tr class="highlight-breakup-row">
                    <td>&nbsp;</td>
                    <td>SUB TOTAL (GOLD VALUE + GST + STORAGE & PRICE LOCKING)</td>
                    <td>B (1 + 2 + 3)</td>
                    <td align="right">₹{{ number_format($subtotalB, 2) }}</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>Our Service Charges (Processing Fee + Platform Convenience Fee + Insured Delivery Charges) @ 6%</td>
                    <td>6% of A</td>
                    <td align="right">₹{{ number_format($processingFee, 2) }}</td>
                </tr>
                <tr>
                    <td>5</td>
                    <td>GST on Our Service Charges @ {{ number_format($invoice->gst_on_charges_percent, 1) }}%</td>
                    <td>{{ number_format($invoice->gst_on_charges_percent, 1) }}% of 4</td>
                    <td align="right">₹{{ number_format($gstOnServiceCharges, 2) }}</td>
                </tr>
                <tr class="highlight-breakup-row">
                    <td>&nbsp;</td>
                    <td>TOTAL INVOICE AMOUNT</td>
                    <td>B + 4 + 5</td>
                    <td align="right">₹{{ number_format($totalInvoiceAmount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Words and Total Block -->
        <table class="words-total-table" width="100%" cellspacing="0">
            <tr>
                <td width="65%" valign="top">
                    <div class="words-card">
                        <div style="font-size: 7.5px; color: #777777; font-weight: bold; text-transform: uppercase;">
                            <!-- Coins stack icon -->
                            💰 Amount in Words:
                        </div>
                        <div style="font-size: 8.5px; font-weight: bold; margin-top: 3px; color: #78350f;">
                            {{ $amountInWords }}
                        </div>
                    </div>
                </td>
                <td width="5%">&nbsp;</td>
                <td width="30%" valign="top">
                    <div class="total-card">
                        <div style="font-size: 7.5px; color: #777777; font-weight: bold; text-transform: uppercase;">TOTAL AMOUNT PAYABLE</div>
                        <div style="font-size: 13px; font-weight: bold; color: #b4831b; margin-top: 2px;">₹{{ number_format($totalInvoiceAmount, 2) }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Row 5 Cards -->
        <table class="info-cards-table" width="100%" cellspacing="0">
            <tr>
                <!-- Notes -->
                <td width="36%" valign="top">
                    <div class="info-card-box">
                        <div class="section-header-bar">⚙ IMPORTANT NOTES</div>
                        <ul class="card-bullets">
                            <li>Gold price is locked as on booking date and will not change during the selected plan period.</li>
                            <li>Gold Storage & Price Locking Charges are calculated @ 12% of the total gold value.</li>
                            <li>Our Service Charges include Processing Fee, Platform Convenience Fee and Insured Delivery Charges calculated @ 6% of the total gold value.</li>
                            <li>GST on gold is charged @ 3% as per applicable GST law.</li>
                            <li>GST on our service charges is charged @ 18%.</li>
                            <li>Gold Coin / Bar (Biscuit) will be delivered after successful completion of the plan and KYC verification.</li>
                            <li>Purity Certificate will be provided with the product at the time of delivery.</li>
                        </ul>
                    </div>
                </td>
                <td width="3%">&nbsp;</td>
                <!-- Bank & Verification -->
                <td width="30%" valign="top">
                    <div class="info-card-box" style="height: 105px;">
                        <div class="section-header-bar">🏦 BANK DETAILS (FOR REFERENCE)</div>
                        <table style="font-size: 7.5px; width: 100%; margin-bottom: 3px;">
                            <tr><td width="42%"><strong>Account Name</strong></td><td>: AurOnGold</td></tr>
                            <tr><td><strong>Bank Name</strong></td><td>: HDFC Bank</td></tr>
                            <tr><td><strong>Account No.</strong></td><td>: 50200012345678</td></tr>
                            <tr><td><strong>IFSC Code</strong></td><td>: HDFC0001234</td></tr>
                            <tr><td><strong>Account Type</strong></td><td>: Current Account</td></tr>
                        </table>
                        
                        <div class="section-header-bar" style="margin-top: 2px; margin-bottom: 2px; font-size: 7px; padding: 1.5px 4px;">🛡 VERIFY THIS INVOICE</div>
                        <table width="100%">
                            <tr>
                                <td width="30%" align="center">
                                    @if(!empty($qrImageSrc))
                                        <img src="{{ $qrImageSrc }}" style="width: 26px; height: 26px; border: 1px solid #e8e2d2;" alt="QR">
                                    @else
                                        <div style="border: 1px dashed #ccc; width: 26px; height: 26px; line-height: 26px; font-size: 5px; color: #999;">QR</div>
                                    @endif
                                </td>
                                <td valign="middle" style="font-size: 6px; color: #666; line-height: 1.1; padding-left: 4px;">
                                    Verification Code: <strong>{{ $verificationCode }}</strong><br>
                                    Scan QR code to verify invoice authenticity.
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="3%">&nbsp;</td>
                <!-- Terms & Signature -->
                <td width="28%" valign="top">
                    <div class="info-card-box">
                        <div class="section-header-bar">📜 TERMS & CONDITIONS</div>
                        <ul class="card-bullets">
                            <li>This is the final GST Tax Invoice.</li>
                            <li>Final GST Invoice is issued only after full payment of the plan amount with all applicable charges.</li>
                            <li>All amounts are inclusive of applicable taxes.</li>
                            <li>This is a computer generated invoice and does not require physical signature.</li>
                        </ul>
                        
                        <div style="height: 24px; position: relative; margin-top: 8px;">
                            <span style="font-family: Georgia, serif; font-style: italic; font-size: 11px; color: #2e40e2; font-weight: bold; display: inline-block;">AurOnGold Official</span>
                            <div class="seal-stamp" style="top: -24px; right: 2px; width: 34px; height: 34px;">
                                <div class="seal-text-small" style="margin-top: 5px; font-size: 3px;">AURONGOLD</div>
                                <div class="seal-text-large" style="font-size: 7px; margin: 0;">AG</div>
                                <div class="seal-text-small" style="font-size: 3px; margin: 0;">SECURE</div>
                            </div>
                        </div>
                        <div style="border-top: 1px solid #999; font-size: 7px; font-weight: bold; text-align: center; margin-top: 2px;">Authorized Signatory</div>
                        <div style="font-size: 6px; color: #666; text-align: center;">AurOnGold</div>
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
