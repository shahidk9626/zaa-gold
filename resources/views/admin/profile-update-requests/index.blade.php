@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Customer Profile Update Requests</h4>
                    <p class="card-description text-muted mb-0">Review and approve or reject profile update requests submitted by KYC-verified customers.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Requests</h5>
            <form action="{{ route('admin.profile-update-requests.index') }}" method="GET" class="row align-items-end">
                <!-- Search Query -->
                <div class="col-md-3 form-group mb-3">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" 
                           placeholder="Customer Name, ID, Mobile, Email..." 
                           value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-3 form-group mb-3">
                    <label class="text-dark font-weight-bold">Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
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
                <div class="col-md-2 form-group mb-3 d-flex">
                    <button type="submit" class="btn btn-primary btn-block mr-2 px-3">
                        <i class="mdi mdi-filter mr-1"></i> Apply Filter
                    </button>
                    <a href="{{ route('admin.profile-update-requests.index') }}" class="btn btn-secondary px-3" title="Reset Filter">
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
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
                </div>
            @endif
            
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-bordered table-striped text-dark" style="white-space: nowrap; width: 100%;">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="font-weight-bold text-center">Sr. No.</th>
                            <th class="font-weight-bold">Request Date</th>
                            <th class="font-weight-bold">Customer Name</th>
                            <th class="font-weight-bold">Customer ID</th>
                            <th class="font-weight-bold">Mobile</th>
                            <th class="font-weight-bold">Email</th>
                            <th class="font-weight-bold">Requested Fields</th>
                            <th class="font-weight-bold">Reason</th>
                            <th class="font-weight-bold text-center">Status</th>
                            <th class="font-weight-bold">Reviewed Date</th>
                            <th class="font-weight-bold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $index => $req)
                            <tr>
                                <td class="text-center font-weight-bold">
                                    {{ (($requests->currentPage() - 1) * $requests->perPage()) + $loop->iteration }}
                                </td>
                                <td>{{ $req->created_at ? $req->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $req->customer->name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-light border font-weight-bold">#{{ $req->customer->id ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $req->customer->phone ?? 'N/A' }}</td>
                                <td>{{ $req->customer->email ?? 'N/A' }}</td>
                                <td>
                                    @if(is_array($req->requested_changes) && count($req->requested_changes) > 0)
                                        @php
                                            $labels = array_column($req->requested_changes, 'field_label');
                                        @endphp
                                        <span class="badge badge-outline-primary font-weight-bold">
                                            {{ implode(', ', array_slice($labels, 0, 3)) }}
                                            @if(count($labels) > 3)
                                                +{{ count($labels) - 3 }} more
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-dark small" title="{{ $req->reason }}">{{ Str::limit($req->reason, 40) }}</span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $stBadge = 'badge-secondary';
                                        if($req->status === 'Pending') $stBadge = 'badge-warning text-dark';
                                        elseif($req->status === 'Approved') $stBadge = 'badge-success';
                                        elseif($req->status === 'Rejected') $stBadge = 'badge-danger';
                                    @endphp
                                    <span class="badge {{ $stBadge }} font-weight-bold px-3 py-2">
                                        {{ $req->status }}
                                    </span>
                                </td>
                                <td>
                                    {{ $req->reviewed_at ? $req->reviewed_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.profile-update-requests.show', $req->id) }}" class="btn btn-sm btn-info px-3 font-weight-bold">
                                        <i class="mdi mdi-eye mr-1"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="mdi mdi-account-search mdi-36px d-block mb-2 text-secondary"></i>
                                    No profile update requests found matching your query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-muted small mb-2">
                    Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} entries
                </div>
                <div>
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
