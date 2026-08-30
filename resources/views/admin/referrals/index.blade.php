@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Customer Referrals</h4>
                    <p class="card-description text-muted mb-0">Monitor and manage employee-to-customer and customer-to-customer referral associations, booking linkages, and cashback processing.</p>
                </div>
                <div>
                    @if(hasPermission('referral.export'))
                    <a href="{{ route('referrals.export', request()->all()) }}" class="btn btn-success px-4">
                        <i class="mdi mdi-export mr-1"></i> Export (CSV)
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="col-12 mb-4">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card bg-white border shadow-sm text-center py-3">
                    <h6 class="text-muted mb-1 font-weight-bold">Total Referrals</h6>
                    <h3 class="font-weight-bold text-primary mb-0">{{ $totalReferrals }}</h3>
                    <small class="text-muted">Total associations logged</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-white border shadow-sm text-center py-3">
                    <h6 class="text-muted mb-1 font-weight-bold">Completed Cashback</h6>
                    <h3 class="font-weight-bold text-success mb-0">₹{{ number_format($completedCashbackSum, 2) }}</h3>
                    <small class="text-muted">{{ $completedCount }} paid out</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-white border shadow-sm text-center py-3">
                    <h6 class="text-muted mb-1 font-weight-bold">Pending Cashback</h6>
                    <h3 class="font-weight-bold text-warning mb-0">₹{{ number_format($pendingCashbackSum, 2) }}</h3>
                    <small class="text-muted">{{ $pendingCount + $underReviewCount + $approvedCount }} awaiting processing</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-white border shadow-sm text-center py-3">
                    <h6 class="text-muted mb-1 font-weight-bold">Rejected / Cancelled</h6>
                    <h3 class="font-weight-bold text-danger mb-0">{{ $rejectedCount }}</h3>
                    <small class="text-muted">Referrals disqualified</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Referrals</h5>
            <form action="{{ route('referrals.index') }}" method="GET" class="row align-items-end">
                <!-- Search Query -->
                <div class="col-md-3 form-group mb-3">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" 
                           placeholder="Referral Code, Booking, Name, Phone, UTR..." 
                           value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-2 form-group mb-3">
                    <label class="text-dark font-weight-bold">Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Under Review" {{ request('status') === 'Under Review' ? 'selected' : '' }}>Under Review</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <!-- From Date -->
                <div class="col-md-2 form-group mb-3">
                    <label class="text-dark font-weight-bold">From Date</label>
                    <input type="date" name="from_date" class="form-control bg-white text-dark" 
                           value="{{ request('from_date', request('start_date')) }}">
                </div>

                <!-- To Date -->
                <div class="col-md-2 form-group mb-3">
                    <label class="text-dark font-weight-bold">To Date</label>
                    <input type="date" name="to_date" class="form-control bg-white text-dark" 
                           value="{{ request('to_date', request('end_date')) }}">
                </div>

                <!-- Action Buttons -->
                <div class="col-md-3 form-group mb-3 d-flex">
                    <button type="submit" class="btn btn-primary btn-block mr-2 px-3">
                        <i class="mdi mdi-filter mr-1"></i> Apply Filter
                    </button>
                    <a href="{{ route('referrals.index') }}" class="btn btn-secondary px-3" title="Reset Filter">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Listings Table Card -->
    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-bordered table-striped text-dark" style="white-space: nowrap; width: 100%;">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="font-weight-bold text-center">Sr. No.</th>
                            <th class="font-weight-bold">Referral Date</th>
                            <th class="font-weight-bold">Referral Code Used</th>
                            <th class="font-weight-bold">Referrer Customer / Staff</th>
                            <th class="font-weight-bold">Referrer Type</th>
                            <th class="font-weight-bold">Referred Customer</th>
                            <th class="font-weight-bold">Booking ID</th>
                            <th class="font-weight-bold text-right">Gold Weight</th>
                            <th class="font-weight-bold text-right">Cashback Rate</th>
                            <th class="font-weight-bold text-right">Cashback Amount</th>
                            <th class="font-weight-bold text-center">Status</th>
                            <th class="font-weight-bold">Payment Reference</th>
                            <th class="font-weight-bold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referrals as $index => $ref)
                            @php
                                $statusBadgeClass = match($ref->status) {
                                    'Completed' => 'badge-success',
                                    'Pending' => 'badge-warning text-dark',
                                    'Under Review' => 'badge-info',
                                    'Approved' => 'badge-primary',
                                    'Rejected' => 'badge-danger',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="text-center font-weight-bold">
                                    {{ (($referrals->currentPage() - 1) * $referrals->perPage()) + $loop->iteration }}
                                </td>
                                <td>{{ $ref->referred_at ? $ref->referred_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td class="font-weight-bold text-primary">{{ $ref->referral_code }}</td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $ref->referrer->name ?? 'N/A' }}</div>
                                    <small class="text-muted">ID: #{{ $ref->referrer->id ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    @php
                                        $type = $ref->referrer_type ? ucfirst($ref->referrer_type) : ($ref->staff_id ? 'Staff' : 'Customer');
                                        $badgeClass = $type === 'Staff' ? 'badge-info' : 'badge-primary';
                                    @endphp
                                    <span class="badge {{ $badgeClass }} font-weight-bold">{{ $type }}</span>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $ref->customer->name ?? 'N/A' }}</div>
                                    <small class="text-muted">ID: #{{ $ref->customer->id ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    @if($ref->booking)
                                        <span class="font-weight-bold text-dark">#{{ $ref->booking->booking_number }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ $ref->gold_weight ? number_format($ref->gold_weight, 3) . ' g' : 'N/A' }}</td>
                                <td class="text-right">{{ $ref->cashback_rate ? '₹' . number_format($ref->cashback_rate, 2) : 'N/A' }}</td>
                                <td class="text-right font-weight-bold text-dark">{{ $ref->cashback_amount ? '₹' . number_format($ref->cashback_amount, 2) : 'N/A' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $statusBadgeClass }} font-weight-bold px-3 py-2">
                                        {{ $ref->status }}
                                    </span>
                                </td>
                                <td class="font-weight-bold">
                                    {{ $ref->payment_reference_number ?? 'N/A' }}
                                </td>
                                <td class="text-center">
                                    @if(hasPermission('referral.view'))
                                    <a href="{{ route('referrals.show', $ref->id) }}" class="btn btn-sm btn-info px-3 font-weight-bold">
                                        <i class="mdi mdi-eye mr-1"></i> View Details
                                    </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-5 text-muted">
                                    <i class="mdi mdi-account-search mdi-36px d-block mb-2 text-secondary"></i>
                                    No customer referrals found matching your query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-muted small mb-2">
                    Showing {{ $referrals->firstItem() ?? 0 }} to {{ $referrals->lastItem() ?? 0 }} of {{ $referrals->total() }} entries
                </div>
                <div>
                    {{ $referrals->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
