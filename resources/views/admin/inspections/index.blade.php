@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Gold Inspection Enquiries</h4>
                    <p class="card-description text-muted mb-0">Review requests for used gold sales, track follow-ups, and transition status.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Inspections</h5>
            <form action="{{ route('inspections.index') }}" method="GET" class="row">
                <!-- Search Input -->
                <div class="col-md-4 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Name, Phone, ID, Gold type..." value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        @foreach(['New', 'Contacted', 'Inspection Scheduled', 'Inspection Completed', 'Converted', 'Rejected'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
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
                    <a href="{{ route('inspections.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
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
                            <th>Sr. No.</th>
                            <th>Inspection ID</th>
                            <th>Customer Name</th>
                            <th>Mobile Number</th>
                            <th>Gold Type</th>
                            <th>Approx. Grams</th>
                            <th>Location</th>
                            <th>Preferred Date</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enquiries as $index => $enq)
                            <tr>
                                <td>{{ $enquiries->firstItem() + $index }}</td>
                                <td class="font-weight-bold">INSP-{{ str_pad($enq->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td class="font-weight-bold text-dark">{{ $enq->name }}</td>
                                <td>{{ $enq->phone }}</td>
                                <td>{{ $enq->gold_type }}</td>
                                <td>{{ number_format($enq->approx_grams, 2) }} g</td>
                                <td>{{ $enq->gold_location }}</td>
                                <td>{{ $enq->preferred_date ? $enq->preferred_date->format('d M Y') : 'N/A' }}</td>
                                <td>{{ $enq->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-secondary';
                                        switch($enq->status) {
                                            case 'New': $badgeClass = 'badge-warning'; break;
                                            case 'Contacted': $badgeClass = 'badge-info'; break;
                                            case 'Inspection Scheduled': $badgeClass = 'badge-primary'; break;
                                            case 'Inspection Completed': $badgeClass = 'badge-success'; break;
                                            case 'Converted': $badgeClass = 'badge-success'; break;
                                            case 'Rejected': $badgeClass = 'badge-danger'; break;
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $enq->status }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('inspections.show', $enq->id) }}" class="btn btn-sm btn-info px-3">
                                        <i class="mdi mdi-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No inspection enquiries logged yet.
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
