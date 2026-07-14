@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Referral Details: {{ $referral->referral_code }}</h4>
                    <p class="card-description text-muted mb-0">Review status, associated purchases, and rewards tracking.</p>
                </div>
                <div>
                    <a href="{{ route('referrals.index') }}" class="btn btn-secondary px-4 mr-2">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to Directory
                    </a>
                    @if(hasPermission('referral.edit'))
                    <a href="{{ route('referrals.edit', $referral->id) }}" class="btn btn-primary px-4">
                        <i class="mdi mdi-pencil mr-1"></i> Edit Referral
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Block -->
    @if(session('success'))
        <div class="col-12 mb-4">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <!-- Information Cards -->
    <div class="col-md-8">
        <!-- Referral Details Card -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Reward Configuration</h5>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Reward Type</span>
                    <strong class="text-dark" style="font-size: 1.1rem;">{{ $referral->reward_type }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Reward Amount</span>
                    <strong class="text-success" style="font-size: 1.1rem;">₹{{ number_format($referral->reward_amount, 2) }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Status</span>
                    @php
                        $badgeClass = 'badge-secondary';
                        switch($referral->reward_status) {
                            case 'Pending': $badgeClass = 'badge-warning'; break;
                            case 'Eligible': $badgeClass = 'badge-info'; break;
                            case 'Rewarded': $badgeClass = 'badge-success'; break;
                            case 'Rejected': $badgeClass = 'badge-danger'; break;
                        }
                    @endphp
                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2 mt-1">{{ $referral->reward_status }}</span>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Associated Purchase</span>
                    @if($referral->booking)
                        <a href="{{ route('bookings.show', $referral->booking->id) }}" class="text-primary font-weight-bold mt-1 d-inline-block">
                            {{ $referral->booking->booking_number }} (₹{{ number_format($referral->booking->grand_total, 2) }})
                        </a>
                    @else
                        <span class="text-muted mt-1 d-inline-block">None</span>
                    @endif
                </div>
                @if($referral->remarks)
                <div class="col-12 mt-2">
                    <span class="text-muted d-block">Remarks</span>
                    <p class="text-dark bg-light p-3 border rounded mb-0">{{ $referral->remarks }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Referrer & Referred Profiles -->
        <div class="row">
            <!-- Referrer Customer -->
            <div class="col-md-6 mb-4">
                <div class="card bg-white border shadow-sm p-4 h-100">
                    <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">
                        <i class="mdi mdi-account-arrow-left text-info mr-1"></i> Referrer Details
                    </h5>
                    @if($referral->referrer)
                        <p class="mb-1"><strong>Name:</strong> {{ $referral->referrer->name }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $referral->referrer->email }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $referral->referrer->phone ?? 'N/A' }}</p>
                        <p class="mb-0"><strong>Status:</strong> <span class="badge badge-success">{{ $referral->referrer->status }}</span></p>
                    @else
                        <p class="text-muted mb-0">No referrer customer associated.</p>
                    @endif
                </div>
            </div>

            <!-- Referred Customer -->
            <div class="col-md-6 mb-4">
                <div class="card bg-white border shadow-sm p-4 h-100">
                    <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">
                        <i class="mdi mdi-account-arrow-right text-success mr-1"></i> Referred Customer
                    </h5>
                    @if($referral->referred)
                        <p class="mb-1"><strong>Name:</strong> {{ $referral->referred->name }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $referral->referred->email }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $referral->referred->phone ?? 'N/A' }}</p>
                        <p class="mb-0"><strong>Status:</strong> <span class="badge badge-success">{{ $referral->referred->status }}</span></p>
                    @else
                        <p class="text-muted mb-0">No referred customer associated.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline Sidebar -->
    <div class="col-md-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Activity Timeline</h5>
            <div class="timeline-wrapper" style="max-height: 450px; overflow-y: auto;">
                <ul class="list-unstyled pl-0">
                    @forelse($activityLogs as $log)
                        <li class="border-left pl-3 pb-3 position-relative">
                            <span class="position-absolute bg-primary rounded-circle" style="left:-6px; top:4px; width:12px; height:12px; border:2px solid #fff;"></span>
                            <div class="d-flex justify-content-between">
                                <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">{{ ucfirst(str_replace('_', ' ', $log->action_type)) }}</span>
                                <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="text-muted mb-0 text-xs mt-1" style="font-size: 0.8rem;">{{ $log->description }}</p>
                            <small class="text-muted text-xs d-block mt-1" style="font-size: 0.75rem;">By: {{ $log->user->name ?? 'System' }}</small>
                        </li>
                    @empty
                        <li class="text-muted py-3">No activity logs recorded.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
