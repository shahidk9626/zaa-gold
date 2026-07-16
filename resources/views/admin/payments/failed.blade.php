@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-12 mb-4"><div class="card bg-white border shadow-sm p-4"><h4 class="font-weight-bold mb-1">Failed Payments</h4><p class="text-muted mb-0">Failed gateway transactions with retry support.</p></div></div>
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <form method="GET" class="row">
                <div class="col-md-4 form-group"><label class="font-weight-bold">Search</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Transaction, booking, customer"></div>
                <div class="col-md-3 form-group d-flex align-items-end"><button class="btn btn-primary btn-sm mr-2">Filter</button><a href="{{ route('payments.failed') }}" class="btn btn-light btn-sm mr-2">Reset</a><a href="{{ route('payments.management.export', ['module' => 'failed', 'type' => 'csv']) }}" class="btn btn-outline-success btn-sm">CSV</a></div>
            </form>
        </div>
    </div>
    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Transaction</th><th>Booking</th><th>Customer</th><th>Amount</th><th>Failure Reason</th><th>Gateway Error</th><th>Created</th><th>Retry Count</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($failedPayments as $payment)
                            <tr>
                                <td class="font-weight-bold text-primary">{{ $payment->transaction_number }}</td>
                                <td>{{ $payment->booking->booking_number ?? 'N/A' }}</td>
                                <td>{{ $payment->booking->customer->name ?? $payment->customer->name ?? 'N/A' }}</td>
                                <td>₹{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->failure_reason ?? 'N/A' }}</td>
                                <td>{{ data_get($payment->gateway_response, 'error', data_get($payment->gateway_response, 'status', 'N/A')) }}</td>
                                <td>{{ $payment->created_at?->format('d M Y H:i') }}</td>
                                <td>{{ \App\Models\PaymentTransaction::where('booking_id', $payment->booking_id)->where('payment_type', $payment->payment_type)->where('created_at', '>', $payment->created_at)->count() }}</td>
                                <td class="text-nowrap"><a href="{{ route('payment-logs.show', $payment) }}" class="btn btn-sm btn-outline-primary">Details</a><form method="POST" action="{{ route('payments.failed.retry', $payment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-warning">Retry</button></form></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No failed payments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $failedPayments->links() }}</div>
        </div>
    </div>
</div>
@endsection
