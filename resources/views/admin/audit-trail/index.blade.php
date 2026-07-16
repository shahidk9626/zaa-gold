@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Audit Trail</h4>
                    <p class="card-description text-muted mb-0">Single source of truth for critical system activity, security events, data changes, and exports.</p>
                </div>
                @if(hasPermission('audit.export'))
                <div class="btn-group mt-3 mt-md-0" role="group">
                    <a href="{{ route('audit-trail.export', array_merge(request()->query(), ['type' => 'csv'])) }}" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-file-delimited mr-1"></i> CSV
                    </a>
                    <a href="{{ route('audit-trail.export', array_merge(request()->query(), ['type' => 'excel'])) }}" class="btn btn-outline-success btn-sm">
                        <i class="mdi mdi-file-excel mr-1"></i> Excel
                    </a>
                    <a href="{{ route('audit-trail.export', array_merge(request()->query(), ['type' => 'pdf'])) }}" class="btn btn-outline-danger btn-sm">
                        <i class="mdi mdi-file-pdf mr-1"></i> PDF
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <form method="GET" action="{{ route('audit-trail.index') }}" class="row">
                <div class="col-md-3 form-group">
                    <label class="font-weight-bold text-dark">Global Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Module, record, user, description">
                </div>
                <div class="col-md-3 form-group">
                    <label class="font-weight-bold text-dark">Module</label>
                    <select name="module" class="form-control">
                        <option value="">All Modules</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}" @selected(request('module') === $module)>{{ \Illuminate\Support\Str::headline($module) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold text-dark">Action</label>
                    <select name="action" class="form-control">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>{{ \Illuminate\Support\Str::headline($action) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold text-dark">User</label>
                    <select name="user_id" class="form-control">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold text-dark">Record ID</label>
                    <input type="text" name="record_id" value="{{ request('record_id') }}" class="form-control">
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold text-dark">From</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold text-dark">To</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold text-dark">Customer</label>
                    <select name="customer_id" class="form-control">
                        <option value="">Any Customer</option>
                        @foreach($users as $user)
                            @if($user->role?->slug === 'customer')
                                <option value="{{ $user->id }}" @selected((string) request('customer_id') === (string) $user->id)>{{ $user->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold text-dark">Staff</label>
                    <select name="staff_id" class="form-control">
                        <option value="">Any Staff</option>
                        @foreach($users as $user)
                            @if($user->role?->slug !== 'customer')
                                <option value="{{ $user->id }}" @selected((string) request('staff_id') === (string) $user->id)>{{ $user->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label class="font-weight-bold text-dark">IP Address</label>
                    <input type="text" name="ip_address" value="{{ request('ip_address') }}" class="form-control">
                </div>
                <div class="col-md-2 form-group d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm mr-2">
                        <i class="mdi mdi-filter mr-1"></i> Filter
                    </button>
                    <a href="{{ route('audit-trail.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>User</th>
                            <th>Record ID</th>
                            <th>Description</th>
                            <th>IP</th>
                            <th>Browser</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                            <tr>
                                <td class="text-nowrap">{{ optional($log->created_at)->format('d M Y H:i') }}</td>
                                <td>{{ \Illuminate\Support\Str::headline($log->module_name) }}</td>
                                <td><span class="badge badge-info">{{ \Illuminate\Support\Str::headline($log->action_type) }}</span></td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td>{{ $log->record_id }}</td>
                                <td style="min-width: 260px;">{{ \Illuminate\Support\Str::limit($log->description, 90) }}</td>
                                <td>{{ $log->ip_address ?? 'N/A' }}</td>
                                <td>{{ $log->browser ?? 'Unknown' }}</td>
                                <td>
                                    @if(hasPermission('audit.details'))
                                        <a href="{{ route('audit-trail.show', $log) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="mdi mdi-eye mr-1"></i> View
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No audit events found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $auditLogs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
