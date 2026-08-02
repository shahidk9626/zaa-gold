@php
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
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Price Lock Certificate - {{ $certificate->certificate_number }}</title>
    
    <!-- Load Google Fonts for Browser Preview -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <style>
        /* PDF Specific Page Styling */
        @page {
            size: A4 portrait;
            margin: 0;
        }
        
        /* Reset and Base Styles */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Montserrat', 'Helvetica Neue', Arial, sans-serif;
            color: #2d3748;
            margin: 10px 12px;
            padding: 0;
            background-color: #ffffff;
            font-size: 7.2px;
            line-height: 1.2;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        /* Inner Container with Double Borders */
        .outer-border {
            border: 2px solid #d4af37;
            padding: 3px;
            background-color: #ffffff;
            width: 100%;
            height: auto;
            page-break-inside: avoid;
        }
        .inner-border {
            border: 1px solid #e5c158;
            padding: 6px 8px;
            background-color: #ffffff;
            position: relative;
            height: auto;
            page-break-inside: avoid;
        }
        
        /* Header Section */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
            page-break-inside: avoid;
        }
        .header-logo {
            width: 55%;
            vertical-align: top;
        }
        .logo-img {
            height: 38px;
            width: auto;
            display: block;
        }
        .header-badge {
            width: 45%;
            text-align: right;
            vertical-align: top;
        }
        
        /* Title Block */
        .title-block {
            text-align: center;
            margin-bottom: 4px;
            page-break-inside: avoid;
        }
        .main-title {
            font-family: 'Cinzel', 'Playfair Display', 'DejaVu Sans', serif;
            font-size: 13.5px;
            font-weight: 700;
            color: #aa8010;
            letter-spacing: 1px;
            margin: 0 0 1px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 6.5px;
            color: #4a5568;
            margin: 0 auto;
            max-width: 90%;
            font-weight: 500;
        }
        
        /* Top Information Bar */
        .info-bar-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            page-break-inside: avoid;
        }
        .info-bar-cell {
            width: 25%;
            padding: 2px 4px;
            border: 1px solid #f2e6c9;
            background-color: #fffdf6;
            vertical-align: middle;
        }
        .info-bar-icon-td {
            width: 12px;
            vertical-align: middle;
        }
        .info-bar-text-td {
            vertical-align: middle;
        }
        .info-bar-label {
            font-size: 5.5px;
            color: #718096;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0px;
        }
        .info-bar-value {
            font-size: 7px;
            font-weight: 700;
            color: #1a202c;
        }
        .info-bar-value.highlight {
            color: #aa8010;
        }
        
        /* Two Column Layout Table */
        .layout-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .left-column {
            width: 67%;
            vertical-align: top;
            padding-right: 4px;
        }
        .right-column {
            width: 33%;
            vertical-align: top;
            padding-left: 4px;
        }
        
        /* Subgrid for left cards */
        .left-cards-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .card-cell-left {
            width: 50%;
            vertical-align: top;
            padding-right: 3px;
            padding-bottom: 3px;
        }
        .card-cell-right {
            width: 50%;
            vertical-align: top;
            padding-left: 3px;
            padding-bottom: 3px;
        }
        .card-cell-full {
            width: 100%;
            vertical-align: top;
            padding-bottom: 3px;
        }
        
        /* Premium Card Styles */
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            background-color: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            height: auto;
            page-break-inside: avoid;
        }
        .card-header {
            background-color: #b89047;
            padding: 2.5px 5px;
            font-weight: 700;
            color: #ffffff;
            font-size: 7px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .card-header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-header-icon-td {
            width: 12px;
            vertical-align: middle;
        }
        .card-header-title-td {
            vertical-align: middle;
        }
        .card-body {
            padding: 3px 5px;
        }
        
        /* Key-Value styling within cards */
        .kv-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kv-table td {
            padding: 0.8px 0;
            vertical-align: top;
            font-size: 6.5px;
        }
        .kv-label {
            color: #718096;
            font-weight: 500;
            width: 40%;
        }
        .kv-separator {
            width: 8px;
            color: #b89047;
            text-align: center;
        }
        .kv-value {
            color: #2d3748;
            font-weight: 600;
            width: 55%;
        }
        .kv-value.highlight-gold {
            color: #7c5c10;
        }
        .kv-value.highlight-black {
            color: #1a202c;
            font-weight: 700;
        }
        
        /* Customer Declaration */
        .declaration-text {
            font-size: 6px;
            color: #4a5568;
            line-height: 1.2;
            margin-bottom: 2px;
            text-align: justify;
        }
        .signature-area {
            text-align: center;
            margin-top: 4px;
            padding-top: 2px;
            border-top: 0.5px dashed #cbd5e0;
        }
        .signature-label {
            font-size: 5.5px;
            color: #718096;
            font-weight: 600;
        }
        
        /* Company Declaration SVG & Seal Layout */
        .company-declaration-layout {
            width: 100%;
            border-collapse: collapse;
        }
        .signatory-td {
            width: 65%;
            vertical-align: bottom;
            text-align: center;
        }
        .seal-td {
            width: 35%;
            vertical-align: middle;
            text-align: right;
        }
        
        /* Registered Office Block */
        .office-block-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            border: 1px dashed #e2e8f0;
            border-radius: 4px;
            background-color: #fafafa;
            page-break-inside: avoid;
        }
        
        /* Verification QR Card details */
        .verification-layout {
            width: 100%;
            border-collapse: collapse;
        }
        .qr-td {
            width: 12%;
            vertical-align: middle;
            text-align: left;
        }
        .qr-td img {
            width: 32px;
            height: 32px;
            border: 1px solid #e2e8f0;
            padding: 1px;
            background-color: #ffffff;
            display: block;
        }
        .verification-details-td {
            width: 88%;
            vertical-align: middle;
            padding-left: 6px;
        }
        .verification-point {
            font-size: 5.8px;
            color: #2e7d32;
            font-weight: 600;
            display: inline-block;
            margin-right: 12px;
        }
        
        /* Terms and Conditions Card */
        .terms-card {
            border: 1px solid #f2e6c9;
            border-radius: 4px;
            background-color: #fffdfa;
            height: auto;
            page-break-inside: avoid;
        }
        .terms-header {
            background-color: #aa8010;
            color: #ffffff;
            padding: 2.5px 5px;
            font-weight: 700;
            font-size: 7px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .terms-body {
            padding: 3px 5px;
        }
        .terms-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .terms-item {
            font-size: 5.8px;
            color: #555555;
            line-height: 1.15;
            margin-bottom: 1.8px;
            padding-left: 8px;
            position: relative;
            text-align: justify;
        }
        .terms-num {
            position: absolute;
            left: 0;
            top: 0;
            font-weight: 700;
            color: #aa8010;
        }
        
        /* Please Note Card */
        .note-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background-color: #f8fafc;
            margin-top: 2px;
            page-break-inside: avoid;
        }
        .note-item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1px;
        }
        .note-item-table:last-child {
            margin-bottom: 0;
        }
        .note-icon-td {
            width: 10px;
            vertical-align: top;
            padding-top: 1px;
        }
        .note-text-td {
            vertical-align: middle;
            font-size: 5.8px;
            color: #4a5568;
            font-weight: 500;
            line-height: 1.1;
        }
        
        /* Footer Ribbon */
        .footer-strip {
            background-color: #b89047;
            text-align: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 7px;
            letter-spacing: 0.5px;
            padding: 2px 0;
            margin-top: 3px;
            border-radius: 2px;
            text-transform: uppercase;
            page-break-inside: avoid;
        }
        
        /* Metadata block below certificate */
        .certificate-meta {
            text-align: center;
            font-size: 5.2px;
            color: #a0aec0;
            margin-top: 1px;
            page-break-inside: avoid;
        }
        
        /* Action Button for preview */
        .no-print {
            text-align: center;
            padding: 6px;
            background-color: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 6px;
        }
        .btn-print {
            background-color: #b89047;
            color: white;
            border: none;
            padding: 4px 12px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-print:hover {
            background-color: #aa8010;
        }
        
        /* General page-break avoidance */
        .card, .terms-card, .office-block-table {
            page-break-inside: avoid !important;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff;
                padding: 0;
                margin: 0;
            }
            .outer-border {
                border: 2px solid #d4af37 !important;
            }
        }
    </style>
</head>
<body>

    @if(isset($is_preview) && $is_preview)
        <div class="no-print">
            <button onclick="window.print()" class="btn-print">Print Price Lock Certificate</button>
        </div>
    @endif

    <div class="outer-border">
        <div class="inner-border">
            
            <!-- Corner Ornaments (CSS styled borders) -->
            <div style="position: absolute; top: 0; left: 0; width: 10px; height: 10px; border-top: 2px solid #d4af37; border-left: 2px solid #d4af37;"></div>
            <div style="position: absolute; top: 0; right: 0; width: 10px; height: 10px; border-top: 2px solid #d4af37; border-right: 2px solid #d4af37;"></div>
            <div style="position: absolute; bottom: 0; left: 0; width: 10px; height: 10px; border-bottom: 2px solid #d4af37; border-left: 2px solid #d4af37;"></div>
            <div style="position: absolute; bottom: 0; right: 0; width: 10px; height: 10px; border-bottom: 2px solid #d4af37; border-right: 2px solid #d4af37;"></div>

            <!-- HEADER -->
            <table class="header-table" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="header-logo" valign="top">
                        @if(file_exists(public_path('assets/images/logo.png')))
                            <img src="{{ public_path('assets/images/logo.png') }}" class="logo-img" alt="Logo" style="height: 44px; object-fit: contain;">
                        @else
                            <span style="font-size: 18px; font-weight: bold; color: #b89047;">AurOnGold</span>
                        @endif
                        <div style="font-size: 8px; font-weight: bold; color: #aa8010; letter-spacing: 0.5px; margin-top: 2px;">AurOnGold</div>
                        <div style="font-size: 6px; color: #718096; font-style: italic; margin-top: 1px;">Invest Smart. Grow Secure.</div>
                    </td>
                    <td class="header-badge" valign="top">
                        <!-- TRUSTED TRANSPARENT SECURE SVG BADGE -->
                        <svg width="42" height="42" viewBox="0 0 120 120" style="display: inline-block; vertical-align: middle;">
                            <g fill="#d4af37">
                                <path d="M 45 75 L 35 110 L 52 100 L 60 110 L 52 75 Z" />
                                <path d="M 75 75 L 85 110 L 68 100 L 60 110 L 68 75 Z" />
                            </g>
                            <circle cx="60" cy="60" r="45" fill="#fcf7e8" stroke="#d4af37" stroke-width="2" />
                            <circle cx="60" cy="60" r="41" fill="none" stroke="#aa8010" stroke-width="1" stroke-dasharray="3,3" />
                            <circle cx="60" cy="60" r="36" fill="#fffdf9" stroke="#d4af37" stroke-width="1.5" />
                            <text x="60" y="44" font-family="'Montserrat', sans-serif" font-size="6" font-weight="bold" fill="#aa8010" text-anchor="middle" letter-spacing="1">TRUSTED</text>
                            <text x="60" y="54" font-family="'Montserrat', sans-serif" font-size="6" font-weight="bold" fill="#aa8010" text-anchor="middle" letter-spacing="1">TRANSPARENT</text>
                            <text x="60" y="64" font-family="'Montserrat', sans-serif" font-size="6" font-weight="bold" fill="#aa8010" text-anchor="middle" letter-spacing="1">SECURE</text>
                            <g fill="#d4af37">
                                <polygon points="60,71 61.5,74.5 65,74.5 62,76.5 63.5,80 60,78 56.5,80 58,76.5 55,74.5 58.5,74.5" />
                                <polygon points="50,73 51,75.5 53.5,75.5 51.5,77 52.5,79.5 50,78 47.5,79.5 48.5,77 46.5,75.5 49,75.5" />
                                <polygon points="70,73 71,75.5 73.5,75.5 71.5,77 72.5,79.5 70,78 67.5,79.5 68.5,77 66.5,75.5 69,75.5" />
                            </g>
                        </svg>
                    </td>
                </tr>
            </table>

            <!-- TITLE BLOCK -->
            <div class="title-block">
                <h1 class="main-title">Gold Price Lock Certificate</h1>
                <div class="subtitle">This certificate confirms the lock-in of gold price for your purchase as per the terms and conditions of AurOnGold Gold Savings Plan.</div>
            </div>

            <!-- TOP INFORMATION BAR -->
            <table class="info-bar-table" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="info-bar-cell">
                        <table style="width: 100%; border-collapse: collapse;" cellspacing="0" cellpadding="0">
                            <tr>
                                <td class="info-bar-icon-td">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#b89047" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                </td>
                                <td class="info-bar-text-td">
                                    <div class="info-bar-label">Certificate No.</div>
                                    <div class="info-bar-value highlight">AGL-PC-{{ substr($certificate->certificate_number, 3) }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="info-bar-cell">
                        <table style="width: 100%; border-collapse: collapse;" cellspacing="0" cellpadding="0">
                            <tr>
                                <td class="info-bar-icon-td">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#b89047" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </td>
                                <td class="info-bar-text-td">
                                    <div class="info-bar-label">Booking ID</div>
                                    <div class="info-bar-value">{{ $booking->booking_number }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="info-bar-cell">
                        <table style="width: 100%; border-collapse: collapse;" cellspacing="0" cellpadding="0">
                            <tr>
                                <td class="info-bar-icon-td">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#b89047" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </td>
                                <td class="info-bar-text-td">
                                    <div class="info-bar-label">Booking Date & Time</div>
                                    <div class="info-bar-value">{{ $booking->booking_date->format('d M Y | h:i A') }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="info-bar-cell">
                        <table style="width: 100%; border-collapse: collapse;" cellspacing="0" cellpadding="0">
                            <tr>
                                <td class="info-bar-icon-td">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#b89047" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </td>
                                <td class="info-bar-text-td">
                                    <div class="info-bar-label">Plan Completion Date</div>
                                    <div class="info-bar-value">{{ $booking->estimated_completion_date->format('d M Y') }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- MAIN SECTION LAYOUT -->
            <table class="layout-table" cellspacing="0" cellpadding="0">
                <tr>
                    <!-- LEFT COLUMN: Grid of Cards (67%) -->
                    <td class="left-column">
                        <table class="left-cards-table" cellspacing="0" cellpadding="0">
                            
                            <!-- Row 1: Customer Details & Gold Purchase Details -->
                            <tr>
                                <td class="card-cell-left">
                                    <div class="card">
                                        <div class="card-header">
                                            <table class="card-header-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="card-header-icon-td">
                                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                    </td>
                                                    <td class="card-header-title-td">1. Customer Details</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="card-body">
                                            <table class="kv-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="kv-label">Customer Name</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value highlight-black">{{ $customer->name ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Customer ID</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">AGCUST{{ str_pad($customer->id ?? 0, 6, '0', STR_PAD_LEFT) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Mobile Number</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $customer->customerDetail->alternate_number ?? $customer->phone ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Email Address</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="word-break: break-all;">{{ $customer->email ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">PAN (Masked)</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $panMasked }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Aadhaar (Last 4)</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $aadharMasked }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Billing Address</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="line-height: 1.2;">{{ $fullAddress }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                                <td class="card-cell-right">
                                    <div class="card">
                                        <div class="card-header">
                                            <table class="card-header-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="card-header-icon-td">
                                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                                                    </td>
                                                    <td class="card-header-title-td">2. Gold Purchase Details</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="card-body">
                                            <table class="kv-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="kv-label">Product Name</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value highlight-black">{{ $product->name ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Product Code (SKU)</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $product->sku ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Gold Purity</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ number_format($booking->gold_purity, 2) }}% ({{ $product->gold_type ?? '24K' }}) Fine Gold</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Weight</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value highlight-gold">{{ number_format($booking->gold_weight, 2) }} Gram</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Product Type</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">Gold Coin / Bar (Biscuit)</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Quantity</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">1 Unit</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 2: Price Lock Details & Financial Summary -->
                            <tr>
                                <td class="card-cell-left">
                                    <div class="card">
                                        <div class="card-header">
                                            <table class="card-header-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="card-header-icon-td">
                                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                    </td>
                                                    <td class="card-header-title-td">3. Price Lock Details</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="card-body">
                                            <table class="kv-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="kv-label">Locked Gold Price per Gram</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value highlight-gold">₹{{ number_format($pricePerGram, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Locked Gold Value</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value highlight-gold">₹{{ number_format($booking->locked_gold_value, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Plan Type</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $plan->plan_name }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Number of EMAPs</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $booking->duration_months }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Monthly Advance Payment</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value highlight-black">₹{{ number_format($booking->monthly_emi, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Booking Date</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $booking->booking_date->format('d M Y') }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Plan Completion Date</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $booking->estimated_completion_date->format('d M Y') }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                                <td class="card-cell-right">
                                    <div class="card">
                                        <div class="card-header">
                                            <table class="card-header-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="card-header-icon-td">
                                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                                    </td>
                                                    <td class="card-header-title-td">4. Financial Summary</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="card-body">
                                            <table class="kv-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="kv-label" style="width: 50%;">Gold Value ({{ number_format($booking->gold_weight, 2) }}g @ ₹{{ number_format($booking->locked_price_per_gram, 2) }})</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="width: 45%; text-align: right;">₹{{ number_format($booking->locked_gold_value, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">GST on Gold ({{ number_format($booking->gst_on_gold_percent, 2) }}%)</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="text-align: right;">₹{{ number_format($booking->gst_on_gold_amount, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Price Locking Charges ({{ number_format($booking->finance_charge_percent, 2) }}%)</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="text-align: right;">₹{{ number_format($booking->finance_charge_amount, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Secure Storage Charges ({{ number_format($booking->storage_charge_percent, 2) }}%)</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="text-align: right;">₹{{ number_format($booking->storage_charge_amount, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Processing Fee</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="text-align: right;">₹{{ number_format($plan->processing_fee ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Platform Convenience Fee</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="text-align: right;">₹0.00</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Insured Delivery Charges</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="text-align: right;">₹0.00</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">GST on Service Charges ({{ number_format($booking->gst_on_charges_percent, 2) }}%)</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="text-align: right;">₹{{ number_format($booking->gst_on_charges_amount, 2) }}</td>
                                                </tr>
                                                <tr style="border-top: 1px double #e2e8f0; font-weight: bold;">
                                                    <td class="kv-label" style="font-weight: bold; color: #1a202c; padding-top: 2px;">GRAND TOTAL</td>
                                                    <td class="kv-separator" style="padding-top: 2px;">:</td>
                                                    <td class="kv-value highlight-gold" style="font-weight: 700; font-size: 8px; text-align: right; padding-top: 2px;">₹{{ number_format($booking->grand_total, 2) }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 3: Payment Status & Important Legal Declaration -->
                            <tr>
                                <td class="card-cell-left">
                                    <div class="card">
                                        <div class="card-header">
                                            <table class="card-header-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="card-header-icon-td">
                                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                                    </td>
                                                    <td class="card-header-title-td">5. Payment Details</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="card-body">
                                            <table class="kv-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="kv-label">Payment Method</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $firstPayment->payment_mode ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Payment Status</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">
                                                        <span style="color: #2e7d32; font-weight: bold;">{{ $booking->status }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Transaction Number</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value" style="word-break: break-all;">{{ $firstPayment->transaction_reference ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Receipt Number</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">{{ $firstPayment->receipt_number ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Amount Paid</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value highlight-gold">₹{{ number_format($amountPaid, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="kv-label">Outstanding Amount</td>
                                                    <td class="kv-separator">:</td>
                                                    <td class="kv-value">₹{{ number_format($outstandingAmount, 2) }}</td>
                                                </tr>
                                            </table>
                                            
                                            <!-- Green info box -->
                                            <div style="background-color: #f0fff4; border: 1px solid #c6f6d5; padding: 4px; border-radius: 4px; display: block; margin-top: 5px;">
                                                <table style="width: 100%; border-collapse: collapse;" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td style="width: 14px; vertical-align: middle; text-align: center;">
                                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#22543d" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                                        </td>
                                                        <td style="vertical-align: middle; font-size: 6.5px; color: #22543d; font-weight: 500; padding-left: 4px; line-height: 1.2;">
                                                            <strong>Thank you! Your plan is active.</strong> Please ensure timely payments to complete your gold purchase.
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="card-cell-right">
                                    <div class="card">
                                        <div class="card-header">
                                            <table class="card-header-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="card-header-icon-td">
                                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                                    </td>
                                                    <td class="card-header-title-td">6. Important Legal Declaration</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="card-body">
                                            <div class="declaration-text" style="font-weight: 600; color: #b7791f;">
                                                This certificate is NOT a certificate of ownership and does not transfer ownership of the gold.
                                            </div>
                                            <div class="declaration-text" style="padding-left: 4px;">
                                                Ownership will pass only after:<br>
                                                ✓ Full payment of all amounts due under the selected plan;<br>
                                                ✓ Successful completion of KYC verification;<br>
                                                ✓ Compliance with AurOnGold policies and T&C; and<br>
                                                ✓ Successful delivery of the gold product.
                                            </div>
                                            <div class="declaration-text">
                                                Valid only for the above mentioned weight, product and plan. It cannot be transferred, sold, or pledged.
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 4: Customer Declaration & Company Declaration -->
                            <tr>
                                <td class="card-cell-left">
                                    <div class="card">
                                        <div class="card-header">
                                            <table class="card-header-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="card-header-icon-td">
                                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                                    </td>
                                                    <td class="card-header-title-td">7. Customer Declaration</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="card-body">
                                            <div class="declaration-text">
                                                I confirm that I have read, understood and accepted the AurOnGold Terms & Conditions, Privacy Policy, and Shipping Policy.
                                            </div>
                                            <div class="signature-area">
                                                <div style="height: 10px;"></div>
                                                <div class="signature-label">Customer Signature</div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="card-cell-right">
                                    <div class="card">
                                        <div class="card-header">
                                            <table class="card-header-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="card-header-icon-td">
                                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                                    </td>
                                                    <td class="card-header-title-td">8. Company Declaration</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="card-body">
                                            <table class="company-declaration-layout" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="signatory-td">
                                                        <!-- blue ink SVG digital signature representation -->
                                                        <svg width="60" height="15" viewBox="0 0 150 50" style="display: block; margin: auto;">
                                                            <path d="M10,38 Q35,10 50,22 T95,12 T120,18 T140,8" fill="none" stroke="#1d4ed8" stroke-width="2.5" />
                                                            <path d="M5,42 C55,40 105,41 145,39" fill="none" stroke="#1d4ed8" stroke-width="1.5" />
                                                        </svg>
                                                        <div style="font-size: 5.5px; color: #1a202c; font-weight: bold; border-top: 0.5px solid #cbd5e0; margin-top: 1px; padding-top: 1px;">Authorised Signatory</div>
                                                    </td>
                                                    <td class="seal-td">
                                                        <!-- Gold Seal circular SVG representation -->
                                                        <svg width="26" height="26" viewBox="0 0 80 80" style="display: inline-block;">
                                                            <circle cx="40" cy="40" r="38" fill="none" stroke="#d4af37" stroke-width="1.5" stroke-dasharray="2,2" />
                                                            <circle cx="40" cy="40" r="34" fill="none" stroke="#d4af37" stroke-width="0.8" />
                                                            <circle cx="40" cy="40" r="30" fill="#fffdf6" stroke="#d4af37" stroke-width="1.2" />
                                                            <text x="40" y="27" font-family="'DejaVu Sans', sans-serif" font-size="5" font-weight="bold" fill="#aa8010" text-anchor="middle">AURONGOLD</text>
                                                            <text x="40" y="44" font-family="'DejaVu Sans', sans-serif" font-size="10" font-weight="bold" fill="#7c5c10" text-anchor="middle">AG</text>
                                                            <text x="40" y="58" font-family="'DejaVu Sans', sans-serif" font-size="4.5" font-weight="bold" fill="#aa8010" text-anchor="middle">OFFICIAL SEAL</text>
                                                        </svg>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 5: Certificate Verification -->
                            <tr>
                                <td colspan="2" class="card-cell-full">
                                    <div class="card">
                                        <div class="card-header">
                                            <table class="card-header-table" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td class="card-header-icon-td">
                                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><rect x="7" y="7" width="3" height="3"/><rect x="14" y="7" width="3" height="3"/><rect x="7" y="14" width="3" height="3"/><rect x="14" y="14" width="3" height="3"/></svg>
                                                    </td>
                                                    <td class="card-header-title-td">9. Verify This Certificate</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="card-body" style="padding: 4px 6px;">
                                            <table class="verification-layout" cellspacing="0" cellpadding="0" width="100%">
                                                <tr>
                                                    <td class="qr-td">
                                                        @if(!empty($qrImageSrc))
                                                            <img src="{{ $qrImageSrc }}" alt="Verification QR">
                                                        @else
                                                            <div style="width: 32px; height: 32px; border: 1px dashed #ccc; background-color: #fafafa; font-size: 5px; text-align: center; vertical-align: middle; line-height: 32px; color: #a0aec0;">QR</div>
                                                        @endif
                                                    </td>
                                                    <td class="verification-details-td">
                                                        <div style="font-size: 6px; font-weight: bold; color: #718096; margin-bottom: 2px;">Scan this QR code to verify:</div>
                                                        <div class="verification-point">✔ Certificate Authenticity</div>
                                                        <div class="verification-point">✔ Booking Details</div>
                                                        <div class="verification-point">✔ Payment & Price Lock Status</div>
                                                        <div style="font-size: 5.5px; color: #718096; margin-top: 1px;">
                                                            Token: {{ substr($certificate->verification_token, 0, 16) }}...
                                                        </div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <!-- RIGHT COLUMN: Terms and Conditions (33%) -->
                    <td class="right-column">
                        <div class="terms-card">
                            <div class="terms-header">Terms & Conditions</div>
                            <div class="terms-body">
                                <ul class="terms-list">
                                    <li class="terms-item"><span class="terms-num">1.</span>This certificate is issued by AurOnGold for price lock based on the first payment.</li>
                                    <li class="terms-item"><span class="terms-num">2.</span>The locked price is valid for the entire plan duration.</li>
                                    <li class="terms-item"><span class="terms-num">3.</span>The customer agrees to pay the monthly advance on or before the due date.</li>
                                    <li class="terms-item"><span class="terms-num">4.</span>If 3 consecutive advance payments are missed, AurOnGold reserves the right to cancel the plan.</li>
                                    <li class="terms-item"><span class="terms-num">5.</span>Gold Coin or Bar will be provided subject to stock availability.</li>
                                    <li class="terms-item"><span class="terms-num">6.</span>All weights are approximate and may vary by &plusmn;0.05 grams.</li>
                                    <li class="terms-item"><span class="terms-num">7.</span>3% GST is applicable on gold value as per government regulations.</li>
                                    <li class="terms-item"><span class="terms-num">8.</span>GST on service charges is 18% as applicable.</li>
                                    <li class="terms-item"><span class="terms-num">9.</span>This certificate is non-transferable and cannot be pledged.</li>
                                    <li class="terms-item"><span class="terms-num">10.</span>AurOnGold is not a bank or NBFC. This is a gold purchase plan only.</li>
                                    <li class="terms-item"><span class="terms-num">11.</span>Any disputes are subject to the courts in Mysuru, Karnataka, India.</li>
                                </ul>

                                <!-- Please Note Section nested inside Terms -->
                                <div class="note-card">
                                    <div style="padding: 3px 5px;">
                                        <table class="note-item-table" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td class="note-icon-td">
                                                    <svg width="6" height="6" viewBox="0 0 24 24" fill="none" stroke="#aa8010" stroke-width="3"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                </td>
                                                <td class="note-text-td">Keep this certificate safe for your records.</td>
                                            </tr>
                                        </table>
                                        <table class="note-item-table" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td class="note-icon-td">
                                                    <svg width="6" height="6" viewBox="0 0 24 24" fill="none" stroke="#aa8010" stroke-width="3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                                </td>
                                                <td class="note-text-td">System generated document; no signature needed.</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- REGISTERED OFFICE BLOCK -->
            <table class="office-block-table" width="100%" cellspacing="0" cellpadding="0" style="margin-top: 4px; padding: 4px 6px;">
                <tr>
                    <!-- Column 1: Logo & Address -->
                    <td width="38%" valign="middle" style="border-right: 1px dashed #e2e8f0; padding-right: 6px;">
                        <table width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="20%" align="center" valign="middle">
                                    <span style="font-family: 'DejaVu Sans', sans-serif; font-size: 13px; font-weight: bold; color: #b89047; display: block; border: 1.2px solid #b89047; border-radius: 4px; padding: 2px 3px; text-align: center; line-height: 1;">AG</span>
                                </td>
                                <td width="80%" style="padding-left: 6px;" valign="middle">
                                    <div style="font-weight: 700; color: #b89047; font-size: 7.2px; text-transform: uppercase;">AURONGOLD AG</div>
                                    <div style="font-size: 5.8px; color: #718096; line-height: 1.1;">
                                        #73, First Floor, Sumatha Woods Layout, Martikyatanahalli Circle, Mysuru – 570026, Karnataka, India
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <!-- Column 2: Web & Contacts -->
                    <td width="27%" valign="middle" style="border-right: 1px dashed #e2e8f0; padding: 0 6px; text-align: center; font-size: 5.8px; color: #4a5568; line-height: 1.35;">
                        🌐 www.aurongold.in<br>
                        ✉ support@aurongold.in<br>
                        📞 +91 73376 16333
                    </td>
                    <!-- Column 3: Tax Details -->
                    <td width="35%" valign="middle" style="padding-left: 6px; font-size: 5.8px; color: #4a5568; line-height: 1.35;">
                        <strong>GSTIN:</strong> 29ABCDE1234F1Z5<br>
                        <strong>PAN:</strong> ABCDE1234F<br>
                        <strong>State Code:</strong> 29 (Karnataka) &nbsp;|&nbsp; <strong>Reverse Charge:</strong> No
                    </td>
                </tr>
            </table>

            <!-- FOOTER STRIP -->
            <div class="footer-strip">
                THANK YOU FOR TRUSTING AURONGOLD. INVEST SMART. GROW SECURE.
            </div>

        </div>
    </div>
    
    <div class="certificate-meta">
        Generated At: {{ $generatedAt }} | Generated By: {{ $generatedBy }} | Certificate Number: AGL-PC-{{ substr($certificate->certificate_number, 3) }} | Booking: {{ $booking->booking_number }}
    </div>

</body>
</html>
