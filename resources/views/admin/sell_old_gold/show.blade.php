@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Old Gold Enquiry: {{ $enquiry->customer_name }}</h4>
                    <p class="card-description text-muted mb-0">Manage assignee, post notes, update status, and review the communication history.</p>
                </div>
                <div>
                    <a href="{{ route('sell-old-gold.index') }}" class="btn btn-secondary px-4 mr-2">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to Directory
                    </a>
                    @if(hasPermission('sell-old-gold.edit'))
                    <a href="{{ route('sell-old-gold.edit', $enquiry->id) }}" class="btn btn-primary px-4 mr-2">
                        <i class="mdi mdi-pencil mr-1"></i> Edit Details
                    </a>
                    <button class="btn btn-danger px-4" onclick="confirmDelete()">
                        <i class="mdi mdi-delete mr-1"></i> Delete Enquiry
                    </button>
                    <form id="deleteForm" action="{{ route('sell-old-gold.destroy', $enquiry->id) }}" method="POST" style="display: none;">
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
        <!-- Overview & Gold Specs Card -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Gold Evaluation Summary</h5>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Gold Type / Purity</span>
                    <strong class="text-dark" style="font-size: 1.1rem;">{{ $enquiry->gold_type }} Gold</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Estimated Weight</span>
                    <strong class="text-dark" style="font-size: 1.1rem;">{{ number_format($enquiry->estimated_weight, 2) }} grams</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Estimated Value</span>
                    <strong class="text-success" style="font-size: 1.1rem;">₹{{ $enquiry->estimated_value ? number_format($enquiry->estimated_value, 2) : '0.00' }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Enquiry Status</span>
                    @php
                        $badgeClass = 'badge-secondary';
                        switch($enquiry->status) {
                            case 'New': $badgeClass = 'badge-warning'; break;
                            case 'Contacted': $badgeClass = 'badge-info'; break;
                            case 'Inspection Scheduled': $badgeClass = 'badge-primary'; break;
                            case 'Quoted': $badgeClass = 'badge-secondary'; break;
                            case 'Accepted': $badgeClass = 'badge-success'; break;
                            case 'Rejected': $badgeClass = 'badge-danger'; break;
                            case 'Closed': $badgeClass = 'badge-dark'; break;
                        }
                    @endphp
                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2 mt-1">{{ $enquiry->status }}</span>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">City / Location</span>
                    <strong class="text-dark">{{ $enquiry->city ?? 'N/A' }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Next Follow-up Date</span>
                    <strong class="text-primary">{{ $enquiry->followup_date ? $enquiry->followup_date->format('d M Y') : 'Not Scheduled' }}</strong>
                </div>
                @if($enquiry->remarks)
                <div class="col-12 mt-2">
                    <span class="text-muted d-block">Internal Description</span>
                    <p class="text-dark bg-light p-3 border rounded mb-0">{{ $enquiry->remarks }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Customer Profile Card -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Customer Profile</h5>
            <div class="row">
                <div class="col-sm-4 mb-2">
                    <span class="text-muted d-block">Full Name</span>
                    <strong class="text-dark">{{ $enquiry->customer_name }}</strong>
                </div>
                <div class="col-sm-4 mb-2">
                    <span class="text-muted d-block">Mobile Number</span>
                    <strong class="text-dark">{{ $enquiry->mobile }}</strong>
                </div>
                <div class="col-sm-4 mb-2">
                    <span class="text-muted d-block">Email Address</span>
                    <strong class="text-dark">{{ $enquiry->email ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>

        <!-- CRM Operations: Notes & Updates -->
        @if(hasPermission('sell-old-gold.edit'))
        <div class="row">
            <!-- Update Status Card -->
            <div class="col-md-6 mb-4">
                <div class="card bg-white border shadow-sm p-4 h-100">
                    <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Update status</h5>
                    <form action="{{ route('sell-old-gold.change_status', $enquiry->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="text-dark">Select Status</label>
                            <select name="status" class="form-control bg-white text-dark" required>
                                @foreach(['New', 'Contacted', 'Inspection Scheduled', 'Quoted', 'Accepted', 'Rejected', 'Closed'] as $st)
                                    <option value="{{ $st }}" {{ $enquiry->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-dark">Transition Remarks</label>
                            <textarea name="remarks" class="form-control bg-white text-dark" rows="2" placeholder="Explain the reason..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-info btn-block">Apply Transition</button>
                    </form>
                </div>
            </div>

            <!-- Quick Assign Card -->
            <div class="col-md-6 mb-4">
                <div class="card bg-white border shadow-sm p-4 h-100">
                    <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Assign Representative</h5>
                    <form action="{{ route('sell-old-gold.assign', $enquiry->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="text-dark">Select Staff Member</label>
                            <select name="assigned_to" class="form-control bg-white text-dark">
                                <option value="">Leave Unassigned</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" {{ $enquiry->assigned_to == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->name }} ({{ $staff->role->name ?? 'Staff' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Assign Staff</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Note Card -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Add Internal Note</h5>
            <form action="{{ route('sell-old-gold.add_note', $enquiry->id) }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <textarea name="note" class="form-control bg-white text-dark" rows="3" placeholder="Enter private note that will be pinned to this enquiry timeline..." required></textarea>
                </div>
                <button type="submit" class="btn btn-dark px-4">Post Note</button>
            </form>
        </div>
        @endif
    </div>

    <!-- Timeline Sidebar -->
    <div class="col-md-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Enquiry Timeline</h5>
            <div class="timeline-wrapper" style="max-height: 500px; overflow-y: auto;">
                <ul class="list-unstyled pl-0">
                    @forelse($timeline as $log)
                        <li class="border-left pl-3 pb-3 position-relative">
                            <span class="position-absolute bg-primary rounded-circle" style="left:-6px; top:4px; width:12px; height:12px; border:2px solid #fff;"></span>
                            <div class="d-flex justify-content-between">
                                <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">
                                    @if($log->action_type === 'internal_note')
                                        <i class="mdi mdi-comment-text text-warning mr-1"></i>Note Added
                                    @else
                                        {{ ucfirst(str_replace('_', ' ', $log->action_type)) }}
                                    @endif
                                </span>
                                <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="text-muted mb-0 text-xs mt-1" style="font-size: 0.8rem;">{{ $log->description }}</p>
                            <small class="text-muted text-xs d-block mt-1" style="font-size: 0.75rem;">By: {{ $log->user->name ?? 'System' }}</small>
                        </li>
                    @empty
                        <li class="text-muted py-3">No timeline records generated.</li>
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
            text: 'Are you sure you want to permanently delete this old gold purchase enquiry? This cannot be undone.',
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
