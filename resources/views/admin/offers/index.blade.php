@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Promotional Offers & Discounts</h4>
                        <p class="card-description text-muted">Manage promotional codes, percentage discounts, fixed cuts, and EMI waivers.</p>
                    </div>
                    @if(hasPermission('offers.create'))
                        <a href="{{ route('offers.create') }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-plus mr-1"></i> Add Offer
                        </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table id="offersTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Offer Name</th>
                                <th>Type</th>
                                <th>Value / Benefit</th>
                                <th>Valid Date Range</th>
                                <th class="text-center">Priority</th>
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
    const canEdit = {{ hasPermission('offers.edit') ? 'true' : 'false' }};
    const canDelete = {{ hasPermission('offers.delete') ? 'true' : 'false' }};
    const canStatus = {{ hasPermission('offers.status') ? 'true' : 'false' }};

    $(document).ready(function () {
        table = $('#offersTable').DataTable({
            ajax: {
                url: "{{ route('offers.index') }}",
                type: 'GET'
            },
            columns: [
                { data: 'offer_code', className: 'align-middle font-weight-bold' },
                { data: 'offer_name', className: 'align-middle' },
                { 
                    data: 'offer_type', 
                    className: 'align-middle text-capitalize',
                    render: function (data) {
                        if (data === 'emi') return 'EMI Discount';
                        return data;
                    }
                },
                {
                    data: null,
                    className: 'align-middle font-weight-bold',
                    render: function (data) {
                        if (data.offer_type === 'percentage') {
                            return `${parseFloat(data.percentage)}% OFF`;
                        } else if (data.offer_type === 'fixed') {
                            return `₹${parseFloat(data.fixed_amount).toLocaleString()} OFF`;
                        } else {
                            return `Pay ${data.required_emi_count} Get ${data.required_emi_count + data.free_emi_count} EMIs`;
                        }
                    }
                },
                {
                    data: null,
                    className: 'align-middle',
                    render: function (data) {
                        let start = data.start_date ? new Date(data.start_date).toLocaleDateString() : 'N/A';
                        let end = data.end_date ? new Date(data.end_date).toLocaleDateString() : 'N/A';
                        return `<small>${start} to ${end}</small>`;
                    }
                },
                { data: 'priority', className: 'text-center align-middle' },
                {
                    data: 'status',
                    className: 'text-center align-middle',
                    render: function (data) {
                        let badgeClass = 'badge-secondary';
                        if (data === 'Active') badgeClass = 'badge-success';
                        else if (data === 'Draft') badgeClass = 'badge-warning';
                        else if (data === 'Expired') badgeClass = 'badge-danger';
                        
                        return `<span class="badge ${badgeClass}">${data}</span>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center align-middle',
                    render: function (data) {
                        let actions = `<div class="d-flex justify-content-center align-items-center">`;

                        // Status Toggling Switch
                        if (canStatus) {
                            let statusIcon = data.status === 'Active' ? 'mdi-toggle-switch text-success' : 'mdi-toggle-switch-off text-muted';
                            let statusTitle = data.status === 'Active' ? 'Deactivate' : 'Activate';
                            let nextStatus = data.status === 'Active' ? 'Inactive' : 'Active';
                            actions += `
                                <button onclick="confirmToggleStatus(${data.id}, '${nextStatus}')" class="btn btn-outline-secondary btn-sm mx-1" title="${statusTitle}" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi ${statusIcon}" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </button>`;
                        }

                        // Edit Action
                        if (canEdit) {
                            actions += `
                                <a href="{{ url('admin/offers') }}/${data.id}/edit" class="btn btn-outline-primary btn-sm mx-1" title="Edit" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi mdi-pencil" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </a>`;
                        }

                        // Delete Action
                        if (canDelete) {
                            actions += `
                                <button onclick="confirmDelete(${data.id})" class="btn btn-outline-danger btn-sm mx-1" title="Delete" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi mdi-delete" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </button>`;
                        }

                        actions += `</div>`;
                        return actions;
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
                "searchPlaceholder": "Search offers..."
            }
        });
    });

    function confirmToggleStatus(id, status) {
        $.ajax({
            url: `{{ url('admin/offers/status') }}/${id}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                status: status
            },
            success: function (response) {
                table.ajax.reload(null, false);
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({
                    icon: 'success',
                    title: response.success
                });
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Failed to update status',
                    confirmButtonColor: '#ff3ca6'
                });
            }
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Offer?',
            text: 'Are you sure you want to delete this offer? This will delete it permanently unless it is applied to active bookings.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/offers/delete') }}/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        Swal.fire('Deleted!', response.success, 'success');
                        table.ajax.reload();
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Deletion Failed',
                            text: xhr.responseJSON.error || 'This offer is in use and cannot be deleted.',
                            confirmButtonColor: '#ff3ca6'
                        });
                    }
                });
            }
        });
    }
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
