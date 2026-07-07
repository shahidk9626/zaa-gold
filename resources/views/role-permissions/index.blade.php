@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Role Permissions Mapping</h4>
                        <p class="card-description text-muted">Configure default permission capabilities of user roles</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="rolePermTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                <th>Role Name</th>
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
    $(document).ready(function () {
        $('#rolePermTable').DataTable({
            ajax: {
                url: "{{ route('role-permissions.index') }}",
                type: 'GET'
            },
            columns: [
                {
                    data: 'name',
                    className: 'font-weight-bold align-middle'
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
                        return `
                            <div class="btn-group" role="group">
                                <a href="{{ url('role-permissions') }}/${data.id}" class="btn btn-outline-primary btn-sm">
                                    <i class="mdi mdi-shield-key-outline mr-1"></i> Manage Permissions
                                </a>
                                <a href="{{ url('roles') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-pencil-outline mr-1"></i> Edit Role
                                </a>
                            </div>
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
                "searchPlaceholder": "Search roles..."
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

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #212529 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #3f50f6 !important;
        color: white !important;
        border: 1px solid #3f50f6 !important;
    }
</style>
@endpush
