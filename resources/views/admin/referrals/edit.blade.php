@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-md-8 mx-auto grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <div>
                        <h4 class="card-title text-dark font-weight-bold">Edit Referral Entry</h4>
                        <p class="card-description text-muted">Update status or associate rewards with specific bookings.</p>
                    </div>
                    <a href="{{ route('referrals.show', $referral->id) }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Cancel & Back
                    </a>
                </div>

                <form id="referralEditForm" action="{{ route('referrals.update', $referral->id) }}" method="POST" class="forms-sample">
                    @csrf
                    
                    <!-- Referral Code -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Referral Code</label>
                        <input type="text" name="referral_code" class="form-control bg-light text-dark" value="{{ old('referral_code', $referral->referral_code) }}" readonly>
                        <small class="text-muted">Referral code cannot be changed once created.</small>
                    </div>

                    <!-- Referrer Customer -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Referrer</label>
                        <select name="referrer_customer_id" class="form-control bg-light text-dark" disabled>
                            <option value="{{ $referral->referrer_customer_id }}">{{ $referral->referrer->name ?? 'N/A' }}</option>
                        </select>
                        <input type="hidden" name="referrer_customer_id" value="{{ $referral->referrer_customer_id }}">
                    </div>

                    <!-- Referred Customer -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Referred Customer</label>
                        <select name="referred_customer_id" class="form-control bg-light text-dark" disabled>
                            <option value="{{ $referral->referred_customer_id }}">{{ $referral->referred->name ?? 'N/A' }}</option>
                        </select>
                        <input type="hidden" name="referred_customer_id" value="{{ $referral->referred_customer_id }}">
                    </div>

                    <!-- Booking Association -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Associated Booking (Referred Customer's Purchase)</label>
                        <select name="booking_id" class="form-control bg-white text-dark select2">
                            <option value="">No Booking Associated</option>
                            @foreach($bookings as $bk)
                                <option value="{{ $bk->id }}" {{ old('booking_id', $referral->booking_id) == $bk->id ? 'selected' : '' }}>
                                    {{ $bk->booking_number }} - ₹{{ number_format($bk->grand_total, 2) }} ({{ $bk->status }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Only active bookings of the referred customer are listed here.</small>
                    </div>

                    <!-- Reward Type -->
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Reward Type</label>
                            <select name="reward_type" class="form-control bg-white text-dark" required>
                                <option value="Cash" {{ old('reward_type', $referral->reward_type) === 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Gold Grams" {{ old('reward_type', $referral->reward_type) === 'Gold Grams' ? 'selected' : '' }}>Gold Grams</option>
                                <option value="Discount" {{ old('reward_type', $referral->reward_type) === 'Discount' ? 'selected' : '' }}>Discount</option>
                            </select>
                        </div>

                        <!-- Reward Amount -->
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Reward Amount (₹)</label>
                            <input type="number" step="0.01" name="reward_amount" class="form-control bg-white text-dark" value="{{ old('reward_amount', $referral->reward_amount) }}" required>
                        </div>
                    </div>

                    <!-- Reward Status -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Reward Status</label>
                        <select name="reward_status" class="form-control bg-white text-dark" required>
                            <option value="Pending" {{ old('reward_status', $referral->reward_status) === 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Eligible" {{ old('reward_status', $referral->reward_status) === 'Eligible' ? 'selected' : '' }}>Eligible</option>
                            <option value="Rewarded" {{ old('reward_status', $referral->reward_status) === 'Rewarded' ? 'selected' : '' }}>Rewarded</option>
                            <option value="Rejected" {{ old('reward_status', $referral->reward_status) === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold">Remarks / Internal Notes</label>
                        <textarea name="remarks" class="form-control bg-white text-dark" rows="3">{{ old('remarks', $referral->remarks) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2 px-4">Update Entry</button>
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
        $("#referralEditForm").validate({
            rules: {
                reward_type: "required",
                reward_amount: {
                    required: true,
                    number: true,
                    min: 0
                },
                reward_status: "required"
            }
        });
    });
</script>
@endpush
