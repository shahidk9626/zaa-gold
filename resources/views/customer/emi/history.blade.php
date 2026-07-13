<x-customer-layout title="EMI History">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">EMI History</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">EMI History</h5></div>

    <div class="row mb-4">
        @foreach([
            ['label' => 'Paid EMI', 'value' => $paid_emi, 'color' => 'success'],
            ['label' => 'Pending EMI', 'value' => $pending_emi, 'color' => 'warning'],
            ['label' => 'Total Paid', 'value' => '₹' . number_format($total_paid, 0), 'color' => 'primary'],
            ['label' => 'Outstanding', 'value' => '₹' . number_format($outstanding, 0), 'color' => 'danger'],
        ] as $stat)
        <div class="col-6 col-md-3 grid-margin">
            <div class="card bg-{{ $stat['color'] }}">
                <div class="card-body text-center text-white py-3">
                    <p class="mb-0 small">{{ $stat['label'] }}</p>
                    <h4 class="mb-0">{{ $stat['value'] }}</h4>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-7 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">EMI Schedule</h5>
                    @foreach($schedule->take(20) as $emi)
                        @include('customer.components.emi-card', ['schedule' => $emi, 'showPayButton' => false])
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-5 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Payments</h5>
                    @forelse($recent_payments as $payment)
                        @include('customer.components.payment-card', ['payment' => $payment])
                    @empty
                        <p class="text-muted">No recent payments.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-customer-layout>
