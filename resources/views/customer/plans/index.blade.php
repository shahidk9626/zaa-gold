<x-customer-layout title="Buy Gold Plans">
    {{-- Header --}}
    <div class="page-header flex-wrap d-none d-md-flex">
        <h3 class="mb-0">Buy Gold Plans</h3>
        <p class="text-muted mb-0">Select a physical gold product and choose a customized flexible EMI plan.</p>
    </div>
    <div class="d-block d-md-none mb-3">
        <h5 class="font-weight-bold mb-1">Buy Gold Plans</h5>
        <p class="text-muted small">Select a product and choose a flexible EMI plan.</p>
    </div>

    {{-- Live Price Banner --}}
    <div class="card bg-dark text-white mb-4 shadow-sm overflow-hidden" style="border-radius: 12px; border: none;">
        <div class="card-body p-3 p-md-4 position-relative">
            <div style="position: absolute; right: 10px; top: 10px; opacity: 0.1; font-size: 5rem;" class="mdi mdi-gold d-none d-md-block"></div>
            <div class="row align-items-center">
                <div class="col-md-3 mb-2 mb-md-0">
                    <span class="badge badge-warning text-dark font-weight-bold px-3 py-2 mb-1" style="font-size: 0.75rem;">
                        <i class="mdi mdi-clock-fast mr-1"></i> Live Gold Price
                    </span>
                    <h5 class="mb-0 font-weight-bold text-warning-custom text-uppercase">Today's Rate</h5>
                </div>
                <div class="col-6 col-md-4 mb-2 mb-md-0">
                    <p class="mb-0 text-muted small text-uppercase">Today's 22K Gold</p>
                    <h4 class="mb-0 font-weight-bold" id="live-price-22k">
                        @if($goldPrice['price'])
                            ₹{{ number_format($goldPrice['price']->price_22k, 2) }}
                        @else
                            —
                        @endif
                        <small class="h6 text-white-50">/g</small>
                    </h4>
                    <span class="small" id="trend-22k-badge">
                        @if($goldPrice['trend_22k'] === 'up')
                            <span class="text-success"><i class="mdi mdi-trending-up"></i> Up</span>
                        @elseif($goldPrice['trend_22k'] === 'down')
                            <span class="text-danger"><i class="mdi mdi-trending-down"></i> Down</span>
                        @else
                            <span class="text-secondary">Stable</span>
                        @endif
                    </span>
                </div>
                <div class="col-6 col-md-4 mb-2 mb-md-0">
                    <p class="mb-0 text-muted small text-uppercase">Today's 24K Gold</p>
                    <h4 class="mb-0 font-weight-bold" id="live-price-24k">
                        @if($goldPrice['price'])
                            ₹{{ number_format($goldPrice['price']->price_24k, 2) }}
                        @else
                            —
                        @endif
                        <small class="h6 text-white-50">/g</small>
                    </h4>
                    <span class="small" id="trend-24k-badge">
                        @if($goldPrice['trend_24k'] === 'up')
                            <span class="text-success"><i class="mdi mdi-trending-up"></i> Up</span>
                        @elseif($goldPrice['trend_24k'] === 'down')
                            <span class="text-danger"><i class="mdi mdi-trending-down"></i> Down</span>
                        @else
                            <span class="text-secondary">Stable</span>
                        @endif
                    </span>
                </div>
                <div class="col-12 mt-2">
                    <hr class="bg-secondary my-2" style="opacity: 0.3;">
                    <p class="text-muted small mb-0" id="live-price-updated">
                        <i class="mdi mdi-clock-outline"></i> Last updated: 
                        @if($goldPrice['price'])
                            {{ $goldPrice['price']->effective_date->format('d M Y, h:i A') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('customer.plans.index') }}" method="GET" class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="font-weight-medium small text-muted">Search Product</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent border-right-0"><i class="mdi mdi-magnify text-muted"></i></span>
                        </div>
                        <input type="text" name="search" class="form-control border-left-0" placeholder="Name or SKU..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6 mb-3">
                    <label class="font-weight-medium small text-muted">Purity / Karat</label>
                    <select name="purity" class="form-control">
                        <option value="">All Purities</option>
                        <option value="22K" {{ request('purity') == '22K' ? 'selected' : '' }}>22K Gold (91.6%)</option>
                        <option value="24K" {{ request('purity') == '24K' ? 'selected' : '' }}>24K Gold (99.9%)</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6 mb-3">
                    <label class="font-weight-medium small text-muted">Gold Weight</label>
                    <select name="weight_range" class="form-control">
                        <option value="">All Weights</option>
                        <option value="under_10" {{ request('weight_range') == 'under_10' ? 'selected' : '' }}>Under 10g</option>
                        <option value="10_50" {{ request('weight_range') == '10_50' ? 'selected' : '' }}>10g - 50g</option>
                        <option value="above_50" {{ request('weight_range') == 'above_50' ? 'selected' : '' }}>Above 50g</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6 mb-3">
                    <label class="font-weight-medium small text-muted">EMI Duration</label>
                    <select name="duration" class="form-control">
                        <option value="">All Durations</option>
                        <option value="6" {{ request('duration') == '6' ? 'selected' : '' }}>6 Months</option>
                        <option value="9" {{ request('duration') == '9' ? 'selected' : '' }}>9 Months</option>
                        <option value="12" {{ request('duration') == '12' ? 'selected' : '' }}>12 Months</option>
                        <option value="18" {{ request('duration') == '18' ? 'selected' : '' }}>18 Months</option>
                        <option value="24" {{ request('duration') == '24' ? 'selected' : '' }}>24 Months</option>
                    </select>
                </div>

                <div class="col-md-3 col-12 mb-3 d-flex align-items-end">
                    <div class="row w-100 no-gutters">
                        <div class="col-6 pr-1">
                            <input type="number" name="min_price" class="form-control" placeholder="Min ₹" value="{{ request('min_price') }}">
                        </div>
                        <div class="col-6 pl-1">
                            <input type="number" name="max_price" class="form-control" placeholder="Max ₹" value="{{ request('max_price') }}">
                        </div>
                    </div>
                </div>

                <div class="col-12 text-right mt-1">
                    <a href="{{ route('customer.plans.index') }}" class="btn btn-light btn-sm mr-2">Reset Filters</a>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Product List --}}
    @if($products->isEmpty())
        <div class="alert alert-info text-center p-4">
            <i class="mdi mdi-alert-circle-outline display-4 d-block mb-3"></i>
            <h5>No Active Plans Found</h5>
            <p class="text-muted">No products matched your active filters. Try resetting search parameters.</p>
            <a href="{{ route('customer.plans.index') }}" class="btn btn-sm btn-primary mt-2">Reset Filters</a>
        </div>
    @else
        {{-- Desktop Grid --}}
        <div class="d-none d-md-block">
            <div class="row">
                @foreach($products as $product)
                    @php 
                        $thumb = $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/images/dashboard/img_1.jpg');
                    @endphp
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card h-100 shadow-sm hover-shadow border-0" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s;">
                            <div class="position-relative">
                                <img src="{{ $thumb }}" class="card-img-top" alt="{{ $product->name }}" style="height: 180px; object-fit: cover; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                                <div class="position-absolute" style="top: 10px; left: 10px;">
                                    <span class="badge badge-warning text-dark font-weight-bold shadow-sm">{{ $product->gold_type }}</span>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column p-3">
                                <h6 class="font-weight-bold mb-1 text-truncate" title="{{ $product->name }}">{{ $product->name }}</h6>
                                <p class="text-muted small mb-2">{{ number_format($product->weight_in_grams, 2) }}g · {{ number_format($product->purity, 1) }}% Purity</p>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                                        <div>
                                            <span class="text-muted small d-block">Today's Price</span>
                                            <span class="font-weight-bold text-primary h6 mb-0">₹{{ number_format($product->computed_price, 2) }}</span>
                                        </div>
                                    </div>
                                    @if($product->starting_emi)
                                        <div class="bg-light rounded p-2 mb-3">
                                            <span class="text-muted small d-block" style="font-size: 0.7rem;">Monthly EMI starts from</span>
                                            <span class="font-weight-bold text-success">₹{{ number_format($product->starting_emi, 0) }}<span class="small font-weight-normal text-muted">/month</span></span>
                                        </div>
                                    @else
                                        <div class="bg-light rounded p-2 mb-3 text-center">
                                            <span class="text-muted small" style="font-size: 0.75rem;">No active EMI plans</span>
                                        </div>
                                    @endif
                                    
                                    <a href="{{ route('customer.plans.show', $product->id) }}" class="btn btn-primary btn-block btn-sm">
                                        View Plans
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Mobile Swipeable Cards --}}
        <div class="d-block d-md-none">
            <h6 class="font-weight-bold mb-3">Available Gold Products</h6>
            <div class="plans-slider">
                @foreach($products as $product)
                    @php 
                        $thumb = $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/images/dashboard/img_1.jpg');
                    @endphp
                    <div class="plan-slide" style="flex: 0 0 78%; max-width: 280px;">
                        <div class="card h-100 shadow-sm border-0" style="border-radius: 12px;">
                            <div class="position-relative">
                                <img src="{{ $thumb }}" class="card-img-top" alt="{{ $product->name }}" style="height: 140px; object-fit: cover; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                                <div class="position-absolute" style="top: 10px; left: 10px;">
                                    <span class="badge badge-warning text-dark font-weight-bold">{{ $product->gold_type }}</span>
                                </div>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="font-weight-bold mb-1 text-truncate">{{ $product->name }}</h6>
                                <p class="text-muted small mb-2">{{ number_format($product->weight_in_grams, 2) }}g · {{ number_format($product->purity, 1) }}% Purity</p>
                                
                                <div class="mt-auto">
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Today's Price</span>
                                        <span class="font-weight-bold text-primary">₹{{ number_format($product->computed_price, 2) }}</span>
                                    </div>
                                    @if($product->starting_emi)
                                        <div class="bg-light rounded p-2 mb-3">
                                            <span class="text-muted small d-block" style="font-size: 0.65rem;">EMI Starting From</span>
                                            <span class="font-weight-bold text-success small">₹{{ number_format($product->starting_emi, 0) }}/mo</span>
                                        </div>
                                    @else
                                        <div class="bg-light rounded p-2 mb-3 text-center">
                                            <span class="text-muted small" style="font-size: 0.7rem;">No EMI Available</span>
                                        </div>
                                    @endif
                                    
                                    <a href="{{ route('customer.plans.show', $product->id) }}" class="btn btn-primary btn-block btn-mobile-lg text-center" style="font-weight: 500;">
                                        View Plans
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="text-center text-muted small mt-2"><i class="mdi mdi-gesture-swipe-horizontal"></i> Swipe left or right to browse products</p>
        </div>
    @endif

    {{-- Styling & Interactive Auto-Refresh JavaScript --}}
    @push('styles')
        <style>
            .hover-shadow:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
            }
            .text-warning-custom {
                color: #ffc107 !important;
            }
            .plans-slider {
                display: flex;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                gap: 16px;
                padding-bottom: 12px;
                scrollbar-width: none;
            }
            .plans-slider::-webkit-scrollbar {
                display: none;
            }
            .plan-slide {
                scroll-snap-align: start;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Periodically update the live price from backend API (Every 30 seconds)
                const priceUpdateInterval = 30000;

                function fetchLivePrice() {
                    fetch('{{ route('customer.plans.live_price') }}')
                        .then(response => {
                            if (!response.ok) throw new Error('Network response not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (data.price_22k && data.price_24k) {
                                document.getElementById('live-price-22k').innerHTML = `₹${data.price_22k}<small class="h6 text-white-50">/g</small>`;
                                document.getElementById('live-price-24k').innerHTML = `₹${data.price_24k}<small class="h6 text-white-50">/g</small>`;
                                document.getElementById('live-price-updated').innerHTML = `<i class="mdi mdi-clock-outline"></i> Last updated: ${data.last_updated}`;

                                // Update trends
                                updateTrendBadge('trend-22k-badge', data.trend_22k);
                                updateTrendBadge('trend-24k-badge', data.trend_24k);
                            }
                        })
                        .catch(error => console.error('Error fetching live gold price:', error));
                }

                function updateTrendBadge(elementId, trend) {
                    const badgeEl = document.getElementById(elementId);
                    if (trend === 'up') {
                        badgeEl.innerHTML = '<span class="text-success"><i class="mdi mdi-trending-up"></i> Up</span>';
                    } else if (trend === 'down') {
                        badgeEl.innerHTML = '<span class="text-danger"><i class="mdi mdi-trending-down"></i> Down</span>';
                    } else {
                        badgeEl.innerHTML = '<span class="text-secondary">Stable</span>';
                    }
                }

                // Poll every 30 seconds
                setInterval(fetchLivePrice, priceUpdateInterval);
            });
        </script>
    @endpush
</x-customer-layout>
