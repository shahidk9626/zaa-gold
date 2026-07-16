<x-customer-layout title="Secure Checkout">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-5 text-center">
                    <div class="spinner-border text-primary mb-4" role="status">
                        <span class="sr-only">Redirecting...</span>
                    </div>
                    <h4 class="font-weight-bold text-dark mb-2">Opening Secure Cashfree Checkout</h4>
                    <p class="text-muted mb-4">
                        Transaction {{ $transaction->transaction_number }} for ₹{{ number_format($transaction->amount, 2) }}
                        @if($transaction->emiSchedule)
                            <br><span class="small">EMI #{{ $transaction->emiSchedule->installment_number }} · {{ $transaction->booking->booking_number ?? 'Booking' }}</span>
                        @endif
                    </p>

                    @if(!$paymentSessionId)
                        <div class="alert alert-danger text-left">
                            Payment session could not be created. Please contact support with transaction {{ $transaction->transaction_number }}.
                        </div>
                        <a href="{{ route('customer.payments.index') }}" class="btn btn-outline-primary">Go to Payment History</a>
                    @else
                        <p class="small text-muted mb-0">Please do not refresh or close this page.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($paymentSessionId)
        @push('scripts')
            <script src="{{ $cashfreeSdkUrl }}"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const cashfree = Cashfree({ mode: @json($cashfreeMode) });
                    cashfree.checkout({
                        paymentSessionId: @json($paymentSessionId),
                        redirectTarget: '_self'
                    });
                });
            </script>
        @endpush
    @endif
</x-customer-layout>
