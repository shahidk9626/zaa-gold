@props(['plan', 'compact' => false])

@php
    $booking = $plan['booking'];
    $product = $booking->product;
    $statusClass = match($booking->status) {
        'Active' => 'badge-primary',
        'Booked' => 'badge-warning',
        'Completed' => 'badge-success',
        'Cancelled' => 'badge-danger',
        default => 'badge-secondary',
    };
    $thumb = $product?->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/images/dashboard/img_1.jpg');
@endphp

<div class="card {{ $compact ? 'mobile-card' : '' }} h-100">
    <div class="card-body">
        <div class="d-flex align-items-start mb-3">
            <img src="{{ $thumb }}" alt="{{ $product?->name }}" class="rounded mr-3" style="width: {{ $compact ? '56px' : '72px' }}; height: {{ $compact ? '56px' : '72px' }}; object-fit: cover;" />
            <div class="flex-grow-1">
                <h6 class="font-weight-bold mb-1">{{ $product?->name ?? 'Gold Plan' }}</h6>
                <p class="text-muted small mb-1">{{ number_format($booking->gold_weight, 2) }}g · {{ $booking->emiPlan?->name ?? 'EMI Plan' }}</p>
                <span class="badge {{ $statusClass }}">{{ $booking->status }}</span>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-6">
                <p class="text-muted small mb-0">Monthly EMI</p>
                <p class="font-weight-bold mb-0">₹{{ number_format($plan['monthly_emi'], 0) }}</p>
            </div>
            <div class="col-6 text-right">
                <p class="text-muted small mb-0">Outstanding</p>
                <p class="font-weight-bold text-danger mb-0">₹{{ number_format($plan['outstanding'], 0) }}</p>
            </div>
        </div>

        <div class="d-flex justify-content-between small text-muted mb-2">
            <span>Paid: {{ $plan['paid_emi'] }}/{{ $plan['total_emi'] }} EMI</span>
            <span>Remaining: {{ $plan['remaining_emi'] }}</span>
        </div>

        <div class="progress mb-3" style="height: 6px;">
            <div class="progress-bar bg-success" style="width: {{ $plan['progress'] }}%"></div>
        </div>

        <a href="{{ route('customer.plans.show', $booking->id) }}" class="btn btn-sm btn-primary btn-block {{ $compact ? 'btn-mobile-lg' : '' }}">
            View Details
        </a>
    </div>
</div>
