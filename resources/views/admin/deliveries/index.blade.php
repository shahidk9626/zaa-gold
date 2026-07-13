@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Delivery & Fulfillment Dashboard</h4>
                    <p class="card-description text-muted mb-0">Monitor, approve, and track physical gold dispatches and pickups once booking payments are completed.</p>
                </div>
                <div>
                    @if(hasPermission('delivery.export'))
                    <a href="{{ route('deliveries.export', request()->all()) }}" class="btn btn-success px-4">
                        <i class="mdi mdi-export mr-1"></i> Export Deliveries (CSV)
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Requests</h5>
            <form action="{{ route('deliveries.index') }}" method="GET" class="row">
                <!-- Search -->
                <div class="col-md-4 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Delivery No, Customer Name, Booking No..." value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-4 form-group">
                    <label class="text-dark font-weight-bold">Delivery Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        @foreach(['Requested', 'Approved', 'Ready For Dispatch', 'Dispatched', 'Out For Delivery', 'Delivered', 'Cancelled', 'Returned'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Method Filter -->
                <div class="col-md-4 form-group">
                    <label class="text-dark font-weight-bold">Delivery Method</label>
                    <select name="method" class="form-control bg-white text-dark">
                        <option value="">All Methods</option>
                        @foreach(['Office Pickup', 'Courier', 'Branch Pickup'] as $method)
                            <option value="{{ $method }}" {{ request('method') === $method ? 'selected' : '' }}>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-12 mt-2 d-flex justify-content-end">
                    <a href="{{ route('deliveries.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
                    <button type="submit" class="btn btn-info px-4">Search & Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Listings Table Card -->
    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-dark small">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th>Delivery Number</th>
                            <th>Booking Ref</th>
                            <th>Customer</th>
                            <th>Delivery Method</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveries as $delivery)
                            <tr>
                                <td class="font-weight-bold text-primary">{{ $delivery->delivery_number }}</td>
                                <td>
                                    <a href="{{ route('bookings.show', $delivery->booking_id) }}" class="font-weight-bold text-decoration-none">
                                        {{ $delivery->booking->booking_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $delivery->customer->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $delivery->customer->email ?? 'N/A' }}</small>
                                </td>
                                <td><span class="badge badge-outline-dark text-dark">{{ $delivery->delivery_method }}</span></td>
                                <td>{{ $delivery->request_date ? $delivery->request_date->format('d M Y, h:i A') : '—' }}</td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-secondary';
                                        switch($delivery->delivery_status) {
                                            case 'Requested': $badgeClass = 'badge-warning'; break;
                                            case 'Approved': $badgeClass = 'badge-info'; break;
                                            case 'Ready For Dispatch': $badgeClass = 'badge-primary'; break;
                                            case 'Dispatched': $badgeClass = 'badge-secondary'; break;
                                            case 'Out For Delivery': $badgeClass = 'badge-dark'; break;
                                            case 'Delivered': $badgeClass = 'badge-success'; break;
                                            case 'Cancelled': $badgeClass = 'badge-danger'; break;
                                            case 'Returned': $badgeClass = 'badge-light text-dark'; break;
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $delivery->delivery_status }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('deliveries.show', $delivery->id) }}" class="btn btn-sm btn-info px-2 py-1 mr-1" title="View Details">
                                        <i class="mdi mdi-eye"></i> View
                                    </a>
                                    @if($delivery->pdf_path && hasPermission('delivery.download'))
                                    <a href="{{ route('deliveries.download', $delivery->id) }}" class="btn btn-sm btn-success px-2 py-1" title="Download Challan">
                                        <i class="mdi mdi-file-pdf"></i> PDF
                                    </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No delivery records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 d-flex justify-content-end">
                {{ $deliveries->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
