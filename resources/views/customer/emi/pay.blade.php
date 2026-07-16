<x-customer-layout title="Pay EMI">
    <div class="mb-3">
        <a href="{{ route('customer.emi.repay') }}" class="text-muted small"><i class="mdi mdi-arrow-left"></i> Back</a>
        <h5 class="font-weight-bold mt-2">Pay EMI #{{ $schedule->installment_number }}</h5>
    </div>

    <div class="card mobile-card">
        <div class="card-body">
            <p><strong>Plan:</strong> {{ $schedule->booking?->product?->name }}</p>
            <p><strong>Due Date:</strong> {{ $schedule->due_date?->format('d M Y') }}</p>
            <h4 class="text-primary font-weight-bold mb-4">₹{{ number_format($schedule->emi_amount, 2) }}</h4>

            <form action="{{ route('customer.emi.process_pay', $schedule->id) }}" method="POST">
                @csrf
                <p class="text-muted small mb-3">You will be redirected to Cashfree hosted checkout. Your EMI will be marked paid only after gateway verification.</p>
                <button type="submit" class="btn btn-primary btn-block btn-mobile-lg">
                    <i class="mdi mdi-shield-check mr-1"></i> Pay Securely
                </button>
            </form>
        </div>
    </div>
</x-customer-layout>
