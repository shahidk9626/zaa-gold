@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">KYC Verification Review</h4>
                        <p class="card-description text-muted">Review, approve, or reject customer identity verification documents</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="kycTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Document Type</th>
                                <th>Document Number</th>
                                <th>Submitted Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let table;
    $(document).ready(function () {
        table = $('#kycTable').DataTable({
            ajax: {
                url: "{{ route('kyc.index') }}",
                type: 'GET'
            },
            columns: [
                { data: 'customer_name', className: 'font-weight-bold align-middle' },
                { data: 'customer_email', className: 'align-middle' },
                { data: 'document_type', className: 'align-middle' },
                { data: 'document_number', className: 'align-middle' },
                { data: 'created_at', className: 'align-middle' },
                {
                    data: 'status',
                    className: 'text-center align-middle',
                    render: function (data) {
                        let badgeClass = 'badge-secondary';
                        if (data === 'approved') badgeClass = 'badge-success';
                        if (data === 'rejected') badgeClass = 'badge-danger';
                        if (data === 'pending') badgeClass = 'badge-warning text-dark';
                        
                        let label = data.charAt(0).toUpperCase() + data.slice(1);
                        return `<span class="badge ${badgeClass}">${label}</span>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center align-middle',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return `
                            <a href="{{ url('admin/kyc') }}/${data.id}/view" class="btn btn-outline-primary btn-sm" title="Review Document" style="padding: 0.25rem 0.5rem;">
                                <i class="mdi mdi-account-search" style="font-size: 1.2rem; vertical-align: middle;"></i> Review
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
                "searchPlaceholder": "Search KYC..."
            }
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
