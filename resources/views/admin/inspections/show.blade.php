@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Inspection Enquiry: INSP-{{ str_pad($enquiry->id, 5, '0', STR_PAD_LEFT) }}</h4>
                    <p class="card-description text-muted mb-0">Update status, write remarks, and review customer request details.</p>
                </div>
                <div>
                    <a href="{{ route('inspections.index') }}" class="btn btn-secondary px-4 mr-2">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to Directory
                    </a>
                    @if(hasPermission('inspection.delete'))
                    <button class="btn btn-danger px-4" onclick="confirmDelete()">
                        <i class="mdi mdi-delete mr-1"></i> Delete Enquiry
                    </button>
                    <form id="deleteForm" action="{{ route('inspections.destroy', $enquiry->id) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endif
                </div>
            </div>
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

    <!-- Content Sections -->
    <div class="col-md-8">
        <!-- Summary Cards organized into Customer details, Gold details, and Inspection details -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Customer Details</h5>
            <div class="row mb-4">
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Customer Name</span>
                    <strong class="text-dark">{{ $enquiry->name }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Mobile Number</span>
                    <strong class="text-dark">{{ $enquiry->phone }}</strong>
                </div>
                <div class="col-12">
                    <span class="text-muted d-block">Address</span>
                    <strong class="text-dark">{{ $enquiry->address }}</strong>
                </div>
            </div>

            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Gold Details</h5>
            <div class="row mb-4">
                <div class="col-sm-4 mb-3">
                    <span class="text-muted d-block">Gold Type</span>
                    <strong class="text-dark">{{ $enquiry->gold_type }}</strong>
                </div>
                <div class="col-sm-4 mb-3">
                    <span class="text-muted d-block">Approx. Grams</span>
                    <strong class="text-dark">{{ number_format($enquiry->approx_grams, 2) }} g</strong>
                </div>
                <div class="col-sm-4 mb-3">
                    <span class="text-muted d-block">Where Gold Kept</span>
                    <strong class="text-dark">{{ $enquiry->gold_location }}</strong>
                </div>
            </div>

            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Inspection Details</h5>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Preferred Inspection Date</span>
                    <strong class="text-dark">{{ $enquiry->preferred_date ? $enquiry->preferred_date->format('d M Y') : 'N/A' }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Current Status</span>
                    @php
                        $badgeClass = 'badge-secondary';
                        switch($enquiry->status) {
                            case 'New': $badgeClass = 'badge-warning'; break;
                            case 'Contacted': $badgeClass = 'badge-info'; break;
                            case 'Inspection Scheduled': $badgeClass = 'badge-primary'; break;
                            case 'Inspection Completed': $badgeClass = 'badge-success'; break;
                            case 'Converted': $badgeClass = 'badge-success'; break;
                            case 'Rejected': $badgeClass = 'badge-danger'; break;
                        }
                    @endphp
                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2 mt-1">{{ $enquiry->status }}</span>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Submission Date</span>
                    <strong class="text-dark">{{ $enquiry->created_at->format('d M Y H:i:s') }}</strong>
                </div>

                @if($enquiry->reviewed_at)
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Reviewed Date</span>
                    <strong class="text-success">{{ $enquiry->reviewed_at->format('d M Y H:i:s') }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Reviewed By</span>
                    <strong class="text-dark">{{ $enquiry->reviewer->name ?? 'System' }}</strong>
                </div>
                @endif

                @if($enquiry->admin_remark)
                <div class="col-12 mt-3">
                    <span class="text-muted d-block">Current Admin Remark</span>
                    <p class="text-dark bg-warning-light p-3 border border-warning rounded mb-0" style="background-color: #fffdf5;">{{ $enquiry->admin_remark }}</p>
                </div>
                @endif

                @if(!empty($enquiry->photos) && is_array($enquiry->photos))
                <div class="col-12 mt-4">
                    <span class="text-muted d-block mb-2">Uploaded Photos</span>
                    <div class="row g-2">
                        @foreach($enquiry->photos as $photo)
                        <div class="col-4 col-sm-3 col-md-2">
                            <a href="{{ storage_url($photo) }}" target="_blank" class="d-block border rounded overflow-hidden">
                                <img src="{{ storage_url($photo) }}" alt="Gold photo" class="w-100 img-fluid" style="height: 100px; object-fit: cover;">
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- CRM Operations: Update Status -->
        @if(hasPermission('inspection.edit'))
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Update Status & Add Remarks</h5>
            <form action="{{ route('inspections.update_status', $enquiry->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label class="text-dark">Select Status</label>
                        <select name="status" class="form-control bg-white text-dark" required>
                            @foreach(['New', 'Contacted', 'Inspection Scheduled', 'Inspection Completed', 'Converted', 'Rejected'] as $st)
                                <option value="{{ $st }}" {{ $enquiry->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12 form-group mb-3">
                        <label class="text-dark">Internal Remarks / Notes</label>
                        <textarea name="remarks" class="form-control bg-white text-dark" rows="3" placeholder="Explain follow-up actions or resolution details..." required>{{ $enquiry->admin_remark }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-info px-4">Apply Status Transition</button>
            </form>
        </div>
        @endif
    </div>

    <!-- Timeline Sidebar -->
    <div class="col-md-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Inspection Timeline</h5>
            <div class="timeline-wrapper" style="max-height: 600px; overflow-y: auto;">
                <ul class="list-unstyled pl-0">
                    @forelse($timeline as $log)
                        <li class="border-left pl-3 pb-3 position-relative">
                            <span class="position-absolute bg-primary rounded-circle" style="left:-6px; top:4px; width:12px; height:12px; border:2px solid #fff;"></span>
                            <div class="d-flex justify-content-between">
                                <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">
                                    {{ ucfirst(str_replace('_', ' ', $log->action_type)) }}
                                </span>
                                <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="text-muted mb-0 text-xs mt-1" style="font-size: 0.8rem;">{{ $log->description }}</p>
                            <small class="text-muted text-xs d-block mt-1" style="font-size: 0.75rem;">By: {{ $log->user->name ?? 'System' }}</small>
                        </li>
                    @empty
                        <li class="text-muted py-3">No timeline records generated yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDelete() {
        Swal.fire({
            title: 'Delete Enquiry?',
            text: 'Are you sure you want to delete this inspection enquiry? This will be soft-deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
</script>
@endpush
