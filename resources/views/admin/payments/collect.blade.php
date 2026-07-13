@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Collect EMI Payment</h4>
                    <p class="card-description text-muted mb-0">Record manual repayment for installment #{{ $schedule->installment_number }} of Gold Booking #{{ $booking->booking_number }}.</p>
                </div>
                <div>
                    <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-secondary px-4">
                        <i class="mdi mdi-arrow-left mr-1"></i> Cancel & Return
                    </a>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Information Column -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Booking & Installment Details</h5>
            
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Booking Number</label>
                    <span class="font-weight-bold text-dark">{{ $booking->booking_number }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Customer Name</label>
                    <span class="font-weight-bold text-dark">{{ $booking->customer->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Gold Product</label>
                    <span class="font-weight-bold text-dark">{{ $booking->product->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">EMI Plan Duration</label>
                    <span class="font-weight-bold text-dark">{{ $booking->duration_months }} Months</span>
                </div>
                
                <div class="col-12 my-2"><hr style="border-color: rgba(0,0,0,0.1);"></div>

                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Installment Number</label>
                    <span class="font-weight-bold text-dark">#{{ $schedule->installment_number }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Scheduled Due Date</label>
                    <span class="font-weight-bold text-dark">{{ $schedule->due_date->format('d M Y') }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Opening Principal</label>
                    <span class="font-weight-bold text-dark">₹{{ number_format($schedule->opening_principal, 2) }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Closing Principal</label>
                    <span class="font-weight-bold text-dark">₹{{ number_format($schedule->closing_principal, 2) }}</span>
                </div>

                <div class="col-12 my-2"><hr style="border-color: rgba(0,0,0,0.1);"></div>

                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">EMI Installment Amount</label>
                    <span class="h5 font-weight-bold text-dark mb-0">₹{{ number_format($schedule->emi_amount, 2) }}</span>
                    <small class="d-block text-muted">(Principal: ₹{{ number_format($schedule->principal_amount, 2) }} + Interest/Charges: ₹{{ number_format($schedule->interest_amount, 2) }})</small>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Late Fee Accrued</label>
                    @if($isOverdue && $lateFee > 0)
                        <span class="h5 font-weight-bold text-danger mb-0">₹{{ number_format($lateFee, 2) }}</span>
                        <span class="badge badge-danger text-dark font-weight-bold ml-2">Overdue</span>
                    @else
                        <span class="h5 font-weight-bold text-dark mb-0">₹0.00</span>
                    @endif
                </div>

                <div class="col-12 my-2"><hr style="border-color: rgba(0,0,0,0.1);"></div>

                <div class="col-12 mb-3 bg-light p-3 rounded border">
                    <label class="small text-muted d-block mb-1">Total Collection Amount</label>
                    <span class="h3 font-weight-bold text-success">₹{{ number_format($totalPayable, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Collection Form -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Record Transaction Details</h5>
            <form action="{{ route('payments.collect_store', [$booking->id, $schedule->id]) }}" method="POST">
                @csrf
                
                <!-- Payment Date -->
                <div class="form-group mb-3">
                    <label class="text-dark font-weight-bold">Payment Collection Date</label>
                    <input type="datetime-local" name="payment_date" class="form-control bg-white text-dark @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('payment_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Payment Mode -->
                <div class="form-group mb-3">
                    <label class="text-dark font-weight-bold">Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" class="form-control bg-white text-dark @error('payment_mode') is-invalid @enderror" required>
                        @foreach(['Cash', 'UPI', 'Bank Transfer', 'Card', 'Cheque', 'Online Gateway'] as $mode)
                            <option value="{{ $mode }}" {{ old('payment_mode') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
                        @endforeach
                    </select>
                    @error('payment_mode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Transaction Reference -->
                <div class="form-group mb-3">
                    <label class="text-dark font-weight-bold">Transaction Reference / Cheque No.</label>
                    <input type="text" name="transaction_reference" class="form-control bg-white text-dark @error('transaction_reference') is-invalid @enderror" placeholder="UPI Txn ID, NEFT Ref, Cheque Number etc." value="{{ old('transaction_reference') }}">
                    @error('transaction_reference')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remarks -->
                <div class="form-group mb-4">
                    <label class="text-dark font-weight-bold">Payment Remarks</label>
                    <textarea name="remarks" class="form-control bg-white text-dark @error('remarks') is-invalid @enderror" rows="3" placeholder="Enter any extra details or transaction comments...">{{ old('remarks') }}</textarea>
                    @error('remarks')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="text-right">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="mdi mdi-cash-multiple mr-1"></i> Confirm & Collect Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
