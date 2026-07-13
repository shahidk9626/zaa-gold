<x-customer-layout title="Outstanding">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">Outstanding Balance</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">Outstanding</h5></div>

    <div class="card bg-danger text-white mb-4">
        <div class="card-body text-center py-4">
            <p class="mb-1">Total Outstanding</p>
            <h2 class="mb-0 font-weight-bold">₹{{ number_format($totalOutstanding, 2) }}</h2>
        </div>
    </div>

    @foreach($plans as $plan)
        @if($plan['outstanding'] > 0)
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold mb-1">{{ $plan['booking']->product?->name }}</h6>
                    <p class="text-muted small mb-0">#{{ $plan['booking']->booking_number }}</p>
                </div>
                <div class="text-right">
                    <h5 class="text-danger font-weight-bold mb-1">₹{{ number_format($plan['outstanding'], 0) }}</h5>
                    <a href="{{ route('customer.emi.repay') }}" class="btn btn-sm btn-primary">Repay</a>
                </div>
            </div>
        </div>
        @endif
    @endforeach
</x-customer-layout>
