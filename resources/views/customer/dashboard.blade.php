<x-customer-layout title="Dashboard">
    {{-- Desktop Header --}}
    <div class="d-none d-md-block">
        <div class="page-header flex-wrap">
            <h3 class="mb-0">Hi, {{ Auth::user()->name }}! <span class="pl-0 h6 pl-sm-2 text-muted d-inline-block">Welcome to your ZAA Gold portal.</span></h3>
            <div class="d-flex">
                <a href="{{ route('customer.profile.index') }}" class="btn btn-sm bg-white btn-icon-text border">
                    <i class="mdi mdi-account btn-icon-prepend"></i> Profile
                </a>
                <a href="{{ route('customer.notifications.index') }}" class="btn btn-sm bg-white btn-icon-text border ml-3">
                    <i class="mdi mdi-bell btn-icon-prepend"></i> Notifications
                </a>
            </div>
        </div>
    </div>

    {{-- Mobile: Active Plans Slider --}}
    <div class="d-block d-md-none mb-4">
        <h5 class="font-weight-bold mb-3">Active Plans</h5>
        @if($plans->isEmpty())
            <div class="alert alert-info">No active plans yet.</div>
        @else
            <div class="plans-slider">
                @foreach($plans as $plan)
                    <div class="plan-slide">
                        @include('customer.components.plan-card', ['plan' => $plan, 'compact' => true])
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Mobile: Gold Price --}}
    <div class="d-block d-md-none mb-4">
        <h5 class="font-weight-bold mb-3">Live Gold Price</h5>
        @include('customer.components.gold-price-card-mobile', [
            'goldPrice' => $goldPrice['price'] ?? null,
            'trend22k' => $goldPrice['trend_22k'] ?? 'neutral',
            'trend24k' => $goldPrice['trend_24k'] ?? 'neutral',
        ])
    </div>

    {{-- Mobile: Service Tiles --}}
    <div class="d-block d-md-none mb-4">
        <h5 class="font-weight-bold mb-3">Services</h5>
        <div class="row">
            @foreach([
                ['icon' => 'mdi-gold', 'label' => 'My Plans', 'route' => route('customer.plans.index')],
                ['icon' => 'mdi-history', 'label' => 'EMI History', 'route' => route('customer.emi.history')],
                ['icon' => 'mdi-cash-refund', 'label' => 'Repay EMI', 'route' => route('customer.emi.repay')],
                ['icon' => 'mdi-scale-balance', 'label' => 'Outstanding', 'route' => route('customer.outstanding.index')],
                ['icon' => 'mdi-cash-multiple', 'label' => 'Payments', 'route' => route('customer.payments.index')],
                ['icon' => 'mdi-file-document', 'label' => 'Receipts', 'route' => route('customer.certificates.index')],
                ['icon' => 'mdi-file-certificate', 'label' => 'GST Invoices', 'route' => route('customer.certificates.index')],
                ['icon' => 'mdi-certificate', 'label' => 'Certificates', 'route' => route('customer.certificates.index')],
                ['icon' => 'mdi-truck-delivery', 'label' => 'Delivery', 'route' => route('customer.deliveries.index')],
                ['icon' => 'mdi-account', 'label' => 'Profile', 'route' => route('customer.profile.index')],
                ['icon' => 'mdi-lifebuoy', 'label' => 'Support', 'route' => route('customer.support.index')],
                ['icon' => 'mdi-bell', 'label' => 'Notifications', 'route' => route('customer.notifications.index')],
            ] as $service)
            <div class="col-6 mb-3">
                @include('customer.components.service-card', $service)
            </div>
            @endforeach
        </div>
    </div>

    {{-- Desktop Layout --}}
    <div class="d-none d-md-block">
        <div class="row">
            <div class="col-lg-8 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Purchased Plans</h4>
                        @if($plans->isEmpty())
                            <p class="text-muted">No active plans. Contact support to get started.</p>
                        @else
                            <div class="row">
                                @foreach($plans as $plan)
                                <div class="col-md-6 grid-margin">
                                    @include('customer.components.plan-card', ['plan' => $plan])
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Live Gold Price</h4>
                        @include('customer.components.gold-price-card', [
                            'goldPrice' => $goldPrice['price'] ?? null,
                            'trend22k' => $goldPrice['trend_22k'] ?? 'neutral',
                            'trend24k' => $goldPrice['trend_24k'] ?? 'neutral',
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Services</h4>
                        <div class="row">
                            @foreach([
                                ['icon' => 'mdi-gold', 'label' => 'My Plans', 'route' => route('customer.plans.index')],
                                ['icon' => 'mdi-history', 'label' => 'EMI History', 'route' => route('customer.emi.history')],
                                ['icon' => 'mdi-cash-refund', 'label' => 'Repay EMI', 'route' => route('customer.emi.repay')],
                                ['icon' => 'mdi-scale-balance', 'label' => 'Outstanding', 'route' => route('customer.outstanding.index')],
                                ['icon' => 'mdi-cash-multiple', 'label' => 'Payment History', 'route' => route('customer.payments.index')],
                                ['icon' => 'mdi-file-document', 'label' => 'Receipts', 'route' => route('customer.certificates.index')],
                                ['icon' => 'mdi-file-certificate', 'label' => 'GST Invoices', 'route' => route('customer.certificates.index')],
                                ['icon' => 'mdi-certificate', 'label' => 'Certificates', 'route' => route('customer.certificates.index')],
                                ['icon' => 'mdi-truck-delivery', 'label' => 'Delivery', 'route' => route('customer.deliveries.index')],
                                ['icon' => 'mdi-account', 'label' => 'Profile', 'route' => route('customer.profile.index')],
                                ['icon' => 'mdi-lifebuoy', 'label' => 'Support', 'route' => route('customer.support.index')],
                                ['icon' => 'mdi-bell', 'label' => 'Notifications', 'route' => route('customer.notifications.index')],
                            ] as $service)
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <a href="{{ $service['route'] }}" class="card text-center p-3 text-decoration-none text-dark h-100">
                                    <i class="mdi {{ $service['icon'] }} text-primary" style="font-size: 2rem;"></i>
                                    <span class="mt-2 font-weight-medium">{{ $service['label'] }}</span>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Recent Activity</h4>
                        @if($recentActivity->isEmpty())
                            <p class="text-muted mb-0">No recent activity.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($recentActivity as $activity)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <h6 class="mb-1">{{ $activity['title'] }}</h6>
                                        <p class="text-muted small mb-0">{{ Str::limit($activity['message'], 80) }}</p>
                                    </div>
                                    <small class="text-muted">{{ $activity['date']->diffForHumans() }}</small>
                                </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-customer-layout>
