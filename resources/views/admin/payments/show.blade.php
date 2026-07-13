@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Payment Transaction Details</h4>
                    <p class="card-description text-muted mb-0">Detailed breakdown of payment #{{ $payment->payment_number }} and tax receipt.</p>
                </div>
                <div>
                    <a href="{{ route('payments.index') }}" class="btn btn-secondary px-4 mr-2">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to History
                    </a>
                    @if(hasPermission('receipt.download'))
                    <a href="{{ route('receipts.download', $payment->id) }}" class="btn btn-success px-4">
                        <i class="mdi mdi-download mr-1"></i> Download PDF Receipt
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Details Card -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Payment Details</h5>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Payment Number</label>
                    <span class="font-weight-bold text-dark">{{ $payment->payment_number }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Receipt Number</label>
                    <span class="font-weight-bold text-dark">{{ $payment->receipt_number }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Payment Mode</label>
                    <span class="badge badge-outline-dark text-dark">{{ $payment->payment_mode }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Payment Date</label>
                    <span class="font-weight-bold text-dark">{{ $payment->payment_date->format('d M Y, h:i A') }}</span>
                </div>
                <div class="col-12 mb-3">
                    <label class="small text-muted d-block mb-1">Transaction Reference</label>
                    <span class="font-weight-bold text-dark">{{ $payment->transaction_reference ?? 'N/A' }}</span>
                </div>
                
                <div class="col-12 my-2"><hr style="border-color: rgba(0,0,0,0.1);"></div>

                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Principal Portion Paid</label>
                    <span class="font-weight-bold text-dark">₹{{ number_format($payment->principal_paid, 2) }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Interest Portion Paid</label>
                    <span class="font-weight-bold text-dark">₹{{ number_format($payment->interest_paid, 2) }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Late Fee Paid</label>
                    <span class="font-weight-bold text-danger">₹{{ number_format($payment->late_fee_paid, 2) }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">GST Paid (Proportional)</label>
                    <span class="font-weight-bold text-dark">₹{{ number_format($payment->gst_paid, 2) }}</span>
                </div>

                <div class="col-12 mb-3 bg-light p-3 rounded border">
                    <label class="small text-muted d-block mb-1">Grand Total Paid Amount</label>
                    <span class="h3 font-weight-bold text-success">₹{{ number_format($payment->amount_paid, 2) }}</span>
                </div>

                <div class="col-12">
                    <label class="small text-muted d-block mb-1">Remarks</label>
                    <span class="text-dark small">{{ $payment->remarks ?? 'No comments.' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Entities Column -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Booking & Customer Details</h5>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Booking Link</label>
                    <a href="{{ route('bookings.show', $payment->booking_id) }}" class="font-weight-bold text-primary">
                        {{ $payment->booking->booking_number }}
                    </a>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Customer Name</label>
                    <span class="font-weight-bold text-dark">{{ $payment->booking->customer->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Customer Email</label>
                    <span class="font-weight-bold text-dark">{{ $payment->booking->customer->email ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Gold Product</label>
                    <span class="font-weight-bold text-dark">{{ $payment->booking->product->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Weight & Purity</label>
                    <span class="font-weight-bold text-dark">{{ number_format($payment->booking->gold_weight, 2) }}g ({{ $payment->booking->product->gold_type ?? 'N/A' }}, {{ number_format($payment->booking->gold_purity, 2) }}%)</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">EMI Installment Number</label>
                    <span class="badge badge-primary text-dark font-weight-bold px-3 py-2">
                        EMI #{{ $payment->emiSchedule->installment_number ?? 'N/A' }}
                    </span>
                </div>
                
                @if($payment->emiSchedule)
                <div class="col-12 my-2"><hr style="border-color: rgba(0,0,0,0.1);"></div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Scheduled Due Date</label>
                    <span class="font-weight-bold text-dark">{{ $payment->emiSchedule->due_date->format('d M Y') }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Installment Status</label>
                    <span class="badge badge-success text-dark font-weight-bold px-3 py-2">Paid</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
