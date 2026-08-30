@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-md-8 mx-auto grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <div>
                        <h4 class="card-title text-dark font-weight-bold">Edit Referral Entry</h4>
                        <p class="card-description text-muted">Update status, link customer bookings, and process cashback payouts.</p>
                    </div>
                    <a href="{{ route('referrals.show', $referral->id) }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Cancel & Back
                    </a>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="referralEditForm" action="{{ route('referrals.update', $referral->id) }}" method="POST" class="forms-sample">
                    @csrf
                    
                    <!-- Referral Code -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Referral Code Used</label>
                        <input type="text" class="form-control bg-light text-dark font-weight-bold" value="{{ $referral->referral_code }}" readonly>
                    </div>

                    <!-- Referrer Customer -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Referrer (Recommender)</label>
                        <input type="text" class="form-control bg-light text-dark" value="{{ $referral->referrer->name ?? 'N/A' }} (ID: #{{ $referral->referrer_id }})" readonly>
                    </div>

                    <!-- Referred Customer -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Referred Customer (Purchaser)</label>
                        <input type="text" class="form-control bg-light text-dark" value="{{ $referral->customer->name ?? 'N/A' }} (ID: #{{ $referral->customer_id }})" readonly>
                    </div>

                    <!-- Booking Association -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Associated Booking (Referred Customer's Purchase)</label>
                        <select name="booking_id" class="form-control bg-white text-dark select2">
                            <option value="">No Booking Associated</option>
                            @foreach($bookings as $bk)
                                <option value="{{ $bk->id }}" {{ old('booking_id', $referral->booking_id) == $bk->id ? 'selected' : '' }}>
                                    #{{ $bk->booking_number }} - Gold Weight: {{ number_format($bk->gold_weight, 3) }}g - Grand Total: ₹{{ number_format($bk->grand_total, 2) }} ({{ $bk->status }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Link this referral to the purchaser's specific Gold Plan booking.</small>
                    </div>

                    <!-- Referral Cashback Status -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Referral Cashback Status</label>
                        <select name="status" id="referral_status" class="form-control bg-white text-dark" required>
                            <option value="Pending" {{ old('status', $referral->status) === 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Under Review" {{ old('status', $referral->status) === 'Under Review' ? 'selected' : '' }}>Under Review</option>
                            <option value="Approved" {{ old('status', $referral->status) === 'Approved' ? 'selected' : '' }}>Approved</option>
                            <option value="Completed" {{ old('status', $referral->status) === 'Completed' ? 'selected' : '' }}>Completed</option>
                            <option value="Rejected" {{ old('status', $referral->status) === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <!-- Payment Reference Number (Required only if status is Completed) -->
                    <div class="form-group mb-3" id="payment-ref-container">
                        <label class="font-weight-bold text-dark">Payment Reference Number (UTR / Bank Transaction ID)</label>
                        <input type="text" name="payment_reference_number" id="payment_reference_number" class="form-control bg-white text-dark" value="{{ old('payment_reference_number', $referral->payment_reference_number) }}" placeholder="Enter UTR, bank transfer reference, etc.">
                        <small class="text-muted">Required only when marking status as Completed.</small>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark">Remarks / Internal Notes</label>
                        <textarea name="remarks" class="form-control bg-white text-dark" rows="3" placeholder="Enter processing remarks, reasons for rejection, or payout notes...">{{ old('remarks', $referral->admin_remark) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2 px-4">Update Referral</button>
                    <a href="{{ route('referrals.show', $referral->id) }}" class="btn btn-light px-4">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        function togglePaymentRef() {
            var status = $("#referral_status").val();
            if (status === "Completed") {
                $("#payment-ref-container").show();
                $("#payment_reference_number").prop('required', true);
            } else {
                $("#payment-ref-container").hide();
                $("#payment_reference_number").prop('required', false);
            }
        }

        $("#referral_status").on("change", togglePaymentRef);
        togglePaymentRef(); // Initial toggle on page load

        $("#referralEditForm").validate({
            rules: {
                status: "required",
                payment_reference_number: {
                    required: function() {
                        return $("#referral_status").val() === "Completed";
                    }
                }
            },
            errorClass: "text-danger small mt-1",
            errorElement: "div"
        });
    });
</script>
@endpush
