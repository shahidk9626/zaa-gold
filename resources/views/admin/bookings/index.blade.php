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
            <form id="searchFilterForm" class="row">
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
                <table id="bookingsTable" class="table table-bordered table-striped text-dark">
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let table;
    $(document).ready(function () {
        table = $('#bookingsTable').DataTable({
            processing: true,
            ajax: {
                url: "{{ route('bookings.index') }}",
                type: 'GET',
                data: function (d) {
                    d.search_query = $('input[name=search]').val();
                    d.status = $('select[name=status]').val();
                    d.product_id = $('select[name=product_id]').val();
                    d.start_date = $('input[name=start_date]').val();
                    d.end_date = $('input[name=end_date]').val();
                }
            },
            columns: [
                { data: 'booking_number', className: 'font-weight-bold text-primary align-middle' },
                { 
                    data: null, 
                    className: 'align-middle',
                    render: function (data) {
                        return `<div class="font-weight-bold text-dark">${data.customer_name}</div>
                                <small class="text-muted">${data.customer_email}</small>`;
                    }
                },
                { 
                    data: null, 
                    className: 'align-middle',
                    render: function (data) {
                        return `<div class="font-weight-bold text-dark">${data.product_name}</div>
                                <small class="badge badge-secondary">${data.product_gold_type}</small>`;
                    }
                },
                { data: 'gold_weight', className: 'align-middle' },
                { data: 'locked_price_per_gram', className: 'align-middle' },
                { data: 'monthly_emi', className: 'font-weight-bold text-primary align-middle' },
                { data: 'grand_total', className: 'font-weight-bold text-success align-middle' },
                { data: 'booking_date', className: 'align-middle' },
                { 
                    data: 'status', 
                    className: 'align-middle text-center',
                    render: function (data) {
                        let badgeStyle = 'background-color: #6c757d; color: #ffffff;';
                        switch(data) {
                            case 'Draft': badgeStyle = 'background-color: #6c757d; color: #ffffff;'; break;
                            case 'Pending First EMI': badgeStyle = 'background-color: #ffc107; color: #000000;'; break;
                            case 'Active': badgeStyle = 'background-color: #28a745; color: #ffffff;'; break;
                            case 'Completed': badgeStyle = 'background-color: #28a745; color: #ffffff;'; break;
                            case 'Cancelled': badgeStyle = 'background-color: #dc3545; color: #ffffff;'; break;
                            case 'Refund Initiated': badgeStyle = 'background-color: #17a2b8; color: #ffffff;'; break;
                            case 'Refunded': badgeStyle = 'background-color: #343a40; color: #ffffff;'; break;
                        }
                        return `<span class="badge font-weight-bold px-3 py-2" style="${badgeStyle}">${data}</span>`;
                    }
                },
                {
                    data: null,
                    className: 'align-middle text-center',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return `
                            @if(hasPermission('booking.view_details'))
                            <a href="${data.view_url}" class="btn btn-sm btn-info px-3">
                                <i class="mdi mdi-eye"></i> View Details
                            </a>
                            @endif
                        `;
                    }
                }
            ],
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "responsive": true,
            "language": {
                "search": "",
                "searchPlaceholder": "Quick Search..."
            }
        });

        $('#searchFilterForm').on('submit', function (e) {
            e.preventDefault();
            table.ajax.reload();
        });
    });
</script>
<style>
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        color: #212529 !important;
        font-size: 0.875rem;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ced4da;
        background-color: #ffffff;
        color: #212529;
        border-radius: 0.25rem;
        padding: 0.375rem 0.75rem;
        outline: none;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #3f50f6 !important;
        color: white !important;
        border: 1px solid #3f50f6 !important;
    }
</style>
@endpush
