@props(['delivery'])

@php
    $statusClass = match($delivery->delivery_status) {
        'Delivered' => 'badge-success',
        'Dispatched', 'Out For Delivery' => 'badge-primary',
        'Approved' => 'badge-info',
        'Requested' => 'badge-warning',
        'Cancelled' => 'badge-danger',
        default => 'badge-secondary',
    };
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="font-weight-bold mb-1">{{ $delivery->delivery_number }}</h6>
                <p class="text-muted small mb-0">Booking: {{ $delivery->booking?->booking_number }}</p>
            </div>
            <span class="badge {{ $statusClass }}">{{ $delivery->delivery_status }}</span>
        </div>
        <p class="mb-1"><strong>{{ $delivery->booking?->product?->name }}</strong></p>
        <p class="text-muted small mb-2">
            Expected: {{ $delivery->dispatch_date?->format('d M Y') ?? $delivery->pickup_date?->format('d M Y') ?? 'TBD' }}
        </p>
        <a href="{{ route('customer.deliveries.show', $delivery->id) }}" class="btn btn-sm btn-primary">View Details</a>
    </div>
</div>
