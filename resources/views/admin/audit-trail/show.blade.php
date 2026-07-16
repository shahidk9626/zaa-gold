@extends('layouts.app')

@section('content')
@php
    $oldValues = $auditLog->old_data ?? [];
    $newValues = $auditLog->new_data ?? [];
    $fields = $auditLog->changed_fields ?: array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
    $formatValue = function ($value) {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if ($value === null || $value === '') {
            return 'N/A';
        }
        return (string) $value;
    };
@endphp
<div class="row text-dark">
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Audit Details</h4>
                    <p class="card-description text-muted mb-0">{{ $auditLog->description }}</p>
                </div>
                <a href="{{ route('audit-trail.index', request()->query()) }}" class="btn btn-light btn-sm mt-3 mt-md-0">
                    <i class="mdi mdi-arrow-left mr-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="font-weight-bold text-dark mb-3">Event Metadata</h5>
            <div class="mb-2"><strong>Module:</strong> {{ \Illuminate\Support\Str::headline($auditLog->module_name) }}</div>
            <div class="mb-2"><strong>Action:</strong> {{ \Illuminate\Support\Str::headline($auditLog->action_type) }}</div>
            <div class="mb-2"><strong>Record ID:</strong> {{ $auditLog->record_id }}</div>
            <div class="mb-2"><strong>User:</strong> {{ $auditLog->user->name ?? 'System' }}</div>
            <div class="mb-2"><strong>Date:</strong> {{ optional($auditLog->created_at)->format('d M Y H:i:s') }}</div>
            <hr>
            <div class="mb-2"><strong>IP:</strong> {{ $auditLog->ip_address ?? 'N/A' }}</div>
            <div class="mb-2"><strong>Browser:</strong> {{ $auditLog->browser ?? 'Unknown' }}</div>
            <div class="mb-2"><strong>Device:</strong> {{ $auditLog->device ?? 'Unknown' }}</div>
            <div class="mb-2"><strong>Platform:</strong> {{ $auditLog->platform ?? 'Unknown' }}</div>
            <div class="mb-2"><strong>Method:</strong> {{ $auditLog->http_method ?? 'N/A' }}</div>
            <div class="mb-2"><strong>Request ID:</strong> <span class="text-monospace">{{ $auditLog->request_id ?? 'N/A' }}</span></div>
            <div class="mb-0"><strong>URL:</strong> <span class="text-break">{{ $auditLog->url ?? 'N/A' }}</span></div>
        </div>
    </div>

    <div class="col-lg-8 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="font-weight-bold text-dark mb-3">Field Level Changes</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 22%;">Field</th>
                            <th>Old Value</th>
                            <th style="width: 40px;"></th>
                            <th>New Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fields as $field)
                            <tr>
                                <td class="font-weight-bold">{{ \Illuminate\Support\Str::headline($field) }}</td>
                                <td class="text-danger bg-light"><pre class="mb-0 text-danger" style="white-space: pre-wrap;">{{ $formatValue($oldValues[$field] ?? null) }}</pre></td>
                                <td class="text-center align-middle"><i class="mdi mdi-arrow-right-bold text-muted"></i></td>
                                <td class="text-success bg-light"><pre class="mb-0 text-success" style="white-space: pre-wrap;">{{ $formatValue($newValues[$field] ?? null) }}</pre></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No field differences were recorded for this event.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="font-weight-bold text-dark mb-3">Timeline</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>User</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($timeline as $item)
                            <tr class="{{ $item->id === $auditLog->id ? 'table-primary' : '' }}">
                                <td class="text-nowrap">{{ optional($item->created_at)->format('d M Y H:i') }}</td>
                                <td><span class="badge badge-secondary">{{ \Illuminate\Support\Str::headline($item->action_type) }}</span></td>
                                <td>{{ $item->user->name ?? 'System' }}</td>
                                <td>{{ $item->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No timeline entries available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
