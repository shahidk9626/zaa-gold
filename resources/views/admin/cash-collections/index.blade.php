@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Cash Collection Requests</h4>
                    <p class="card-description text-muted mb-0">Verify and verify cash collection requests made by staff members.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Requests</h5>
            <form id="searchFilterForm" class="row">
                <!-- Search Input -->
                <div class="col-md-4 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Collection No, Booking, Transaction, Customer..." value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-4 form-group">
                    <label class="text-dark font-weight-bold">Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        @foreach(['Pending Verification', 'Verified', 'Rejected'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-4 d-flex align-items-end mb-3">
                    <a href="{{ route('admin.cash-collections.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
                    <button type="submit" class="btn btn-info px-4">Search & Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Listings Table Card -->
    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            <div class="table-responsive">
                <table id="cashCollectionsTable" class="table table-bordered table-striped text-dark">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th>Collection ID</th>
                            <th>Transaction No</th>
                            <th>Booking No</th>
                            <th>Customer Name</th>
                            <th>Collected By (Staff)</th>
                            <th>Amount</th>
                            <th>Collection Date</th>
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
        table = $('#cashCollectionsTable').DataTable({
            processing: true,
            ajax: {
                url: "{{ route('admin.cash-collections.index') }}",
                type: 'GET',
                data: function (d) {
                    d.search = $('input[name=search]').val();
                    d.status = $('select[name=status]').val();
                }
            },
            columns: [
                { data: 'collection_number', className: 'font-weight-bold text-primary align-middle' },
                { data: 'transaction_number', className: 'align-middle' },
                { data: 'booking_number', className: 'align-middle font-weight-bold text-dark' },
                { data: 'customer_name', className: 'font-weight-bold text-dark align-middle' },
                { data: 'collected_by', className: 'align-middle' },
                { data: 'amount', className: 'font-weight-bold text-success align-middle' },
                { data: 'collection_date', className: 'align-middle' },
                { 
                    data: 'status', 
                    className: 'align-middle text-center',
                    render: function (data) {
                        let badgeStyle = 'background-color: #6c757d; color: #ffffff;';
                        switch(data) {
                            case 'Pending Verification': badgeStyle = 'background-color: #ffc107; color: #000000;'; break;
                            case 'Verified': badgeStyle = 'background-color: #28a745; color: #ffffff;'; break;
                            case 'Rejected': badgeStyle = 'background-color: #dc3545; color: #ffffff;'; break;
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
                            <a href="${data.view_url}" class="btn btn-sm btn-info px-3">
                                <i class="mdi mdi-eye"></i> View Details
                            </a>
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
