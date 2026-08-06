@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="text-dark"><i class="mdi mdi-close-circle text-danger"></i> Cancellation Request Details</h3>
            <a href="{{ route('admin.cancellations.index') }}" class="btn btn-secondary btn-sm">
                <i class="mdi mdi-arrow-left"></i> Back to Requests
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
    </div>
@endif

<div class="row">
    <!-- Main Details Column -->
    <div class="col-lg-8 grid-margin">
        <!-- Customer & Booking Overview Card -->
        <div class="card bg-white border shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title text-dark font-weight-bold mb-3"><i class="mdi mdi-account-box text-primary"></i> Account & Plan Summary</h5>
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h6 class="text-muted font-weight-bold mb-2">Customer Profile</h6>
                        <p class="mb-1"><strong>Name:</strong> {{ $cancellationRequest->customer?->name }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $cancellationRequest->customer?->email }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $cancellationRequest->customer?->customerDetail?->phone_number ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>KYC Status:</strong> 
                            <span class="badge badge-{{ $cancellationRequest->customer?->customerDetail?->kyc_status === 'verified' ? 'success' : 'warning' }}">
                                {{ ucfirst($cancellationRequest->customer?->customerDetail?->kyc_status ?? 'Pending') }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6 pl-md-4">
                        <h6 class="text-muted font-weight-bold mb-2">Booking Details</h6>
                        <p class="mb-1"><strong>Booking Number:</strong> {{ $booking->booking_number }}</p>
                        <p class="mb-1"><strong>Status:</strong> <span class="badge badge-primary">{{ $booking->status }}</span></p>
                        <p class="mb-1"><strong>Product:</strong> {{ $booking->product?->name }}</p>
                        <p class="mb-1"><strong>Locked Weight:</strong> {{ number_format($booking->gold_weight, 2) }}g (₹{{ number_format($booking->locked_price_per_gram, 2) }}/g)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calculation Breakdown Card -->
        <div class="card bg-white border shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title text-dark font-weight-bold mb-3"><i class="mdi mdi-calculator text-success"></i> Refund & Cancellation Calculation</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered text-dark mb-3">
                        <thead class="bg-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-right">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Total Amount Paid</strong> (Successful Payments Only)</td>
                                <td align="right" class="font-weight-bold text-dark">₹{{ number_format($cancellationRequest->total_amount_paid, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-danger"><strong>Cancellation Charges Deduciton</strong> ({{ number_format($cancellationRequest->cancellation_charge_percent, 2) }}%)</td>
                                <td align="right" class="text-danger font-weight-bold">- ₹{{ number_format($cancellationRequest->cancellation_charge_amount, 2) }}</td>
                            </tr>
                            <tr class="table-success text-success" style="font-size: 1.1rem;">
                                <td><strong>Net Refundable Amount</strong></td>
                                <td align="right" class="font-weight-bold">₹{{ number_format($cancellationRequest->refund_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card bg-light border-0 mb-2">
                    <div class="card-body p-3">
                        <h6 class="font-weight-bold text-dark mb-1"><i class="mdi mdi-alert-circle"></i> Customer Reason for Cancellation</h6>
                        <p class="font-italic text-dark mb-0">"{{ $cancellationRequest->cancellation_reason }}"</p>
                    </div>
                </div>

                @if($cancellationRequest->bank_account_number)
                <div class="card bg-light border-0">
                    <div class="card-body p-3">
                        <h6 class="font-weight-bold text-dark mb-2"><i class="mdi mdi-bank"></i> Customer Bank Details</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Bank Name</span>
                                <strong class="text-dark">{{ $cancellationRequest->bank_name }}</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Account Number</span>
                                <strong class="text-dark">{{ $cancellationRequest->bank_account_number }}</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">IFSC Code</span>
                                <strong class="text-dark">{{ $cancellationRequest->bank_ifsc }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Payments and EMI Repayments Card -->
        <div class="card bg-white border shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title text-dark font-weight-bold mb-3"><i class="mdi mdi-calendar-text text-info"></i> Repayments & EMI Schedule</h5>
                
                <ul class="nav nav-tabs border-0 bg-light rounded p-1 mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active py-2" data-toggle="tab" href="#emi-schedule">EMI Installments</a></li>
                    <li class="nav-item"><a class="nav-link py-2" data-toggle="tab" href="#payment-receipts">Receipts History</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="emi-schedule">
                        <div class="table-responsive">
                            <table class="table table-hover text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Due Date</th>
                                        <th>EMI Amount</th>
                                        <th>Status</th>
                                        <th>Paid At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schedule as $emi)
                                    <tr>
                                        <td>{{ $emi->installment_number }}</td>
                                        <td>{{ $emi->due_date?->format('d M Y') }}</td>
                                        <td>₹{{ number_format($emi->emi_amount, 2) }}</td>
                                        <td>
                                            @php
                                                $badgeClass = 'warning';
                                                if ($emi->status === 'Paid') $badgeClass = 'success';
                                                elseif ($emi->status === 'Waived') $badgeClass = 'light text-dark border';
                                                elseif ($emi->status === 'Overdue') $badgeClass = 'danger';
                                            @endphp
                                            <span class="badge badge-{{ $badgeClass }}">{{ $emi->status }}</span>
                                        </td>
                                        <td>{{ $emi->paid_at?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="payment-receipts">
                        <div class="table-responsive">
                            <table class="table table-hover text-dark">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Receipt Number</th>
                                        <th>Payment Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payments as $payment)
                                    <tr>
                                        <td class="font-weight-bold">{{ $payment->receipt_number ?? 'N/A' }}</td>
                                        <td>{{ $payment->payment_date?->format('d M Y, h:i A') }}</td>
                                        <td class="font-weight-bold">₹{{ number_format($payment->amount_paid, 2) }}</td>
                                        <td>{{ $payment->payment_mode }}</td>
                                        <td>
                                            <span class="badge badge-{{ $payment->status === 'Paid' ? 'success' : 'danger' }}">{{ $payment->status }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">No receipts recorded yet.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions & Timeline Sidebar Column -->
    <div class="col-lg-4 grid-margin">
        <!-- Admin Operations Card -->
        <div class="card bg-white border shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title text-dark font-weight-bold mb-3"><i class="mdi mdi-gavel text-warning"></i> Admin Panel Actions</h5>
                
                <div class="mb-3">
                    <span class="text-muted d-block small">Current Status</span>
                    <h5 class="font-weight-bold text-dark mb-0">{{ $cancellationRequest->status }}</h5>
                </div>

                <hr class="my-3">

                <!-- Actions Flow Panel -->
                @if(in_array($cancellationRequest->status, ['Requested', 'Under Review']))
                    <div class="d-flex flex-column">
                        <button type="button" class="btn btn-success mb-2" data-toggle="modal" data-target="#approveModal">
                            <i class="mdi mdi-check"></i> Approve Cancellation
                        </button>
                        <button type="button" class="btn btn-danger mb-2" data-toggle="modal" data-target="#rejectModal">
                            <i class="mdi mdi-close"></i> Reject Cancellation
                        </button>
                        <button type="button" class="btn btn-info mb-2" data-toggle="modal" data-target="#retainModal">
                            <i class="mdi mdi-heart"></i> Retain Customer
                        </button>
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#reviewModal">
                            <i class="mdi mdi-eye"></i> Mark Under Review
                        </button>
                    </div>
                @elseif($cancellationRequest->status === 'Approved')
                    <div class="alert alert-success py-2 mb-3">
                        <i class="mdi mdi-check-circle"></i> Cancellation Approved. Setup refund below.
                    </div>
                    <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#initiateRefundModal">
                        <i class="mdi mdi-arrow-right-bold"></i> Initiate Refund
                    </button>
                @elseif($cancellationRequest->status === 'Refund Initiated')
                    <div class="alert alert-warning py-2 mb-3 text-dark">
                        <i class="mdi mdi-alert"></i> Refund is currently pending process.
                    </div>
                    <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#completeRefundModal">
                        <i class="mdi mdi-checkbox-marked-circle"></i> Mark Refund Completed
                    </button>
                @elseif($cancellationRequest->status === 'Refund Completed')
                    <div class="alert alert-success py-2 mb-0">
                        <h6 class="font-weight-bold mb-1"><i class="mdi mdi-check-circle"></i> Refund Processed</h6>
                        <p class="small mb-1"><strong>Txn Number:</strong> {{ $cancellationRequest->refund_transaction_number }}</p>
                        <p class="small mb-1"><strong>Refund Mode:</strong> {{ $cancellationRequest->refund_mode }}</p>
                        <p class="small mb-0"><strong>Completed At:</strong> {{ $cancellationRequest->refund_completed_at?->format('d M Y') }}</p>
                    </div>
                @elseif($cancellationRequest->status === 'Rejected')
                    <div class="alert alert-danger py-2 mb-0">
                        <i class="mdi mdi-close-circle"></i> Cancellation Request was Rejected.
                    </div>
                @elseif($cancellationRequest->status === 'Customer Retained')
                    <div class="alert alert-success py-2 mb-0">
                        <i class="mdi mdi-heart"></i> Customer was retained successfully.
                    </div>
                @endif
            </div>
        </div>

        <!-- Activity Audit Logs Timeline -->
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-dark font-weight-bold mb-3"><i class="mdi mdi-history"></i> Audit Trail History</h5>
                <ul class="bullet-line-list text-dark pl-3">
                    @forelse($logs->take(10) as $log)
                    <li class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="font-weight-bold small text-dark">{{ ucwords(str_replace('_', ' ', $log->action_type)) }}</span>
                            <span class="text-muted small">{{ $log->created_at->format('d M H:i') }}</span>
                        </div>
                        <p class="small text-muted mb-0">{{ $log->description }}</p>
                    </li>
                    @empty
                    <li class="text-muted small">No audit history recorded.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODALS FOR ADMINISTRATIVE ACTIONS
     ========================================== -->

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.cancellations.process_action', $cancellationRequest->id) }}" method="POST" class="modal-content text-dark bg-white border">
            @csrf
            <input type="hidden" name="action" value="approve">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold text-white"><i class="mdi mdi-check"></i> Approve Cancellation Request</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this plan cancellation? This action is irreversible.</p>
                <div class="alert alert-warning small text-dark">
                    <strong>Warning:</strong> The Booking will be marked as <strong>Cancelled</strong>. Future EMI repayments will freeze and cannot be collected.
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Remark <span class="text-danger">*</span></label>
                    <textarea name="admin_remark" required class="form-control bg-white text-dark border" rows="3" placeholder="Provide approval remark..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Confirm Approval</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.cancellations.process_action', $cancellationRequest->id) }}" method="POST" class="modal-content text-dark bg-white border">
            @csrf
            <input type="hidden" name="action" value="reject">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold text-white"><i class="mdi mdi-close"></i> Reject Cancellation Request</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reject this cancellation request?</p>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea name="admin_remark" required class="form-control bg-white text-dark border" rows="3" placeholder="Provide rejection reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<!-- Retain Modal -->
<div class="modal fade" id="retainModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.cancellations.process_action', $cancellationRequest->id) }}" method="POST" class="modal-content text-dark bg-white border">
            @csrf
            <input type="hidden" name="action" value="retain">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold text-white"><i class="mdi mdi-heart"></i> Mark Customer Retained</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Choose this if you convinced the customer to continue with the plan. The booking remains active and the customer can request cancellation again later if needed.</p>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Remark <span class="text-danger">*</span></label>
                    <textarea name="admin_remark" required class="form-control bg-white text-dark border" rows="3" placeholder="Provide retention remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info">Confirm Retained</button>
            </div>
        </form>
    </div>
</div>

<!-- Under Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.cancellations.process_action', $cancellationRequest->id) }}" method="POST" class="modal-content text-dark bg-white border">
            @csrf
            <input type="hidden" name="action" value="review">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title font-weight-bold text-white"><i class="mdi mdi-eye"></i> Mark Under Review</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Mark this request as currently being reviewed by the operations or management team.</p>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Remark <span class="text-danger">*</span></label>
                    <textarea name="admin_remark" required class="form-control bg-white text-dark border" rows="3" placeholder="Provide update remark..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning text-white">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Initiate Refund Modal -->
<div class="modal fade" id="initiateRefundModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.cancellations.refund_initiate', $cancellationRequest->id) }}" method="POST" class="modal-content text-dark bg-white border">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold text-white"><i class="mdi mdi-arrow-right-bold"></i> Initiate Refund Process</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you initiating the refund transaction with the payment gateway or bank transfer?</p>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Remark <span class="text-danger">*</span></label>
                    <textarea name="admin_remark" required class="form-control bg-white text-dark border" rows="3" placeholder="Provide refund initiation remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Mark Refund Initiated</button>
            </div>
        </form>
    </div>
</div>

<!-- Complete Refund Modal -->
<div class="modal fade" id="completeRefundModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.cancellations.refund_complete', $cancellationRequest->id) }}" method="POST" class="modal-content text-dark bg-white border">
            @csrf
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold text-white"><i class="mdi mdi-checkbox-marked-circle"></i> Complete Refund Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">Refund Transaction Number <span class="text-danger">*</span></label>
                    <input type="text" name="refund_transaction_number" required class="form-control bg-white text-dark border" placeholder="e.g. UTR123456789">
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Refund Mode <span class="text-danger">*</span></label>
                        <select name="refund_mode" required class="form-control bg-white text-dark border">
                            <option value="Online Transfer">Online Transfer</option>
                            <option value="UPI">UPI</option>
                            <option value="Gateway Refund">Gateway Auto-Refund</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Refund Date <span class="text-danger">*</span></label>
                        <input type="date" name="refund_date" required value="{{ date('Y-m-d') }}" class="form-control bg-white text-dark border">
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Remark <span class="text-danger">*</span></label>
                    <textarea name="admin_remark" required class="form-control bg-white text-dark border" rows="3" placeholder="Provide refund completion remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Mark Refund Completed</button>
            </div>
        </form>
    </div>
</div>
@endsection
