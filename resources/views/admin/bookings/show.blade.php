@extends('layouts.app')

@section('content')
<style>
    .timeline {
        position: relative;
        padding: 10px 0;
        margin-top: 4px;
        list-style: none;
    }
    .timeline:before {
        content: " ";
        position: absolute;
        top: 0;
        bottom: 0;
        left: 20px;
        width: 3px;
        background-color: #e9ecef;
    }
    .timeline-item {
        margin-bottom: 25px;
        position: relative;
    }
    .timeline-item:before, .timeline-item:after {
        content: " ";
        display: table;
    }
    .timeline-item:after {
        clear: both;
    }
    .timeline-badge {
        color: #fff;
        width: 14px;
        height: 14px;
        position: absolute;
        top: 6px;
        left: 15px;
        margin-left: 0;
        background-color: #3f50f6;
        border: 3px solid #fff;
        border-radius: 50%;
        z-index: 100;
        box-shadow: 0 0 0 3px rgba(63, 80, 246, 0.2);
    }
    .timeline-badge.badge-warning {
        background-color: #ffc107;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.2);
    }
    .timeline-badge.badge-success {
        background-color: #24b47e;
        box-shadow: 0 0 0 3px rgba(36, 180, 126, 0.2);
    }
    .timeline-badge.badge-danger {
        background-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
    }
    .timeline-panel {
        width: calc(100% - 50px);
        float: right;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 15px;
        position: relative;
        background: #f8fafc;
    }
    .timeline-title {
        margin-top: 0;
        color: #333;
        font-weight: bold;
    }
    .timeline-panel:before {
        position: absolute;
        top: 6px;
        left: -11px;
        display: inline-block;
        border-top: 11px solid transparent;
        border-right: 11px solid #dee2e6;
        border-left: 0 solid #dee2e6;
        border-bottom: 11px solid transparent;
        content: " ";
    }
    .timeline-panel:after {
        position: absolute;
        top: 7px;
        left: -10px;
        display: inline-block;
        border-top: 10px solid transparent;
        border-right: 10px solid #f8fafc;
        border-left: 0 solid #f8fafc;
        border-bottom: 10px solid transparent;
        content: " ";
    }
    .nav-tabs .nav-link {
        border: 0;
        background: transparent;
        font-weight: bold;
        color: #8392ab !important;
        border-radius: 0.5rem;
        padding: 0.75rem 1.25rem;
    }
    .nav-tabs .nav-link.active {
        background: linear-gradient(135deg, #3f50f6 0%, #ff3ca6 100%) !important;
        color: white !important;
        box-shadow: 0 4px 10px rgba(63, 80, 246, 0.2);
    }
    .tab-content {
        background: transparent;
        border: 0;
    }
</style>

<div class="row text-dark">
    <!-- Header panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <div class="d-flex align-items-center">
                        <span class="h4 font-weight-bold text-dark mb-0 mr-3">Booking #{{ $booking->booking_number }}</span>
                        @php
                            $badgeClass = 'badge-secondary';
                            switch($booking->status) {
                                case 'Draft': $badgeClass = 'badge-secondary'; break;
                                case 'Booked': $badgeClass = 'badge-warning'; break;
                                case 'Active': $badgeClass = 'badge-primary'; break;
                                case 'Completed': $badgeClass = 'badge-success'; break;
                                case 'Cancelled': $badgeClass = 'badge-danger'; break;
                                case 'Refund Initiated': $badgeClass = 'badge-info'; break;
                                case 'Refunded': $badgeClass = 'badge-dark'; break;
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $booking->status }}</span>
                    </div>
                    <p class="text-muted mb-0 mt-2">Locked Gold Price: <strong>₹{{ number_format($booking->locked_price_per_gram, 2) }} / g</strong> | Booked on {{ $booking->booking_date->format('d M Y, h:i A') }}</p>
                </div>
                <div>
                    <a href="{{ route('bookings.index') }}" class="btn btn-secondary px-4">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to list
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="col-12 mb-4">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close text-dark" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="col-12 mb-4">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle mr-1"></i> {{ session('error') }}
                <button type="button" class="close text-dark" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="col-12">
        <ul class="nav nav-tabs border-0 bg-light border rounded p-1 mb-4" id="bookingDetailTabs" role="tablist">
            <li class="nav-item flex-fill text-center">
                <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">
                    <i class="mdi mdi-information mr-1"></i> Overview
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="financial-tab" data-toggle="tab" href="#financial" role="tab" aria-controls="financial" aria-selected="false">
                    <i class="mdi mdi-chart-donut mr-1"></i> Financial Summary
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="outstanding-tab" data-toggle="tab" href="#outstanding" role="tab" aria-controls="outstanding" aria-selected="false">
                    <i class="mdi mdi-scale-balance mr-1"></i> Outstanding
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="schedule-tab" data-toggle="tab" href="#schedule" role="tab" aria-controls="schedule" aria-selected="false">
                    <i class="mdi mdi-calendar-clock mr-1"></i> EMI Schedule
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="payments-tab" data-toggle="tab" href="#payments" role="tab" aria-controls="payments" aria-selected="false">
                    <i class="mdi mdi-cash-multiple mr-1"></i> Payments
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="receipts-tab" data-toggle="tab" href="#receipts" role="tab" aria-controls="receipts" aria-selected="false">
                    <i class="mdi mdi-file-document mr-1"></i> Receipts
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="certificates-tab" data-toggle="tab" href="#certificates" role="tab" aria-controls="certificates" aria-selected="false">
                    <i class="mdi mdi-certificate mr-1"></i> Certificates
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="timeline-tab" data-toggle="tab" href="#timeline" role="tab" aria-controls="timeline" aria-selected="false">
                    <i class="mdi mdi-history mr-1"></i> Timeline
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="activity-tab" data-toggle="tab" href="#activity" role="tab" aria-controls="activity" aria-selected="false">
                    <i class="mdi mdi-file-document-box-outline mr-1"></i> Activity Logs
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="delivery-tab" data-toggle="tab" href="#delivery" role="tab" aria-controls="delivery" aria-selected="false">
                    <i class="mdi mdi-truck-delivery mr-1"></i> Delivery
                </a>
            </li>
        </ul>
    </div>

    <!-- Tabs Content -->
    <div class="col-12">
        <div class="tab-content" id="bookingDetailTabsContent">
            
            <!-- 1. Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                <div class="row">
                    <!-- Customer Details & Product Specs -->
                    <div class="col-md-6 mb-4">
                        <div class="card bg-white border shadow-sm p-4 h-100">
                            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Customer Details</h5>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="small text-muted d-block mb-1">Name</label>
                                    <span class="font-weight-bold text-dark">{{ $booking->customer->name ?? 'N/A' }}</span>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="small text-muted d-block mb-1">Email</label>
                                    <span class="font-weight-bold text-dark">{{ $booking->customer->email ?? 'N/A' }}</span>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="small text-muted d-block mb-1">Phone Number</label>
                                    <span class="font-weight-bold text-dark">{{ $booking->customer->customerDetail->phone_number ?? $booking->customer->phone ?? 'N/A' }}</span>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="small text-muted d-block mb-1">KYC Status</label>
                                    <span class="badge badge-success text-dark font-weight-bold">Approved</span>
                                </div>
                            </div>
                            
                            <h5 class="text-primary font-weight-bold mt-3 mb-3 border-bottom pb-2">Product Specifications</h5>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="small text-muted d-block mb-1">Product Name</label>
                                    <span class="font-weight-bold text-dark">{{ $booking->product->name ?? 'N/A' }}</span>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="small text-muted d-block mb-1">SKU</label>
                                    <span class="font-weight-bold text-dark">{{ $booking->product->sku ?? 'N/A' }}</span>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="small text-muted d-block mb-1">Gold Weight</label>
                                    <span class="font-weight-bold text-dark">{{ number_format($booking->gold_weight, 2) }}g [{{ $booking->product->gold_type ?? 'N/A' }}]</span>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="small text-muted d-block mb-1">Purity</label>
                                    <span class="font-weight-bold text-dark">{{ number_format($booking->gold_purity, 2) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions & Status Transition -->
                    <div class="col-md-6 mb-4">
                        <div class="card bg-white border shadow-sm p-4 h-100">
                            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Price Lock Certificate Quick-View</h5>
                            @if($booking->certificate)
                                <div class="d-flex align-items-center flex-wrap mb-4">
                                    @if($booking->certificate->qr_code && Storage::disk('public')->exists($booking->certificate->qr_code))
                                        <div class="mr-3 border p-2 bg-light rounded text-center">
                                            <img src="{{ asset('storage/' . $booking->certificate->qr_code) }}" alt="QR Code" style="width: 80px; height: 80px;">
                                            <div class="small text-muted mt-1" style="font-size: 7px;">VERIFICATION QR</div>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-weight-bold text-dark mb-1">Certificate #{{ $booking->certificate->certificate_number }}</div>
                                        <div class="small text-muted mb-2">Issued on: {{ $booking->certificate->issued_at->format('d M Y, h:i A') }}</div>
                                        @if(hasPermission('booking.download_certificate'))
                                            <a href="{{ route('bookings.download_certificate', $booking->id) }}" class="btn btn-success btn-sm px-3">
                                                <i class="mdi mdi-download mr-1"></i> Download Certificate
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning mb-3">No Price Lock Certificate registered.</div>
                            @endif

                            @if(hasPermission('booking.change_status'))
                                <h5 class="text-primary font-weight-bold mt-2 mb-3 border-bottom pb-2">Lifecycle Management</h5>
                                <form action="{{ route('bookings.change_status', $booking->id) }}" method="POST" class="row">
                                    @csrf
                                    <div class="col-md-6 form-group">
                                        <label class="small text-muted font-weight-bold">Change Status</label>
                                        <select name="status" class="form-control bg-white text-dark" required>
                                            @foreach(['Draft', 'Booked', 'Active', 'Completed', 'Cancelled', 'Refund Initiated', 'Refunded'] as $statusOption)
                                                <option value="{{ $statusOption }}" {{ $booking->status === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="small text-muted font-weight-bold">Remarks</label>
                                        <input type="text" name="remarks" class="form-control bg-white text-dark" placeholder="Specify transition reason...">
                                    </div>
                                    <div class="col-12 text-right">
                                        <button type="submit" class="btn btn-primary px-4 btn-md">Update Status</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Financial Summary Tab -->
            <div class="tab-pane fade" id="financial" role="tabpanel" aria-labelledby="financial-tab">
                <div class="card bg-white border shadow-sm p-4">
                    @include('admin.partials.financial-summary', [
                        'showCustomer' => false,
                        'showBooking' => false,
                        'outstandingUrl' => route('purchase-preview.outstanding'),
                        'pdfUrl' => route('purchase-preview.outstanding.pdf')
                    ])
                </div>
            </div>

            <!-- 3. Outstanding Tab -->
            <div class="tab-pane fade" id="outstanding" role="tabpanel" aria-labelledby="outstanding-tab">
                <div class="card bg-white border shadow-sm p-4">
                    <h5 class="text-primary font-weight-bold mb-4 border-bottom pb-2">Financial Outstanding Statement</h5>
                    
                    <!-- Progress Section -->
                    @php
                        $percentagePaid = $totalBooked > 0 ? ($totalPaid / $totalBooked) * 100 : 0;
                    @endphp
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="font-weight-bold text-dark">Plan Payment Progress</span>
                            <span class="font-weight-bold text-success">{{ number_format($percentagePaid, 1) }}% Paid</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentagePaid }}%;" aria-valuenow="{{ $percentagePaid }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <!-- Statistics Grid -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light border p-3 text-center">
                                <label class="small text-muted d-block">Grand Total Booked</label>
                                <span class="h4 font-weight-bold text-primary">₹{{ number_format($totalBooked, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border p-3 text-center">
                                <label class="small text-muted d-block">Total Paid to Date</label>
                                <span class="h4 font-weight-bold text-success">₹{{ number_format($totalPaid, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border p-3 text-center">
                                <label class="small text-muted d-block">Remaining Balance Outstanding</label>
                                <span class="h4 font-weight-bold text-danger">₹{{ number_format($outstandingBalance, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Table -->
                    <h5 class="text-dark font-weight-bold mt-4 mb-3">Repayment Breakdown Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered text-dark small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Financial Parameter</th>
                                    <th>Allocated Value (₹)</th>
                                    <th>Total Paid (₹)</th>
                                    <th>Remaining (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Principal amount (Gold Value)</td>
                                    <td>₹{{ number_format($booking->locked_gold_value, 2) }}</td>
                                    <td>₹{{ number_format($principalPaid, 2) }}</td>
                                    <td>₹{{ number_format(max(0, $booking->locked_gold_value - $principalPaid), 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Interest & Finance Charges</td>
                                    <td>₹{{ number_format($booking->grand_total - $booking->locked_gold_value - $booking->gst_on_gold_amount - $booking->gst_on_charges_amount, 2) }}</td>
                                    <td>₹{{ number_format($interestPaid, 2) }}</td>
                                    <td>₹{{ number_format(max(0, ($booking->grand_total - $booking->locked_gold_value - $booking->gst_on_gold_amount - $booking->gst_on_charges_amount) - $interestPaid), 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Taxes & GST (Accumulated)</td>
                                    <td>₹{{ number_format($booking->gst_on_gold_amount + $booking->gst_on_charges_amount, 2) }}</td>
                                    <td>₹{{ number_format($gstPaid, 2) }}</td>
                                    <td>₹{{ number_format(max(0, ($booking->gst_on_gold_amount + $booking->gst_on_charges_amount) - $gstPaid), 2) }}</td>
                                </tr>
                                <tr class="text-danger font-weight-bold">
                                    <td>Late Fees Accrued & Paid</td>
                                    <td>₹{{ number_format($lateFeePaid, 2) }}</td>
                                    <td>₹{{ number_format($lateFeePaid, 2) }}</td>
                                    <td>₹0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. EMI Schedule Tab -->
            <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                <div class="card bg-white border shadow-sm p-4">
                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Booking Repayment Schedule</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped text-dark small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Installment #</th>
                                    <th>Due Date</th>
                                    <th>Opening Principal</th>
                                    <th>Monthly EMI</th>
                                    <th>Principal Component</th>
                                    <th>Interest/Charges</th>
                                    <th>Late Fee</th>
                                    <th>Outstanding Balance</th>
                                    <th>Status</th>
                                    <th>Paid At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedule as $row)
                                    <tr>
                                        <td class="font-weight-bold text-center">#{{ $row->installment_number }}</td>
                                        <td>{{ $row->due_date->format('d M Y') }}</td>
                                        <td>₹{{ number_format($row->opening_principal, 2) }}</td>
                                        <td class="font-weight-bold text-primary">₹{{ number_format($row->emi_amount, 2) }}</td>
                                        <td>₹{{ number_format($row->principal_amount, 2) }}</td>
                                        <td>₹{{ number_format($row->interest_amount, 2) }}</td>
                                        <td class="text-danger font-weight-bold">{{ $row->late_fee > 0 ? '₹' . number_format($row->late_fee, 2) : '—' }}</td>
                                        <td>₹{{ number_format($row->outstanding_balance, 2) }}</td>
                                        <td>
                                            @php
                                                $badgeClass = 'badge-secondary';
                                                switch($row->status) {
                                                    case 'Pending': $badgeClass = 'badge-warning'; break;
                                                    case 'Paid': $badgeClass = 'badge-success'; break;
                                                    case 'Partial': $badgeClass = 'badge-info'; break;
                                                    case 'Overdue': $badgeClass = 'badge-danger'; break;
                                                }
                                            @endphp
                                            <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-1">{{ $row->status }}</span>
                                        </td>
                                        <td>{{ $row->paid_at ? $row->paid_at->format('d M Y, H:i') : '—' }}</td>
                                        <td>
                                            @if($row->status !== 'Paid' && hasPermission('payment.collect'))
                                                <a href="{{ route('payments.collect_form', [$booking->id, $row->id]) }}" class="btn btn-sm btn-primary px-3 py-1">
                                                    Collect Payment
                                                </a>
                                                <form action="{{ route('payment-links.generate', [$booking->id, $row->id]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-success px-3 py-1 mt-1">
                                                        Generate Payment Link
                                                    </button>
                                                </form>
                                            @elseif($row->status === 'Paid')
                                                @if($row->payment_id)
                                                <a href="{{ route('payments.show', $row->payment_id) }}" class="btn btn-sm btn-info px-2 py-1 mr-1" title="View details">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                @endif
                                                @if($row->payment_id && hasPermission('receipt.download'))
                                                <a href="{{ route('receipts.download', $row->payment_id) }}" class="btn btn-sm btn-success px-2 py-1" title="Download Invoice Receipt">
                                                    <i class="mdi mdi-download"></i>
                                                </a>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center py-3 text-muted">No repayment schedule generated.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 5. Payments Tab -->
            <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                <div class="card bg-white border shadow-sm p-4">
                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Payment Summary</h5>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3"><div class="bg-light border rounded p-3 h-100"><small class="text-muted font-weight-bold text-uppercase">Booking Payment</small><div class="font-weight-bold text-dark mt-2">{{ $paymentSummary['booking_payment']->transaction_number ?? 'N/A' }}</div><div class="small text-muted">{{ $paymentSummary['booking_payment']->payment_status ?? '' }}</div></div></div>
                        <div class="col-md-3 mb-3"><div class="bg-light border rounded p-3 h-100"><small class="text-muted font-weight-bold text-uppercase">EMI Payments</small><div class="font-weight-bold text-dark mt-2">{{ $paymentSummary['emi_payments']->count() }}</div></div></div>
                        <div class="col-md-3 mb-3"><div class="bg-light border rounded p-3 h-100"><small class="text-muted font-weight-bold text-uppercase">Pending EMI</small><div class="font-weight-bold text-dark mt-2">{{ $paymentSummary['pending_emi'] }}</div></div></div>
                        <div class="col-md-3 mb-3"><div class="bg-light border rounded p-3 h-100"><small class="text-muted font-weight-bold text-uppercase">Payment Links</small><div class="font-weight-bold text-dark mt-2">{{ $paymentSummary['payment_links']->count() }}</div></div></div>
                    </div>

                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Gateway Payment Transactions</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-striped text-dark small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Gateway</th>
                                    <th>Transaction Number</th>
                                    <th>Gateway Order ID</th>
                                    <th>Gateway Payment ID</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                    <th>Paid At</th>
                                    <th>Gateway Response</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($booking->paymentTransactions as $transaction)
                                    <tr>
                                        <td>{{ ucfirst($transaction->gateway) }}</td>
                                        <td class="font-weight-bold text-primary">{{ $transaction->transaction_number }}</td>
                                        <td class="text-monospace">{{ $transaction->gateway_order_id }}</td>
                                        <td class="text-monospace">{{ $transaction->gateway_payment_id ?? 'N/A' }}</td>
                                        <td class="font-weight-bold text-success">₹{{ number_format($transaction->amount, 2) }}</td>
                                        <td>
                                            @php
                                                $gatewayBadge = match($transaction->payment_status) {
                                                    'Success' => 'badge-success',
                                                    'Failed', 'Cancelled' => 'badge-danger',
                                                    'Processing' => 'badge-warning',
                                                    default => 'badge-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $gatewayBadge }} text-dark font-weight-bold px-3 py-1">{{ $transaction->payment_status }}</span>
                                        </td>
                                        <td>{{ $transaction->paid_at?->format('d M Y, h:i A') ?? 'N/A' }}</td>
                                        <td>
                                            <pre class="mb-0 small" style="max-height: 120px; overflow: auto;">{{ json_encode($transaction->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-3 text-muted">No gateway transactions recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">EMI Payments History</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped text-dark small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Payment No.</th>
                                    <th>Receipt No.</th>
                                    <th>EMI #</th>
                                    <th>Mode</th>
                                    <th>Reference</th>
                                    <th>Amount Paid</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Late Fee</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr>
                                        <td class="font-weight-bold text-primary">{{ $payment->payment_number }}</td>
                                        <td class="font-weight-bold text-dark">{{ $payment->receipt_number }}</td>
                                        <td class="text-center">#{{ $payment->emiSchedule->installment_number ?? 'N/A' }}</td>
                                        <td><span class="badge badge-outline-dark text-dark">{{ $payment->payment_mode }}</span></td>
                                        <td>{{ $payment->transaction_reference ?? 'N/A' }}</td>
                                        <td class="font-weight-bold text-success">₹{{ number_format($payment->amount_paid, 2) }}</td>
                                        <td>₹{{ number_format($payment->principal_paid, 2) }}</td>
                                        <td>₹{{ number_format($payment->interest_paid, 2) }}</td>
                                        <td class="text-danger">₹{{ number_format($payment->late_fee_paid, 2) }}</td>
                                        <td>{{ $payment->payment_date->format('d M Y, h:i A') }}</td>
                                        <td><span class="badge badge-success text-dark font-weight-bold px-3 py-1">{{ $payment->status }}</span></td>
                                        <td>
                                            <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-sm btn-info px-2 py-1 mr-1">
                                                <i class="mdi mdi-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-3 text-muted">No payments recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 6. Receipts Tab -->
            <div class="tab-pane fade" id="receipts" role="tabpanel" aria-labelledby="receipts-tab">
                <div class="card bg-white border shadow-sm p-4">
                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Tax Receipts</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped text-dark small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Receipt Number</th>
                                    <th>Payment Number</th>
                                    <th>Installment</th>
                                    <th>Amount Paid</th>
                                    <th>Payment Date</th>
                                    <th>Download</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receipts as $payment)
                                    <tr>
                                        <td class="font-weight-bold text-success">{{ $payment->receipt_number }}</td>
                                        <td class="font-weight-bold text-primary">{{ $payment->payment_number }}</td>
                                        <td>Installment #{{ $payment->emiSchedule->installment_number ?? 'N/A' }}</td>
                                        <td class="font-weight-bold text-success">₹{{ number_format($payment->amount_paid, 2) }}</td>
                                        <td>{{ $payment->payment_date->format('d M Y, h:i A') }}</td>
                                        <td>
                                            @if(hasPermission('receipt.download'))
                                            <a href="{{ route('receipts.download', $payment->id) }}" class="btn btn-sm btn-success px-3 py-1">
                                                <i class="mdi mdi-download mr-1"></i> Download PDF
                                            </a>
                                            @else
                                            —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">No receipts generated yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 7. Certificates Tab -->
            <div class="tab-pane fade" id="certificates" role="tabpanel" aria-labelledby="certificates-tab">
                <div class="card bg-white border shadow-sm p-4">
                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Price Lock Certificate Details</h5>
                    @if($booking->certificate)
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="small text-muted d-block mb-1">Certificate Number</label>
                                        <span class="font-weight-bold text-dark">{{ $booking->certificate->certificate_number }}</span>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="small text-muted d-block mb-1">Issued At</label>
                                        <span class="font-weight-bold text-dark">{{ $booking->certificate->issued_at->format('d M Y, h:i A') }}</span>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="small text-muted d-block mb-1">Locked Price per Gram</label>
                                        <span class="font-weight-bold text-dark">₹{{ number_format($booking->certificate->locked_price, 2) }}</span>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="small text-muted d-block mb-1">Locked Gold Weight</label>
                                        <span class="font-weight-bold text-dark">{{ number_format($booking->certificate->gold_weight, 2) }}g</span>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="small text-muted d-block mb-1">Grand Total</label>
                                        <span class="font-weight-bold text-dark">₹{{ number_format($booking->certificate->grand_total, 2) }}</span>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="small text-muted d-block mb-1">Verification URL Token</label>
                                        <span class="font-weight-bold text-dark small" style="word-break: break-all;">{{ $booking->certificate->verification_token }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                @if($booking->certificate->qr_code && Storage::disk('public')->exists($booking->certificate->qr_code))
                                    <div class="border p-3 bg-light rounded d-inline-block">
                                        <img src="{{ asset('storage/' . $booking->certificate->qr_code) }}" alt="QR Code" style="width: 140px; height: 140px;">
                                        <div class="small text-muted mt-2 font-weight-bold" style="font-size: 9px;">VERIFICATION QR CODE</div>
                                    </div>
                                @endif
                                <div class="mt-3">
                                    @if(hasPermission('booking.download_certificate'))
                                        <a href="{{ route('bookings.download_certificate', $booking->id) }}" class="btn btn-success px-4 w-100">
                                            <i class="mdi mdi-download mr-1"></i> Download PDF Certificate
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">No active Price Lock Certificate registered.</div>
                    @endif
                </div>
            </div>

            <!-- 8. Timeline Tab -->
            <div class="tab-pane fade" id="timeline" role="tabpanel" aria-labelledby="timeline-tab">
                <div class="card bg-white border shadow-sm p-4">
                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Lifecycle Audit Timeline</h5>
                    <ul class="timeline">
                        @forelse($booking->statusHistory as $history)
                            @php
                                $timelineBadgeClass = '';
                                switch($history->new_status) {
                                    case 'Draft': $timelineBadgeClass = ''; break;
                                    case 'Booked': $timelineBadgeClass = 'badge-warning'; break;
                                    case 'Active': $timelineBadgeClass = 'badge-primary'; break;
                                    case 'Completed': $timelineBadgeClass = 'badge-success'; break;
                                    case 'Cancelled': $timelineBadgeClass = 'badge-danger'; break;
                                }
                            @endphp
                            <li class="timeline-item">
                                <div class="timeline-badge {{ $timelineBadgeClass }}"></div>
                                <div class="timeline-panel">
                                    <div class="timeline-heading">
                                        <h6 class="timeline-title font-weight-bold text-dark">{{ $history->new_status }}</h6>
                                        <p class="mb-2"><small class="text-muted"><i class="mdi mdi-clock-outline"></i> {{ $history->created_at->format('d M Y, h:i A') }} by {{ $history->changedBy->name ?? 'System' }}</small></p>
                                    </div>
                                    <div class="timeline-body">
                                        <p class="mb-0 text-dark small">{{ $history->remarks }}</p>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-muted text-center py-3">No lifecycle updates found.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- 9. Activity Logs Tab -->
            <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                <div class="card bg-white border shadow-sm p-4">
                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Transaction Activity Logs</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered text-dark small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Timestamp</th>
                                    <th>Performed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activityLogs as $log)
                                    <tr>
                                        <td class="font-weight-bold text-uppercase">{{ str_replace('_', ' ', $log->action_type) }}</td>
                                        <td>{{ $log->description }}</td>
                                        <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                        <td>{{ $log->user->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No activity logs recorded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 10. Delivery Tab -->
            <div class="tab-pane fade" id="delivery" role="tabpanel" aria-labelledby="delivery-tab">
                <div class="card bg-white border shadow-sm p-4">
                    <h5 class="text-primary font-weight-bold mb-4 border-bottom pb-2">Gold Delivery & Fulfillment</h5>
                    
                    @if($delivery)
                        <!-- Active Delivery Request Details -->
                        <div class="row text-dark">
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="small text-muted d-block mb-1">Delivery Request ID</label>
                                        <span class="font-weight-bold text-dark">{{ $delivery->delivery_number }}</span>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="small text-muted d-block mb-1">Fulfillment Method</label>
                                        <span class="font-weight-bold text-dark">{{ $delivery->delivery_method }}</span>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="small text-muted d-block mb-1">Status</label>
                                        @php
                                            $deliveryBadge = 'badge-secondary';
                                            switch($delivery->delivery_status) {
                                                case 'Requested': $deliveryBadge = 'badge-warning'; break;
                                                case 'Approved': $deliveryBadge = 'badge-info'; break;
                                                case 'Dispatched': $deliveryBadge = 'badge-primary'; break;
                                                case 'Delivered': $deliveryBadge = 'badge-success'; break;
                                                case 'Cancelled': $deliveryBadge = 'badge-danger'; break;
                                            }
                                        @endphp
                                        <span class="badge {{ $deliveryBadge }} text-dark font-weight-bold px-3 py-1 mt-1">{{ $delivery->delivery_status }}</span>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="small text-muted d-block mb-1">Request Date</label>
                                        <span class="font-weight-bold text-dark">{{ $delivery->request_date ? $delivery->request_date->format('d M Y, H:i') : '—' }}</span>
                                    </div>
                                    
                                    @if($delivery->delivery_method === 'Courier')
                                        <div class="col-12 mb-3">
                                            <label class="small text-muted d-block mb-1">Shipping Address</label>
                                            <span class="font-weight-bold text-dark">{{ $delivery->delivery_address }}</span>
                                        </div>
                                        @if($delivery->tracking_number)
                                            <div class="col-6 mb-3">
                                                <label class="small text-muted d-block mb-1">Courier Partner</label>
                                                <span class="font-weight-bold text-dark">{{ $delivery->courier_partner }}</span>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label class="small text-muted d-block mb-1">Tracking Number</label>
                                                @if($delivery->tracking_url)
                                                    <a href="{{ $delivery->tracking_url }}" target="_blank" class="font-weight-bold text-primary">{{ $delivery->tracking_number }} <i class="mdi mdi-open-in-new"></i></a>
                                                @else
                                                    <span class="font-weight-bold text-dark">{{ $delivery->tracking_number }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    @elseif($delivery->delivery_method === 'Branch Pickup')
                                        <div class="col-4 mb-3">
                                            <label class="small text-muted d-block mb-1">Pickup Branch</label>
                                            <span class="font-weight-bold text-dark">{{ $delivery->pickup_branch }}</span>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <label class="small text-muted d-block mb-1">Scheduled Date</label>
                                            <span class="font-weight-bold text-dark">{{ $delivery->pickup_date ? $delivery->pickup_date->format('d M Y') : '—' }}</span>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <label class="small text-muted d-block mb-1">Scheduled Time</label>
                                            <span class="font-weight-bold text-dark">{{ $delivery->pickup_time ?? '—' }}</span>
                                        </div>
                                    @endif

                                    @if($delivery->receiver_name)
                                        <div class="col-6 mb-3">
                                            <label class="small text-muted d-block mb-1">Received By</label>
                                            <span class="font-weight-bold text-success">{{ $delivery->receiver_name }}</span>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="small text-muted d-block mb-1">Received Date</label>
                                            <span class="font-weight-bold text-dark">{{ $delivery->delivered_date ? $delivery->delivered_date->format('d M Y') : '—' }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4">
                                    <a href="{{ route('deliveries.show', $delivery->id) }}" class="btn btn-info px-4 mr-2">
                                        <i class="mdi mdi-eye mr-1"></i> Manage Delivery
                                    </a>
                                    @if($delivery->pdf_path && hasPermission('delivery.download'))
                                        <a href="{{ route('deliveries.download', $delivery->id) }}" class="btn btn-success px-4">
                                            <i class="mdi mdi-download mr-1"></i> Download Challan PDF
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6 border-left pl-4">
                                <h6 class="font-weight-bold text-dark mb-3">Delivery Timeline</h6>
                                <ul class="delivery-timeline">
                                    @foreach($delivery->statusHistories as $hist)
                                        <li class="delivery-timeline-item">
                                            <div class="delivery-timeline-badge"></div>
                                            <div class="font-weight-bold text-dark small">{{ $hist->new_status }}</div>
                                            <div class="small text-muted">{{ $hist->created_at->format('d M Y, H:i') }} by {{ $hist->changedBy->name ?? 'System' }}</div>
                                            @if($hist->remarks)
                                                <div class="small text-muted italic font-style-italic">"{{ $hist->remarks }}"</div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @else
                        <!-- No active delivery request yet -->
                        @if($booking->status === 'Completed' && $outstandingBalance <= 0)
                            <div class="alert alert-success p-4 mb-4">
                                <h5 class="font-weight-bold"><i class="mdi mdi-check-circle-outline mr-1"></i> Booking Completed & Fully Paid!</h5>
                                <p class="mb-0">All installment repayments are clear. You can now request the physical gold delivery for this booking below.</p>
                            </div>

                            <!-- Request delivery form -->
                            @if(hasPermission('delivery.request'))
                                <form action="{{ route('deliveries.store_request', $booking->id) }}" method="POST" class="row text-dark">
                                    @csrf
                                    
                                    <div class="col-md-4 form-group">
                                        <label class="font-weight-bold small">Delivery Method <span class="text-danger">*</span></label>
                                        <select name="delivery_method" id="deliveryMethodSelect" class="form-control bg-white text-dark" required>
                                            <option value="Office Pickup">Office Pickup (BKC Corporate Office)</option>
                                            <option value="Courier">Courier Handover</option>
                                            <option value="Branch Pickup">Branch Pickup (Scheduled)</option>
                                        </select>
                                    </div>

                                    <!-- Courier Address Field -->
                                    <div class="col-md-8 form-group" id="courierAddressBlock" style="display:none;">
                                        <label class="font-weight-bold small">Shipping Delivery Address <span class="text-danger">*</span></label>
                                        <textarea name="delivery_address" class="form-control bg-white text-dark" rows="1" placeholder="Specify full street address with landmarks...">{{ $booking->customer->customerDetail->address ?? '' }}</textarea>
                                    </div>

                                    <!-- Branch Pickup Fields -->
                                    <div class="col-md-8" id="branchPickupBlock" style="display:none;">
                                        <div class="row">
                                            <div class="col-md-4 form-group">
                                                <label class="font-weight-bold small">Branch Name <span class="text-danger">*</span></label>
                                                <input type="text" name="pickup_branch" class="form-control bg-white text-dark" placeholder="e.g. Bandra Branch">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label class="font-weight-bold small">Pickup Date <span class="text-danger">*</span></label>
                                                <input type="date" name="pickup_date" class="form-control bg-white text-dark">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label class="font-weight-bold small">Pickup Time <span class="text-danger">*</span></label>
                                                <input type="time" name="pickup_time" class="form-control bg-white text-dark">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 form-group">
                                        <label class="font-weight-bold small">Special Instructions / Remarks</label>
                                        <textarea name="remarks" class="form-control bg-white text-dark" rows="2" placeholder="Write any instructions for dispatch..."></textarea>
                                    </div>

                                    <div class="col-12 text-right">
                                        <button type="submit" class="btn btn-primary px-4 btn-md">Submit Delivery Request</button>
                                    </div>
                                </form>
                            @endif
                        @else
                            <div class="alert alert-warning p-4">
                                <h5 class="font-weight-bold"><i class="mdi mdi-lock mr-1"></i> Delivery Dispatch Locked</h5>
                                <p class="mb-2">Gold delivery can ONLY be requested after all payment conditions are satisfied:</p>
                                <ul class="mb-3 small">
                                    <li>Booking Status must be <strong>Completed</strong> (Current: <span class="badge badge-outline-dark text-dark">{{ $booking->status }}</span>)</li>
                                    <li>Outstanding Plan Balance must be <strong>₹0.00</strong> (Current Outstanding: <strong class="text-danger">₹{{ number_format($outstandingBalance, 2) }}</strong>)</li>
                                </ul>
                                <p class="mb-0 small text-muted">Please process all overdue schedules in the <strong>EMI Schedule</strong> tab before submitting delivery requests.</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    let selectedCustomerId = "{{ $booking->customer_id }}";
    let selectedProductId = "{{ $booking->product_id }}";
    let selectedEmiPlanId = "{{ $booking->emi_plan_id }}";

    $(document).ready(function() {
        // Hydrate the Reusable Financial Summary Partial
        $('#sumProductSpecs').text("{{ $booking->product->name }} ({{ number_format($booking->gold_weight, 2) }}g, {{ $booking->product->gold_type }})");
        $('#sumPlanName').text("{{ $booking->emiPlan->plan_name }} ({{ $booking->duration_months }} Months)");
        $('#sumGoldRate').text("₹" + parseFloat("{{ $booking->locked_price_per_gram }}").toLocaleString() + "/g");
        $('#sumProductPrice').text("₹" + parseFloat("{{ $booking->locked_gold_value }}").toLocaleString(undefined, {minimumFractionDigits: 2}));

        @if($booking->gst_on_gold_amount > 0 || $booking->finance_charge_amount > 0 || $booking->storage_charge_amount > 0)
            $('#sumProcessingFeeBlock, #sumInterestBlock').attr('style', 'display: none !important;');
            
            @if($booking->gst_on_gold_amount > 0)
                $('#sumGstGoldBlock').attr('style', 'display: block !important;');
                $('#sumGstGold').text("₹" + parseFloat("{{ $booking->gst_on_gold_amount }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            @endif

            @if($booking->finance_charge_amount > 0)
                $('#sumFinanceBlock').attr('style', 'display: block !important;');
                $('#sumFinance').text("₹" + parseFloat("{{ $booking->finance_charge_amount }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            @endif

            @if($booking->storage_charge_amount > 0)
                $('#sumStorageBlock').attr('style', 'display: block !important;');
                $('#sumStorage').text("₹" + parseFloat("{{ $booking->storage_charge_amount }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            @endif

            @if($booking->gst_on_charges_amount > 0)
                $('#sumGstChargesBlock').attr('style', 'display: block !important;');
                $('#sumGstCharges').text("₹" + parseFloat("{{ $booking->gst_on_charges_amount }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            @endif
        @else
            $('#sumProcessingFeeBlock').attr('style', 'display: block !important;');
            $('#sumProcessingFee').text("₹" + parseFloat("{{ $booking->emiPlan->processing_fee }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            
            $('#sumInterestBlock').attr('style', 'display: block !important;');
            $('#sumInterest').text("₹" + parseFloat("{{ $booking->grand_total - $booking->locked_gold_value }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            
            $('#sumGstGoldBlock, #sumFinanceBlock, #sumStorageBlock, #sumGstChargesBlock').attr('style', 'display: none !important;');
        @endif

        $('#sumEmiAmount').text("₹" + parseFloat("{{ $booking->monthly_emi }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
        $('#sumTotalPayable').text("₹" + parseFloat("{{ $booking->grand_total }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
        $('#sumCompletionDate').text("{{ $booking->estimated_completion_date->format('Y-m-d') }}");
        $('#sumLateFee').text("₹" + parseFloat("{{ $booking->emiPlan->late_fee }}").toLocaleString() + " / Default");

        $('#summaryRow').show();

        // Toggle delivery fields based on method selection
        $('#deliveryMethodSelect').on('change', function() {
            let selected = $(this).val();
            if (selected === 'Courier') {
                $('#courierAddressBlock').show().find('textarea').attr('required', true);
                $('#branchPickupBlock').hide().find('input').attr('required', false);
            } else if (selected === 'Branch Pickup') {
                $('#courierAddressBlock').hide().find('textarea').attr('required', false);
                $('#branchPickupBlock').show().find('input').attr('required', true);
            } else {
                $('#courierAddressBlock').hide().find('textarea').attr('required', false);
                $('#branchPickupBlock').hide().find('input').attr('required', false);
            }
        });
    });
</script>
@endpush
