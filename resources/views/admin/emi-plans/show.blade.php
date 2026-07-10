@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Specifications details -->
    <div class="col-lg-7 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <div>
                    <h5 class="text-primary font-weight-bold mb-0">Plan Specifications Summary</h5>
                    <span class="text-muted small">Plan Code: <strong>{{ $plan->plan_code }}</strong></span>
                </div>
                <a href="{{ route('emi-plans.index') }}" class="btn btn-secondary btn-sm">
                    <i class="mdi mdi-arrow-left mr-1"></i> Back
                </a>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block uppercase">Plan Name</label>
                    <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">{{ $plan->plan_name }}</span>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block uppercase">Plan Code</label>
                    <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">{{ $plan->plan_code }}</span>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block uppercase">Plan Duration</label>
                    <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">{{ $plan->duration_months }} Months</span>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block uppercase">Status / Priority</label>
                    <div>
                        <span class="badge {{ $plan->status === 'active' ? 'badge-success' : 'badge-secondary' }} mr-2">{{ ucfirst($plan->status) }}</span>
                        @if($plan->is_default)
                            <span class="badge badge-primary">Default Template</span>
                        @endif
                    </div>
                </div>
            </div>

            <h6 class="text-primary font-weight-bold mb-3 mt-4 border-bottom pb-1">Limits & Validation Weights</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">Booking Amount Limits</label>
                    <span class="font-weight-bold">₹{{ number_format($plan->minimum_booking_amount, 2) }} - ₹{{ number_format($plan->maximum_booking_amount, 2) }}</span>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">Gold Weight Limits</label>
                    <span class="font-weight-bold">{{ number_format($plan->minimum_gold_weight, 2) }}g - {{ number_format($plan->maximum_gold_weight, 2) }}g</span>
                </div>
            </div>

            <h6 class="text-primary font-weight-bold mb-3 mt-4 border-bottom pb-1">Interest & Penalties Rules</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">Interest Engine</label>
                    <span class="font-weight-bold">{{ number_format($plan->interest_rate, 2) }}% ({{ ucfirst($plan->interest_type) }})</span>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">Processing Fee</label>
                    <span class="font-weight-bold">
                        {{ number_format($plan->processing_fee, 2) }}{{ $plan->processing_fee_type === 'percent' ? '%' : ' ₹' }}
                    </span>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">Late Penalty Fee</label>
                    <span class="font-weight-bold">
                        {{ number_format($plan->late_fee, 2) }}{{ $plan->late_fee_type === 'percent' ? '%' : ' ₹' }}
                    </span>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">Grace Days Allowed</label>
                    <span class="font-weight-bold">{{ $plan->grace_days }} Days</span>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">Termination Threshold</label>
                    <span class="font-weight-bold">{{ $plan->auto_terminate_after_missed_emi }} Missed Payments</span>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">Maintenance Deduction Fee</label>
                    <span class="font-weight-bold text-danger">{{ number_format($plan->maintenance_deduction_percent, 2) }}%</span>
                </div>
            </div>

            @if($plan->description)
                <h6 class="text-primary font-weight-bold mb-2 mt-4">Description / Notes</h6>
                <p class="text-dark">{{ $plan->description }}</p>
            @endif
        </div>

        <!-- Financial Charges Configuration Card -->
        <div class="card bg-white border shadow-sm p-4 mt-4 text-dark">
            <div class="border-bottom pb-2 mb-4">
                <h5 class="text-primary font-weight-bold mb-0">Financial Charges Configuration</h5>
                <span class="text-muted small">Tax and fee rules applied dynamically to transactions</span>
            </div>
            
            <div class="row">
                <!-- GST ON GOLD -->
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">GST ON GOLD</label>
                    @if($plan->gst_on_gold_enabled)
                        <span class="badge badge-success mb-1">Enabled</span>
                        <span class="font-weight-bold text-dark d-block" style="font-size: 1.1rem;">{{ number_format($plan->gst_on_gold_percent, 2) }}%</span>
                    @else
                        <span class="badge badge-secondary mb-1">Disabled</span>
                        <span class="text-muted d-block">N/A</span>
                    @endif
                </div>

                <!-- GST ON CHARGES -->
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">GST ON CHARGES</label>
                    @if($plan->gst_on_charges_enabled)
                        <span class="badge badge-success mb-1">Enabled</span>
                        <span class="font-weight-bold text-dark d-block" style="font-size: 1.1rem;">{{ number_format($plan->gst_on_charges_percent, 2) }}%</span>
                    @else
                        <span class="badge badge-secondary mb-1">Disabled</span>
                        <span class="text-muted d-block">N/A</span>
                    @endif
                </div>

                <!-- FINANCE CHARGE -->
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">FINANCE CHARGE</label>
                    @if($plan->finance_charge_enabled)
                        <span class="badge badge-success mb-1">Enabled</span>
                        <span class="font-weight-bold text-dark d-block" style="font-size: 1.1rem;">
                            {{ number_format($plan->finance_charge_value, 2) }}{{ strtolower($plan->finance_charge_type) === 'fixed' ? ' ₹' : '%' }}
                            <small class="text-muted">({{ ucfirst($plan->finance_charge_type) }})</small>
                        </span>
                    @else
                        <span class="badge badge-secondary mb-1">Disabled</span>
                        <span class="text-muted d-block">N/A</span>
                    @endif
                </div>

                <!-- STORAGE CHARGE -->
                <div class="col-md-6 mb-3">
                    <label class="small text-muted d-block mb-1">STORAGE / INSURANCE / PRICE LOCK</label>
                    @if($plan->storage_charge_enabled)
                        <span class="badge badge-success mb-1">Enabled</span>
                        <span class="font-weight-bold text-dark d-block" style="font-size: 1.1rem;">
                            {{ number_format($plan->storage_charge_value, 2) }}{{ strtolower($plan->storage_charge_type) === 'fixed' ? ' ₹' : '%' }}
                            <small class="text-muted">({{ ucfirst($plan->storage_charge_type) }})</small>
                        </span>
                    @else
                        <span class="badge badge-secondary mb-1">Disabled</span>
                        <span class="text-muted d-block">N/A</span>
                    @endif
                </div>

                <!-- ROUNDING RULE -->
                <div class="col-md-12">
                    <label class="small text-muted d-block mb-1">ROUNDING RULE</label>
                    <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">
                        {{ ucwords(str_replace('_', ' ', $plan->rounding_type ?? 'None')) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Simulator calculations card -->
    <div class="col-lg-5 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-primary font-weight-bold mb-4 border-bottom pb-2">EMI Simulator Panel</h5>
            <p class="text-muted small mb-4">Test validation ranges and check simulated payment schedule estimates</p>

            <form id="simulateForm">
                @csrf
                <div class="form-group mb-3">
                    <label for="simulateAmount" class="text-dark">Test Booking Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="simulateAmount" required min="{{ (float)$plan->minimum_booking_amount }}" max="{{ (float)$plan->maximum_booking_amount }}" class="form-control bg-white text-dark" placeholder="e.g. 50000">
                    <small class="text-muted">Min: ₹{{ number_format($plan->minimum_booking_amount) }} | Max: ₹{{ number_format($plan->maximum_booking_amount) }}</small>
                </div>
                
                <button type="submit" id="simulateBtn" class="btn btn-primary btn-block w-100 mb-4">
                    <i class="mdi mdi-calculator mr-1"></i> Run Estimation Model
                </button>
            </form>

            <!-- Results container -->
            <div id="resultsCard" style="display: none;" class="p-3 bg-light rounded border">
                <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3">Simulation Estimations Output</h6>
                
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Monthly Installment (EMI)</span>
                    <span class="font-weight-bold text-dark" id="resInstallment">₹0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-2" id="resGstGoldRow" style="display: none !important;">
                    <span class="text-muted small">GST on Gold</span>
                    <span class="font-weight-bold text-dark" id="resGstGold">₹0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-2" id="resFinanceRow" style="display: none !important;">
                    <span class="text-muted small">Finance Charge</span>
                    <span class="font-weight-bold text-dark" id="resFinance">₹0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-2" id="resStorageRow" style="display: none !important;">
                    <span class="text-muted small">Storage / Insurance / Price Lock</span>
                    <span class="font-weight-bold text-dark" id="resStorage">₹0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-2" id="resGstChargesRow" style="display: none !important;">
                    <span class="text-muted small">GST on Charges</span>
                    <span class="font-weight-bold text-dark" id="resGstCharges">₹0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total Interest portion</span>
                    <span class="font-weight-bold text-dark" id="resInterest">₹0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Processing Fee</span>
                    <span class="font-weight-bold text-dark" id="resProcessing">₹0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                    <span class="text-muted small">Late penalty fee (per missed EMI)</span>
                    <span class="font-weight-bold text-dark" id="resLate">₹0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-2 pt-2">
                    <span class="text-dark font-weight-bold">Total Payable amount</span>
                    <span class="font-weight-bold text-success" id="resTotal" style="font-size: 1.1rem;">₹0.00</span>
                </div>

                <div class="d-flex justify-content-between mb-0">
                    <span class="text-muted small">Estimated Completion Date</span>
                    <span class="font-weight-bold text-dark" id="resCompletion">N/A</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('#simulateForm').on('submit', function (e) {
            e.preventDefault();
            let amount = $('#simulateAmount').val();
            let simBtn = $('#simulateBtn');

            simBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Calculating...');

            $.ajax({
                url: "{{ url('admin/emi-plans') }}/{{ $plan->id }}/view",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    amount: amount
                },
                success: function (response) {
                    $('#resInstallment').text(`₹${parseFloat(response.installment).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#resInterest').text(`₹${parseFloat(response.interest).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#resProcessing').text(`₹${parseFloat(response.processing_fee).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#resLate').text(`₹${parseFloat(response.late_fee).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#resTotal').text(`₹${parseFloat(response.total_payable).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#resCompletion').text(response.completion_date);

                    if (response.use_financial_engine) {
                        if (response.gst_on_gold_enabled) {
                            $('#resGstGoldRow').attr('style', 'display: flex !important;');
                            $('#resGstGold').text(`₹${parseFloat(response.gst_on_gold).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                        } else {
                            $('#resGstGoldRow').attr('style', 'display: none !important;');
                        }

                        if (response.finance_charge_enabled) {
                            $('#resFinanceRow').attr('style', 'display: flex !important;');
                            $('#resFinance').text(`₹${parseFloat(response.finance_charge).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                        } else {
                            $('#resFinanceRow').attr('style', 'display: none !important;');
                        }

                        if (response.storage_charge_enabled) {
                            $('#resStorageRow').attr('style', 'display: flex !important;');
                            $('#resStorage').text(`₹${parseFloat(response.storage_charge).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                        } else {
                            $('#resStorageRow').attr('style', 'display: none !important;');
                        }

                        if (response.gst_on_charges_enabled) {
                            $('#resGstChargesRow').attr('style', 'display: flex !important;');
                            $('#resGstCharges').text(`₹${parseFloat(response.gst_on_charges).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                        } else {
                            $('#resGstChargesRow').attr('style', 'display: none !important;');
                        }
                    } else {
                        $('#resGstGoldRow, #resFinanceRow, #resStorageRow, #resGstChargesRow').attr('style', 'display: none !important;');
                    }

                    $('#resultsCard').slideDown();
                    simBtn.prop('disabled', false).html('<i class="mdi mdi-calculator mr-1"></i> Run Estimation Model');
                },
                error: function (xhr) {
                    simBtn.prop('disabled', false).html('<i class="mdi mdi-calculator mr-1"></i> Run Estimation Model');
                    Swal.fire({
                        icon: 'error',
                        title: 'Calculation Error',
                        text: xhr.responseJSON.message || 'Simulating EMI calculations failed.',
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });
    });
</script>

@endpush
