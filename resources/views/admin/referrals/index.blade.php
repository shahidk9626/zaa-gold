@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Customer Referrals</h4>
                    <p class="card-description text-muted mb-0">Monitor and manage employee-to-customer referral associations and registration histories.</p>
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

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Referrals</h5>
            <form action="{{ route('referrals.index') }}" method="GET" class="row align-items-end">
                <!-- Search Query -->
                <div class="col-md-4 form-group mb-3">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" 
                           placeholder="Referral Code, Employee / Customer Name, Phone, Email..." 
                           value="{{ request('search') }}">
                </div>

                <!-- From Date -->
                <div class="col-md-3 form-group mb-3">
                    <label class="text-dark font-weight-bold">From Date</label>
                    <input type="date" name="from_date" class="form-control bg-white text-dark" 
                           value="{{ request('from_date', request('start_date')) }}">
                </div>

                <!-- To Date -->
                <div class="col-md-3 form-group mb-3">
                    <label class="text-dark font-weight-bold">To Date</label>
                    <input type="date" name="to_date" class="form-control bg-white text-dark" 
                           value="{{ request('to_date', request('end_date')) }}">
                </div>

                <!-- Action Buttons -->
                <div class="col-md-2 form-group mb-3 d-flex">
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
                            <th class="font-weight-bold">Referrer Name</th>
                            <th class="font-weight-bold">Referrer Type</th>
                            <th class="font-weight-bold">Referrer Code</th>
                            <th class="font-weight-bold">Customer Name</th>
                            <th class="font-weight-bold">Customer ID</th>
                            <th class="font-weight-bold">Customer Mobile</th>
                            <th class="font-weight-bold">Customer Email</th>
                            <th class="font-weight-bold">Customer Registration Date</th>
                            <th class="font-weight-bold text-center">Status</th>
                            <th class="font-weight-bold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referrals as $index => $ref)
                            <tr>
                                <td class="text-center font-weight-bold">
                                    {{ (($referrals->currentPage() - 1) * $referrals->perPage()) + $loop->iteration }}
                                </td>
                                <td>{{ $ref->referred_at ? $ref->referred_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td class="font-weight-bold text-primary">{{ $ref->referral_code }}</td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $ref->referrer->name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    @php
                                        $type = $ref->referrer_type ? ucfirst($ref->referrer_type) : ($ref->staff_id ? 'Staff' : 'Customer');
                                        $badgeClass = $type === 'Staff' ? 'badge-info' : 'badge-primary';
                                    @endphp
                                    <span class="badge {{ $badgeClass }} font-weight-bold">{{ $type }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-outline-dark font-weight-bold">
                                        {{ $ref->referrer->referral_code ?? ($ref->referrer->staffDetail->emp_code ?? 'N/A') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $ref->customer->name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-light border font-weight-bold">#{{ $ref->customer->id ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $ref->customer->phone ?? 'N/A' }}</td>
                                <td>{{ $ref->customer->email ?? 'N/A' }}</td>
                                <td>{{ $ref->customer && $ref->customer->created_at ? $ref->customer->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td class="text-center">
                                    @php
                                        $status = $ref->customer->status ?? 'active';
                                        $badgeClass = $status === 'active' ? 'badge-success' : 'badge-danger';
                                    @endphp
                                    <span class="badge {{ $badgeClass }} font-weight-bold px-3 py-2">
                                        {{ ucfirst($status) }}
                                    </span>
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
                                <td colspan="12" class="text-center py-5 text-muted">
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
