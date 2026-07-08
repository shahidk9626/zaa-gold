@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Product Bullion Master</h4>
                        <p class="card-description text-muted">Manage system bullion products, purity rates, weights, and displays</p>
                    </div>
                    <div class="d-flex align-items-center">
                        @if(hasPermission('product.delete'))
                            <button id="bulkDeleteBtn" style="display: none;" onclick="bulkDelete()" class="btn btn-danger btn-sm mr-2">
                                <i class="mdi mdi-trash-can mr-1"></i> Delete Selected
                            </button>
                        @endif
                        @if(hasPermission('product.create'))
                            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
                                <i class="mdi mdi-plus mr-1"></i> Add Product
                            </a>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="productTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                @if(hasPermission('product.delete'))
                                    <th style="width: 40px;" class="text-center table-checkbox-cell">
                                        <div class="form-check m-0 d-inline-block">
                                            <label class="form-check-label">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                                <i class="input-helper"></i>
                                            </label>
                                        </div>
                                    </th>
                                @endif
                                <th>Thumbnail</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Weight</th>
                                <th>Purity</th>
                                <th>Category</th>
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
    const canEdit = {{ hasPermission('product.edit') ? 'true' : 'false' }};
    const canDelete = {{ hasPermission('product.delete') ? 'true' : 'false' }};
    const canStatus = {{ hasPermission('product.status') ? 'true' : 'false' }};

    $(document).ready(function () {
        table = $('#productTable').DataTable({
            ajax: {
                url: "{{ route('products.index') }}",
                type: 'GET'
            },
            columns: [
                @if(hasPermission('product.delete'))
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
                    data: 'thumbnail',
                    className: 'align-middle text-center',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        if (data) {
                            return `<img src="{{ asset('storage') }}/${data}" style="width: 45px; height: 45px; border-radius: 4px; object-fit: cover;">`;
                        }
                        return `<div class="bg-light d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 4px;"><i class="mdi mdi-image text-muted" style="font-size: 1.2rem;"></i></div>`;
                    }
                },
                { data: 'name', className: 'font-weight-bold align-middle' },
                { data: 'sku', className: 'align-middle' },
                { 
                    data: 'weight', 
                    className: 'align-middle',
                    render: function(data) { return `${parseFloat(data).toFixed(2)} g`; }
                },
                { 
                    data: 'purity', 
                    className: 'align-middle',
                    render: function(data) { return `${parseFloat(data).toFixed(2)}%`; }
                },
                { data: 'category', className: 'align-middle' },
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
                            <a href="{{ url('admin/products') }}/${data.id}/view" class="btn btn-outline-info btn-sm mx-1" title="View Profile" style="padding: 0.25rem 0.5rem;">
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
                                <a href="{{ url('admin/products') }}/${data.id}/edit" class="btn btn-outline-primary btn-sm mx-1" title="Edit" style="padding: 0.25rem 0.5rem;">
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
                "searchPlaceholder": "Search products..."
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
    });

    function toggleBulkDeleteButton() {
        const checkedCount = $('.row-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#bulkDeleteBtn').show();
        } else {
            $('#bulkDeleteBtn').hide();
        }
    }

    function confirmToggleStatus(id) {
        $.ajax({
            url: `{{ url('admin/products/status') }}/${id}`,
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
            title: 'Delete Product?',
            text: 'Are you sure you want to delete this product? Associated inventory will be affected.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/products/delete') }}/${id}`,
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

    function bulkDelete() {
        let selectedIds = [];
        $('.row-checkbox:checked').each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Delete Selected?',
            text: 'Are you sure you want to delete all selected products?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, delete all!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('products.bulk-destroy') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: selectedIds
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
