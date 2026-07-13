@props(['schedule', 'showPayButton' => true])

@php
    $statusClass = match($schedule->status) {
        'Paid' => 'badge-success',
        'Overdue' => 'badge-danger',
        'Pending' => 'badge-warning',
        default => 'badge-secondary',
    };
    $isFuture = $schedule->due_date && $schedule->due_date->isFuture();
@endphp

<div class="card mb-3 mobile-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="font-weight-bold mb-0">EMI #{{ $schedule->installment_number }}</h6>
            <span class="badge {{ $statusClass }}">{{ $schedule->status }}</span>
        </div>
        <p class="text-muted small mb-1">{{ $schedule->booking?->product?->name ?? 'Gold Plan' }}</p>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted small mb-0">Due Date</p>
                <p class="font-weight-medium mb-0">{{ $schedule->due_date?->format('d M Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-muted small mb-0">Amount</p>
                <p class="font-weight-bold mb-0">₹{{ number_format($schedule->emi_amount, 2) }}</p>
            </div>
        </div>
        @if($showPayButton && in_array($schedule->status, ['Pending', 'Overdue']))
        <a href="{{ route('customer.emi.pay_form', $schedule->id) }}" class="btn btn-primary btn-block btn-mobile-lg mt-3">
            Pay Now
        </a>
        @elseif($isFuture && $schedule->status === 'Pending')
        <span class="badge badge-light mt-2">Future EMI</span>
        @endif
    </div>
</div>
