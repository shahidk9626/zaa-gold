@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Franchise Partnership Lead: {{ $enquiry->full_name }}</h4>
                    <p class="card-description text-muted mb-0">Track proposal status, schedules, assignments, and log notes.</p>
                </div>
                <div>
                    <a href="{{ route('franchise.index') }}" class="btn btn-secondary px-4 mr-2">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to Directory
                    </a>
                    @if(hasPermission('franchise.edit'))
                    <a href="{{ route('franchise.edit', $enquiry->id) }}" class="btn btn-primary px-4 mr-2">
                        <i class="mdi mdi-pencil mr-1"></i> Edit Lead
                    </a>
                    <button class="btn btn-danger px-4" onclick="confirmDelete()">
                        <i class="mdi mdi-delete mr-1"></i> Delete Enquiry
                    </button>
                    <form id="deleteForm" action="{{ route('franchise.destroy', $enquiry->id) }}" method="POST" style="display: none;">
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
        <!-- Overview & Budget Specs Card -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Partnership Proposal Details</h5>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Investment Budget Bracket</span>
                    <strong class="text-success" style="font-size: 1.1rem;">{{ $enquiry->investment_budget }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Lead Status</span>
                    @php
                        $badgeClass = 'badge-secondary';
                        switch($enquiry->status) {
                            case 'New': $badgeClass = 'badge-warning'; break;
                            case 'Contacted': $badgeClass = 'badge-info'; break;
                            case 'Meeting Scheduled': $badgeClass = 'badge-primary'; break;
                            case 'Proposal Sent': $badgeClass = 'badge-secondary'; break;
                            case 'Approved': $badgeClass = 'badge-success'; break;
                            case 'Rejected': $badgeClass = 'badge-danger'; break;
                            case 'Closed': $badgeClass = 'badge-dark'; break;
                        }
                    @endphp
                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2 mt-1">{{ $enquiry->status }}</span>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Target Location</span>
                    <strong class="text-dark" style="font-size: 1.1rem;">{{ $enquiry->city }}, {{ $enquiry->state }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Meeting / Follow-up Date</span>
                    <strong class="text-primary">{{ $enquiry->followup_date ? $enquiry->followup_date->format('d M Y') : 'Not Scheduled' }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Current Business Type</span>
                    <strong class="text-dark">{{ $enquiry->current_business ?? 'None' }}</strong>
                </div>
                <div class="col-sm-6 mb-3">
                    <span class="text-muted d-block">Assigned Representative</span>
                    <strong class="text-dark">{{ $enquiry->assignedStaff->name ?? 'Unassigned' }}</strong>
                </div>
                
                @if($enquiry->business_experience)
                <div class="col-12 mb-3">
                    <span class="text-muted d-block font-weight-bold">Business Experience</span>
                    <p class="text-dark bg-light p-3 border rounded mb-0">{{ $enquiry->business_experience }}</p>
                </div>
                @endif

                @if($enquiry->message)
                <div class="col-12 mb-3">
                    <span class="text-muted d-block font-weight-bold">Message / Cover Note</span>
                    <p class="text-dark bg-light p-3 border rounded mb-0">{{ $enquiry->message }}</p>
                </div>
                @endif

                @if($enquiry->remarks)
                <div class="col-12">
                    <span class="text-muted d-block font-weight-bold">Office Remarks</span>
                    <p class="text-dark bg-light p-3 border rounded mb-0">{{ $enquiry->remarks }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Contact Information Card -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Lead Contact Info</h5>
            <div class="row">
                <div class="col-sm-4 mb-2">
                    <span class="text-muted d-block">Full Name</span>
                    <strong class="text-dark">{{ $enquiry->full_name }}</strong>
                </div>
                <div class="col-sm-4 mb-2">
                    <span class="text-muted d-block">Mobile Number</span>
                    <strong class="text-dark">{{ $enquiry->mobile }}</strong>
                </div>
                <div class="col-sm-4 mb-2">
                    <span class="text-muted d-block">Email Address</span>
                    <strong class="text-dark">{{ $enquiry->email }}</strong>
                </div>
            </div>
        </div>

        <!-- CRM Operations: Notes & Updates -->
        @if(hasPermission('franchise.edit'))
        <div class="row">
            <!-- Update Status Card -->
            <div class="col-md-6 mb-4">
                <div class="card bg-white border shadow-sm p-4 h-100">
                    <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Update status</h5>
                    <form action="{{ route('franchise.change_status', $enquiry->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="text-dark">Select Status</label>
                            <select name="status" class="form-control bg-white text-dark" required>
                                @foreach(['New', 'Contacted', 'Meeting Scheduled', 'Proposal Sent', 'Approved', 'Rejected', 'Closed'] as $st)
                                    <option value="{{ $st }}" {{ $enquiry->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-dark">Transition Remarks</label>
                            <textarea name="remarks" class="form-control bg-white text-dark" rows="2" placeholder="Explain status change..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-info btn-block">Apply Transition</button>
                    </form>
                </div>
            </div>

            <!-- Quick Assign Card -->
            <div class="col-md-6 mb-4">
                <div class="card bg-white border shadow-sm p-4 h-100">
                    <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Assign Representative</h5>
                    <form action="{{ route('franchise.assign', $enquiry->id) }}" method="POST">
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
            <form action="{{ route('franchise.add_note', $enquiry->id) }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <textarea name="note" class="form-control bg-white text-dark" rows="3" placeholder="Enter private note that will be pinned to this franchise timeline..." required></textarea>
                </div>
                <button type="submit" class="btn btn-dark px-4">Post Note</button>
            </form>
        </div>
        @endif
    </div>

    <!-- Timeline Sidebar -->
    <div class="col-md-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Lead Timeline</h5>
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
            title: 'Delete Lead?',
            text: 'Are you sure you want to permanently delete this franchise partnership lead? This cannot be undone.',
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
