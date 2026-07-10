@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card bg-white text-dark border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Edit EMI Plan</h4>
                        <p class="card-description text-muted">Update EMI template configurations and parameters</p>
                    </div>
                    <a href="{{ route('emi-plans.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to List
                    </a>
                </div>

                <form id="planForm" action="{{ route('emi-plans.update', $plan->id) }}" method="POST" class="mt-4">
                    @csrf
                    
                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">1. Basic Plan Properties</h5>
                    <div class="row mb-4">
                        <div class="col-md-4 form-group">
                            <label class="text-dark">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="plan_name" required value="{{ $plan->plan_name }}" class="form-control bg-white text-dark">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Plan Code <span class="text-danger">*</span></label>
                            <input type="text" name="plan_code" required value="{{ $plan->plan_code }}" class="form-control bg-white text-dark">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Duration (Months) <span class="text-danger">*</span></label>
                            <input type="number" name="duration_months" required min="1" value="{{ $plan->duration_months }}" class="form-control bg-white text-dark">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Display Order</label>
                            <input type="number" name="display_order" value="{{ $plan->display_order }}" class="form-control bg-white text-dark">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Status <span class="text-danger">*</span></label>
                            <select name="status" required class="form-control bg-white text-dark">
                                <option value="active" {{ $plan->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $plan->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-4 form-group d-flex align-items-center pt-4">
                            <div class="form-check m-0">
                                <label class="form-check-label text-dark font-weight-bold">
                                    <input type="checkbox" name="is_default" value="1" class="form-check-input" {{ $plan->is_default ? 'checked' : '' }}>
                                    Set as Default Template Plan
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>

                        <div class="col-12 form-group">
                            <label class="text-dark">Plan Description</label>
                            <textarea name="description" rows="3" class="form-control bg-white text-dark">{{ $plan->description }}</textarea>
                        </div>
                    </div>

                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">2. Booking Limits & Thresholds</h5>
                    <div class="row mb-4">
                        <div class="col-md-3 form-group">
                            <label class="text-dark">Min Booking Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="minimum_booking_amount" required min="0" step="0.01" value="{{ $plan->minimum_booking_amount }}" class="form-control bg-white text-dark">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Max Booking Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="maximum_booking_amount" required min="0" step="0.01" value="{{ $plan->maximum_booking_amount }}" class="form-control bg-white text-dark">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Min Gold Weight (g) <span class="text-danger">*</span></label>
                            <input type="number" name="minimum_gold_weight" required min="0" step="0.01" value="{{ $plan->minimum_gold_weight }}" class="form-control bg-white text-dark">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="text-dark">Max Gold Weight (g) <span class="text-danger">*</span></label>
                            <input type="number" name="maximum_gold_weight" required min="0" step="0.01" value="{{ $plan->maximum_gold_weight }}" class="form-control bg-white text-dark">
                        </div>
                    </div>

                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">3. Financial Charges</h5>
                    <div class="row mb-4">
                        <!-- GST ON GOLD -->
                        <div class="col-md-6 mb-4">
                            <div class="p-3 border rounded bg-light">
                                <div class="form-check mb-2">
                                    <label class="form-check-label text-dark font-weight-bold">
                                        <input type="checkbox" name="gst_on_gold_enabled" value="1" id="gst_on_gold_enabled" class="form-check-input" {{ $plan->gst_on_gold_enabled ? 'checked' : '' }}>
                                        GST on Gold Enabled
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                                <div class="form-group mb-0 mt-3">
                                    <label class="text-dark">GST on Gold (%) <span class="text-danger">*</span></label>
                                    <input type="number" name="gst_on_gold_percent" id="gst_on_gold_percent" min="0" max="100" step="0.01" value="{{ $plan->gst_on_gold_percent ?? '3.00' }}" class="form-control bg-white text-dark">
                                </div>
                            </div>
                        </div>

                        <!-- GST ON CHARGES -->
                        <div class="col-md-6 mb-4">
                            <div class="p-3 border rounded bg-light">
                                <div class="form-check mb-2">
                                    <label class="form-check-label text-dark font-weight-bold">
                                        <input type="checkbox" name="gst_on_charges_enabled" value="1" id="gst_on_charges_enabled" class="form-check-input" {{ $plan->gst_on_charges_enabled ? 'checked' : '' }}>
                                        GST on Charges Enabled
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                                <div class="form-group mb-0 mt-3">
                                    <label class="text-dark">GST on Charges (%) <span class="text-danger">*</span></label>
                                    <input type="number" name="gst_on_charges_percent" id="gst_on_charges_percent" min="0" max="100" step="0.01" value="{{ $plan->gst_on_charges_percent ?? '18.00' }}" class="form-control bg-white text-dark">
                                </div>
                            </div>
                        </div>

                        <!-- FINANCE CHARGE -->
                        <div class="col-md-6 mb-4">
                            <div class="p-3 border rounded bg-light">
                                <div class="form-check mb-2">
                                    <label class="form-check-label text-dark font-weight-bold">
                                        <input type="checkbox" name="finance_charge_enabled" value="1" id="finance_charge_enabled" class="form-check-input" {{ $plan->finance_charge_enabled ? 'checked' : '' }}>
                                        Finance Charge Enabled
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-6 form-group mb-0">
                                        <label class="text-dark">Charge Type <span class="text-danger">*</span></label>
                                        <select name="finance_charge_type" id="finance_charge_type" class="form-control bg-white text-dark">
                                            <option value="percentage" {{ strtolower($plan->finance_charge_type) === 'percentage' || strtolower($plan->finance_charge_type) === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                                            <option value="fixed" {{ strtolower($plan->finance_charge_type) === 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                                        </select>
                                    </div>
                                    <div class="col-6 form-group mb-0">
                                        <label class="text-dark">Charge Value <span class="text-danger">*</span></label>
                                        <input type="number" name="finance_charge_value" id="finance_charge_value" min="0" step="0.01" value="{{ $plan->finance_charge_value }}" class="form-control bg-white text-dark" placeholder="e.g. 12.00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STORAGE CHARGE -->
                        <div class="col-md-6 mb-4">
                            <div class="p-3 border rounded bg-light">
                                <div class="form-check mb-2">
                                    <label class="form-check-label text-dark font-weight-bold">
                                        <input type="checkbox" name="storage_charge_enabled" value="1" id="storage_charge_enabled" class="form-check-input" {{ $plan->storage_charge_enabled ? 'checked' : '' }}>
                                        Storage / Insurance / Price Lock Enabled
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-6 form-group mb-0">
                                        <label class="text-dark">Charge Type <span class="text-danger">*</span></label>
                                        <select name="storage_charge_type" id="storage_charge_type" class="form-control bg-white text-dark">
                                            <option value="percentage" {{ strtolower($plan->storage_charge_type) === 'percentage' || strtolower($plan->storage_charge_type) === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                                            <option value="fixed" {{ strtolower($plan->storage_charge_type) === 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                                        </select>
                                    </div>
                                    <div class="col-6 form-group mb-0">
                                        <label class="text-dark">Charge Value <span class="text-danger">*</span></label>
                                        <input type="number" name="storage_charge_value" id="storage_charge_value" min="0" step="0.01" value="{{ $plan->storage_charge_value }}" class="form-control bg-white text-dark" placeholder="e.g. 6.00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ROUNDING RULE -->
                        <div class="col-md-4 form-group mb-4">
                            <label class="text-dark font-weight-bold">Rounding Rule <span class="text-danger">*</span></label>
                            <select name="rounding_type" required class="form-control bg-white text-dark">
                                <option value="none" {{ strtolower($plan->rounding_type) === 'none' ? 'selected' : '' }}>None</option>
                                <option value="nearest_rupee" {{ strtolower(str_replace(' ', '_', $plan->rounding_type)) === 'nearest_rupee' ? 'selected' : '' }}>Nearest Rupee</option>
                                <option value="nearest_10" {{ strtolower(str_replace(' ', '_', $plan->rounding_type)) === 'nearest_10' ? 'selected' : '' }}>Nearest 10</option>
                                <option value="nearest_100" {{ strtolower(str_replace(' ', '_', $plan->rounding_type)) === 'nearest_100' ? 'selected' : '' }}>Nearest 100</option>
                            </select>
                        </div>

                        <!-- LATE FEE PENALTY TYPE -->
                        <div class="col-md-4 form-group mb-4">
                            <label class="text-dark font-weight-bold">Late Fee Penalty Type <span class="text-danger">*</span></label>
                            <select name="late_fee_type" required class="form-control bg-white text-dark">
                                <option value="fixed" {{ $plan->late_fee_type === 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                                <option value="percent" {{ $plan->late_fee_type === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                            </select>
                        </div>

                        <!-- LATE FEE PENALTY VALUE -->
                        <div class="col-md-4 form-group mb-4">
                            <label class="text-dark font-weight-bold">Late Fee Penalty Value <span class="text-danger">*</span></label>
                            <input type="number" name="late_fee" required min="0" step="0.01" value="{{ $plan->late_fee }}" class="form-control bg-white text-dark" placeholder="e.g. 100">
                        </div>

                        <!-- GRACE DAYS ALLOWED -->
                        <div class="col-md-4 form-group mb-4">
                            <label class="text-dark font-weight-bold">Grace Days Allowed <span class="text-danger">*</span></label>
                            <input type="number" name="grace_days" required min="0" value="{{ $plan->grace_days }}" class="form-control bg-white text-dark" placeholder="e.g. 5">
                        </div>

                        <!-- MISSED EMIS TERMINATION LIMIT -->
                        <div class="col-md-4 form-group mb-4">
                            <label class="text-dark font-weight-bold">Missed EMIs Termination Limit <span class="text-danger">*</span></label>
                            <input type="number" name="auto_terminate_after_missed_emi" required min="0" value="{{ $plan->auto_terminate_after_missed_emi }}" class="form-control bg-white text-dark" placeholder="e.g. 3">
                        </div>

                        <!-- MAINTENANCE DEDUCTION FEE -->
                        <div class="col-md-4 form-group mb-4">
                            <label class="text-dark font-weight-bold">Maintenance Deduction Fee (%) <span class="text-danger">*</span></label>
                            <input type="number" name="maintenance_deduction_percent" required min="0" max="100" step="0.01" value="{{ $plan->maintenance_deduction_percent }}" class="form-control bg-white text-dark" placeholder="e.g. 10.00">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                        <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                            <i class="mdi mdi-check mr-1"></i> Save Changes
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
        function toggleFinancialFields() {
            // GST on Gold
            let gstGoldEnabled = $('#gst_on_gold_enabled').is(':checked');
            $('#gst_on_gold_percent').prop('disabled', !gstGoldEnabled).prop('required', gstGoldEnabled);

            // GST on Charges
            let gstChargesEnabled = $('#gst_on_charges_enabled').is(':checked');
            $('#gst_on_charges_percent').prop('disabled', !gstChargesEnabled).prop('required', gstChargesEnabled);

            // Finance Charge
            let financeEnabled = $('#finance_charge_enabled').is(':checked');
            $('#finance_charge_type, #finance_charge_value').prop('disabled', !financeEnabled).prop('required', financeEnabled);

            // Storage Charge
            let storageEnabled = $('#storage_charge_enabled').is(':checked');
            $('#storage_charge_type, #storage_charge_value').prop('disabled', !storageEnabled).prop('required', storageEnabled);
        }

        $('#gst_on_gold_enabled, #gst_on_charges_enabled, #finance_charge_enabled, #storage_charge_enabled').on('change', toggleFinancialFields);
        toggleFinancialFields();

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
                        title: 'EMI Plan Updated',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    }).then(() => {
                        window.location.href = "{{ route('emi-plans.index') }}";
                    });
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html('<i class="mdi mdi-check mr-1"></i> Save Changes');
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
