<x-customer-layout title="Dashboard">
    {{-- Top Verification Warning Banner --}}
    @if($plans->isNotEmpty() && $kycStatus !== 'Approved')
        <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-center justify-content-between p-3" role="alert" style="border-radius: 8px;">
            <div class="d-flex align-items-center">
                <i class="mdi mdi-alert-circle text-warning mr-3" style="font-size: 2rem;"></i>
                <div>
                    <h6 class="alert-heading mb-1 font-weight-bold" style="color: #664d03;">Action Required: Complete KYC</h6>
                    <p class="mb-0 small text-dark">Your profile is incomplete or KYC documents are pending approval. Delivery of your gold is restricted until your verification is approved.</p>
                </div>
            </div>
            <a href="{{ route('customer.profile.index') }}" class="btn btn-warning btn-sm font-weight-bold px-3 py-2 text-dark">Verify Now</a>
        </div>
    @endif

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

    {{-- Mobile: KYC Status Card --}}
    <div class="d-block d-md-none mb-4">
        <h5 class="font-weight-bold mb-3">Verification Status</h5>
        <div class="card bg-white border p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="small text-muted d-block font-weight-medium">KYC Status</span>
                    @if($kycStatus === 'Approved')
                        <span class="badge badge-success mt-1"><i class="mdi mdi-check-circle mr-1"></i> Approved</span>
                    @elseif($kycStatus === 'Pending Review')
                        <span class="badge badge-warning text-dark mt-1"><i class="mdi mdi-clock mr-1"></i> Pending Review</span>
                    @elseif($kycStatus === 'Rejected')
                        <span class="badge badge-danger mt-1"><i class="mdi mdi-close-circle mr-1"></i> Rejected</span>
                    @elseif($kycStatus === 'Resubmission Required')
                        <span class="badge badge-info mt-1"><i class="mdi mdi-alert-circle mr-1"></i> Resubmission Required</span>
                    @else
                        <span class="badge badge-secondary mt-1"><i class="mdi mdi-file-document mr-1"></i> Draft</span>
                    @endif
                </div>
                @if($kycStatus !== 'Approved')
                    <a href="{{ route('customer.profile.index') }}" class="btn btn-sm btn-primary">
                        {{ $kycStatus === 'Draft' ? 'Verify' : 'Update' }}
                    </a>
                @endif
            </div>
        </div>
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
                ['icon' => 'mdi-gold', 'label' => 'My Plans', 'route' => route('customer.my-plans.index')],
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
                            <p class="text-muted">No active plans. Navigate to <a href="{{ route('customer.plans.index') }}">Buy Gold Plans</a> to get started.</p>
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
            <div class="col-lg-4 grid-margin">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Live Gold Price</h4>
                        @include('customer.components.gold-price-card', [
                            'goldPrice' => $goldPrice['price'] ?? null,
                            'trend22k' => $goldPrice['trend_22k'] ?? 'neutral',
                            'trend24k' => $goldPrice['trend_24k'] ?? 'neutral',
                        ])
                    </div>
                </div>

                {{-- Desktop KYC Status Card --}}
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">KYC Verification</h4>
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <div>
                                <h6 class="mb-1 font-weight-bold">Status:</h6>
                                @if($kycStatus === 'Approved')
                                    <span class="badge badge-success font-weight-bold px-3 py-2"><i class="mdi mdi-check-circle mr-1"></i> Approved</span>
                                @elseif($kycStatus === 'Pending Review')
                                    <span class="badge badge-warning text-dark font-weight-bold px-3 py-2"><i class="mdi mdi-clock mr-1"></i> Pending Review</span>
                                @elseif($kycStatus === 'Rejected')
                                    <span class="badge badge-danger font-weight-bold px-3 py-2"><i class="mdi mdi-close-circle mr-1"></i> Rejected</span>
                                @elseif($kycStatus === 'Resubmission Required')
                                    <span class="badge badge-info font-weight-bold px-3 py-2"><i class="mdi mdi-alert-circle mr-1"></i> Resubmission Required</span>
                                @else
                                    <span class="badge badge-secondary font-weight-bold px-3 py-2"><i class="mdi mdi-file-document mr-1"></i> Draft</span>
                                @endif
                            </div>
                            <i class="mdi mdi-shield-check text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                        <p class="text-muted small mt-3">
                            @if($kycStatus === 'Approved')
                                Your identity has been verified. You can now place physical gold delivery requests.
                            @elseif($kycStatus === 'Pending Review')
                                Compliance team is currently reviewing your uploaded documents.
                            @elseif($kycStatus === 'Rejected')
                                Your verification was rejected. Please update your profile or re-submit.
                            @elseif($kycStatus === 'Resubmission Required')
                                Compliance team has requested some document changes. Please upload updated files.
                            @else
                                Complete your profile details and submit documents to enable gold delivery options.
                            @endif
                        </p>
                        @if($kycStatus !== 'Approved')
                            <a href="{{ route('customer.profile.index') }}" class="btn btn-outline-primary btn-sm btn-block mt-3">
                                {{ $kycStatus === 'Draft' ? 'Verify Now' : 'Edit documents' }}
                            </a>
                        @endif
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
                                ['icon' => 'mdi-gold', 'label' => 'My Plans', 'route' => route('customer.my-plans.index')],
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

    {{-- Professional Profile & KYC Reminder Bootstrap Modal --}}
    <div class="modal fade" id="kycReminderModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="kycReminderModalLabel" aria-hidden="true" style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content bg-white text-dark border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title font-weight-bold text-dark" id="kycReminderModalLabel">Complete Your Profile & KYC</h5>
                    <button type="button" class="close text-dark" onclick="dismissKycReminder()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-4">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background-color: #f1f3f9;">
                            <i class="mdi mdi-shield-account text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                    <h6 class="font-weight-bold text-center mb-2">Congratulations on your Gold Plan purchase!</h6>
                    <p class="text-muted text-center small px-3">To ensure smooth delivery of your Gold and uninterrupted services, please complete your Profile details and KYC verification.</p>
                </div>
                <div class="modal-footer border-top-0 pt-0 d-flex flex-column gap-2 w-100 pb-4">
                    <a href="{{ route('customer.profile.index') }}" class="btn btn-primary btn-block py-2 font-weight-bold">Complete Now</a>
                    <button type="button" class="btn btn-link text-muted btn-block py-1 mt-1 small" onclick="dismissKycReminder()">Remind Me Later</button>
                </div>
            </div>
        </div>
    </div>
</x-customer-layout>

@if($showReminderModal)
    @push('scripts')
    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('#kycReminderModal').modal('show');
            }, 800);
        });

        function dismissKycReminder() {
            $('#kycReminderModal').modal('hide');
            $.ajax({
                url: "{{ route('customer.dashboard.dismiss_reminder') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    console.log('Reminder deferred successfully');
                }
            });
        }
    </script>
    @endpush
@endif
