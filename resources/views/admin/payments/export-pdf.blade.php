<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment {{ ucfirst($module) }} Export</title>
    <style>body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#222}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:5px}th{background:#f2f2f2}</style>
</head>
<body>
    <h2>Payment {{ ucfirst($module) }} Export</h2>
    <p>Generated {{ now()->format('Y-m-d H:i:s') }}</p>
    <table>
        <thead><tr><th>Transaction</th><th>Gateway</th><th>Order</th><th>Booking</th><th>Customer</th><th>Type</th><th>Amount</th><th>Status</th><th>Failure</th></tr></thead>
        <tbody>
            @foreach($transactions as $transaction)
                <tr><td>{{ $transaction->transaction_number }}</td><td>{{ $transaction->gateway }}</td><td>{{ $transaction->gateway_order_id }}</td><td>{{ $transaction->booking->booking_number ?? 'N/A' }}</td><td>{{ $transaction->booking->customer->name ?? $transaction->customer->name ?? 'N/A' }}</td><td>{{ $transaction->payment_type }}</td><td>{{ $transaction->amount }}</td><td>{{ $transaction->payment_status }}</td><td>{{ $transaction->failure_reason }}</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
