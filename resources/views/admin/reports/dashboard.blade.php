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

            <!-- Card 9: Pending EMI -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Pending EMIs</small>
                            <h3 class="text-dark font-weight-bold mt-1 mb-0">{{ $stats['pending_emi'] }}</h3>
                        </div>
                        <i class="mdi mdi-clock-outline text-secondary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Card 10: Overdue EMI -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-white border shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold text-uppercase d-block">Overdue EMIs</small>
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
                    <div class="nav flex-column nav-pills border rounded bg-light p-2">
                        @foreach([
                            'booking' => 'Booking Report',
                            'payment' => 'Payment Report',
                            'customer' => 'Customer Report',
                            'product' => 'Product Report',
                            'delivery' => 'Delivery Report',
                            'emi' => 'EMI Report',
                            'outstanding' => 'Outstanding Report',
                            'referral' => 'Referral Report',
                            'sell_old_gold' => 'Sell Old Gold Report',
                            'franchise' => 'Franchise Report',
                            'purchase_limit' => 'Purchase Limit Report'
                        ] as $key => $label)
                            <a href="{{ route('reports.dashboard', ['report' => $key]) }}" class="nav-link text-dark font-weight-bold mb-1 {{ $reportType === $key ? 'active bg-primary text-white' : '' }}">
                                <i class="mdi mdi-file-document-outline mr-2"></i> {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Filters & Table Panel -->
                <div class="col-md-9">
                    <!-- Dynamic Filters Form -->
                    <div class="border rounded p-3 mb-4 bg-light">
                        <h6 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Data</h6>
                        <form action="{{ route('reports.dashboard') }}" method="GET" class="row">
                            <input type="hidden" name="report" value="{{ $reportType }}">

                            <!-- Start Date -->
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">Start Date</label>
                                <input type="date" name="start_date" class="form-control bg-white text-dark" value="{{ request('start_date') }}">
                            </div>

                            <!-- End Date -->
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">End Date</label>
                                <input type="date" name="end_date" class="form-control bg-white text-dark" value="{{ request('end_date') }}">
                            </div>

                            <!-- Customer Filter (if applicable) -->
                            @if(in_array($reportType, ['booking', 'payment', 'delivery', 'emi', 'outstanding', 'referral', 'purchase_limit']))
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
                            @if(in_array($reportType, ['booking', 'outstanding']))
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
                            @if(in_array($reportType, ['payment', 'delivery']))
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
                            @if($reportType === 'payment')
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
                            @if($reportType !== 'customer' && $reportType !== 'outstanding' && $reportType !== 'purchase_limit')
                            <div class="col-md-4 form-group mb-2">
                                <label class="text-dark font-weight-bold">Status</label>
                                <select name="status" class="form-control bg-white text-dark">
                                    <option value="">All Statuses</option>
                                    @php
                                        $statuses = [];
                                        if ($reportType === 'booking') $statuses = ['Draft', 'Pending First EMI', 'Active', 'Completed', 'Cancelled', 'Refund Initiated', 'Refunded'];
                                        elseif ($reportType === 'payment') $statuses = ['Paid', 'Failed', 'Refunded'];
                                        elseif ($reportType === 'product') $statuses = ['active', 'inactive'];
                                        elseif ($reportType === 'delivery') $statuses = ['Requested', 'Approved', 'Ready For Dispatch', 'Dispatched', 'Out For Delivery', 'Delivered', 'Cancelled', 'Returned'];
                                        elseif ($reportType === 'emi') $statuses = ['Pending', 'Paid', 'Partial', 'Overdue'];
                                        elseif ($reportType === 'referral') $statuses = ['Pending', 'Eligible', 'Rewarded', 'Rejected'];
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
                                @if(hasPermission('report.export'))
                                <a href="{{ route('reports.export', array_merge(['type' => $reportType], request()->all())) }}" class="btn btn-success">
                                    <i class="mdi mdi-export"></i> Export CSV
                                </a>
                                @endif
                                <div>
                                    <a href="{{ route('reports.dashboard', ['report' => $reportType]) }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
                                    <button type="submit" class="btn btn-info px-4">Generate Report</button>
                                </div>
                            </div>
                        </form>
                    </div>

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
                                        <th>Purity (%)</th>
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
                                        <td>{{ number_format($row->purity, 2) }}%</td>
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
                                        <td>{{ $row->receiver_name ?? 'N/A' }}</td>
                                        <td>{{ $row->receiver_mobile ?? 'N/A' }}</td>
                                        <td><span class="badge badge-info">{{ $row->delivery_status }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Report: EMI -->
                            @elseif($reportType === 'emi')
                            <table class="table table-bordered table-striped text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Customer</th>
                                        <th>EMI Amount</th>
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
                                        $totalPaid = \App\Models\BookingPayment::where('booking_id', $row->id)->where('status', 'Paid')->sum('amount_paid');
                                        $outstanding = max($row->grand_total - $totalPaid, 0);
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


