@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">User Permission Overrides</h4>
                        <p class="card-description text-muted">Override role-inherited permissions for individual users (Super Admin only)</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="userPermTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Employee Code</th>
                                <th>Role</th>
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
        $('#userPermTable').DataTable({
            ajax: {
                url: "{{ route('user-permissions.index') }}",
                type: 'GET'
            },
            columns: [
                {
                    data: 'name',
                    className: 'font-weight-bold align-middle'
                },
                {
                    data: 'staff_detail.emp_code',
                    className: 'align-middle',
                    defaultContent: 'N/A'
                },
                {
                    data: 'role.name',
                    className: 'align-middle',
                    defaultContent: 'N/A'
                },
                {
                    data: 'status',
                    className: 'text-center align-middle',
                    render: function (data) {
                        let badgeClass = data === 'active' ? 'badge-success' : 'badge-secondary';
                        let statusText = data === 'active' ? 'Active' : 'Inactive';
                        return `<span class="badge ${badgeClass}">${statusText}</span>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center align-middle',
                    render: function (data) {
                        return `
                            <a href="{{ url('user-permissions') }}/${data.id}" class="btn btn-outline-success btn-sm">
                                <i class="mdi mdi-account-key mr-1"></i> Override Permissions
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
                "searchPlaceholder": "Search staff..."
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
