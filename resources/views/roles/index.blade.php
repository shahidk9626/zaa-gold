@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Roles Management</h4>
                        <p class="card-description text-muted">Manage system roles and set default permission mapping</p>
                    </div>
                    <div>
                        @if(hasPermission('roles.delete'))
                            <button id="bulkDeleteBtn" style="display: none;" onclick="bulkDelete()"
                                class="btn btn-danger btn-sm mr-2">
                                <i class="mdi mdi-trash-can mr-1"></i> Delete Selected
                            </button>
                        @endif
                        @if(hasPermission('roles.create'))
                            <button onclick="openAddModal()" class="btn btn-primary btn-sm">
                                <i class="mdi mdi-plus mr-1"></i> Add Role
                            </button>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="roleTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                @if(hasPermission('roles.delete'))
                                    <th style="width: 40px;" class="text-center table-checkbox-cell">
                                        <div class="form-check m-0 d-inline-block">
                                            <label class="form-check-label">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                                <i class="input-helper"></i>
                                            </label>
                                        </div>
                                    </th>
                                @endif
                                <th>Role Name</th>
                                <th>Slug</th>
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

<!-- Modal -->
<div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel" aria-hidden="true" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content bg-white text-dark border">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-dark" id="roleModal-title">Add Role</h5>
                <button type="button" class="close text-dark" onclick="closeModal()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="roleForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="roleId" name="id">
                    
                    <div class="form-group">
                        <label for="roleName" class="text-dark">Role Name <span class="text-danger">*</span></label>
                        <input type="text" id="roleName" name="name" required class="form-control bg-white text-dark" placeholder="Enter role name">
                    </div>
                    
                    <div class="form-group">
                        <label for="roleDescription" class="text-dark">Description</label>
                        <textarea id="roleDescription" name="description" rows="2" class="form-control bg-white text-dark" placeholder="Enter description"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="roleStatus" class="text-dark">Status</label>
                        <select id="roleStatus" name="status" class="form-control bg-white text-dark">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group mt-4">
                        <label class="d-block mb-3 text-dark font-weight-bold">Permissions Mapping</label>
                        <div class="table-responsive border rounded">
                            <table class="table table-bordered table-hover text-dark mb-0">
                                <thead class="bg-light text-dark">
                                    <tr>
                                        <th>Module</th>
                                        <th class="text-center">View</th>
                                        <th class="text-center">Create</th>
                                        <th class="text-center">Edit</th>
                                        <th class="text-center">Delete</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modules as $module)
                                        <tr>
                                            <td class="font-weight-bold">{{ $module->name }}</td>
                                            @foreach (['view', 'create', 'edit', 'delete', 'status'] as $action)
                                                @php
                                                    $permission = $module->permissions->where('slug', $module->slug . '.' . $action)->first();
                                                @endphp
                                                <td class="text-center">
                                                    @if ($permission)
                                                        <div class="form-check m-0 d-inline-block">
                                                            <label class="form-check-label">
                                                                <input type="checkbox" name="permissions[{{ $permission->id }}]" value="1"
                                                                    class="form-check-input permission-checkbox"
                                                                    data-permission-id="{{ $permission->id }}">
                                                                <i class="input-helper"></i>
                                                            </label>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let table;
    const canEdit = {{ hasPermission('roles.edit') ? 'true' : 'false' }};
    const canDelete = {{ hasPermission('roles.delete') ? 'true' : 'false' }};
    const canStatus = {{ hasPermission('roles.status') ? 'true' : 'false' }};

    $(document).ready(function () {
        table = $('#roleTable').DataTable({
            ajax: {
                url: "{{ route('roles.index') }}",
                type: 'GET'
            },
            columns: [
                @if(hasPermission('roles.delete'))
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
                    data: 'slug',
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

                        if (canStatus) {
                            let statusIcon = data.status ? 'mdi-toggle-switch text-success' : 'mdi-toggle-switch-off text-muted';
                            let statusTitle = data.status ? 'Deactivate' : 'Activate';
                            actions += `
                                <button onclick="confirmToggleStatus(${data.id}, ${data.status})" class="btn btn-outline-secondary btn-sm mx-1" title="${statusTitle}" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi ${statusIcon}" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </button>`;
                        }

                        if (canEdit) {
                            actions += `
                                <button onclick="openEditModal(${data.id}, '${data.name}', ${data.status}, '${data.description || ''}')" class="btn btn-outline-primary btn-sm mx-1" title="Edit" style="padding: 0.25rem 0.5rem;">
                                    <i class="mdi mdi-pencil" style="font-size: 1.2rem; vertical-align: middle;"></i>
                                </button>`;
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
                "searchPlaceholder": "Search roles..."
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

    function openModalLogic() {
        $('#roleModal').modal('show');
    }

    function closeModal() {
        $('#roleModal').modal('hide');
    }

    function openAddModal() {
        $('#roleModal-title').text('Add Role');
        $('#roleId').val('');
        $('#roleForm')[0].reset();
        $('.permission-checkbox').prop('checked', false);
        openModalLogic();
    }

    function openEditModal(id, name, status, description) {
        $('#roleModal-title').text('Edit Role');
        $('#roleId').val(id);
        $('#roleName').val(name);
        $('#roleStatus').val(status);
        $('#roleDescription').val(description);

        $('.permission-checkbox').prop('checked', false);

        // Load Permissions
        $.get(`{{ url('roles/permissions') }}/${id}`, function (permissions) {
            permissions.forEach(function (rp) {
                if (rp.allowed) {
                    $(`.permission-checkbox[data-permission-id="${rp.permission_id}"]`).prop('checked', true);
                }
            });
        });

        openModalLogic();
    }

    $('#roleForm').on('submit', function (e) {
        e.preventDefault();
        let roleId = $('#roleId').val();
        let url = roleId ? `{{ url('roles/update') }}/${roleId}` : `{{ route('roles.store') }}`;

        $.ajax({
            url: url,
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                closeModal();
                Toast.fire({
                    icon: 'success',
                    title: response.success
                });
                table.ajax.reload();
            },
            error: function (xhr) {
                let errors = xhr.responseJSON.errors;
                let errorMsg = '';
                for (let key in errors) {
                    errorMsg += errors[key][0] + '\n';
                }
                Swal.fire('Error', errorMsg, 'error');
            }
        });
    });

    function confirmToggleStatus(id, currentStatus) {
        let action = currentStatus ? 'Deactivate' : 'Activate';
        Swal.fire({
            title: 'Are you sure?',
            text: `You want to ${action.toLowerCase()} this role?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3f50f6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: `Yes, ${action} it!`
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('roles/status') }}/${id}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        Toast.fire({
                            icon: 'success',
                            title: response.success
                        });
                        table.ajax.reload();
                    }
                });
            }
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Record',
            text: "Are you sure you want to delete this record?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('roles/delete') }}/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.error) {
                            Swal.fire('Cannot Delete!', response.error, 'error');
                        } else {
                            Swal.fire('Deleted!', 'Record deleted successfully.', 'success');
                            table.ajax.reload(null, false);
                            $('#selectAll').prop('checked', false);
                            toggleBulkDeleteButton();
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to delete role.', 'error');
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
            title: 'Delete Selected Records',
            text: "Are you sure you want to delete the selected records?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('roles.bulk-destroy') }}",
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
                        Swal.fire('Error', 'Failed to delete selected roles.', 'error');
                    }
                });
            }
        });
    }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
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
