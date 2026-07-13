@props(['goldPrice', 'trend22k' => 'neutral', 'trend24k' => 'neutral'])

@if($goldPrice)
<div class="d-flex gap-3 plans-slider mb-2" style="gap: 12px;">
    <div class="gold-price-mobile-card flex-shrink-0" style="min-width: 160px;">
        <p class="mb-1 small opacity-75">22K Gold</p>
        <h5 class="mb-1 font-weight-bold">₹{{ number_format($goldPrice->price_22k, 0) }}/g</h5>
        @if($trend22k === 'up')<span class="badge badge-success badge-sm"><i class="mdi mdi-arrow-up"></i></span>
        @elseif($trend22k === 'down')<span class="badge badge-danger badge-sm"><i class="mdi mdi-arrow-down"></i></span>
        @endif
    </div>
    <div class="gold-price-mobile-card gold-24k flex-shrink-0" style="min-width: 160px;">
        <p class="mb-1 small opacity-75">24K Gold</p>
        <h5 class="mb-1 font-weight-bold">₹{{ number_format($goldPrice->price_24k, 0) }}/g</h5>
        @if($trend24k === 'up')<span class="badge badge-success badge-sm"><i class="mdi mdi-arrow-up"></i></span>
        @elseif($trend24k === 'down')<span class="badge badge-danger badge-sm"><i class="mdi mdi-arrow-down"></i></span>
        @endif
    </div>
</div>
<p class="text-muted small"><i class="mdi mdi-clock-outline"></i> Updated {{ $goldPrice->effective_date->format('d M Y, h:i A') }}</p>
@endif
