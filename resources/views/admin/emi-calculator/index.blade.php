@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Configuration Selector Step -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h4 class="card-title text-dark">EMI Calculator Tool</h4>
            <p class="card-description text-muted">A public simulation tool for evaluating dynamic product valuations and repayment structures. No database writes or customer bindings will be committed.</p>

            <form id="calculatorSelectForm" class="row">
                <div class="col-md-12 form-group">
                    <label for="productId" class="text-dark font-weight-bold">Select Product <span class="text-danger">*</span></label>
                    <select id="productId" required class="form-control bg-white text-dark select2-selector">
                        <option value="">Choose Bullion Product...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }} ({{ $product->sku }}) - {{ number_format($product->weight_in_grams, 2) }}g [{{ $product->gold_type }}]
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Product Details & Eligible EMI Plans Container (Initially Hidden) -->
<div class="row text-dark" id="step2Row" style="display: none;">
    <!-- Product Detail Card -->
    <div class="col-md-5 mb-4">
        <div class="card bg-white border shadow-sm h-100 p-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Selected Product Details</h5>
            <div class="text-center mb-4 p-3 bg-light rounded">
                <img id="prodImage" src="" alt="Product Thumbnail" class="img-fluid rounded border shadow-sm" style="max-height: 180px; display: none;">
                <div id="prodImagePlaceholder" class="d-flex justify-content-center align-items-center bg-secondary text-white rounded border" style="height: 180px;">
                    <i class="mdi mdi-image" style="font-size: 3rem;"></i>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="small text-muted d-block uppercase mb-1">Product Name</label>
                <span class="font-weight-bold text-dark" id="prodName" style="font-size: 1.1rem;">-</span>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">SKU</label>
                    <span class="font-weight-bold text-dark" id="prodSku">-</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Gold Type</label>
                    <span class="badge badge-warning text-dark font-weight-bold" id="prodGoldType" style="font-size: 0.9rem;">-</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Weight</label>
                    <span class="font-weight-bold text-dark" id="prodWeight">-</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Purity</label>
                    <span class="font-weight-bold text-dark" id="prodPurity">-</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Active Gold Rate</label>
                    <span class="font-weight-bold text-dark" id="prodGoldRate">-</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1 text-success font-weight-bold">Dynamic Product Price</label>
                    <span class="font-weight-bold text-success" id="prodPrice" style="font-size: 1.05rem;">-</span>
                </div>
            </div>
            <div class="mb-0">
                <label class="small text-muted d-block mb-1">Description</label>
                <p class="text-dark small mb-0" id="prodDesc" style="line-height: 1.4;">-</p>
            </div>
        </div>
    </div>

    <!-- Available EMI Plans List -->
    <div class="col-md-7 mb-4">
        <div class="card bg-white border shadow-sm h-100 p-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Available Eligible EMI Plans</h5>
            <p class="text-muted small mb-3">Select one of the compliant plans configured in the database templates below:</p>

            <div id="emiPlanList" class="row" style="max-height: 480px; overflow-y: auto; padding-right: 5px;">
                <!-- Cards injected by JS -->
            </div>
            <div id="noPlansMsg" style="display: none;" class="alert alert-warning mt-2">
                <i class="mdi mdi-alert mr-1"></i> No eligible EMI plans match this product's dynamic valuation and weight limits.
            </div>
        </div>
    </div>
</div>

<!-- Reusable Financial Summary Component -->
@include('admin.partials.financial-summary', [
    'showCustomer' => false,
    'showBooking' => false,
    'outstandingUrl' => route('emi-calculator.outstanding'),
    'pdfUrl' => route('emi-calculator.outstanding.pdf')
])
@endsection

@push('scripts')
<script>
    let selectedProductId = null;
    let selectedEmiPlanId = null;
    let productDetails = {};

    $(document).ready(function () {
        // Trigger product selection
        $('#productId').on('change', function () {
            selectedProductId = $('#productId').val();
            
            // Reset summaries
            $('#summaryRow').slideUp();
            selectedEmiPlanId = null;

            if (selectedProductId) {
                loadProductAndPlans();
            } else {
                $('#step2Row').slideUp();
            }
        });
    });

    function loadProductAndPlans() {
        $.ajax({
            url: "{{ route('emi-calculator.calculate') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                product_id: selectedProductId
            },
            success: function (response) {
                productDetails = response;
                
                // Set product specs
                $('#prodName').text(response.product_name);
                $('#prodSku').text(response.sku);
                $('#prodGoldType').text(response.gold_type);
                $('#prodWeight').text(`${parseFloat(response.weight_in_grams).toFixed(2)}g`);
                $('#prodPurity').text(`${parseFloat(response.purity).toFixed(2)}% Purity`);
                $('#prodGoldRate').text(`₹${parseFloat(response.gold_price_per_gram).toLocaleString()}/g`);
                $('#prodPrice').text(`₹${parseFloat(response.product_price).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                $('#prodDesc').text(response.description || 'No description available.');

                if (response.thumbnail) {
                    $('#prodImage').attr('src', response.thumbnail).show();
                    $('#prodImagePlaceholder').hide();
                } else {
                    $('#prodImage').hide();
                    $('#prodImagePlaceholder').show();
                }

                // Render eligible EMI plan cards
                let plansHtml = '';
                if (response.eligible_plans && response.eligible_plans.length > 0) {
                    $('#noPlansMsg').hide();
                    response.eligible_plans.forEach(plan => {
                        let defaultBadge = plan.is_default ? '<span class="badge badge-primary float-right">Default</span>' : '';
                        
                        let chargesInfo = '';
                        if (plan.use_financial_engine) {
                            if (plan.gst_on_gold_enabled) {
                                chargesInfo += `<div class="small text-muted mb-1">GST on Gold: <strong class="text-dark">${parseFloat(plan.gst_on_gold_percent)}%</strong></div>`;
                            }
                            if (plan.finance_charge_enabled) {
                                chargesInfo += `<div class="small text-muted mb-1">Finance Charge: <strong class="text-dark">${parseFloat(plan.finance_charge_value)}${plan.finance_charge_type === 'fixed' ? ' ₹' : '%'}</strong></div>`;
                            }
                            if (plan.storage_charge_enabled) {
                                chargesInfo += `<div class="small text-muted mb-1">Storage Charge: <strong class="text-dark">${parseFloat(plan.storage_charge_value)}${plan.storage_charge_type === 'fixed' ? ' ₹' : '%'}</strong></div>`;
                            }
                            if (plan.gst_on_charges_enabled) {
                                chargesInfo += `<div class="small text-muted mb-1">GST on Charges: <strong class="text-dark">${parseFloat(plan.gst_on_charges_percent)}%</strong></div>`;
                            }
                        } else {
                            chargesInfo += `
                                <div class="small text-muted mb-1">Interest: <strong class="text-dark">${parseFloat(plan.interest_rate)}% (${plan.interest_type.toUpperCase()})</strong></div>
                                <div class="small text-muted mb-1">Processing: <strong class="text-dark">₹${parseFloat(plan.processing_fee).toLocaleString()}</strong></div>
                            `;
                        }

                        plansHtml += `
                            <div class="col-md-6 mb-3">
                                <div class="card border p-3 rounded h-100 emi-plan-card text-dark" style="cursor: pointer; transition: 0.2s;" onclick="selectEmiPlan(${plan.id})" id="plan-card-${plan.id}">
                                    <div class="d-block mb-2">
                                        <span class="font-weight-bold text-dark">${plan.plan_name}</span>
                                        ${defaultBadge}
                                    </div>
                                    <div class="small text-muted mb-2">Duration: <strong class="text-dark">${plan.duration_months} Months</strong></div>
                                    ${chargesInfo}
                                    <div class="border-top pt-2 mt-2">
                                        <div class="small text-muted">Monthly EMI</div>
                                        <div class="h5 font-weight-bold text-primary mb-1">₹${parseFloat(plan.installment).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                                        <div class="small text-muted">Total: <strong>₹${parseFloat(plan.total_payable).toLocaleString(undefined, {minimumFractionDigits: 2})}</strong></div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    $('#noPlansMsg').show();
                }

                $('#emiPlanList').html(plansHtml);
                $('#step2Row').slideDown();
            },
            error: function (xhr) {
                $('#step2Row').slideUp();
                Swal.fire({
                    icon: 'error',
                    title: 'Evaluation Error',
                    text: xhr.responseJSON.error || 'Failed to retrieve dynamic pricing rules.',
                    confirmButtonColor: '#ff3ca6'
                });
            }
        });
    }

    function selectEmiPlan(planId) {
        selectedEmiPlanId = planId;
        
        // Highlight chosen card
        $('.emi-plan-card').removeClass('bg-light border-primary').css('border-color', '#dee2e6');
        $(`#plan-card-${planId}`).addClass('bg-light border-primary').css('border-color', '#3f50f6');

        // Fetch detailed calculations for summary
        $.ajax({
            url: "{{ route('emi-calculator.calculate') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                product_id: selectedProductId,
                emi_plan_id: planId
            },
            success: function (response) {
                // Populate summary
                $('#sumProductSpecs').text(`${response.product_name} (${parseFloat(response.weight_in_grams).toFixed(2)}g, ${response.gold_type})`);
                $('#sumPlanName').text(`${response.plan_name} (${response.duration_months} Months)`);
                
                $('#sumGoldRate').text(`₹${parseFloat(response.gold_price_per_gram).toLocaleString()}/g`);
                $('#sumProductPrice').text(`₹${parseFloat(response.product_price).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                
                if (response.use_financial_engine) {
                    $('#sumProcessingFeeBlock, #sumInterestBlock').attr('style', 'display: none !important;');
                    
                    if (response.gst_on_gold_enabled) {
                        $('#sumGstGoldBlock').attr('style', 'display: block !important;');
                        $('#sumGstGold').text(`₹${parseFloat(response.gst_on_gold).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                    } else {
                        $('#sumGstGoldBlock').attr('style', 'display: none !important;');
                    }

                    if (response.finance_charge_enabled) {
                        $('#sumFinanceBlock').attr('style', 'display: block !important;');
                        $('#sumFinance').text(`₹${parseFloat(response.finance_charge).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                    } else {
                        $('#sumFinanceBlock').attr('style', 'display: none !important;');
                    }

                    if (response.storage_charge_enabled) {
                        $('#sumStorageBlock').attr('style', 'display: block !important;');
                        $('#sumStorage').text(`₹${parseFloat(response.storage_charge).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                    } else {
                        $('#sumStorageBlock').attr('style', 'display: none !important;');
                    }

                    if (response.gst_on_charges_enabled) {
                        $('#sumGstChargesBlock').attr('style', 'display: block !important;');
                        $('#sumGstCharges').text(`₹${parseFloat(response.gst_on_charges).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                    } else {
                        $('#sumGstChargesBlock').attr('style', 'display: none !important;');
                    }
                } else {
                    $('#sumProcessingFeeBlock, #sumInterestBlock').attr('style', 'display: block !important;');
                    $('#sumProcessingFee').text(`₹${parseFloat(response.processing_fee).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                    $('#sumInterest').text(`₹${parseFloat(response.interest).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                    
                    $('#sumGstGoldBlock, #sumFinanceBlock, #sumStorageBlock, #sumGstChargesBlock').attr('style', 'display: none !important;');
                }

                $('#sumEmiAmount').text(`₹${parseFloat(response.installment).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                $('#sumTotalPayable').text(`₹${parseFloat(response.total_payable).toLocaleString(undefined, {minimumFractionDigits: 2})}`);
                $('#sumCompletionDate').text(response.completion_date);
                $('#sumLateFee').text(`₹${parseFloat(response.late_fee).toLocaleString()} / Default`);

                $('#summaryRow').slideDown();
            },
            error: function (xhr) {
                $('#summaryRow').slideUp();
                Swal.fire({
                    icon: 'error',
                    title: 'Calculation Error',
                    text: xhr.responseJSON.error || 'Failed to complete summary.',
                    confirmButtonColor: '#ff3ca6'
                });
            }
        });
    }
</script>

<style>
    .emi-plan-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        border-color: #3f50f6 !important;
    }
</style>
@endpush
