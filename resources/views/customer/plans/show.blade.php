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
                        <div class="col-md-5">
                            @php 
                                $thumb = $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/images/dashboard/img_1.jpg');
                            @endphp
                            <img src="{{ $thumb }}" class="card-img h-100" alt="{{ $product->name }}" style="object-fit: cover; border-top-left-radius: 12px; border-bottom-left-radius: 12px; min-height: 220px;">
                        </div>
                        <div class="col-md-7">
                            <div class="card-body p-4">
                                <span class="badge badge-warning text-dark font-weight-bold mb-2">{{ $product->gold_type }}</span>
                                <h4 class="font-weight-bold mb-2 text-dark">{{ $product->name }}</h4>
                                <p class="text-muted mb-3">{{ $product->description ?? 'Premium ZAA Gold certified bullion product.' }}</p>
                                
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
                                    @if($badge)
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
                                            <span class="text-muted small d-block" style="font-size: 0.7rem;">Monthly EMI</span>
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
                                        <th>Monthly EMI</th>
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

                                <div class="row border-bottom pb-2 mb-3">
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Gold Value</span>
                                        <span class="font-weight-medium text-dark" id="calc-gold-value">₹0.00</span>
                                    </div>
                                    <div class="col-6 text-right">
                                        <span class="text-muted small d-block">GST on Gold</span>
                                        <span class="font-weight-medium text-dark" id="calc-gst-gold">₹0.00</span>
                                    </div>
                                </div>

                                <div class="row border-bottom pb-2 mb-3">
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Charges (Finance & Storage)</span>
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
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="font-weight-bold text-dark">Grand Total</span>
                                        <span class="font-weight-bold text-primary h5 mb-0" id="calc-grand-total">₹0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="font-weight-bold text-success">Monthly EMI</span>
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
                                    
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold text-muted small">Special Remarks (Optional)</label>
                                        <textarea name="remarks" class="form-control" rows="2" placeholder="Any special request..."></textarea>
                                    </div>

                                    <div class="custom-control custom-checkbox mb-4">
                                        <input type="checkbox" name="terms" class="custom-control-input" id="terms-checkbox" required>
                                        <label class="custom-control-label small text-muted" for="terms-checkbox" style="line-height: 1.4; cursor: pointer;">
                                            I agree to lock my gold price at today's rate and confirm booking. I authorize ZAA Gold to automatically charge my payment source for the first EMI installment of <strong id="calc-first-emi-term" class="text-success">₹0.00</strong> to activate the plan.
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-success btn-lg btn-block font-weight-bold shadow-sm" style="border-radius: 8px;">
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
                    <span class="text-muted small d-block">Monthly EMI starting</span>
                    <span class="font-weight-bold text-success h5 mb-0" id="mobile-buy-emi">₹0.00</span>
                </div>
                <button type="button" class="btn btn-success px-4 py-2 font-weight-bold" onclick="scrollToCalculator()">
                    Book Now
                </button>
            </div>
        </div>
    @endif

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

            function fetchCalculations(planId) {
                const spinner = document.getElementById('calculator-spinner');
                const output = document.getElementById('calculator-output');

                spinner.classList.remove('d-none');
                output.classList.add('d-none');

                const url = '{{ route('customer.plans.calculate', ['productId' => $product->id, 'planId' => ':planId']) }}'.replace(':planId', planId);

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

            function scrollToCalculator() {
                const element = document.querySelector('.sticky-calculator');
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        </script>
    @endpush
</x-customer-layout>
