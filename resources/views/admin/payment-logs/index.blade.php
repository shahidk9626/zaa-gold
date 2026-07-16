@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-12 mb-4"><div class="card bg-white border shadow-sm p-4"><h4 class="font-weight-bold mb-1">Payment Logs</h4><p class="text-muted mb-0">Gateway request, response, webhook, and internal transaction statuses.</p></div></div>
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <form method="GET" class="row">
                <div class="col-md-4 form-group"><label class="font-weight-bold">Search</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Transaction, order, customer"></div>
                <div class="col-md-3 form-group"><label class="font-weight-bold">Status</label><select name="status" class="form-control"><option value="">All</option>@foreach(['Pending','Processing','Success','Failed','Cancelled','Expired','Refunded'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
                <div class="col-md-3 form-group d-flex align-items-end"><button class="btn btn-primary btn-sm mr-2">Filter</button><a href="{{ route('payment-logs.index') }}" class="btn btn-light btn-sm">Reset</a></div>
            </form>
        </div>
    </div>
    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Transaction Number</th><th>Gateway</th><th>Cashfree Order ID</th><th>Gateway Payment ID</th><th>Booking</th><th>Customer</th><th>Type</th><th>EMI</th><th>Amount</th><th>Gateway Status</th><th>Internal Status</th><th>Failure Reason</th><th>Webhook Received</th><th>Created At</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="font-weight-bold text-primary">{{ $log->transaction_number }}</td><td>{{ ucfirst($log->gateway) }}</td><td class="text-monospace">{{ $log->gateway_order_id }}</td><td>{{ $log->gateway_payment_id ?? 'N/A' }}</td><td>{{ $log->booking->booking_number ?? 'N/A' }}</td><td>{{ $log->booking->customer->name ?? $log->customer->name ?? 'N/A' }}</td><td>{{ strtoupper($log->payment_type) }}</td><td>{{ $log->emiSchedule ? '#' . $log->emiSchedule->installment_number : 'N/A' }}</td><td>₹{{ number_format($log->amount, 2) }}</td><td>{{ data_get($log->gateway_response, 'status', data_get($log->gateway_response, 'order_status', 'N/A')) }}</td><td>{{ $log->payment_status }}</td><td>{{ $log->failure_reason ?? 'N/A' }}</td><td>{{ $log->webhook_processed_at?->format('d M Y H:i') ?? 'No' }}</td><td>{{ $log->created_at?->format('d M Y H:i') }}</td><td><a href="{{ route('payment-logs.show', $log) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="15" class="text-center text-muted py-4">No payment logs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $logs->links() }}</div>
        </div>
    </div>
</div>
@endsection
