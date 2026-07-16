<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Payment Status - ZAA Gold</title>
    <link rel="stylesheet" href="{{ asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/vendor.bundle.base.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        @php
                            $icon = $status === 'success' ? 'mdi-check-circle text-success' : ($status === 'expired' ? 'mdi-clock-alert text-warning' : 'mdi-alert-circle text-danger');
                        @endphp
                        <i class="mdi {{ $icon }}" style="font-size: 4rem;"></i>
                        <h4 class="font-weight-bold text-dark mt-3">{{ ucfirst($status) }}</h4>
                        <p class="text-muted">{{ $message }}</p>
                        <div class="bg-light rounded p-3 text-left mt-4">
                            <div><strong>Transaction:</strong> {{ $transaction->transaction_number }}</div>
                            <div><strong>Amount:</strong> ₹{{ number_format($transaction->amount, 2) }}</div>
                            <div><strong>Status:</strong> {{ $transaction->payment_status }}</div>
                        </div>
                        <a href="{{ url('/') }}" class="btn btn-primary mt-4">Go to ZAA Gold</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
