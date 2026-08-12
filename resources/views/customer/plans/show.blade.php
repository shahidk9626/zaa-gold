<x-customer-layout title="Plan Details">
    {{-- Header --}}
    <div class="page-header flex-wrap d-none d-md-flex">
        <h3 class="mb-0">Product Details: {{ $product->name }}</h3>
        <a href="{{ route('customer.plans.index') }}" class="btn btn-secondary btn-sm">
            <i class="mdi mdi-arrow-left"></i> Back to Marketplace
        </a>
    </div>
    <div class="d-block d-md-none mb-3">
        <a href="{{ route('customer.plans.index') }}" class="text-muted small">
            <i class="mdi mdi-arrow-left"></i> Back to Marketplace
        </a>
        <h5 class="font-weight-bold mt-2">{{ $product->name }}</h5>
    </div>

    @if(session('purchase_limit_error'))
        <div class="alert alert-danger border-0 p-4 mb-4" role="alert" style="border-radius: 12px; background-color: #fee2e2; border-left: 5px solid #ef4444 !important; color: #7f1d1d;">
            <h5 class="alert-heading font-weight-bold mb-2"><i class="mdi mdi-alert-circle mr-1"></i> Purchase Limit Exceeded</h5>
            <p class="mb-2">You have already purchased <strong>{{ number_format(session('purchase_limit_error.purchased'), 2) }} grams</strong> during the current financial year.</p>
            <p class="mb-3">Maximum allowed purchase is <strong>{{ number_format(session('purchase_limit_error.limit'), 2) }} grams</strong>.</p>
            <hr class="my-2" style="border-color: rgba(239, 68, 68, 0.2);">
            <p class="mb-0 small font-weight-medium">Please contact customer support for further assistance.</p>
        </div>
    @endif

    @if(empty($eligiblePlans))
        <div class="alert alert-warning">
            <h5>No Eligible EMI Plans</h5>
            <p>This product weight or price is outside the limits of all active EMI plans. Please choose another product or contact support.</p>
        </div>
    @else
        <div class="row">
            {{-- Left column: Product info and EMI plans --}}
            <div class="col-lg-8 grid-margin">
                {{-- Product Info Card --}}
                <div class="card mb-4 border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="row no-gutters">
                        <div class="col-md-5 d-flex align-items-center justify-content-center bg-white" style="border-top-left-radius: 12px; border-bottom-left-radius: 12px; overflow: hidden; min-height: 220px;">
                            <img src="{{ $product->getThumbnailUrl() }}" class="img-fluid" alt="{{ $product->name }}" style="max-height: 260px; object-fit: contain; width: 100%;">
                        </div>
                        <div class="col-md-7">
                            <div class="card-body p-4">
                                <span class="badge badge-warning text-dark font-weight-bold mb-2">{{ $product->gold_type }}</span>
                                <h4 class="font-weight-bold mb-2 text-dark">{{ $product->name }}</h4>
                                <div class="position-relative mb-2">
                                    <div id="description-wrapper" class="text-muted" style="overflow: hidden; max-height: none; transition: max-height 0.3s ease-out; line-height: 1.6;">
                                        {!! $product->description ?? 'Premium AurOnGold certified bullion product.' !!}
                                    </div>
                                    <div id="description-fade" style="position: absolute; bottom: 0; left: 0; width: 100%; height: 50px; background: linear-gradient(to bottom, rgba(255, 255, 255, 0), rgba(255, 255, 255, 1)); pointer-events: none; display: none;"></div>
                                </div>
                                <button type="button" id="read-more-btn" class="btn btn-link text-primary p-0 mb-3 font-weight-bold shadow-none" style="display: none; text-decoration: none; font-size: 0.85rem;">
                                    Read More <i class="mdi mdi-chevron-down"></i>
                                </button>
                                
                                <div class="row text-center text-md-left border-top pt-3">
                                    <div class="col-4">
                                        <span class="text-muted small d-block">Gold Weight</span>
                                        <span class="font-weight-bold text-dark">{{ number_format($product->weight_in_grams, 2) }}g</span>
                                    </div>
                                    <div class="col-4 border-left border-right">
                                        <span class="text-muted small d-block">Purity</span>
                                        <span class="font-weight-bold text-dark">{{ number_format($product->purity, 1) }}%</span>
                                    </div>
                                    <div class="col-4">
                                        <span class="text-muted small d-block">Today's Price</span>
                                        <span class="font-weight-bold text-primary">₹{{ number_format($productPrice, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- EMI Plan List --}}
                <h5 class="font-weight-bold mb-3 text-dark">Select an EMI Plan</h5>
                <div class="row">
                    @foreach($eligiblePlans as $pData)
                        @php 
                            $plan = $pData['plan'];
                            $calc = $pData['calculations'];
                            $badge = $pData['badge'];
                        @endphp
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm plan-select-card" id="plan-card-{{ $plan->id }}" onclick="selectPlan('{{ $plan->id }}')" style="border-radius: 12px; cursor: pointer; transition: transform 0.2s, border 0.2s; border: 2px solid transparent;">
                                <div class="card-body p-3 position-relative">
                                    @if($pData['best_offer'])
                                        <div class="position-absolute" style="top: -10px; right: 15px;">
                                            <span class="badge badge-danger text-white font-weight-bold px-2 py-1 shadow-sm" style="font-size: 0.65rem; border-radius: 4px;">
                                                @if($pData['best_offer']->offer_type === 'percentage')
                                                    🔥 {{ (float)$pData['best_offer']->percentage }}% OFF
                                                @elseif($pData['best_offer']->offer_type === 'fixed')
                                                    🎁 ₹{{ number_format($pData['best_offer']->fixed_amount, 0) }} OFF
                                                @else
                                                    ⭐ Waive {{ $pData['best_offer']->free_emi_count }} EMI
                                                @endif
                                            </span>
                                        </div>
                                    @elseif($badge)
                                        <div class="position-absolute" style="top: -10px; right: 15px;">
                                            <span class="badge badge-success text-white font-weight-bold px-2 py-1 shadow-sm" style="font-size: 0.65rem; border-radius: 4px;">{{ $badge }}</span>
                                        </div>
                                    @endif

                                    <div class="d-flex align-items-center mb-2">
                                        <div class="custom-control custom-radio mr-2">
                                            <input type="radio" id="plan-radio-{{ $plan->id }}" name="plan-selector" class="custom-control-input" value="{{ $plan->id }}" {{ $plan->id == $cheapestPlanId ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="plan-radio-{{ $plan->id }}"></label>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold text-dark mb-0">{{ $plan->plan_name }}</h6>
                                            <span class="text-muted small">{{ $plan->duration_months }} Months Duration</span>
                                        </div>
                                    </div>

                                    <div class="row border-top pt-2 mt-2">
                                        <div class="col-6">
                                            <span class="text-muted small d-block" style="font-size: 0.7rem;">EMAP (Easy Monthly Advance Payment)</span>
                                            <span class="font-weight-bold text-success" style="font-size: 1.1rem;">₹{{ number_format($calc['installment'], 2) }}</span>
                                        </div>
                                        <div class="col-6 text-right">
                                            <span class="text-muted small d-block" style="font-size: 0.7rem;">Interest Rate</span>
                                            <span class="font-weight-bold text-dark">{{ number_format($plan->interest_rate, 2) }}% <small class="text-muted">({{ $plan->interest_type }})</small></span>
                                        </div>
                                    </div>

                                    <div class="collapse plan-details-collapse mt-2" id="details-collapse-{{ $plan->id }}">
                                        <div class="border-top pt-2 mt-2 text-muted small" style="font-size: 0.75rem; line-height: 1.4;">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Processing Fee:</span>
                                                <span class="text-dark">₹{{ number_format($calc['processing_fee'], 2) }} {{ $plan->processing_fee_type === 'percent' ? "({$plan->processing_fee}%)" : '' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Finance Charge:</span>
                                                <span class="text-dark">₹{{ number_format($calc['finance_charge'], 2) }} {{ $plan->finance_charge_enabled && strtolower($plan->finance_charge_type) === 'percentage' ? "({$plan->finance_charge_value}%)" : '' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Storage Charge:</span>
                                                <span class="text-dark">₹{{ number_format($calc['storage_charge'], 2) }} {{ $plan->storage_charge_enabled && strtolower($plan->storage_charge_type) === 'percentage' ? "({$plan->storage_charge_value}%)" : '' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>GST (Gold + Charges):</span>
                                                <span class="text-dark">₹{{ number_format($calc['gst_on_gold'] + $calc['gst_on_charges'], 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Compare Plans Card --}}
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-body p-4">
                        <h5 class="font-weight-bold mb-3 text-dark">Compare EMI Plans</h5>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0 text-center">
                                <thead class="bg-light text-muted small font-weight-bold">
                                    <tr>
                                        <th>EMI Plan</th>
                                        <th>Duration</th>
                                        <th>EMAP (Easy Monthly Advance Payment)</th>
                                        <th>Finance Charges</th>
                                        <th>Storage Charges</th>
                                        <th>Grand Total</th>
                                        <th>Highlight</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($eligiblePlans as $pData)
                                        @php 
                                            $plan = $pData['plan'];
                                            $calc = $pData['calculations'];
                                            $badge = $pData['badge'];
                                        @endphp
                                        <tr class="{{ $plan->id == $cheapestPlanId ? 'table-success-light' : '' }}">
                                            <td class="font-weight-bold text-dark">{{ $plan->plan_name }}</td>
                                            <td>{{ $plan->duration_months }} Months</td>
                                            <td class="text-success font-weight-bold">₹{{ number_format($calc['installment'], 2) }}</td>
                                            <td>₹{{ number_format($calc['finance_charge'], 2) }}</td>
                                            <td>₹{{ number_format($calc['storage_charge'], 2) }}</td>
                                            <td class="font-weight-bold text-primary">₹{{ number_format($calc['total_payable'], 2) }}</td>
                                            <td>
                                                @if($plan->id == $cheapestPlanId)
                                                    <span class="badge badge-success text-white px-2 py-1">Best Value</span>
                                                @elseif($plan->is_default)
                                                    <span class="badge badge-primary text-white px-2 py-1">Popular</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right column: Calculator & Purchase Flow --}}
            <div class="col-lg-4 grid-margin">
                <div class="sticky-calculator">
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-primary text-white p-3">
                            <h5 class="mb-0 font-weight-bold"><i class="mdi mdi-calculator mr-1"></i> Cost Summary</h5>
                        </div>
                        <div class="card-body p-4" id="calculator-card-body">
                            {{-- Calculator AJAX output goes here --}}
                            <div class="text-center py-4" id="calculator-spinner">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading calculations...</span>
                                </div>
                            </div>
                            
                            <div class="calculator-content d-none" id="calculator-output">
                                <div class="mb-3 border-bottom pb-2">
                                    <span class="text-muted small d-block">Selected Product</span>
                                    <span class="font-weight-bold text-dark" id="calc-product-name">{{ $product->name }}</span>
                                </div>
                                <div class="mb-3 border-bottom pb-2">
                                    <span class="text-muted small d-block">Selected EMI Plan</span>
                                    <span class="font-weight-bold text-dark" id="calc-plan-name">Loading...</span>
                                </div>
                                <div class="mb-3 border-bottom pb-2 d-none" id="calc-offer-container">
                                    <span class="text-muted small d-block mb-1 font-weight-bold">Select Available Offer</span>
                                    <select class="form-control form-control-sm text-dark bg-white" id="calc-offer-select" onchange="changeOffer(this.value)">
                                        <!-- Will be dynamically populated -->
                                    </select>
                                </div>

                                <div class="row border-bottom pb-2 mb-3">
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Gold Value</span>
                                        <span class="font-weight-medium text-dark" id="calc-gold-value">₹0.00</span>
                                    </div>
                                    <div class="col-6 text-right">
                                        <span class="text-muted small d-block">GST on Gold - 3%</span>
                                        <span class="font-weight-medium text-dark" id="calc-gst-gold">₹0.00</span>
                                    </div>
                                </div>

                                <div class="row border-bottom pb-2 mb-3">
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Price Locking with secure storage charges - 12%</span>
                                        <span class="font-weight-medium text-dark" id="calc-charges">₹0.00</span>
                                    </div>
                                    <div class="col-6 text-right">
                                        <span class="text-muted small d-block">GST on Charges</span>
                                        <span class="font-weight-medium text-dark" id="calc-gst-charges">₹0.00</span>
                                    </div>
                                </div>

                                <div class="row border-bottom pb-2 mb-3">
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Processing Fee</span>
                                        <span class="font-weight-medium text-dark" id="calc-processing-fee">₹0.00</span>
                                    </div>
                                    <div class="col-6 text-right">
                                        <span class="text-muted small d-block">Completion Date</span>
                                        <span class="font-weight-medium text-dark" id="calc-completion-date">N/A</span>
                                    </div>
                                </div>

                                <div class="bg-light rounded p-3 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2 d-none" id="calc-original-row">
                                        <span class="text-muted small">Original Plan Value</span>
                                        <span class="text-muted font-weight-bold" style="text-decoration: line-through;" id="calc-original-total">₹0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2 d-none" id="calc-savings-row">
                                        <span class="text-danger font-weight-bold small" id="calc-savings-label">Promo Discount</span>
                                        <span class="text-danger font-weight-bold" id="calc-savings-amount">- ₹0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="font-weight-bold text-dark">Grand Total</span>
                                        <span class="font-weight-bold text-primary h5 mb-0" id="calc-grand-total">₹0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="font-weight-bold text-success">EMAP (Easy Monthly Advance Payment)</span>
                                            <small class="text-muted d-block" style="font-size: 0.65rem; line-height: 1;">Payable now to confirm</small>
                                        </div>
                                        <span class="font-weight-bold text-success h4 mb-0" id="calc-monthly-emi">₹0.00</span>
                                    </div>
                                </div>

                                {{-- Purchase Checkout Form --}}
                                <form action="{{ route('customer.plans.book') }}" method="POST" id="checkout-form">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="emi_plan_id" id="form-emi-plan-id" value="">
                                    <input type="hidden" name="offer_id" id="form-offer-id" value="">
                                    
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold text-muted small">Special Remarks (Optional)</label>
                                        <textarea name="remarks" class="form-control" rows="2" placeholder="Any special request..."></textarea>
                                    </div>

                                    <input type="checkbox" name="terms" id="terms-checkbox" class="d-none">
                                    <div class="mb-4 small text-muted" style="line-height: 1.4;">
                                        By clicking "Proceed To Booking", you agree to lock your gold price at today's rate. You will be prompted to review and accept the booking terms on the next step.
                                    </div>

                                    <button type="button" class="btn btn-success btn-lg btn-block font-weight-bold shadow-sm" style="border-radius: 8px;" onclick="openTermsModal()">
                                        <i class="mdi mdi-shield-check mr-1"></i> Proceed To Booking
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Sticky Mobile Checkout bar --}}
    @if(!empty($eligiblePlans))
        <div class="d-block d-md-none sticky-mobile-buy-bar bg-white border-top shadow-lg p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small d-block">EMAP (Easy Monthly Advance Payment) starting</span>
                    <span class="font-weight-bold text-success h5 mb-0" id="mobile-buy-emi">₹0.00</span>
                </div>
                <button type="button" class="btn btn-success px-4 py-2 font-weight-bold" onclick="scrollToCalculator()">
                    Book Now
                </button>
            </div>
        </div>
    @endif

    <!-- Terms and Conditions Modal -->
    <div class="modal fade text-dark" id="termsModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content bg-white border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-primary text-white border-bottom-0 p-3">
                    <h5 class="modal-title font-weight-bold" id="termsModalLabel">
                        <i class="mdi mdi-shield-text mr-1"></i> AurOnGold – Gold Booking Terms
                    </h5>
                    <button type="button" class="close text-white shadow-none" data-dismiss="modal" aria-label="Close" style="outline: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4" style="max-height: 60vh; overflow-y: auto; font-size: 0.9rem; line-height: 1.6; color: #334155;">
                    <h6 class="font-weight-bold text-primary mb-3">IMPORTANT TERMS & CONDITIONS – GOLD BOOKING</h6>
                    <p class="text-muted small mb-3">Please read the following important terms before proceeding with your booking.</p>
                    
                    <ol class="pl-3 mb-4 text-secondary" style="list-style-type: decimal;">
                        <li class="mb-2"><strong>Price Lock:</strong> The gold price will be locked on the Booking Date after receipt of the first EMAP payment.</li>
                        <li class="mb-2"><strong>EMAP Plan:</strong> AurOnGold offers 12-month and 24-month Easy Monthly Advance Payment (EMAP) plans.</li>
                        <li class="mb-2"><strong>Monthly Payments:</strong> Up to two missed monthly payments may be cleared before plan completion. More than two missed payments may result in cancellation of the plan.</li>
                        <li class="mb-2"><strong>Applicable Charges & GST:</strong> Applicable Price Locking & Secure Storage Charges, Processing/Platform/Service/Delivery Charges, and GST will apply as displayed during the booking process.</li>
                        <li class="mb-2"><strong>Gold Delivery:</strong> Delivery within India will be made only after full payment, KYC completion and verification. Estimated delivery time is 7–10 business days.</li>
                        <li class="mb-2"><strong>KYC Requirement:</strong> PAN and Aadhaar KYC are mandatory for completing the purchase.</li>
                        <li class="mb-2"><strong>Cancellation & Refund:</strong> Cancellation and refunds are subject to the applicable Cancellation and Refund Policy, including any applicable deductions.</li>
                        <li class="mb-2"><strong>Buyback:</strong> Buyback, where offered, is subject to verification and the applicable prevailing gold price. Buyback deductions may apply, and no exchange facility is provided.</li>
                        <li class="mb-2"><strong>Customer Confirmation:</strong> By proceeding with the booking, you confirm that you have read, understood and agreed to these important terms and the applicable policies.</li>
                    </ol>
                    
                    <p class="text-muted small border-top pt-3 mb-0">
                        For complete Terms & Conditions, please visit the <a href="/terms-and-conditions" target="_blank" class="font-weight-medium text-primary">Terms & Conditions</a> page available in the footer of our website.
                    </p>
                </div>
                <div class="modal-footer bg-light p-3 flex-column align-items-stretch">
                    <div class="custom-control custom-checkbox mb-3 pl-4">
                        <input type="checkbox" class="custom-control-input" id="modal-terms-checkbox" onchange="toggleModalSubmitButton(this.checked)">
                        <label class="custom-control-label font-weight-medium text-dark" for="modal-terms-checkbox" style="cursor: pointer; font-size: 0.9rem; line-height: 1.4;">
                            I have read and agree to the above Terms & Conditions and applicable policies.
                        </label>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="button" class="btn btn-success font-weight-bold" id="modal-submit-btn" disabled onclick="submitCheckoutForm()" style="border-radius: 8px;">
                            Confirm & Proceed to Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .plan-select-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 10px rgba(0,0,0,0.08) !important;
            }
            .plan-select-card.selected {
                border-color: #3f50f6 !important;
                background-color: #f7f8ff !important;
            }
            .table-success-light {
                background-color: #f1f9f5 !important;
            }
            .sticky-calculator {
                position: sticky;
                top: 80px;
                z-index: 10;
            }
            .sticky-mobile-buy-bar {
                position: fixed;
                bottom: calc(55px + env(safe-area-inset-bottom)); /* Lift above mobile bottom-nav */
                left: 0;
                right: 0;
                z-index: 1030;
                animation: slideUp 0.3s ease-out;
            }
            @keyframes slideUp {
                from { transform: translateY(100%); }
                to { transform: translateY(0); }
            }
            @media (max-width: 767.98px) {
                .content-wrapper {
                    padding-bottom: 140px !important; /* Make room for both navigation bars */
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            // Select the initial plan
            document.addEventListener('DOMContentLoaded', function() {
                const initialPlanId = '{{ $cheapestPlanId }}';
                if (initialPlanId) {
                    selectPlan(initialPlanId);
                }
            });

            function selectPlan(planId) {
                // 1. Check the radio button
                document.getElementById('plan-radio-' + planId).checked = true;

                // 2. Add 'selected' class to card, remove from others
                document.querySelectorAll('.plan-select-card').forEach(card => {
                    card.classList.remove('selected');
                });
                document.getElementById('plan-card-' + planId).classList.add('selected');

                // 3. Toggle details accordion collapses
                document.querySelectorAll('.plan-details-collapse').forEach(collapse => {
                    collapse.classList.remove('show');
                });
                document.getElementById('details-collapse-' + planId).classList.add('show');

                // 4. Update the hidden form input
                document.getElementById('form-emi-plan-id').value = planId;

                // 5. Run the calculations AJAX
                fetchCalculations(planId);
            }

            function fetchCalculations(planId, offerId = null) {
                const spinner = document.getElementById('calculator-spinner');
                const output = document.getElementById('calculator-output');

                spinner.classList.remove('d-none');
                output.classList.add('d-none');

                const url = '{{ route('customer.plans.calculate', ['productId' => $product->id, 'planId' => ':planId']) }}'.replace(':planId', planId) + (offerId !== null ? '?offer_id=' + offerId : '');

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('Failed to fetch calculations');
                        return response.json();
                    })
                    .then(data => {
                        // Populate results
                        document.getElementById('calc-plan-name').innerText = data.plan_name + ' (' + data.duration_months + ' mo)';
                        document.getElementById('calc-gold-value').innerText = '₹' + parseFloat(data.gold_value).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        document.getElementById('calc-gst-gold').innerText = '₹' + parseFloat(data.gst_on_gold).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        const totalCharges = parseFloat(data.finance_charge) + parseFloat(data.storage_charge);
                        document.getElementById('calc-charges').innerText = '₹' + totalCharges.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        document.getElementById('calc-gst-charges').innerText = '₹' + parseFloat(data.gst_on_charges).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        document.getElementById('calc-processing-fee').innerText = '₹' + parseFloat(data.processing_fee).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        // Parse date
                        const completion = new Date(data.completion_date);
                        const options = { year: 'numeric', month: 'short', day: 'numeric' };
                        document.getElementById('calc-completion-date').innerText = completion.toLocaleDateString('en-US', options);

                        const grandTotal = parseFloat(data.total_payable);
                        const monthlyEmi = parseFloat(data.installment);

                        // Populate eligible offers dropdown
                        const offerContainer = document.getElementById('calc-offer-container');
                        const offerSelect = document.getElementById('calc-offer-select');
                        
                        if (data.eligible_offers && data.eligible_offers.length > 0) {
                            offerSelect.innerHTML = '';
                            
                            // Option 1: No Offer
                            const noOfferOpt = document.createElement('option');
                            noOfferOpt.value = 'none';
                            noOfferOpt.innerText = 'No Offer / Apply Normal Price';
                            offerSelect.appendChild(noOfferOpt);
                            
                            // Other options
                            data.eligible_offers.forEach(offer => {
                                const opt = document.createElement('option');
                                opt.value = offer.id;
                                opt.innerText = offer.offer_name + offer.savings_message;
                                offerSelect.appendChild(opt);
                            });
                            
                            // Set current selected value
                            offerSelect.value = data.applied_offer_id || 'none';
                            document.getElementById('form-offer-id').value = data.applied_offer_id || 'none';
                            
                            offerContainer.classList.remove('d-none');
                        } else {
                            offerSelect.innerHTML = '';
                            offerContainer.classList.add('d-none');
                            document.getElementById('form-offer-id').value = '';
                        }
                        
                        if (data.discount_amount && parseFloat(data.discount_amount) > 0) {
                            document.getElementById('calc-original-row').classList.remove('d-none');
                            document.getElementById('calc-savings-row').classList.remove('d-none');
                            
                            document.getElementById('calc-original-total').innerText = '₹' + parseFloat(data.original_amount || data.original_total).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            document.getElementById('calc-savings-amount').innerText = '- ₹' + parseFloat(data.discount_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            document.getElementById('calc-savings-label').innerText = data.applied_offer_name ? `${data.applied_offer_name} Savings` : 'Promo Discount';
                        } else {
                            document.getElementById('calc-original-row').classList.add('d-none');
                            document.getElementById('calc-savings-row').classList.add('d-none');
                        }

                        document.getElementById('calc-grand-total').innerText = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        document.getElementById('calc-monthly-emi').innerText = '₹' + monthlyEmi.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        document.getElementById('calc-first-emi-term').innerText = '₹' + monthlyEmi.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        // Update mobile sticky buy bar
                        if (document.getElementById('mobile-buy-emi')) {
                            document.getElementById('mobile-buy-emi').innerText = '₹' + monthlyEmi.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + '/mo';
                        }

                        spinner.classList.add('d-none');
                        output.classList.remove('d-none');
                    })
                    .catch(error => {
                        console.error('Error fetching calculations:', error);
                        spinner.classList.add('d-none');
                    });
            }

            function changeOffer(offerId) {
                const planId = document.getElementById('form-emi-plan-id').value;
                if (planId) {
                    fetchCalculations(planId, offerId);
                }
            }

            function openTermsModal() {
                // Reset checkbox state to unchecked when opening
                const checkbox = document.getElementById('modal-terms-checkbox');
                if (checkbox) {
                    checkbox.checked = false;
                }
                const submitBtn = document.getElementById('modal-submit-btn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }
                
                // Show modal
                $('#termsModal').modal('show');
            }

            function toggleModalSubmitButton(isChecked) {
                const submitBtn = document.getElementById('modal-submit-btn');
                if (submitBtn) {
                    submitBtn.disabled = !isChecked;
                }
            }

            function submitCheckoutForm() {
                const checkbox = document.getElementById('modal-terms-checkbox');
                if (checkbox && checkbox.checked) {
                    // Check the hidden checkbox in the form
                    const formTerms = document.getElementById('terms-checkbox');
                    if (formTerms) {
                        formTerms.checked = true;
                    }
                    // Submit the form
                    document.getElementById('checkout-form').submit();
                }
            }

            function scrollToCalculator() {
                const element = document.querySelector('.sticky-calculator');
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            // Read More / Read Less Toggle for HTML description
            document.addEventListener('DOMContentLoaded', function() {
                const wrapper = document.getElementById('description-wrapper');
                const fade = document.getElementById('description-fade');
                const btn = document.getElementById('read-more-btn');
                
                if (wrapper && btn) {
                    const maxHeight = 160; // Max collapsed height in pixels
                    if (wrapper.scrollHeight > maxHeight) {
                        // Collapse initially
                        wrapper.style.maxHeight = maxHeight + 'px';
                        fade.style.display = 'block';
                        btn.style.display = 'inline-block';
                        
                        btn.addEventListener('click', function() {
                            if (wrapper.style.maxHeight === maxHeight + 'px') {
                                // Expand
                                wrapper.style.maxHeight = wrapper.scrollHeight + 'px';
                                fade.style.display = 'none';
                                btn.innerHTML = 'Read Less <i class="mdi mdi-chevron-up"></i>';
                            } else {
                                // Collapse
                                wrapper.style.maxHeight = maxHeight + 'px';
                                fade.style.display = 'block';
                                btn.innerHTML = 'Read More <i class="mdi mdi-chevron-down"></i>';
                                // Scroll wrapper into view if needed
                                wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }
                        });
                    }
                }
            });
        </script>
    @endpush
</x-customer-layout>
