@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-12 mb-4"><div class="card bg-white border shadow-sm p-4"><h4 class="font-weight-bold mb-1">Payment Reconciliation</h4><p class="text-muted mb-0">Compare internal payment state with Cashfree gateway state.</p></div></div>
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <form method="GET" class="row">
                <div class="col-md-3 form-group"><label class="font-weight-bold">Search</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Transaction, order, customer"></div>
                <div class="col-md-2 form-group"><label class="font-weight-bold">Status</label><select name="status" class="form-control"><option value="">All</option>@foreach(['Pending','Processing','Success','Failed','Cancelled','Expired','Refunded','Partially Refunded'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
                <div class="col-md-2 form-group"><label class="font-weight-bold">Type</label><select name="payment_type" class="form-control"><option value="">All</option><option value="booking" @selected(request('payment_type') === 'booking')>Booking</option><option value="emi" @selected(request('payment_type') === 'emi')>EMI</option></select></div>
                <div class="col-md-3 form-group d-flex align-items-end"><button class="btn btn-primary btn-sm mr-2">Filter</button><a href="{{ route('payments.reconciliation') }}" class="btn btn-light btn-sm mr-2">Reset</a><a href="{{ route('payments.management.export', array_merge(request()->query(), ['module' => 'reconciliation', 'type' => 'csv'])) }}" class="btn btn-outline-success btn-sm">CSV</a></div>
            </form>
        </div>
    </div>
    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Transaction</th><th>Gateway Order</th><th>Gateway Payment</th><th>Booking</th><th>Customer</th><th>Amount</th><th>Internal</th><th>Gateway</th><th>Difference</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            @php($row = $reconciliation[$transaction->id] ?? ['gatewayStatus' => 'Unknown', 'difference' => 'Pending'])
                            <tr>
                                <td class="font-weight-bold text-primary">{{ $transaction->transaction_number }}</td>
                                <td class="text-monospace">{{ $transaction->gateway_order_id }}</td>
                                <td>{{ $transaction->gateway_payment_id ?? 'N/A' }}</td>
                                <td>{{ $transaction->booking->booking_number ?? 'N/A' }}</td>
                                <td>{{ $transaction->booking->customer->name ?? $transaction->customer->name ?? 'N/A' }}</td>
                                <td>₹{{ number_format($transaction->amount, 2) }}</td>
                                <td>{{ $transaction->payment_status }}</td>
                                <td>{{ $row['gatewayStatus'] }}</td>
                                <td><span class="badge badge-{{ $row['difference'] === 'Matched' ? 'success' : ($row['difference'] === 'Mismatch' ? 'danger' : 'warning') }}">{{ $row['difference'] }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('payment-logs.show', $transaction) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <form method="POST" action="{{ route('payments.reconciliation.refresh', $transaction) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-info">Refresh</button></form>
                                    <form method="POST" action="{{ route('payments.reconciliation.verify', $transaction) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Verify</button></form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">No transactions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $transactions->links() }}</div>
        </div>
    </div>
</div>
@endsection
