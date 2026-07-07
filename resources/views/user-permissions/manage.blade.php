@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Custom User Permissions: <span class="text-success font-weight-bold">{{ $user->name }}</span></h4>
                        <p class="card-description text-muted">Override role-inherited permissions for individual users</p>
                    </div>
                    <div>
                        <a href="{{ route('user-permissions.index') }}" class="btn btn-secondary btn-sm">
                            <i class="mdi mdi-arrow-left mr-1"></i> Back to List
                        </a>
                    </div>
                </div>

                @if($user->id === 1)
                    <div class="text-center py-5 bg-light rounded border">
                        <i class="mdi mdi-crown text-warning" style="font-size: 4rem;"></i>
                        <h5 class="font-weight-bold text-dark mt-3">All Access Granted</h5>
                        <p class="text-muted">Super Admin (User ID: 1) has full system access and requires no manual overrides.</p>
                    </div>
                @else
                    <div class="alert alert-info bg-info text-dark border-0 mb-4">
                        <strong>Assigned Role:</strong> {{ $user->role->name ?? 'None' }} <br>
                        <span style="font-size: 0.8rem; font-style: italic;">Checking a permission here will grant it explicitly, overriding role defaults. Unchecking will remove the explicit grant.</span>
                    </div>

                    <div class="form-check mb-4">
                        <label class="form-check-label text-dark font-weight-bold" style="cursor: pointer;">
                            <input type="checkbox" id="selectAllGlobal" class="form-check-input">
                            Select All Overrides
                            <i class="input-helper"></i>
                        </label>
                    </div>

                    <form id="userPermForm" action="{{ route('user-permissions.save', $user->id) }}" method="POST">
                        @csrf
                        <div class="table-responsive border rounded">
                            <table class="table table-bordered table-hover text-dark mb-0">
                                <thead class="bg-light text-dark">
                                    <tr class="text-uppercase font-weight-bold text-center">
                                        <th class="text-left">Module</th>
                                        <th class="text-success">All</th>
                                        <th>View</th>
                                        <th>Create</th>
                                        <th>Edit</th>
                                        <th>Delete</th>
                                        <th>Status</th>
                                        <th>Verify</th>
                                        <th>Approve</th>
                                        <th>Reject</th>
                                        <th>Detail</th>
                                        <th>Export</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($modules as $module)
                                        <tr class="module-row" data-module="{{ $module->slug }}">
                                            <td class="font-weight-bold align-middle">{{ $module->name }}</td>
                                            <td class="text-center align-middle table-checkbox-cell">
                                                @if($module->permissions->count() > 0)
                                                    <div class="form-check m-0 d-inline-block">
                                                        <label class="form-check-label">
                                                            <input type="checkbox" class="form-check-input row-select-all">
                                                            <i class="input-helper"></i>
                                                        </label>
                                                    </div>
                                                @endif
                                            </td>
                                            @foreach (['view', 'create', 'edit', 'delete', 'status', 'verify', 'approve', 'reject', 'view_detail', 'export'] as $action)
                                                @php
                                                    $permission = $module->permissions->where('slug', $module->slug . '.' . $action)->first();
                                                @endphp
                                                <td class="text-center align-middle table-checkbox-cell">
                                                    @if ($permission)
                                                        <div class="form-check m-0 d-inline-block">
                                                            <label class="form-check-label">
                                                                <input type="checkbox" name="user_permissions[{{ $permission->id }}]" value="1"
                                                                    {{ !isset($userPermissions[$permission->id]) || $userPermissions[$permission->id] ? 'checked' : '' }}
                                                                    class="form-check-input permission-checkbox">
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

                        <div class="mt-4 text-right">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="mdi mdi-content-save mr-1"></i> Save User Overrides
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // Check initial state for row-wise "All"
        $('.module-row').each(function () {
            updateRowState($(this));
        });
        updateGlobalState();

        // Global Select All
        $('#selectAllGlobal').on('change', function () {
            const checked = $(this).is(':checked');
            $('.permission-checkbox, .row-select-all').prop('checked', checked);
        });

        // Row-wise Select All
        $('.row-select-all').on('change', function () {
            const checked = $(this).is(':checked');
            $(this).closest('tr').find('.permission-checkbox').prop('checked', checked);
            updateGlobalState();
        });

        // Individual Permission Checkbox
        $('.permission-checkbox').on('change', function () {
            updateRowState($(this).closest('tr'));
            updateGlobalState();
        });

        function updateRowState(row) {
            const total = row.find('.permission-checkbox').length;
            const checked = row.find('.permission-checkbox:checked').length;
            row.find('.row-select-all').prop('checked', total > 0 && total === checked);
        }

        function updateGlobalState() {
            const total = $('.permission-checkbox').length;
            const checked = $('.permission-checkbox:checked').length;
            $('#selectAllGlobal').prop('checked', total > 0 && total === checked);
        }

        $('#userPermForm').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.success,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = "{{ route('user-permissions.index') }}";
                    });
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: xhr.responseJSON.error || 'Something went wrong!'
                    });
                }
            });
        });
    });
</script>

<style>
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
