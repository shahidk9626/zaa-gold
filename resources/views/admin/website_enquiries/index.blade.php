@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Website Contact Enquiries</h4>
                    <p class="card-description text-muted mb-0">Review visitor requests, track follow-ups, and update status and remarks.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Enquiries</h5>
            <form action="{{ route('website-enquiries.index') }}" method="GET" class="row">
                <!-- Search Input -->
                <div class="col-md-4 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Name, Email, Phone, Subject..." value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        @foreach(['New', 'In Progress', 'Contacted', 'Resolved', 'Closed'] as $st)
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
                    <a href="{{ route('website-enquiries.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
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
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enquiries as $enq)
                            <tr>
                                <td class="font-weight-bold text-dark">{{ $enq->name }}</td>
                                <td>{{ $enq->email }}</td>
                                <td>{{ $enq->phone }}</td>
                                <td>{{ $enq->subject }}</td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-secondary';
                                        switch($enq->status) {
                                            case 'New': $badgeClass = 'badge-warning'; break;
                                            case 'In Progress': $badgeClass = 'badge-primary'; break;
                                            case 'Contacted': $badgeClass = 'badge-info'; break;
                                            case 'Resolved': $badgeClass = 'badge-success'; break;
                                            case 'Closed': $badgeClass = 'badge-dark'; break;
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $enq->status }}</span>
                                </td>
                                <td>{{ $enq->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('website-enquiries.show', $enq->id) }}" class="btn btn-sm btn-info px-3">
                                        <i class="mdi mdi-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No website enquiries logged yet.
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
