@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Gold Bookings Directory</h4>
                    <p class="card-description text-muted mb-0">Monitor transactions, lock-in certificates, and status timelines.</p>
                </div>
                <div>
                    @if(hasPermission('booking.export'))
                    <a href="{{ route('bookings.export', request()->all()) }}" class="btn btn-success px-4 mr-2">
                        <i class="mdi mdi-export mr-1"></i> Export list (CSV)
                    </a>
                    @endif
                    @if(hasPermission('purchase-preview.view'))
                    <a href="{{ route('purchase-preview.index') }}" class="btn btn-primary px-4">
                        <i class="mdi mdi-plus-circle mr-1"></i> New Gold Booking
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Bookings</h5>
            <form action="{{ route('bookings.index') }}" method="GET" class="row">
                <!-- Search Input -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Booking No, Customer, SKU..." value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        @foreach(['Draft', 'Pending First EMI', 'Active', 'Completed', 'Cancelled', 'Refund Initiated', 'Refunded'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Product Filter -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Product</label>
                    <select name="product_id" class="form-control bg-white text-dark select2-selector">
                        <option value="">All Products</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>{{ $prod->name }} ({{ $prod->sku }})</option>
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
                    <a href="{{ route('bookings.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
                    <button type="submit" class="btn btn-info px-4">Search & Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Listings Table Card -->
    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-dark">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th>Booking Number</th>
                            <th>Customer Name</th>
                            <th>Product Name</th>
                            <th>Gold Weight</th>
                            <th>Locked Gold Price</th>
                            <th>Monthly EMI</th>
                            <th>Grand Total</th>
                            <th>Booking Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td class="font-weight-bold text-primary">{{ $booking->booking_number }}</td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $booking->customer->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $booking->customer->email ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $booking->product->name ?? 'N/A' }}</div>
                                    <small class="badge badge-secondary">{{ $booking->product->gold_type ?? 'N/A' }}</small>
                                </td>
                                <td>{{ number_format($booking->gold_weight, 2) }}g</td>
                                <td>₹{{ number_format($booking->locked_price_per_gram, 2) }}/g</td>
                                <td class="font-weight-bold text-primary">₹{{ number_format($booking->monthly_emi, 2) }}</td>
                                <td class="font-weight-bold text-success">₹{{ number_format($booking->grand_total, 2) }}</td>
                                <td>{{ $booking->booking_date->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-secondary';
                                        switch($booking->status) {
                                            case 'Draft': $badgeClass = 'badge-secondary'; break;
                                            case 'Pending First EMI': $badgeClass = 'badge-warning'; break;
                                            case 'Active': $badgeClass = 'badge-primary'; break;
                                            case 'Completed': $badgeClass = 'badge-success'; break;
                                            case 'Cancelled': $badgeClass = 'badge-danger'; break;
                                            case 'Refund Initiated': $badgeClass = 'badge-info'; break;
                                            case 'Refunded': $badgeClass = 'badge-dark'; break;
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $booking->status }}</span>
                                </td>
                                <td>
                                    @if(hasPermission('booking.view_details'))
                                    <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-sm btn-info px-3">
                                        <i class="mdi mdi-eye"></i> View Details
                                    </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No gold bookings found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            <div class="mt-4 d-flex justify-content-end">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
