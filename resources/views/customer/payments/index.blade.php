<x-customer-layout title="Payment History">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">Payment History</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">Payment History</h5></div>

    @if($payments->isEmpty())
        <div class="alert alert-info">No payment history found.</div>
    @else
        <div class="d-none d-md-block">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="bg-light">
                                <tr><th>Receipt</th><th>Amount</th><th>Date</th><th>Mode</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                <tr>
                                    <td>{{ $payment->receipt_number ?? $payment->payment_number }}</td>
                                    <td class="text-success font-weight-bold">₹{{ number_format($payment->amount_paid, 2) }}</td>
                                    <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                                    <td>{{ $payment->payment_mode }}</td>
                                    <td>
                                        @if($payment->status === 'Paid')
                                        <a href="{{ route('customer.payments.receipt', $payment->id) }}" class="btn btn-sm btn-outline-primary">Download</a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-block d-md-none timeline-mobile">
            @foreach($payments as $payment)
                @include('customer.components.payment-card', ['payment' => $payment])
            @endforeach
        </div>
    @endif
</x-customer-layout>
