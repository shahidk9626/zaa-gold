@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card bg-white text-dark border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Create EMI Plan Master</h4>
                        <p class="card-description text-muted">Configure corporate EMI templates and calculations constraints</p>
                    </div>
                    <a href="{{ route('emi-plans.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to List
                    </a>
                </div>

                <form id="planForm" action="{{ route('emi-plans.store') }}" method="POST" class="mt-4">
                    @csrf
                    
                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">1. Basic Plan Properties</h5>
                    <div class="row mb-4">
                        <div class="col-md-4 form-group">
                            <label class="text-dark">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="plan_name" required class="form-control bg-white text-dark" placeholder="e.g. 12 Month Gold Accumulator">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Plan Code <span class="text-danger">*</span></label>
                            <input type="text" name="plan_code" required class="form-control bg-white text-dark" placeholder="e.g. GOLD-EMI-12M">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Duration (Months) <span class="text-danger">*</span></label>
                            <input type="number" name="duration_months" required min="1" class="form-control bg-white text-dark" placeholder="e.g. 12">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Display Order</label>
                            <input type="number" name="display_order" value="0" class="form-control bg-white text-dark" placeholder="e.g. 0">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Status <span class="text-danger">*</span></label>
                            <select name="status" required class="form-control bg-white text-dark">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-4 form-group d-flex align-items-center pt-4">
                            <div class="form-check m-0">
                                <label class="form-check-label text-dark font-weight-bold">
                                    <input type="checkbox" name="is_default" value="1" class="form-check-input">
                                    Set as Default Template Plan
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>

                        <div class="col-12 form-group">
                            <label class="text-dark">Plan Description</label>
                            <textarea name="description" rows="3" class="form-control bg-white text-dark" placeholder="Detailed plan info..."></textarea>
                        </div>
                    </div>

                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">2. Booking Limits & Thresholds</h5>
                    <div class="row mb-4">
                        <div class="col-md-3 form-group">
                            <label class="text-dark">Min Booking Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="minimum_booking_amount" required min="0" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 5000">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Max Booking Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="maximum_booking_amount" required min="0" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 1000000">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Min Gold Weight (g) <span class="text-danger">*</span></label>
                            <input type="number" name="minimum_gold_weight" required min="0" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 1.00">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Max Gold Weight (g) <span class="text-danger">*</span></label>
                            <input type="number" name="maximum_gold_weight" required min="0" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 200.00">
                        </div>
                    </div>

                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">3. Pricing Policies & Parameters</h5>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label class="text-dark">Interest Calculation Type <span class="text-danger">*</span></label>
                            <select name="interest_type" required class="form-control bg-white text-dark">
                                <option value="flat">Flat Interest Rate</option>
                                <option value="reducing">Reducing Balance Rate</option>
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Annual Interest Rate (%) <span class="text-danger">*</span></label>
                            <input type="number" name="interest_rate" required min="0" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 6.00">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Processing Fee Type <span class="text-danger">*</span></label>
                            <select name="processing_fee_type" required class="form-control bg-white text-dark">
                                <option value="fixed">Fixed Amount (₹)</option>
                                <option value="percent">Percentage (%)</option>
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Processing Fee Value <span class="text-danger">*</span></label>
                            <input type="number" name="processing_fee" required min="0" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 500">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Late Fee Penalty Type <span class="text-danger">*</span></label>
                            <select name="late_fee_type" required class="form-control bg-white text-dark">
                                <option value="fixed">Fixed Amount (₹)</option>
                                <option value="percent">Percentage (%)</option>
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Late Fee Penalty Value <span class="text-danger">*</span></label>
                            <input type="number" name="late_fee" required min="0" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 100">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Grace Days Allowed <span class="text-danger">*</span></label>
                            <input type="number" name="grace_days" required min="0" class="form-control bg-white text-dark" placeholder="e.g. 5">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Missed EMIs Termination Limit <span class="text-danger">*</span></label>
                            <input type="number" name="auto_terminate_after_missed_emi" required min="0" class="form-control bg-white text-dark" placeholder="e.g. 3">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Maintenance Deduction Fee (%) <span class="text-danger">*</span></label>
                            <input type="number" name="maintenance_deduction_percent" required min="0" max="100" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 10.00">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                        <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                            <i class="mdi mdi-check mr-1"></i> Save Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('#planForm').on('submit', function (e) {
            e.preventDefault();
            let submitBtn = $('#submitBtn');
            submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'EMI Plan Saved',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    }).then(() => {
                        window.location.href = "{{ route('emi-plans.index') }}";
                    });
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html('<i class="mdi mdi-check mr-1"></i> Save Plan');
                    let errors = xhr.responseJSON.errors;
                    let errorMsg = '';
                    if (errors) {
                        Object.keys(errors).forEach(key => {
                            errorMsg += errors[key][0] + '\n';
                        });
                    } else {
                        errorMsg = xhr.responseJSON.message || 'Something went wrong';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Failed',
                        text: errorMsg,
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });
    });
</script>
@endpush
