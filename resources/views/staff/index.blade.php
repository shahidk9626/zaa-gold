@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Staff Management</h4>
                        <p class="card-description text-muted">Manage system employees, credentials, and profiles</p>
                    </div>
                    <div>
                        @if(hasPermission('staff.delete'))
                            <button id="bulkDeleteBtn" style="display: none;" onclick="bulkDelete()"
                                class="btn btn-danger btn-sm mr-2">
                                <i class="mdi mdi-trash-can mr-1"></i> Delete Selected
                            </button>
                        @endif
                        @if(hasPermission('staff.create'))
                            <a href="{{ route('staff.create') }}" class="btn btn-primary btn-sm">
                                <i class="mdi mdi-plus mr-1"></i> Add Staff
                            </a>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="staffTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                @if(hasPermission('staff.delete'))
                                    <th style="width: 40px;" class="text-center table-checkbox-cell">
                                        <div class="form-check m-0 d-inline-block">
                                            <label class="form-check-label">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                                <i class="input-helper"></i>
                                            </label>
                                        </div>
                                    </th>
                                @endif
                                <th>Emp Code</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <th>Joining Date</th>
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
    const canEdit = {{ hasPermission('staff.edit') ? 'true' : 'false' }};
    const canDelete = {{ hasPermission('staff.delete') ? 'true' : 'false' }};
    const canStatus = {{ hasPermission('staff.status') ? 'true' : 'false' }};
    const canView = {{ hasPermission('staff.view') ? 'true' : 'false' }};

    $(document).ready(function () {
        table = $('#staffTable').DataTable({
            ajax: {
                url: "{{ route('staff.index') }}",
                type: 'GET'
            },
            columns: [
                @if(hasPermission('staff.delete'))
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
                    data: 'emp_code',
                    className: 'align-middle font-weight-bold'
                },
                {
                    data: 'full_name',
                    className: 'align-middle font-weight-bold'
                },
                {
                    data: 'role.name',
                    className: 'align-middle',
                    defaultContent: 'N/A'
                },
                {
                    data: 'phone',
                    className: 'align-middle'
                },
                {
                    data: 'department',
                    className: 'align-middle'
                },
                {
                    data: 'joining_date',
                    className: 'align-middle'
                },
                {
                    data: 'status',
                    className: 'text-center align-middle',
                    render: function (data) {
                        let badgeClass = data ? 'badge-success' : 'badge-secondary';
                        let statusText = data ? 'Active' : 'Inactive';
                        return `<span class="badge ${badgeClass}">${statusText}</span>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center align-middle',
                    render: function (data) {
                        let actions = `<div class="d-flex justify-content-center align-items-center">`;

                        if (canView) {
                            actions += `
                                <a href="{{ url('staff') }}/${data.id}/view" class="btn btn-outline-info btn-sm mx-1" title="View Profile" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi mdi-eye" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </a>`;
                        }

                        if (canStatus) {
                            let statusIcon = data.status ? 'mdi-toggle-switch text-success' : 'mdi-toggle-switch-off text-muted';
                            let statusTitle = data.status ? 'Deactivate' : 'Activate';
                            actions += `
                                <button onclick="confirmToggleStatus(${data.id}, ${data.status})" class="btn btn-outline-secondary btn-sm mx-1" title="${statusTitle}" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi ${statusIcon}" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </button>`;
                        }

                        if (canEdit && data.slug) {
                            actions += `
                                <a href="{{ url('staff/edit') }}/${data.slug}" class="btn btn-outline-primary btn-sm mx-1" title="Edit" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi mdi-pencil" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </a>`;
                        }

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
                "searchPlaceholder": "Search staff..."
            }
        });

        // Select All Checkbox
        $(document).on('change', '#selectAll', function() {
            $('.row-checkbox').prop('checked', this.checked);
            toggleBulkDeleteButton();
        });

        $(document).on('change', '.row-checkbox', function() {
            if (!this.checked) {
                $('#selectAll').prop('checked', false);
            } else if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                $('#selectAll').prop('checked', true);
            }
            toggleBulkDeleteButton();
        });

        table.on('draw', function() {
            $('#selectAll').prop('checked', false);
            toggleBulkDeleteButton();
        });
    });

    function confirmToggleStatus(id, currentStatus) {
        let action = currentStatus ? 'Deactivate' : 'Activate';
        Swal.fire({
            title: 'Are you sure?',
            text: `You want to ${action.toLowerCase()} this staff member?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3f50f6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: `Yes, ${action} them!`
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('staff/status') }}/${id}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        Swal.fire('Success!', response.success, 'success');
                        table.ajax.reload();
                    }
                });
            }
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Staff Member',
            text: "Are you sure you want to delete this staff member? This will set them as inactive.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('staff/delete') }}/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.error) {
                            Swal.fire('Cannot Delete!', response.error, 'error');
                        } else {
                            Swal.fire('Deleted!', 'Staff member deleted successfully.', 'success');
                            table.ajax.reload(null, false);
                            $('#selectAll').prop('checked', false);
                            toggleBulkDeleteButton();
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to delete staff member.', 'error');
                    }
                });
            }
        });
    }

    function toggleBulkDeleteButton() {
        const checkedCount = $('.row-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#bulkDeleteBtn').show();
        } else {
            $('#bulkDeleteBtn').hide();
        }
    }

    function bulkDelete() {
        const selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire('Warning', 'Please select at least one record.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Delete Selected Staff',
            text: "Are you sure you want to delete the selected staff members?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('staff.bulk-destroy') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        ids: selectedIds
                    },
                    success: function (response) {
                        if (response.summary) {
                            Swal.fire({
                                title: 'Bulk Delete Summary',
                                html: response.summary.message.replace(/\n/g, '<br>'),
                                icon: response.summary.deleted > 0 ? 'success' : 'info'
                            });
                        } else {
                            Swal.fire('Deleted!', 'Selected records deleted.', 'success');
                        }
                        table.ajax.reload(null, false);
                        $('#selectAll').prop('checked', false);
                        toggleBulkDeleteButton();
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to delete selected staff.', 'error');
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
