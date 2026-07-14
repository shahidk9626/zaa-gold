@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-md-8 mx-auto grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <div>
                        <h4 class="card-title text-dark font-weight-bold">Add Referral Record</h4>
                        <p class="card-description text-muted">Create a new referral mapping between two customers.</p>
                    </div>
                    <a href="{{ route('referrals.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Back to Directory
                    </a>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="referralForm" action="{{ route('referrals.store') }}" method="POST" class="forms-sample">
                    @csrf
                    
                    <!-- Referral Code -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Referral Code</label>
                        <div class="input-group">
                            <input type="text" id="referral_code" name="referral_code" class="form-control bg-white text-dark" placeholder="REF-XXXXXX" value="{{ old('referral_code', 'REF-' . strtoupper(Str::random(8))) }}" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info text-white" onclick="regenerateCode()">Generate</button>
                            </div>
                        </div>
                    </div>

                    <!-- Referrer Customer -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Referrer (Customer who shared the code)</label>
                        <select name="referrer_customer_id" class="form-control bg-white text-dark select2" required>
                            <option value="">Select Referrer</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('referrer_customer_id') == $cust->id ? 'selected' : '' }}>
                                    {{ $cust->name }} ({{ $cust->phone ?? $cust->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Referred Customer -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Referred (New Customer joining)</label>
                        <select name="referred_customer_id" class="form-control bg-white text-dark select2" required>
                            <option value="">Select Referred Customer</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('referred_customer_id') == $cust->id ? 'selected' : '' }}>
                                    {{ $cust->name }} ({{ $cust->phone ?? $cust->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Reward Type -->
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Reward Type</label>
                            <select name="reward_type" class="form-control bg-white text-dark" required>
                                <option value="Cash" {{ old('reward_type') === 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Gold Grams" {{ old('reward_type') === 'Gold Grams' ? 'selected' : '' }}>Gold Grams</option>
                                <option value="Discount" {{ old('reward_type') === 'Discount' ? 'selected' : '' }}>Discount</option>
                            </select>
                        </div>

                        <!-- Reward Amount -->
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Reward Amount (₹)</label>
                            <input type="number" step="0.01" name="reward_amount" class="form-control bg-white text-dark" placeholder="e.g. 500" value="{{ old('reward_amount', '0.00') }}" required>
                        </div>
                    </div>

                    <!-- Reward Status -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Reward Status</label>
                        <select name="reward_status" class="form-control bg-white text-dark" required>
                            <option value="Pending" {{ old('reward_status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Eligible" {{ old('reward_status') === 'Eligible' ? 'selected' : '' }}>Eligible</option>
                            <option value="Rewarded" {{ old('reward_status') === 'Rewarded' ? 'selected' : '' }}>Rewarded</option>
                            <option value="Rejected" {{ old('reward_status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold">Remarks / Internal Notes</label>
                        <textarea name="remarks" class="form-control bg-white text-dark" rows="3" placeholder="Enter any extra details...">{{ old('remarks') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2 px-4">Create Entry</button>
                    <a href="{{ route('referrals.index') }}" class="btn btn-light px-4">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function regenerateCode() {
        let text = "";
        let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        for (let i = 0; i < 8; i++)
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        document.getElementById('referral_code').value = "REF-" + text;
    }

    $(document).ready(function () {
        $("#referralForm").validate({
            rules: {
                referral_code: "required",
                referrer_customer_id: "required",
                referred_customer_id: {
                    required: true,
                    notEqualTo: "[name='referrer_customer_id']"
                },
                reward_type: "required",
                reward_amount: {
                    required: true,
                    number: true,
                    min: 0
                },
                reward_status: "required"
            },
            messages: {
                referred_customer_id: {
                    notEqualTo: "Referred customer must be different from referrer customer."
                }
            }
        });

        // Add custom rule
        $.validator.addMethod("notEqualTo", function(value, element, param) {
            return this.optional(element) || value != $(param).val();
        }, "Please specify a different value");
    });
</script>
@endpush
