@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Customer Management</h4>
                        <p class="card-description text-muted">Manage system customers, onboarding profiles, status, and KYC records</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if(hasPermission('customer.delete'))
                            <button id="bulkDeleteBtn" style="display: none;" onclick="bulkDelete()" class="btn btn-danger btn-sm mr-2">
                                <i class="mdi mdi-trash-can mr-1"></i> Delete Selected
                            </button>
                        @endif

                        @if(hasPermission('customer.import'))
                            <button class="btn btn-outline-success btn-sm mr-2" data-toggle="modal" data-target="#importModal">
                                <i class="mdi mdi-upload mr-1"></i> Import Excel
                            </button>
                        @endif

                        @if(hasPermission('customer.export'))
                            <button onclick="exportCustomers()" class="btn btn-outline-primary btn-sm mr-2">
                                <i class="mdi mdi-download mr-1"></i> Export Excel
                            </button>
                        @endif

                        @if(hasPermission('customer.create'))
                            <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                                <i class="mdi mdi-plus mr-1"></i> Add Customer
                            </a>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="customerTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                @if(hasPermission('customer.delete'))
                                    <th style="width: 40px;" class="text-center table-checkbox-cell">
                                        <div class="form-check m-0 d-inline-block">
                                            <label class="form-check-label">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                                <i class="input-helper"></i>
                                            </label>
                                        </div>
                                    </th>
                                @endif
                                <th>Name</th>
                                <th>Email / Contact</th>
                                <th>Referred By</th>
                                <th class="text-center">Verification</th>
                                <th class="text-center">Profile</th>
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

<!-- Import Modal -->
@if(hasPermission('customer.import'))
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-white text-dark border">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-dark">Import Customers</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-4">Please upload an excel or CSV file containing customer details. You can download the sample template below.</p>
                    
                    <div class="form-group mb-4">
                        <label class="text-dark font-weight-bold">Select File</label>
                        <input type="file" name="file" required class="form-control bg-white text-dark" style="height: auto;">
                    </div>

                    <div class="text-center bg-light p-3 rounded">
                        <a href="{{ route('customers.import-template') }}" class="btn btn-link text-primary font-weight-bold p-0">
                            <i class="mdi mdi-download mr-1"></i> Download Sample Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    let table;
    const canEdit = {{ hasPermission('customer.edit') ? 'true' : 'false' }};
    const canDelete = {{ hasPermission('customer.delete') ? 'true' : 'false' }};
    const canStatus = {{ hasPermission('customer.status') ? 'true' : 'false' }};
    const canViewDetails = {{ hasPermission('customer.view_details') ? 'true' : 'false' }};

    $(document).ready(function () {
        table = $('#customerTable').DataTable({
            ajax: {
                url: "{{ route('customers.index') }}",
                type: 'GET'
            },
            columns: [
                @if(hasPermission('customer.delete'))
                {
                    data: 'id',
                    className: 'text-center align-middle table-checkbox-cell',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return `
                            <div class="form-check m-0 d-inline-block">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input row-checkbox" value="${data}">
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        `;
                    }
                },
                @endif
                {
                    data: 'name',
                    className: 'font-weight-bold align-middle'
                },
                {
                    data: null,
                    className: 'align-middle',
                    render: function (data) {
                        return `
                            <div class="d-flex flex-column">
                                <span class="font-weight-bold">${data.email}</span>
                                <span class="text-muted small"><i class="mdi mdi-phone mr-1"></i>${data.phone}</span>
                                <span class="text-muted small"><i class="mdi mdi-whatsapp text-success mr-1"></i>${data.whatsapp}</span>
                            </div>
                        `;
                    }
                },
                {
                    data: 'referral',
                    className: 'align-middle'
                },
                {
                    data: 'verified',
                    className: 'text-center align-middle',
                    render: function (data) {
                        let badgeClass = 'badge-secondary';
                        if (data === 'verified') badgeClass = 'badge-success';
                        if (data === 'rejected') badgeClass = 'badge-danger';
                        
                        let label = data ? data.charAt(0).toUpperCase() + data.slice(1) : 'Pending';
                        return `<span class="badge ${badgeClass}">${label}</span>`;
                    }
                },
                {
                    data: 'profile_complete',
                    className: 'text-center align-middle',
                    render: function (data) {
                        let badgeClass = data === 'Yes' ? 'badge-info' : 'badge-danger';
                        let label = data === 'Yes' ? 'Complete' : 'Incomplete';
                        return `<span class="badge ${badgeClass}">${label}</span>`;
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

                        // Verify Action
                        if (data.verified !== 'verified') {
                            if (canStatus) {
                                actions += `
                                    <button onclick="confirmVerify(${data.id})" class="btn btn-outline-success btn-sm mx-1" title="Verify Profile" style="padding: 0.25rem 0.5rem;">
                                        <i class="mdi mdi-check-circle" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                    </button>`;
                            }
                        }

                        // View Action
                        if (canViewDetails) {
                            actions += `
                                <a href="{{ url('admin/customers') }}/${data.id}/view" class="btn btn-outline-info btn-sm mx-1" title="View Profile" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi mdi-eye" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </a>`;
                        }

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
                                <a href="{{ url('admin/customers') }}/${data.id}/edit" class="btn btn-outline-primary btn-sm mx-1" title="Edit" style="padding: 0.25rem 0.5rem;">
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
                "searchPlaceholder": "Search customers..."
            }
        });

        // Select All Checkbox
        $(document).on('change', '#selectAll', function () {
            $('.row-checkbox').prop('checked', this.checked);
            toggleBulkDeleteButton();
        });

        $(document).on('change', '.row-checkbox', function () {
            if (!this.checked) {
                $('#selectAll').prop('checked', false);
            } else if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                $('#selectAll').prop('checked', true);
            }
            toggleBulkDeleteButton();
        });

        table.on('draw', function () {
            $('#selectAll').prop('checked', false);
            toggleBulkDeleteButton();
        });

        // Handle CSV/Excel Import Form Submit
        $('#importForm').on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            let submitBtn = $(this).find('button[type="submit"]');

            submitBtn.prop('disabled', true).text('Uploading & Importing...');

            $.ajax({
                url: "{{ route('customers.import') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#importModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Import Successful',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    });
                    table.ajax.reload();
                    submitBtn.prop('disabled', false).text('Upload & Import');
                    $('#importForm')[0].reset();
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).text('Upload & Import');
                    let errors = xhr.responseJSON.errors;
                    let errorHtml = '';
                    if (errors && Array.isArray(errors)) {
                        errorHtml = '<ul class="text-left small text-danger mt-2">';
                        errors.forEach(err => {
                            errorHtml += `<li>${err}</li>`;
                        });
                        errorHtml += '</ul>';
                    } else {
                        errorHtml = xhr.responseJSON.error || 'Failed to import customers';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Import Failed',
                        html: errorHtml,
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });
    });

    function toggleBulkDeleteButton() {
        const checkedCount = $('.row-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#bulkDeleteBtn').show();
        } else {
            $('#bulkDeleteBtn').hide();
        }
    }

    function confirmVerify(id) {
        Swal.fire({
            title: 'Verify Customer?',
            text: 'Are you sure you want to verify this customer profile?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2dce89',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, verify!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/customers') }}/${id}/verify`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        Swal.fire('Verified!', response.message, 'success');
                        table.ajax.reload();
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON.message || 'Something went wrong', 'error');
                    }
                });
            }
        });
    }

    function confirmToggleStatus(id) {
        $.ajax({
            url: `{{ url('admin/customers/status') }}/${id}`,
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
            title: 'Delete Customer?',
            text: 'Are you sure you want to delete this customer? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/customers/delete') }}/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.error) {
                            Swal.fire('Cannot Delete!', response.error, 'error');
                        } else {
                            Swal.fire('Deleted!', response.success, 'success');
                            table.ajax.reload();
                        }
                    }
                });
            }
        });
    }

    function bulkDelete() {
        let selectedIds = [];
        $('.row-checkbox:checked').each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Delete Selected?',
            text: 'Are you sure you want to delete all selected customers?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, delete all!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('customers.bulk-destroy') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: selectedIds
                    },
                    success: function (response) {
                        Swal.fire({
                            title: 'Bulk Delete Results',
                            html: response.summary.message.replace(/\n/g, '<br>'),
                            icon: response.summary.deleted > 0 ? 'success' : 'info'
                        });
                        table.ajax.reload();
                    }
                });
            }
        });
    }

    function exportCustomers() {
        let selectedIds = [];
        $('.row-checkbox:checked').each(function () {
            selectedIds.push($(this).val());
        });

        let url = "{{ route('customers.export') }}";
        if (selectedIds.length > 0) {
            url += "?ids=" + JSON.stringify(selectedIds);
        }
        window.location.href = url;
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

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #212529 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #3f50f6 !important;
        color: white !important;
        border: 1px solid #3f50f6 !important;
    }

    .table-checkbox-cell {
        text-align: center;
        vertical-align: middle !important;
    }
    .table-checkbox-cell .form-check {
        margin: 0 !important;
        padding: 0 !important;
        display: inline-block !important;
        width: 18px;
        height: 18px;
        position: relative;
    }
    .table-checkbox-cell .form-check .form-check-label {
        margin: 0 !important;
        padding: 0 !important;
        width: 18px;
        height: 18px;
        display: block;
        position: relative;
    }
    .table-checkbox-cell .form-check .form-check-label input[type="checkbox"] {
        position: absolute;
        top: 0;
        left: 0;
        margin: 0;
        padding: 0;
        width: 18px;
        height: 18px;
        opacity: 0;
        z-index: 1;
        cursor: pointer;
    }
    .table-checkbox-cell .form-check .form-check-label .input-helper:before,
    .table-checkbox-cell .form-check .form-check-label .input-helper:after {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        margin: 0 !important;
    }
</style>
@endpush
