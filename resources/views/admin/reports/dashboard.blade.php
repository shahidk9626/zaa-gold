@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Reports & Analytics Hub</h4>
                    <p class="card-description text-muted mb-0">System performance trackers, live collection trends, gold sale analytics, and tabular reporting tools.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Date Filter -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <form action="{{ route('reports.dashboard') }}" method="GET" class="row align-items-end">
                <input type="hidden" name="report" value="{{ $reportType }}">
                
                @foreach(request()->except(['start_date', 'end_date', 'report']) as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                
                <div class="col-md-3 form-group mb-2 mb-md-0">
                    <label class="text-dark font-weight-bold">From Date</label>
                    <input type="date" name="start_date" class="form-control bg-white text-dark shadow-sm" value="{{ request('start_date', $startDate) }}">
                </div>
                
                <div class="col-md-3 form-group mb-2 mb-md-0">
                    <label class="text-dark font-weight-bold">To Date</label>
                    <input type="date" name="end_date" class="form-control bg-white text-dark shadow-sm" value="{{ request('end_date', $endDate) }}">
                </div>
                
                <div class="col-md-6 mb-0">
                    <button type="submit" class="btn btn-info px-4 mr-2 shadow-sm font-weight-bold">Apply Filter</button>
                    <a href="{{ route('reports.dashboard', ['report' => $reportType]) }}" class="btn btn-secondary px-4 shadow-sm font-weight-bold">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- 12 KPI Metric Cards Grid -->
    <div class="col-12 mb-4">
        <div class="row">
            <!-- Card 1: Today's Collection -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Today's Collection</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($stats['today_collection'], 2) }}</h3>
                        </div>
                        <i class="mdi mdi-cash-multiple text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 2: Monthly Collection -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Monthly Collection</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($stats['monthly_collection'], 2) }}</h3>
                        </div>
                        <i class="mdi mdi-chart-line text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 3: Yearly Collection -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Yearly Collection</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($stats['yearly_collection'], 2) }}</h3>
                        </div>
                        <i class="mdi mdi-chart-bar text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 4: Outstanding Amount -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Outstanding Amount</small>
                            <h3 class="text-danger font-weight-bold mt-1 mb-0">₹{{ number_format($stats['outstanding_amount'], 2) }}</h3>
                        </div>
                        <i class="mdi mdi-alert-circle-outline text-danger" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 5: Active Bookings -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Active Bookings</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">{{ $stats['active_bookings'] }}</h3>
                        </div>
                        <i class="mdi mdi-cart-outline text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 6: Completed Bookings -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Completed Bookings</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">{{ $stats['completed_bookings'] }}</h3>
                        </div>
                        <i class="mdi mdi-check-circle-outline text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 7: Pending Deliveries -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Pending Deliveries</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">{{ $stats['pending_deliveries'] }}</h3>
                        </div>
                        <i class="mdi mdi-truck-delivery text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 8: Gold Sold -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Gold Sold</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">{{ number_format($stats['gold_sold'], 2) }} g</h3>
                        </div>
                        <i class="mdi mdi-matrix text-gold" style="font-size: 2rem; color: #d4af37;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 9: Pending EMAP -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Pending EMAPs</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">{{ $stats['pending_emi'] }}</h3>
                        </div>
                        <i class="mdi mdi-clock-outline text-secondary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 10: Overdue EMAP -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Overdue EMAPs</small>
                            <h3 class="text-danger font-weight-bold mt-1 mb-0">{{ $stats['overdue_emi'] }}</h3>
                        </div>
                        <i class="mdi mdi-calendar-remove text-danger" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 11: Active Customers -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Active Customers</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">{{ $stats['active_customers'] }}</h3>
                        </div>
                        <i class="mdi mdi-account-multiple text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 12: New Customers (30d) -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">New Customers (30d)</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">{{ $stats['new_customers'] }}</h3>
                        </div>
                        <i class="mdi mdi-account-plus text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Tabular Reports Segment -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-4 border-bottom pb-2">Tabular Reports Generator</h5>
            <div class="row">
                <!-- Sidebar Report Types -->
                <div class="col-md-3 mb-4">
                    <div class="nav flex-column nav-pills border rounded bg-light p-2 shadow-sm" style="max-height: 80vh; overflow-y: auto;">
                        <div class="px-3 py-2 font-weight-bold text-muted small text-uppercase border-bottom mb-2">Standard Reports</div>
                        @foreach([
                            'booking' => 'Booking Report',
                            'payment' => 'Payment Report',
                            'customer' => 'Customer Report',
                            'product' => 'Product Report',
                            'delivery' => 'Delivery Report',
                            'emi' => 'EMAP Report',
                            'outstanding' => 'Outstanding Report',
                            'referral' => 'Referral Report',
                            'sell_old_gold' => 'Sell Old Gold Report',
                            'franchise' => 'Franchise Report',
                            'purchase_limit' => 'Purchase Limit Report',
                            'cancellation' => 'Cancellations & Refunds'
                        ] as $key => $label)
                            <a href="{{ route('reports.dashboard', ['report' => $key, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="nav-link text-dark font-weight-bold mb-1 {{ $reportType === $key ? 'active bg-primary text-white shadow-sm' : '' }}">
                                <i class="mdi mdi-file-document-outline mr-2"></i> {{ $label }}
                            </a>
                        @endforeach
                        
                        <div class="px-3 py-2 mt-3 font-weight-bold text-muted small text-uppercase border-bottom mb-2">Financial Reports</div>
                        @foreach([
                            'financial_summary' => 'Financial Summary',
                            'charge_breakdown' => 'Charge Breakdown',
                            'service_charge' => 'Service Charge Report',
                            'gst_report' => 'GST Report',
                            'payment_collection' => 'Payment Collection'
                        ] as $key => $label)
                            <a href="{{ route('reports.dashboard', ['report' => $key, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="nav-link text-dark font-weight-bold mb-1 {{ $reportType === $key ? 'active bg-primary text-white shadow-sm' : '' }}">
                                <i class="mdi mdi-cash-multiple mr-2"></i> {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Filters & Table Panel -->
                <div class="col-md-9">
                    @if($reportType !== 'financial_summary')
                    <!-- Dynamic Filters Form -->
                    <div class="border rounded p-3 mb-4 bg-light shadow-sm">
                        <h6 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Data</h6>
                        <form action="{{ route('reports.dashboard') }}" method="GET" class="row">
                            <input type="hidden" name="report" value="{{ $reportType }}">
                            <input type="hidden" name="start_date" value="{{ request('start_date', $startDate) }}">
                            <input type="hidden" name="end_date" value="{{ request('end_date', $endDate) }}">

                            <!-- Customer Filter (if applicable) -->
                            @if(in_array($reportType, ['booking', 'payment', 'delivery', 'emi', 'outstanding', 'referral', 'purchase_limit', 'cancellation', 'charge_breakdown', 'service_charge', 'gst_report', 'payment_collection']))
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">Customer</label>
                                <select name="customer_id" class="form-control bg-white text-dark">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Product Filter (if applicable) -->
                            @if(in_array($reportType, ['booking', 'outstanding', 'charge_breakdown', 'service_charge']))
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">Product</label>
                                <select name="product_id" class="form-control bg-white text-dark">
                                    <option value="">All Products</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Booking Filter (if applicable) -->
                            @if(in_array($reportType, ['payment', 'delivery', 'charge_breakdown', 'service_charge', 'gst_report', 'payment_collection']))
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">Booking</label>
                                <select name="booking_id" class="form-control bg-white text-dark">
                                    <option value="">All Bookings</option>
                                    @foreach($bookings as $b)
                                        <option value="{{ $b->id }}" {{ request('booking_id') == $b->id ? 'selected' : '' }}>{{ $b->booking_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Payment Mode Filter (if applicable) -->
                            @if(in_array($reportType, ['payment', 'payment_collection']))
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">Payment Mode</label>
                                <select name="payment_mode" class="form-control bg-white text-dark">
                                    <option value="">All Modes</option>
                                    @foreach(['Cash', 'UPI', 'Bank Transfer', 'Card', 'Cheque', 'Online Gateway'] as $mode)
                                        <option value="{{ $mode }}" {{ request('payment_mode') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Payment Type Filter (if applicable) -->
                            @if($reportType === 'payment_collection')
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">Payment Type</label>
                                <select name="payment_type" class="form-control bg-white text-dark">
                                    <option value="">All Types</option>
                                    @foreach(['Initial EMAP', 'EMI', 'Product Purchase'] as $typeOpt)
                                        <option value="{{ $typeOpt }}" {{ request('payment_type') === $typeOpt ? 'selected' : '' }}>{{ $typeOpt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Financial Year Filter (if applicable) -->
                            @if($reportType === 'purchase_limit')
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">Financial Year</label>
                                <select name="financial_year" class="form-control bg-white text-dark">
                                    @php
                                        $currentYear = (int) date('Y');
                                    @endphp
                                    @for($y = $currentYear; $y >= 2024; $y--)
                                        <option value="{{ $y }}" {{ request('financial_year', $currentYear) == $y ? 'selected' : '' }}>{{ $y }} - {{ $y + 1 }}</option>
                                    @endfor
                                </select>
                            </div>
                            @endif

                            <!-- Status Filter -->
                            @if($reportType !== 'customer' && $reportType !== 'outstanding' && $reportType !== 'purchase_limit' && $reportType !== 'financial_summary')
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">Status</label>
                                <select name="status" class="form-control bg-white text-dark">
                                    <option value="">All Statuses</option>
                                    @php
                                        $statuses = [];
                                        if (in_array($reportType, ['booking', 'charge_breakdown', 'service_charge'])) $statuses = ['Draft', 'Pending First EMI', 'Active', 'Completed', 'Cancelled', 'Refund Initiated', 'Refunded'];
                                        elseif (in_array($reportType, ['payment', 'payment_collection'])) $statuses = ['Paid', 'Failed', 'Refunded'];
                                        elseif ($reportType === 'product') $statuses = ['active', 'inactive'];
                                        elseif ($reportType === 'delivery') $statuses = ['Pending Admin Approval', 'Approved', 'Hold', 'Ready For Dispatch', 'Dispatched', 'In Transit', 'Out For Delivery', 'Delivered', 'Collected', 'Rejected', 'Cancelled'];
                                        elseif ($reportType === 'emi') $statuses = ['Pending', 'Paid', 'Partial', 'Overdue'];
                                        elseif ($reportType === 'referral') $statuses = ['Pending', 'Eligible', 'Rewarded', 'Rewarded', 'Rejected'];
                                        elseif ($reportType === 'cancellation') $statuses = ['Requested', 'Under Review', 'Customer Retained', 'Approved', 'Refund Initiated', 'Refund Completed', 'Rejected'];
                                        elseif ($reportType === 'sell_old_gold' || $reportType === 'franchise') $statuses = ['New', 'Contacted', 'Meeting Scheduled', 'Proposal Sent', 'Approved', 'Rejected', 'Closed'];
                                    @endphp
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Action Buttons -->
                            <div class="col-12 mt-2 d-flex justify-content-between align-items-center">
                                @if(in_array($reportType, ['financial_summary', 'charge_breakdown', 'service_charge', 'gst_report', 'payment_collection']))
                                    @if(hasPermission('report.export'))
                                    <a href="{{ route('reports.export_excel', request()->all()) }}" class="btn btn-success shadow-sm font-weight-bold">
                                        <i class="mdi mdi-file-excel-box mr-1"></i> Export Excel
                                    </a>
                                    @endif
                                @else
                                    @if(hasPermission('report.export'))
                                     <a href="{{ route('reports.export', array_merge(['type' => $reportType], request()->all())) }}" class="btn btn-success shadow-sm font-weight-bold">
                                        <i class="mdi mdi-export mr-1"></i> Export CSV
                                    </a>
                                    @endif
                                @endif
                                <div>
                                    <a href="{{ route('reports.dashboard', ['report' => $reportType, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-secondary px-4 mr-2 shadow-sm font-weight-bold">Clear Filters</a>
                                    <button type="submit" class="btn btn-info px-4 shadow-sm font-weight-bold">Generate Report</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @else
                    <!-- Financial Summary Export Button only -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="text-dark font-weight-bold mb-0">Financial Summary Analytics</h6>
                        @if(hasPermission('report.export'))
                        <a href="{{ route('reports.export_excel', request()->all()) }}" class="btn btn-success shadow-sm font-weight-bold">
                            <i class="mdi mdi-file-excel-box mr-1"></i> Export Excel
                        </a>
                        @endif
                    </div>
                    @endif

                    <!-- Dynamic Report Content -->
                    <div class="border rounded p-3 bg-white">
                        <div class="table-responsive">
                            <!-- Report: Booking -->
                            @if($reportType === 'booking')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Customer</th>
                                        <th>Product</th>
                                        <th>Weight (g)</th>
                                        <th>Locked Price</th>
                                        <th>Grand Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="font-weight-bold text-primary">{{ $row->booking_number }}</td>
                                        <td>{{ $row->customer->name ?? 'N/A' }}</td>
                                        <td>{{ $row->product->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($row->gold_weight, 2) }}g</td>
                                        <td>₹{{ number_format($row->locked_price_per_gram, 2) }}</td>
                                        <td class="font-weight-bold text-success">₹{{ number_format($row->grand_total, 2) }}</td>
                                        <td><span class="badge badge-info">{{ $row->status }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: Payment -->
                            @elseif($reportType === 'payment')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Payment #</th>
                                        <th>Receipt #</th>
                                        <th>Booking #</th>
                                        <th>Customer</th>
                                        <th>Paid Amount</th>
                                        <th>Mode</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="font-weight-bold">{{ $row->payment_number }}</td>
                                        <td>{{ $row->receipt_number }}</td>
                                        <td>{{ $row->booking->booking_number ?? 'N/A' }}</td>
                                        <td>{{ $row->customer->name ?? 'N/A' }}</td>
                                        <td class="font-weight-bold text-success">₹{{ number_format($row->amount_paid, 2) }}</td>
                                        <td>{{ $row->payment_mode }}</td>
                                        <td>{{ $row->payment_date->format('Y-m-d') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: Customer -->
                            @elseif($reportType === 'customer')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>WhatsApp</th>
                                        <th>Status</th>
                                        <th>Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="font-weight-bold">{{ $row->name }}</td>
                                        <td>{{ $row->email }}</td>
                                        <td>{{ $row->phone ?? 'N/A' }}</td>
                                        <td>{{ $row->whatsapp_number ?? 'N/A' }}</td>
                                        <td><span class="badge badge-success">{{ $row->status }}</span></td>
                                        <td>{{ $row->created_at->format('Y-m-d') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: Product -->
                            @elseif($reportType === 'product')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Weight (g)</th>
                                        <th>Purity</th>
                                        <th>Gold Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="font-weight-bold">{{ $row->name }}</td>
                                        <td>{{ $row->sku }}</td>
                                        <td>{{ number_format($row->weight_in_grams, 2) }}g</td>
                                        <td>{{ (float)$row->purity }} fine gold</td>
                                        <td>{{ $row->gold_type }}</td>
                                        <td><span class="badge badge-secondary">{{ $row->status }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: Delivery -->
                            @elseif($reportType === 'delivery')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Delivery #</th>
                                        <th>Booking #</th>
                                        <th>Customer</th>
                                        <th>Method</th>
                                        <th>Courier</th>
                                        <th>Tracking</th>
                                        <th>Expected</th>
                                        <th>Receiver Name</th>
                                        <th>Receiver Contact</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="font-weight-bold text-primary">{{ $row->delivery_number }}</td>
                                        <td>{{ $row->booking->booking_number ?? 'N/A' }}</td>
                                        <td>{{ $row->customer->name ?? 'N/A' }}</td>
                                        <td>{{ $row->delivery_method }}</td>
                                        <td>{{ $row->courier_partner ?? 'N/A' }}</td>
                                        <td>{{ $row->tracking_number ?? 'N/A' }}</td>
                                        <td>{{ $row->expected_delivery_date?->format('d M Y') ?? 'N/A' }}</td>
                                        <td>{{ $row->receiver_name ?? 'N/A' }}</td>
                                        <td>{{ $row->receiver_mobile ?? 'N/A' }}</td>
                                        <td><span class="badge badge-info">{{ $row->delivery_status }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="10" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: EMAP -->
                            @elseif($reportType === 'emi')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Customer</th>
                                        <th>EMAP Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Paid At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="font-weight-bold text-primary">{{ $row->booking->booking_number ?? 'N/A' }}</td>
                                        <td>{{ $row->booking->customer->name ?? 'N/A' }}</td>
                                        <td class="font-weight-bold text-success">₹{{ number_format($row->emi_amount, 2) }}</td>
                                        <td>{{ $row->due_date }}</td>
                                        <td><span class="badge badge-warning">{{ $row->status }}</span></td>
                                        <td>{{ $row->paid_at ? $row->paid_at->format('Y-m-d') : 'N/A' }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: Outstanding -->
                            @elseif($reportType === 'outstanding')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Customer</th>
                                        <th>Product</th>
                                        <th>Total Booked</th>
                                        <th>Total Paid</th>
                                        <th>Outstanding Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    @php
                                        $financialService = app(\App\Services\FinancialCalculationService::class);
                                        $rawPaid = \App\Models\BookingPayment::where('booking_id', $row->id)->where('status', 'Paid')->sum('amount_paid');
                                        $totalPaid = $financialService->displayPaidTotal($row, (float) $rawPaid);
                                        $outstanding = $financialService->outstanding($row, (float) $rawPaid);
                                    @endphp
                                    <tr>
                                        <td class="font-weight-bold text-primary">{{ $row->booking_number }}</td>
                                        <td>{{ $row->customer->name ?? 'N/A' }}</td>
                                        <td>{{ $row->product->name ?? 'N/A' }}</td>
                                        <td>₹{{ number_format($row->grand_total, 2) }}</td>
                                        <td class="text-success">₹{{ number_format($totalPaid, 2) }}</td>
                                        <td class="font-weight-bold text-danger">₹{{ number_format($outstanding, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: Referral -->
                            @elseif($reportType === 'referral')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Referrer</th>
                                        <th>Referred</th>
                                        <th>Booking #</th>
                                        <th>Reward Type</th>
                                        <th>Reward Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="font-weight-bold text-primary">{{ $row->referral_code }}</td>
                                        <td>{{ $row->referrer->name ?? 'N/A' }}</td>
                                        <td>{{ $row->referred->name ?? 'N/A' }}</td>
                                        <td>{{ $row->booking->booking_number ?? 'N/A' }}</td>
                                        <td>{{ $row->reward_type }}</td>
                                        <td class="font-weight-bold text-success">₹{{ number_format($row->reward_amount, 2) }}</td>
                                        <td><span class="badge badge-info">{{ $row->reward_status }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: Sell Old Gold -->
                            @elseif($reportType === 'sell_old_gold')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>Mobile</th>
                                        <th>Gold Type</th>
                                        <th>Est. Weight</th>
                                        <th>Est. Value</th>
                                        <th>Assigned Staff</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="font-weight-bold">{{ $row->customer_name }}</td>
                                        <td>{{ $row->mobile }}</td>
                                        <td>{{ $row->gold_type }}</td>
                                        <td>{{ number_format($row->estimated_weight, 2) }}g</td>
                                        <td class="font-weight-bold text-success">₹{{ number_format($row->estimated_value, 2) }}</td>
                                        <td>{{ $row->assignedStaff->name ?? 'Unassigned' }}</td>
                                        <td><span class="badge badge-info">{{ $row->status }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: Franchise -->
                            @elseif($reportType === 'franchise')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Partner Name</th>
                                        <th>Contact Info</th>
                                        <th>Location</th>
                                        <th>Investment Budget</th>
                                        <th>Assigned Staff</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="font-weight-bold">{{ $row->full_name }}</td>
                                        <td>
                                            <div>{{ $row->mobile }}</div>
                                            <small class="text-muted">{{ $row->email }}</small>
                                        </td>
                                        <td>{{ $row->city }}, {{ $row->state }}</td>
                                        <td class="font-weight-bold text-success">{{ $row->investment_budget }}</td>
                                        <td>{{ $row->assignedStaff->name ?? 'Unassigned' }}</td>
                                        <td><span class="badge badge-info">{{ $row->status }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            @elseif($reportType === 'purchase_limit')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Allowed Limit</th>
                                        <th>Purchased Weight</th>
                                        <th>Remaining Limit</th>
                                        <th>Exceeded Limit?</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $maxLimit = (float) \App\Models\SystemSetting::get('customer_max_purchase_grams', 100.00);
                                    @endphp
                                    @forelse($reportData as $row)
                                    @php
                                        $purchased = (float) $row->purchased_weight;
                                        $remaining = max(0, $maxLimit - $purchased);
                                        $exceeded = $purchased > $maxLimit;
                                    @endphp
                                    <tr>
                                        <td class="font-weight-bold">{{ $row->name }}</td>
                                        <td>{{ number_format($maxLimit, 2) }} g</td>
                                        <td class="text-info">{{ number_format($purchased, 2) }} g</td>
                                        <td class="text-success">{{ number_format($remaining, 2) }} g</td>
                                        <td>
                                            @if($exceeded)
                                                <span class="badge badge-danger font-weight-bold">Yes ({{ number_format($purchased - $maxLimit, 2) }}g)</span>
                                            @else
                                                <span class="badge badge-success font-weight-bold">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            @elseif($reportType === 'cancellation')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Request No</th>
                                        <th>Booking No</th>
                                        <th>Customer</th>
                                        <th>Plan Name</th>
                                        <th class="text-right">Paid Amount</th>
                                        <th class="text-right">Cancellation Charge</th>
                                        <th class="text-right">Refund Amount</th>
                             <table class="table table-bordered table-striped text-dark">
                                 <thead class="bg-light">
                                     <tr>
                                         <th>Request No</th>
                                         <th>Booking No</th>
                                         <th>Customer</th>
                                         <th>Plan Name</th>
                                         <th class="text-right">Paid Amount</th>
                                         <th class="text-right">Cancellation Charge</th>
                                         <th class="text-right">Refund Amount</th>
                                         <th>Status</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($reportData as $row)
                                     <tr>
                                         <td class="font-weight-bold">{{ $row->request_number }}</td>
                                         <td>{{ $row->booking?->booking_number }}</td>
                                         <td>
                                             <div class="font-weight-bold">{{ $row->customer?->name }}</div>
                                             <small class="text-muted">{{ $row->customer?->email }}</small>
                                         </td>
                                         <td>{{ $row->booking?->emiPlan?->plan_name }}</td>
                                         <td class="text-right font-weight-bold text-dark">₹{{ number_format($row->total_amount_paid, 2) }}</td>
                                         <td class="text-right text-danger font-weight-bold">₹{{ number_format($row->cancellation_charge_amount, 2) }} ({{ number_format($row->cancellation_charge_percent, 2) }}%)</td>
                                         <td class="text-right text-success font-weight-bold">₹{{ number_format($row->refund_amount, 2) }}</td>
                                         <td>
                                             @php
                                                 $badge = 'secondary';
                                                 if ($row->status === 'Requested') $badge = 'info';
                                                 elseif ($row->status === 'Under Review') $badge = 'warning';
                                                 elseif ($row->status === 'Customer Retained' || $row->status === 'Refund Completed') $badge = 'success';
                                                 elseif ($row->status === 'Approved' || $row->status === 'Refund Initiated') $badge = 'primary';
                                                 elseif ($row->status === 'Rejected') $badge = 'danger';
                                             @endphp
                                             <span class="badge badge-{{ $badge }}">{{ $row->status }}</span>
                                         </td>
                                     </tr>
                                     @empty
                                     <tr><td colspan="8" class="text-center text-muted">No records found.</td></tr>
                                     @endforelse
                                 </tbody>
                             </table>

                             @elseif($reportType === 'financial_summary')
                             <!-- Financial Summary KPI Grid -->
                             <div class="row">
                                 <!-- Card 1: Total Gold Value -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total Gold Value</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_gold_value'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-gold text-warning" style="font-size: 2rem; color: #d4af37;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 2: Total GST on Gold -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total GST on Gold</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_gst_on_gold'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-percent text-info" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 3: Total Service Charges -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total Service Charges</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_service_charges'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-cogs text-primary" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 4: Total GST on Charges -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total GST on Charges</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_gst_on_charges'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-percent text-secondary" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 5: Total Storage Charges -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total Storage Charges</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_storage_charges'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-warehouse text-info" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 6: Total Insurance Charges -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total Insurance Charges</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_insurance_charges'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-shield-check text-success" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 7: Total Price Lock Charges -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total Price Lock Charges</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_price_lock_charges'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-lock-clock text-warning" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 8: Total Processing Fees -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total Processing Fees</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_processing_fees'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-file-document-edit-outline text-primary" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 9: Total Platform Convenience Fees -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Platform Convenience Fees</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_platform_convenience_fees'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-laptop text-secondary" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 10: Total Delivery Charges -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Insured Delivery Charges</small>
                                                 <h3 class="text-dark font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_delivery_charges'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-truck-delivery text-info" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 11: Total Payments Received -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Payments Received</small>
                                                 <h3 class="text-success font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_payments_received'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-cash text-success" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 12: Total Outstanding -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total Outstanding</small>
                                                 <h3 class="text-danger font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_outstanding'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-alert-circle text-danger" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 13: Total Refunds -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Total Refunds</small>
                                                 <h3 class="text-danger font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['total_refunds'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-undo-variant text-danger" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                                 <!-- Card 14: Net Collection -->
                                 <div class="col-md-4 col-sm-6 mb-3">
                                     <div class="card bg-white border shadow-sm p-3 h-100" style="background-color: #fcfbf5 !important;">
                                         <div class="d-flex justify-content-between align-items-center">
                                             <div>
                                                 <small class="text-muted font-weight-bold text-uppercase d-block">Net Collection</small>
                                                 <h3 class="text-primary font-weight-bold mt-1 mb-0">₹{{ number_format($financialStats['net_collection'], 2) }}</h3>
                                             </div>
                                             <i class="mdi mdi-wallet text-primary" style="font-size: 2rem;"></i>
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             @elseif($reportType === 'charge_breakdown')
                             <table class="table table-bordered table-striped text-dark">
                                 <thead class="bg-light">
                                     <tr>
                                         <th>Booking #</th>
                                         <th>Customer</th>
                                         <th>Cust ID</th>
                                         <th>Booking Date</th>
                                         <th>Product</th>
                                         <th class="text-right">Gold Weight</th>
                                         <th class="text-right">Gold Value</th>
                                         <th class="text-right">GST Gold</th>
                                         <th class="text-right">Price Lock</th>
                                         <th class="text-right">Storage</th>
                                         <th class="text-right">Insurance</th>
                                         <th class="text-right">Processing</th>
                                         <th class="text-right">Convenience</th>
                                         <th class="text-right">Delivery</th>
                                         <th class="text-right">GST Charges</th>
                                         <th class="text-right">Total Charges</th>
                                         <th class="text-right">Booking Value</th>
                                         <th class="text-right">Paid</th>
                                         <th class="text-right">Outstanding</th>
                                         <th>Status</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($reportData as $row)
                                     @php
                                         $rowProcessingFee = $row->emiPlan ? app(\App\Services\EmiCalculationService::class)->calculateProcessingFee($row->emiPlan, $row->locked_gold_value) : 0.00;
                                         $rowTotalCharges = $row->finance_charge_amount + $row->storage_charge_amount + $rowProcessingFee;
                                         
                                         $financialService = app(\App\Services\FinancialCalculationService::class);
                                         $rowPaidRaw = $row->payments()->where('status', 'Paid')->sum('amount_paid');
                                         $rowPaid = $financialService->displayPaidTotal($row, (float) $rowPaidRaw);
                                         $rowOutstanding = $financialService->outstanding($row, (float) $rowPaidRaw);
                                     @endphp
                                     <tr>
                                         <td class="font-weight-bold">{{ $row->booking_number }}</td>
                                         <td>{{ $row->customer->name ?? 'N/A' }}</td>
                                         <td>{{ $row->customer_id ? 'AGCUST' . str_pad($row->customer_id, 6, '0', STR_PAD_LEFT) : 'N/A' }}</td>
                                         <td>{{ $row->booking_date ? $row->booking_date->format('Y-m-d') : 'N/A' }}</td>
                                         <td>{{ $row->product->name ?? 'N/A' }}</td>
                                         <td class="text-right">{{ number_format($row->gold_weight, 2) }}g</td>
                                         <td class="text-right">₹{{ number_format($row->locked_gold_value, 2) }}</td>
                                         <td class="text-right">₹{{ number_format($row->gst_on_gold_amount, 2) }}</td>
                                         <td class="text-right">₹{{ number_format($row->finance_charge_amount, 2) }}</td>
                                         <td class="text-right">₹{{ number_format($row->storage_charge_amount, 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($rowProcessingFee, 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($row->gst_on_charges_amount, 2) }}</td>
                                         <td class="text-right font-weight-bold">₹{{ number_format($rowTotalCharges, 2) }}</td>
                                         <td class="text-right font-weight-bold text-dark">₹{{ number_format($row->grand_total, 2) }}</td>
                                         <td class="text-right text-success font-weight-bold">₹{{ number_format($rowPaid, 2) }}</td>
                                         <td class="text-right text-danger font-weight-bold">₹{{ number_format($rowOutstanding, 2) }}</td>
                                         <td>
                                             @php
                                                 $badge = 'secondary';
                                                 if ($row->status === 'Active') $badge = 'primary';
                                                 elseif ($row->status === 'Completed') $badge = 'success';
                                                 elseif (in_array($row->status, ['Cancelled', 'Refunded'])) $badge = 'danger';
                                             @endphp
                                             <span class="badge badge-{{ $badge }}">{{ $row->status }}</span>
                                         </td>
                                     </tr>
                                     @empty
                                     <tr><td colspan="20" class="text-center text-muted">No records found.</td></tr>
                                     @endforelse
                                 </tbody>
                                 @if(!empty($totals))
                                 <tfoot class="bg-light font-weight-bold">
                                     <tr>
                                         <td colspan="5">TOTALS</td>
                                         <td class="text-right">-</td>
                                         <td class="text-right">₹{{ number_format($totals['gold_value'], 2) }}</td>
                                         <td class="text-right">₹{{ number_format($totals['gst_on_gold'], 2) }}</td>
                                         <td class="text-right">₹{{ number_format($totals['price_lock'], 2) }}</td>
                                         <td class="text-right">₹{{ number_format($totals['storage'], 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($totals['processing_fee'], 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($totals['gst_on_charges'], 2) }}</td>
                                         <td class="text-right">₹{{ number_format($totals['total_charges'], 2) }}</td>
                                         <td class="text-right">₹{{ number_format($totals['booking_value'], 2) }}</td>
                                         <td class="text-right text-success">₹{{ number_format($totals['amount_paid'], 2) }}</td>
                                         <td class="text-right text-danger">₹{{ number_format($totals['outstanding'], 2) }}</td>
                                         <td></td>
                                     </tr>
                                 </tfoot>
                                 @endif
                             </table>

                             @elseif($reportType === 'service_charge')
                             <table class="table table-bordered table-striped text-dark">
                                 <thead class="bg-light">
                                     <tr>
                                         <th>Booking #</th>
                                         <th>Customer</th>
                                         <th>Product</th>
                                         <th>Booking Date</th>
                                         <th class="text-right">Processing Fee</th>
                                         <th class="text-right">Convenience Fee</th>
                                         <th class="text-right">Insurance</th>
                                         <th class="text-right">Storage</th>
                                         <th class="text-right">Price Lock</th>
                                         <th class="text-right">Other</th>
                                         <th class="text-right">GST on Charges</th>
                                         <th class="text-right">Total Charges</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($reportData as $row)
                                     @php
                                         $rowProcessingFee = $row->emiPlan ? app(\App\Services\EmiCalculationService::class)->calculateProcessingFee($row->emiPlan, $row->locked_gold_value) : 0.00;
                                         $rowTotalCharges = $row->finance_charge_amount + $row->storage_charge_amount + $rowProcessingFee;
                                     @endphp
                                     <tr>
                                         <td class="font-weight-bold">{{ $row->booking_number }}</td>
                                         <td>{{ $row->customer->name ?? 'N/A' }}</td>
                                         <td>{{ $row->product->name ?? 'N/A' }}</td>
                                         <td>{{ $row->booking_date ? $row->booking_date->format('Y-m-d') : 'N/A' }}</td>
                                         <td class="text-right">₹{{ number_format($rowProcessingFee, 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($row->storage_charge_amount, 2) }}</td>
                                         <td class="text-right">₹{{ number_format($row->finance_charge_amount, 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($row->gst_on_charges_amount, 2) }}</td>
                                         <td class="text-right font-weight-bold">₹{{ number_format($rowTotalCharges, 2) }}</td>
                                     </tr>
                                     @empty
                                     <tr><td colspan="12" class="text-center text-muted">No records found.</td></tr>
                                     @endforelse
                                 </tbody>
                                 @if(!empty($totals))
                                 <tfoot class="bg-light font-weight-bold">
                                     <tr>
                                         <td colspan="4">TOTALS</td>
                                         <td class="text-right">₹{{ number_format($totals['processing_fee'], 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($totals['storage'], 2) }}</td>
                                         <td class="text-right">₹{{ number_format($totals['price_lock'], 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($totals['gst_on_charges'], 2) }}</td>
                                         <td class="text-right font-weight-bold">₹{{ number_format($totals['total_charges'], 2) }}</td>
                                     </tr>
                                 </tfoot>
                                 @endif
                             </table>

                             @elseif($reportType === 'gst_report')
                             <table class="table table-bordered table-striped text-dark">
                                 <thead class="bg-light">
                                     <tr>
                                         <th>Invoice #</th>
                                         <th>Booking #</th>
                                         <th>Customer</th>
                                         <th>Invoice Date</th>
                                         <th class="text-right">Gold Value</th>
                                         <th class="text-right">GST Gold</th>
                                         <th class="text-right">Storage</th>
                                         <th class="text-right">Insurance</th>
                                         <th class="text-right">Price Lock</th>
                                         <th class="text-right">Other Taxable</th>
                                         <th class="text-right">GST Charges</th>
                                         <th class="text-right">Total GST</th>
                                         <th class="text-right">Invoice Amount</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($reportData as $row)
                                     <tr>
                                         <td class="font-weight-bold">{{ $row->invoice_number }}</td>
                                         <td>{{ $row->booking?->booking_number ?? 'N/A' }}</td>
                                         <td>{{ $row->customer_name }}</td>
                                         <td>{{ $row->invoice_date ? $row->invoice_date->format('Y-m-d') : 'N/A' }}</td>
                                         <td class="text-right">₹{{ number_format($row->gold_value, 2) }}</td>
                                         <td class="text-right">₹{{ number_format($row->gst_on_gold_amount, 2) }}</td>
                                         <td class="text-right">₹{{ number_format($row->storage_charge, 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($row->finance_charge, 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($row->gst_on_charges_amount, 2) }}</td>
                                         <td class="text-right font-weight-bold">₹{{ number_format($row->gst_on_gold_amount + $row->gst_on_charges_amount, 2) }}</td>
                                         <td class="text-right font-weight-bold text-dark">₹{{ number_format($row->grand_total, 2) }}</td>
                                     </tr>
                                     @empty
                                     <tr><td colspan="13" class="text-center text-muted">No records found.</td></tr>
                                     @endforelse
                                 </tbody>
                                 @if(!empty($totals))
                                 <tfoot class="bg-light font-weight-bold">
                                     <tr>
                                         <td colspan="4">TOTALS</td>
                                         <td class="text-right">₹{{ number_format($totals['gold_value'], 2) }}</td>
                                         <td class="text-right">₹{{ number_format($totals['gst_on_gold'], 2) }}</td>
                                         <td class="text-right">₹{{ number_format($totals['storage'], 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($totals['price_lock'], 2) }}</td>
                                         <td class="text-right">₹0.00</td>
                                         <td class="text-right">₹{{ number_format($totals['gst_on_charges'], 2) }}</td>
                                         <td class="text-right font-weight-bold">₹{{ number_format($totals['total_gst'], 2) }}</td>
                                         <td class="text-right font-weight-bold">₹{{ number_format($totals['invoice_amount'], 2) }}</td>
                                     </tr>
                                 </tfoot>
                                 @endif
                             </table>

                             @elseif($reportType === 'payment_collection')
                             <table class="table table-bordered table-striped text-dark">
                                 <thead class="bg-light">
                                     <tr>
                                         <th>Txn #</th>
                                         <th>Booking #</th>
                                         <th>Customer</th>
                                         <th>Payment Date</th>
                                         <th>Method</th>
                                         <th>Gateway</th>
                                         <th>Gateway Txn ID</th>
                                         <th>Payment Type</th>
                                         <th class="text-right">Amount</th>
                                         <th>Status</th>
                                         <th>Reference</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($reportData as $row)
                                     @php
                                         $paymentType = 'Other';
                                         if ($row->emiSchedule) {
                                             $paymentType = $row->emiSchedule->installment_number == 1 ? 'Initial EMAP' : 'EMI';
                                         } else {
                                             $paymentType = 'Product Purchase';
                                         }
                                     @endphp
                                     <tr>
                                         <td class="font-weight-bold">{{ $row->payment_number }}</td>
                                         <td>{{ $row->booking?->booking_number ?? 'N/A' }}</td>
                                         <td>{{ $row->customer->name ?? 'N/A' }}</td>
                                         <td>{{ $row->payment_date ? $row->payment_date->format('Y-m-d') : 'N/A' }}</td>
                                         <td>{{ $row->payment_mode }}</td>
                                         <td>{{ $row->gateway_name ?? ($row->payment_mode === 'Online Gateway' ? 'Cashfree' : 'N/A') }}</td>
                                         <td>{{ $row->gateway_txn_id ?? $row->transaction_reference ?? 'N/A' }}</td>
                                         <td>{{ $paymentType }}</td>
                                         <td class="text-right font-weight-bold text-dark">₹{{ number_format($row->amount_paid, 2) }}</td>
                                         <td>
                                             @php
                                                 $badge = 'secondary';
                                                 if ($row->status === 'Paid') $badge = 'success';
                                                 elseif ($row->status === 'Failed') $badge = 'danger';
                                                 elseif ($row->status === 'Refunded') $badge = 'warning';
                                             @endphp
                                             <span class="badge badge-{{ $badge }}">{{ $row->status }}</span>
                                         </td>
                                         <td>{{ $row->transaction_reference ?? 'N/A' }}</td>
                                     </tr>
                                     @empty
                                     <tr><td colspan="11" class="text-center text-muted">No records found.</td></tr>
                                     @endforelse
                                 </tbody>
                                 @if(!empty($totals))
                                 <tfoot class="bg-light font-weight-bold">
                                     <tr>
                                         <td colspan="8">TOTALS</td>
                                         <td class="text-right text-success">₹{{ number_format($totals['amount'], 2) }}</td>
                                         <td colspan="2"></td>
                                     </tr>
                                 </tfoot>
                                 @endif
                             </table>
                             @endif
                        </div>

                        <!-- Pagination Links -->
                        <div class="mt-4 d-flex justify-content-end">
                            {{ $reportData->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
