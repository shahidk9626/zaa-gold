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

    <div class="card mt-4">
        <div class="card-body">
            <h5 class="font-weight-bold text-dark mb-3">Booking Payment Transactions</h5>
            @if($transactions->isEmpty())
                <p class="text-muted mb-0">No booking payment transactions found.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>Transaction Number</th>
                                <th>Booking Number</th>
                                <th>EMI Number</th>
                                <th>Gateway</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Paid Date</th>
                                <th>Receipt</th>
                                <th>GST Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                @php
                                    $receipt = $payments->firstWhere('booking_id', $transaction->booking_id);
                                    if ($transaction->emi_schedule_id) {
                                        $receipt = $payments->firstWhere('emi_schedule_id', $transaction->emi_schedule_id);
                                    }
                                    $invoice = $receipt ? ($invoices[$receipt->id] ?? null) : null;
                                    $badge = match($transaction->payment_status) {
                                        'Success' => 'badge-success',
                                        'Failed', 'Cancelled' => 'badge-danger',
                                        'Processing' => 'badge-warning',
                                        default => 'badge-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="font-weight-bold">{{ $transaction->transaction_number }}</td>
                                    <td>{{ $transaction->booking->booking_number ?? 'Draft' }}</td>
                                    <td>{{ $transaction->emiSchedule ? '#' . $transaction->emiSchedule->installment_number : 'Booking' }}</td>
                                    <td>{{ ucfirst($transaction->gateway) }}</td>
                                    <td class="text-success font-weight-bold">₹{{ number_format($transaction->amount, 2) }}</td>
                                    <td>{{ $transaction->created_at?->format('d M Y') }}</td>
                                    <td><span class="badge {{ $badge }}">{{ $transaction->payment_status }}</span></td>
                                    <td>{{ $transaction->paid_at?->format('d M Y') ?? 'N/A' }}</td>
                                    <td>
                                        @if($receipt && $receipt->status === 'Paid')
                                            <a href="{{ route('customer.payments.receipt', $receipt->id) }}" class="btn btn-sm btn-outline-primary">Download</a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $invoice ? $invoice->invoice_number : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-customer-layout>
