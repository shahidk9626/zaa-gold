@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">EMI Plan Master Configuration</h4>
                        <p class="card-description text-muted">Configure corporate EMI templates, limits, interest, and penalties</p>
                    </div>
                    @if(hasPermission('emi-plan.create'))
                        <a href="{{ route('emi-plans.create') }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-plus mr-1"></i> Add EMI Plan
                        </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table id="emiPlanTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Plan Name</th>
                                <th>Duration</th>
                                <th>Amount Range</th>
                                <th>Gold Weight Range</th>
                                <th>Interest Rate</th>
                                <th>Processing Fee</th>
                                <th class="text-center">Default</th>
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
    const canEdit = {{ hasPermission('emi-plan.edit') ? 'true' : 'false' }};
    const canDelete = {{ hasPermission('emi-plan.delete') ? 'true' : 'false' }};
    const canStatus = {{ hasPermission('emi-plan.status') ? 'true' : 'false' }};

    $(document).ready(function () {
        table = $('#emiPlanTable').DataTable({
            ajax: {
                url: "{{ route('emi-plans.index') }}",
                type: 'GET'
            },
            columns: [
                { data: 'plan_code', className: 'align-middle font-weight-bold' },
                { data: 'plan_name', className: 'align-middle' },
                { 
                    data: 'duration_months', 
                    className: 'align-middle font-weight-bold',
                    render: function (data) { return `${data} Months`; }
                },
                {
                    data: null,
                    className: 'align-middle',
                    render: function (data) {
                        return `₹${parseFloat(data.minimum_booking_amount).toLocaleString()} - ₹${parseFloat(data.maximum_booking_amount).toLocaleString()}`;
                    }
                },
                {
                    data: null,
                    className: 'align-middle',
                    render: function (data) {
                        return `${parseFloat(data.minimum_gold_weight)}g - ${parseFloat(data.maximum_gold_weight)}g`;
                    }
                },
                {
                    data: null,
                    className: 'align-middle',
                    render: function (data) {
                        return `${parseFloat(data.interest_rate)}% (${data.interest_type.toUpperCase()})`;
                    }
                },
                {
                    data: null,
                    className: 'align-middle',
                    render: function (data) {
                        if (data.processing_fee_type === 'percent') {
                            return `${parseFloat(data.processing_fee)}%`;
                        }
                        return `₹${parseFloat(data.processing_fee)}`;
                    }
                },
                {
                    data: 'is_default',
                    className: 'text-center align-middle',
                    render: function (data) {
                        return data 
                            ? `<span class="badge badge-primary"><i class="mdi mdi-check-circle mr-1"></i>Default</span>`
                            : `<span class="text-muted small">-</span>`;
                    }
                },
                {
                    data: 'status',
                    className: 'text-center align-middle',
                    render: function (data) {
                        let badgeClass = data === 'active' ? 'badge-success' : 'badge-secondary';
                        return `<span class="badge ${badgeClass}">${data}</span>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center align-middle',
                    render: function (data) {
                        let actions = `<div class="d-flex justify-content-center align-items-center">`;

                        // View Action
                        actions += `
                            <a href="{{ url('admin/emi-plans') }}/${data.id}/view" class="btn btn-outline-info btn-sm mx-1" title="View Specifications" style="padding: 0.25rem 0.5rem;">
                                <i class="mdi mdi-eye" style="font-size: 1.2rem; vertical-align: middle;"></i>
                            </a>`;

                        // Toggle status action
                        if (canStatus) {
                            let statusIcon = data.status === 'active' ? 'mdi-toggle-switch text-success' : 'mdi-toggle-switch-off text-muted';
                            let statusTitle = data.status === 'active' ? 'Deactivate' : 'Activate';
                            actions += `
                                <button onclick="confirmToggleStatus(${data.id})" class="btn btn-outline-secondary btn-sm mx-1" title="${statusTitle}" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi ${statusIcon}" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </button>`;
                        }

                        // Edit Action
                        if (canEdit) {
                            actions += `
                                <a href="{{ url('admin/emi-plans') }}/${data.id}/edit" class="btn btn-outline-primary btn-sm mx-1" title="Edit" style="padding: 0.25rem 0.5rem;">
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
                "searchPlaceholder": "Search plans..."
            }
        });
    });

    function confirmToggleStatus(id) {
        $.ajax({
            url: `{{ url('admin/emi-plans/status') }}/${id}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
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
            }
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete EMI Plan?',
            text: 'Are you sure you want to delete this plan? This action is soft deleted and reversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/emi-plans/delete') }}/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        Swal.fire('Deleted!', response.success, 'success');
                        table.ajax.reload();
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
