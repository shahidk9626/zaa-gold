@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Plan Cancellation Requests</h4>
                        <p class="card-description text-muted">Review, approve, reject, or process refunds for gold purchase plan cancellations</p>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Search & Filters -->
                <form action="{{ route('admin.cancellations.index') }}" method="GET" class="row mb-4">
                    <div class="col-md-5 form-group mb-2 mb-md-0">
                        <div class="input-group">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-white text-dark border" placeholder="Search Request No, Booking No, Customer...">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i> Search</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 form-group mb-2 mb-md-0">
                        <select name="status" class="form-control bg-white text-dark border" onchange="this.form.submit()">
                            <option value="">-- Filter Status --</option>
                            <option value="Requested" {{ request('status') === 'Requested' ? 'selected' : '' }}>Requested</option>
                            <option value="Under Review" {{ request('status') === 'Under Review' ? 'selected' : '' }}>Under Review</option>
                            <option value="Customer Retained" {{ request('status') === 'Customer Retained' ? 'selected' : '' }}>Customer Retained</option>
                            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                            <option value="Refund Initiated" {{ request('status') === 'Refund Initiated' ? 'selected' : '' }}>Refund Initiated</option>
                            <option value="Refund Completed" {{ request('status') === 'Refund Completed' ? 'selected' : '' }}>Refund Completed</option>
                            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group mb-0 d-flex align-items-center">
                        @if(request()->anyFilled(['search', 'status']))
                            <a href="{{ route('admin.cancellations.index') }}" class="btn btn-link text-danger p-0 ml-2 font-weight-bold"><i class="mdi mdi-close"></i> Clear Filters</a>
                        @endif
                    </div>
                </form>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table table-hover text-dark">
                        <thead class="bg-light">
                            <tr>
                                <th>Request Number</th>
                                <th>Booking Number</th>
                                <th>Customer</th>
                                <th>Gold Plan</th>
                                <th class="text-right">Paid Amount</th>
                                <th class="text-right">Refund Amount</th>
                                <th>Requested Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                                <tr>
                                    <td class="align-middle font-weight-bold">{{ $req->request_number }}</td>
                                    <td class="align-middle">{{ $req->booking?->booking_number }}</td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold">{{ $req->customer?->name }}</div>
                                        <div class="small text-muted">{{ $req->customer?->email }}</div>
                                    </td>
                                    <td class="align-middle">{{ $req->booking?->emiPlan?->plan_name }}</td>
                                    <td class="align-middle text-right font-weight-bold">₹{{ number_format($req->total_amount_paid, 2) }}</td>
                                    <td class="align-middle text-right text-success font-weight-bold">₹{{ number_format($req->refund_amount, 2) }}</td>
                                    <td class="align-middle">{{ $req->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="align-middle text-center">
                                        @php
                                            $badge = 'secondary';
                                            if ($req->status === 'Requested') $badge = 'info';
                                            elseif ($req->status === 'Under Review') $badge = 'warning';
                                            elseif ($req->status === 'Customer Retained' || $req->status === 'Refund Completed') $badge = 'success';
                                            elseif ($req->status === 'Approved' || $req->status === 'Refund Initiated') $badge = 'primary';
                                            elseif ($req->status === 'Rejected') $badge = 'danger';
                                        @endphp
                                        <span class="badge badge-{{ $badge }}">{{ $req->status }}</span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <a href="{{ route('admin.cancellations.show', $req->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="mdi mdi-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No cancellation requests found matching the parameters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
