@props(['goldPrice', 'trend22k' => 'neutral', 'trend24k' => 'neutral'])

@if($goldPrice)
<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 text-white-50">Today's 22K Gold</p>
                        <h3 class="text-white mb-0">₹{{ number_format($goldPrice->price_22k, 2) }}<small class="h6">/g</small></h3>
                    </div>
                    <div class="text-right">
                        @if($trend22k === 'up')
                            <span class="badge badge-success"><i class="mdi mdi-trending-up"></i> Up</span>
                        @elseif($trend22k === 'down')
                            <span class="badge badge-danger"><i class="mdi mdi-trending-down"></i> Down</span>
                        @else
                            <span class="badge badge-light text-dark">Stable</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 text-white-50">Today's 24K Gold</p>
                        <h3 class="text-white mb-0">₹{{ number_format($goldPrice->price_24k, 2) }}<small class="h6">/g</small></h3>
                    </div>
                    <div class="text-right">
                        @if($trend24k === 'up')
                            <span class="badge badge-success"><i class="mdi mdi-trending-up"></i> Up</span>
                        @elseif($trend24k === 'down')
                            <span class="badge badge-danger"><i class="mdi mdi-trending-down"></i> Down</span>
                        @else
                            <span class="badge badge-light text-dark">Stable</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <p class="text-muted small mb-0"><i class="mdi mdi-clock-outline"></i> Last updated: {{ $goldPrice->effective_date->format('d M Y, h:i A') }}</p>
    </div>
</div>
@else
<div class="alert alert-info">Gold price data is not available at the moment.</div>
@endif
