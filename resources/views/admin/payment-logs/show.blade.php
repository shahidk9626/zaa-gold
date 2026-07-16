@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between flex-wrap"><div><h4 class="font-weight-bold mb-1">Payment Log {{ $paymentLog->transaction_number }}</h4><p class="text-muted mb-0">{{ ucfirst($paymentLog->gateway) }} · {{ $paymentLog->payment_status }}</p></div><a href="{{ route('payment-logs.index') }}" class="btn btn-light btn-sm">Back</a></div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="font-weight-bold mb-3">Payment Timeline</h5>
            @foreach($timeline as $item)
                <div class="border-bottom pb-2 mb-2"><div class="font-weight-bold">{{ $item['label'] }}</div><div class="small text-muted">{{ $item['at']?->format('d M Y H:i') }}</div><div class="small">{{ $item['description'] }}</div></div>
            @endforeach
            @if($timeline->isEmpty())<p class="text-muted mb-0">No timeline entries found.</p>@endif
        </div>
    </div>
    <div class="col-lg-7 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="font-weight-bold mb-3">Gateway Data</h5>
            <div class="row mb-3">
                <div class="col-md-6 mb-2"><strong>Customer:</strong> {{ $paymentLog->booking->customer->name ?? 'N/A' }}</div>
                <div class="col-md-6 mb-2"><strong>Booking:</strong> {{ $paymentLog->booking->booking_number ?? 'N/A' }}</div>
                <div class="col-md-6 mb-2"><strong>EMI:</strong> {{ $paymentLog->emiSchedule ? '#' . $paymentLog->emiSchedule->installment_number : 'Booking Payment' }}</div>
                <div class="col-md-6 mb-2"><strong>Receipt:</strong> {{ $receipt->receipt_number ?? 'N/A' }}</div>
                <div class="col-md-6 mb-2"><strong>GST Invoice:</strong> {{ $invoice->invoice_number ?? 'N/A' }}</div>
                <div class="col-md-6 mb-2"><strong>Internal Status:</strong> {{ $paymentLog->payment_status }}</div>
            </div>
            <div class="mb-2"><strong>Gateway Order ID:</strong> {{ $paymentLog->gateway_order_id }}</div>
            <div class="mb-2"><strong>Gateway Payment ID:</strong> {{ $paymentLog->gateway_payment_id ?? 'N/A' }}</div>
            <div class="mb-2"><strong>Failure Reason:</strong> {{ $paymentLog->failure_reason ?? 'N/A' }}</div>
            <h6 class="font-weight-bold mt-4">Gateway Request</h6><pre class="bg-light p-3">{{ json_encode($paymentLog->gateway_request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            <h6 class="font-weight-bold mt-4">Gateway Response</h6><pre class="bg-light p-3">{{ json_encode($paymentLog->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            <h6 class="font-weight-bold mt-4">Webhook Payload</h6><pre class="bg-light p-3">{{ json_encode($paymentLog->webhook_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            <h6 class="font-weight-bold mt-4">Activity Logs</h6>
            @forelse($activityLogs as $log)
                <div class="border-bottom py-2"><strong>{{ \Illuminate\Support\Str::headline($log->action_type) }}</strong><div class="small text-muted">{{ $log->created_at?->format('d M Y H:i') }}</div><div>{{ $log->description }}</div></div>
            @empty
                <p class="text-muted mb-0">No activity logs found.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
