@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h4 class="font-weight-bold mb-1">Payment Dashboard</h4>
            <p class="text-muted mb-0">Enterprise collection health, gateway performance, and payment pipeline status.</p>
        </div>
    </div>

    @foreach([
        'Today Collection' => ['value' => '₹' . number_format($stats['today_collection'], 2), 'icon' => 'mdi-cash', 'color' => 'success'],
        'Monthly Collection' => ['value' => '₹' . number_format($stats['monthly_collection'], 2), 'icon' => 'mdi-calendar-month', 'color' => 'primary'],
        'Yearly Collection' => ['value' => '₹' . number_format($stats['yearly_collection'], 2), 'icon' => 'mdi-chart-line', 'color' => 'info'],
        'Successful Payments' => ['value' => $stats['successful_payments'], 'icon' => 'mdi-check-circle', 'color' => 'success'],
        'Pending Payments' => ['value' => $stats['pending_payments'], 'icon' => 'mdi-timer-sand', 'color' => 'warning'],
        'Failed Payments' => ['value' => $stats['failed_payments'], 'icon' => 'mdi-alert-circle', 'color' => 'danger'],
        'Expired Links' => ['value' => $stats['expired_links'], 'icon' => 'mdi-link-off', 'color' => 'secondary'],
        'Payment Links' => ['value' => $stats['total_links'], 'icon' => 'mdi-link-variant', 'color' => 'primary'],
        'Gateway Collection' => ['value' => '₹' . number_format($stats['gateway_collection'], 2), 'icon' => 'mdi-bank', 'color' => 'success'],
        'Pending Collection' => ['value' => '₹' . number_format($stats['pending_collection'], 2), 'icon' => 'mdi-clock-outline', 'color' => 'warning'],
        'Outstanding Amount' => ['value' => '₹' . number_format($stats['outstanding_amount'], 2), 'icon' => 'mdi-scale-balance', 'color' => 'danger'],
        'Success Rate' => ['value' => number_format($stats['success_rate'], 2) . '%', 'icon' => 'mdi-percent', 'color' => 'info'],
    ] as $label => $card)
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-white border shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted font-weight-bold text-uppercase">{{ $label }}</small>
                        <h4 class="font-weight-bold text-dark mt-2 mb-0">{{ $card['value'] }}</h4>
                    </div>
                    <i class="mdi {{ $card['icon'] }} text-{{ $card['color'] }}" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    @endforeach

    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="font-weight-bold mb-3">Daily Collection</h5>
            @forelse($charts['daily_collection'] as $period => $total)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $period }}</span><strong>₹{{ number_format($total, 2) }}</strong></div>
            @empty
                <p class="text-muted mb-0">No collection data available.</p>
            @endforelse
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="font-weight-bold mb-3">Payment Success vs Failed</h5>
            @foreach($charts['success_failed'] as $status => $total)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $status }}</span><strong>{{ $total }}</strong></div>
            @endforeach
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="font-weight-bold mb-3">Monthly Collection</h5>
            @forelse($charts['monthly_collection'] as $period => $total)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $period }}</span><strong>₹{{ number_format($total, 2) }}</strong></div>
            @empty
                <p class="text-muted mb-0">No collection data available.</p>
            @endforelse
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="font-weight-bold mb-3">Payment Mode Trend</h5>
            @forelse($charts['payment_mode_trend'] as $mode => $total)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $mode }}</span><strong>₹{{ number_format($total, 2) }}</strong></div>
            @empty
                <p class="text-muted mb-0">No payment mode data available.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
