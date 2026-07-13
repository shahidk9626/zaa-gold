@props(['payment'])

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6 class="font-weight-bold mb-1">{{ $payment->receipt_number ?? $payment->payment_number }}</h6>
                <p class="text-muted small mb-1">{{ $payment->booking?->product?->name ?? 'Gold Plan' }}</p>
                <p class="text-muted small mb-0">{{ $payment->payment_date?->format('d M Y, h:i A') }}</p>
            </div>
            <div class="text-right">
                <h5 class="text-success font-weight-bold mb-1">₹{{ number_format($payment->amount_paid, 2) }}</h5>
                <span class="badge badge-light">{{ $payment->payment_mode }}</span>
            </div>
        </div>
        @if($payment->status === 'Paid')
        <a href="{{ route('customer.payments.receipt', $payment->id) }}" class="btn btn-sm btn-outline-primary mt-2">
            <i class="mdi mdi-download"></i> Download Receipt
        </a>
        @endif
    </div>
</div>
