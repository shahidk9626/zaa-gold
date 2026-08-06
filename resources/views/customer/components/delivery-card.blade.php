@props(['delivery'])

@php
    $statusClass = match($delivery->delivery_status) {
        'Delivered' => 'badge-success',
        'Dispatched', 'In Transit', 'Out For Delivery', 'Ready For Dispatch' => 'badge-primary',
        'Approved' => 'badge-info',
        'Requested', 'Pending Admin Approval', 'Hold' => 'badge-warning',
        'Cancelled', 'Rejected' => 'badge-danger',
        'Collected' => 'badge-success',
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
            Expected: {{ $delivery->expected_delivery_date?->format('d M Y') ?? $delivery->pickup_date?->format('d M Y') ?? 'TBD' }}
        </p>
        <div class="d-flex flex-wrap align-items-center">
            <a href="{{ route('customer.deliveries.show', $delivery->id) }}" class="btn btn-sm btn-primary mr-2 mb-2">View Details</a>
            @if($delivery->tracking_url)
                <a href="{{ $delivery->tracking_url }}" target="_blank" class="btn btn-sm btn-outline-primary mb-2">Track Shipment <i class="mdi mdi-open-in-new"></i></a>
            @endif
        </div>
    </div>
</div>
