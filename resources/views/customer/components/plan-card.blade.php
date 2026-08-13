@props(['plan', 'compact' => false])

@php
    $booking = $plan['booking'];
    $product = $booking->product;
    $statusClass = match($booking->status) {
        'Active' => 'badge-primary',
        'Booked' => 'badge-warning',
        'Completed' => 'badge-success',
        'Cancelled', 'Refund Initiated', 'Refunded' => 'badge-danger',
        'Draft' => 'badge-secondary',
        default => 'badge-secondary',
    };
    $thumb = $product ? $product->getThumbnailUrl() : asset('assets/images/dashboard/img_1.jpg');
@endphp

<div class="card {{ $compact ? 'mobile-card' : '' }} h-100">
    <div class="card-body">
        <div class="d-flex align-items-start mb-3">
            <img src="{{ $thumb }}" alt="{{ $product?->name }}" class="rounded mr-3" style="width: {{ $compact ? '56px' : '72px' }}; height: {{ $compact ? '56px' : '72px' }}; object-fit: cover;" />
            <div class="flex-grow-1">
                <h6 class="font-weight-bold mb-1">{{ $product?->name ?? 'Gold Plan' }}</h6>
                <p class="text-muted small mb-1">{{ number_format($booking->gold_weight, 2) }}g · {{ $booking->emiPlan?->name ?? 'EMI Plan' }}</p>
                <span class="badge {{ $statusClass }}">{{ in_array($booking->status, ['Cancelled', 'Refund Initiated', 'Refunded']) ? 'Cancelled' : $booking->status }}</span>
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

        @if(in_array($booking->status, ['Cancelled', 'Refund Initiated', 'Refunded']))
            @php
                $latestCancel = $booking->latestCancellationRequest;
            @endphp
            <div class="mb-3">
                <div class="text-center mb-2">
                    <span class="badge badge-danger px-4 py-2 font-weight-bold" style="font-size: 0.9rem;">Cancelled</span>
                </div>
                @if($latestCancel)
                    <div class="bg-light p-2 rounded small text-dark">
                        <div class="mb-1 d-flex justify-content-between">
                            <span class="text-muted">Cancellation Date:</span>
                            <span class="font-weight-medium">{{ $latestCancel->approved_at?->format('d M Y') ?? $latestCancel->created_at?->format('d M Y') }}</span>
                        </div>
                        @if($latestCancel->cancellation_reason)
                            <div class="mb-1">
                                <span class="text-muted">Reason:</span>
                                <span class="d-block font-italic text-truncate mt-1 px-1 border-left text-muted" title="{{ $latestCancel->cancellation_reason }}" style="border-width: 2px !important; border-color: #dc3545 !important;">"{{ $latestCancel->cancellation_reason }}"</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Refund Status:</span>
                            @if($latestCancel->status === 'Refund Completed')
                                <span class="text-success font-weight-bold">Completed</span>
                            @elseif($latestCancel->status === 'Refund Initiated')
                                <span class="text-warning font-weight-bold">Refund Initiated</span>
                            @else
                                <span class="text-secondary font-weight-bold">Pending</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="d-flex justify-content-between small text-muted mb-2">
                <span>Paid: {{ $plan['paid_emi'] }}/{{ $plan['total_emi'] }} EMI</span>
                <span>Remaining: {{ $plan['remaining_emi'] }}</span>
            </div>

            <div class="progress mb-3" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: {{ $plan['progress'] }}%"></div>
            </div>
        @endif

        <div class="d-flex align-items-center">
            <a href="{{ route('customer.my-plans.show', $booking->id) }}" class="btn btn-sm btn-primary flex-grow-1 mr-2 {{ $compact ? 'btn-mobile-lg' : '' }}">
                View Details
            </a>
            @if($booking->status === 'Draft')
                <form action="{{ route('customer.my-plans.destroy', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this draft booking?');" class="flex-grow-1" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger btn-block {{ $compact ? 'btn-mobile-lg' : '' }}"><i class="mdi mdi-delete"></i> Delete</button>
                </form>
            @endif
        </div>
    </div>
</div>
