@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Referral Program Directory</h4>
                    <p class="card-description text-muted mb-0">Track and reward customer referrals, update statuses, and export histories.</p>
                </div>
                <div>
                    @if(hasPermission('referral.export'))
                    <a href="{{ route('referrals.export', request()->all()) }}" class="btn btn-success px-4 mr-2">
                        <i class="mdi mdi-export mr-1"></i> Export list (CSV)
                    </a>
                    @endif
                    @if(hasPermission('referral.edit'))
                    <a href="{{ route('referrals.create') }}" class="btn btn-primary px-4">
                        <i class="mdi mdi-plus-circle mr-1"></i> Add Referral
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Referrals</h5>
            <form action="{{ route('referrals.index') }}" method="GET" class="row">
                <!-- Search Input -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Code, Customer name, phone..." value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        @foreach(['Pending', 'Eligible', 'Rewarded', 'Rejected'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reward Type Filter -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Reward Type</label>
                    <select name="reward_type" class="form-control bg-white text-dark">
                        <option value="">All Types</option>
                        @foreach(['Cash', 'Gold Grams', 'Discount'] as $rt)
                            <option value="{{ $rt }}" {{ request('reward_type') === $rt ? 'selected' : '' }}>{{ $rt }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Start Date -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">From Date</label>
                    <input type="date" name="start_date" class="form-control bg-white text-dark" value="{{ request('start_date') }}">
                </div>

                <!-- End Date -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">To Date</label>
                    <input type="date" name="end_date" class="form-control bg-white text-dark" value="{{ request('end_date') }}">
                </div>

                <!-- Action Buttons -->
                <div class="col-12 mt-2 d-flex justify-content-end">
                    <a href="{{ route('referrals.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
                    <button type="submit" class="btn btn-info px-4">Search & Filter</button>
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
                </div>
            @endif
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-dark">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th>Referral Code</th>
                            <th>Referrer Customer</th>
                            <th>Referred Customer</th>
                            <th>Booking Number</th>
                            <th>Reward Type</th>
                            <th>Reward Amount</th>
                            <th>Created Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referrals as $ref)
                            <tr>
                                <td class="font-weight-bold text-primary">{{ $ref->referral_code }}</td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $ref->referrer->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $ref->referrer->email ?? '' }}</small>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $ref->referred->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $ref->referred->email ?? '' }}</small>
                                </td>
                                <td>
                                    @if($ref->booking)
                                        <a href="{{ route('bookings.show', $ref->booking->id) }}" class="text-primary font-weight-bold">
                                            {{ $ref->booking->booking_number }}
                                        </a>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td>{{ $ref->reward_type }}</td>
                                <td class="font-weight-bold text-success">₹{{ number_format($ref->reward_amount, 2) }}</td>
                                <td>{{ $ref->created_at->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-secondary';
                                        switch($ref->reward_status) {
                                            case 'Pending': $badgeClass = 'badge-warning'; break;
                                            case 'Eligible': $badgeClass = 'badge-info'; break;
                                            case 'Rewarded': $badgeClass = 'badge-success'; break;
                                            case 'Rejected': $badgeClass = 'badge-danger'; break;
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $ref->reward_status }}</span>
                                </td>
                                <td>
                                    @if(hasPermission('referral.view'))
                                    <a href="{{ route('referrals.show', $ref->id) }}" class="btn btn-sm btn-info px-3">
                                        <i class="mdi mdi-eye"></i> View
                                    </a>
                                    @endif
                                    @if(hasPermission('referral.edit'))
                                    <a href="{{ route('referrals.edit', $ref->id) }}" class="btn btn-sm btn-primary px-3">
                                        <i class="mdi mdi-pencil"></i> Edit
                                    </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No referrals found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            <div class="mt-4 d-flex justify-content-end">
                {{ $referrals->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
