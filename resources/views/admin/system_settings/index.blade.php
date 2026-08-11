@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h4 class="card-title text-dark font-weight-bold mb-1">System Settings</h4>
            <p class="card-description text-muted mb-0">Configure global configurations, manage maintenance state, and view environment details.</p>
        </div>
    </div>

    <!-- Alert Block -->
    @if(session('success'))
        <div class="col-12 mb-4">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <!-- Settings Controls -->
    <div class="col-md-8">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-4 border-bottom pb-2">Global Settings</h5>

            <div class="d-flex justify-content-between align-items-center flex-wrap py-3 border-bottom">
                <div style="max-width: 70%;">
                    <h6 class="text-dark font-weight-bold mb-1">Website Maintenance Mode</h6>
                    <p class="text-muted mb-0" style="font-size: 0.85rem;">
                        Activating Maintenance Mode blocks access to all landing pages, customer portals, and public APIs. Only Super Admins bypass this.
                    </p>
                </div>
                <div class="d-flex align-items-center gap-3 mt-3 mt-sm-0">
                    <span class="mr-3">
                        @if($maintenanceMode)
                            <span class="badge badge-danger text-dark font-weight-bold px-3 py-2">ON</span>
                        @else
                            <span class="badge badge-secondary text-dark font-weight-bold px-3 py-2">OFF</span>
                        @endif
                    </span>
                    
                    <form id="maintenanceForm" action="{{ route('admin.system-settings.toggle') }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" id="maintenanceStatusInput" value="{{ $maintenanceMode ? '0' : '1' }}">
                        <button type="button" class="btn {{ $maintenanceMode ? 'btn-success' : 'btn-danger' }} px-4" onclick="confirmToggleMaintenance()">
                            {{ $maintenanceMode ? 'Disable Maintenance' : 'Enable Maintenance' }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap py-3">
                <div>
                    <h6 class="text-dark font-weight-bold mb-1">Current System Status</h6>
                    <p class="text-muted mb-0" style="font-size: 0.85rem;">System availability tracking status.</p>
                </div>
                <div>
                    @if($maintenanceMode)
                        <span class="badge badge-warning text-dark font-weight-bold px-3 py-2">
                            <span class="d-inline-block rounded-circle bg-danger mr-1" style="width: 8px; height: 8px; box-shadow: 0 0 5px red;"></span>
                            Under Maintenance
                        </span>
                    @else
                        <span class="badge badge-success text-dark font-weight-bold px-3 py-2">
                            <span class="d-inline-block rounded-circle bg-success mr-1" style="width: 8px; height: 8px; box-shadow: 0 0 5px green;"></span>
                            System Online
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- System Info Card -->
    <div class="col-md-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Environment Info</h5>
            <ul class="list-unstyled pl-0 mb-0">
                <li class="py-2 border-bottom d-flex justify-content-between">
                    <span class="text-muted">Application Version</span>
                    <strong class="text-dark">12.0.0</strong>
                </li>
                <li class="py-2 border-bottom d-flex justify-content-between">
                    <span class="text-muted">Laravel Version</span>
                    <strong class="text-dark">{{ app()->version() }}</strong>
                </li>
                <li class="py-2 border-bottom d-flex justify-content-between">
                    <span class="text-muted">PHP Version</span>
                    <strong class="text-dark">{{ PHP_VERSION }}</strong>
                </li>
                <li class="py-2 d-flex justify-content-between">
                    <span class="text-muted">Environment</span>
                    <strong class="text-dark">{{ app()->environment() }}</strong>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmToggleMaintenance() {
        const isEnabling = document.getElementById('maintenanceStatusInput').value === '1';
        
        if (isEnabling) {
            Swal.fire({
                title: 'Enable Maintenance Mode?',
                text: 'All customers, staff and public visitors will temporarily lose access to AurOnGold.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3ca6',
                cancelButtonColor: '#8392ab',
                confirmButtonText: 'Enable Maintenance',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('maintenanceForm').submit();
                }
            });
        } else {
            Swal.fire({
                title: 'Disable Maintenance Mode?',
                text: 'Are you sure you want to turn off maintenance mode? The site will become immediately accessible.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#8392ab',
                confirmButtonText: 'Disable Maintenance',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('maintenanceForm').submit();
                }
            });
        }
    }
</script>
@endpush
