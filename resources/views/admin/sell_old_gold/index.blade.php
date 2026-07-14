@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Old Gold Purchase Enquiries</h4>
                    <p class="card-description text-muted mb-0">Review valuation requests, schedule inspections, assign staff, and track follow-ups.</p>
                </div>
                <div>
                    @if(hasPermission('sell-old-gold.export'))
                    <a href="{{ route('sell-old-gold.export', request()->all()) }}" class="btn btn-success px-4 mr-2">
                        <i class="mdi mdi-export mr-1"></i> Export list (CSV)
                    </a>
                    @endif
                    @if(hasPermission('sell-old-gold.edit'))
                    <a href="{{ route('sell-old-gold.create') }}" class="btn btn-primary px-4">
                        <i class="mdi mdi-plus-circle mr-1"></i> Log Enquiry
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Enquiries</h5>
            <form action="{{ route('sell-old-gold.index') }}" method="GET" class="row">
                <!-- Search Input -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Name, Phone, City..." value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        @foreach(['New', 'Contacted', 'Inspection Scheduled', 'Quoted', 'Accepted', 'Rejected', 'Closed'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Assigned To Filter -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Assigned Staff</label>
                    <select name="assigned_to" class="form-control bg-white text-dark">
                        <option value="">All Staff</option>
                        @foreach($staffMembers as $staff)
                            <option value="{{ $staff->id }}" {{ request('assigned_to') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
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
                    <a href="{{ route('sell-old-gold.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
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
                            <th>Customer Name</th>
                            <th>Contact Info</th>
                            <th>Gold Type</th>
                            <th>Est. Weight</th>
                            <th>Est. Value</th>
                            <th>Assigned Staff</th>
                            <th>Follow-up Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enquiries as $enq)
                            <tr>
                                <td class="font-weight-bold text-dark">{{ $enq->customer_name }}</td>
                                <td>
                                    <div>Mobile: {{ $enq->mobile }}</div>
                                    <small class="text-muted">{{ $enq->email }}</small>
                                </td>
                                <td>{{ $enq->gold_type }}</td>
                                <td>{{ number_format($enq->estimated_weight, 2) }} g</td>
                                <td class="font-weight-bold text-success">₹{{ $enq->estimated_value ? number_format($enq->estimated_value, 2) : '0.00' }}</td>
                                <td>
                                    @if($enq->assignedStaff)
                                        <span class="text-dark font-weight-bold">{{ $enq->assignedStaff->name }}</span>
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    @if($enq->followup_date)
                                        <span class="text-primary font-weight-bold">{{ $enq->followup_date->format('d M Y') }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-secondary';
                                        switch($enq->status) {
                                            case 'New': $badgeClass = 'badge-warning'; break;
                                            case 'Contacted': $badgeClass = 'badge-info'; break;
                                            case 'Inspection Scheduled': $badgeClass = 'badge-primary'; break;
                                            case 'Quoted': $badgeClass = 'badge-secondary'; break;
                                            case 'Accepted': $badgeClass = 'badge-success'; break;
                                            case 'Rejected': $badgeClass = 'badge-danger'; break;
                                            case 'Closed': $badgeClass = 'badge-dark'; break;
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $enq->status }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('sell-old-gold.show', $enq->id) }}" class="btn btn-sm btn-info px-3">
                                        <i class="mdi mdi-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No old gold enquiries logged yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            <div class="mt-4 d-flex justify-content-end">
                {{ $enquiries->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
