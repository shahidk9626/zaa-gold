@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Profile Update Request #{{ $profileRequest->id }}</h4>
                    <p class="card-description text-muted mb-0">Review requested profile modifications for customer {{ $profileRequest->customer->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.profile-update-requests.index') }}" class="btn btn-secondary px-4">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to Requests List
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="col-12 mb-4">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="col-12 mb-4">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
            </div>
        </div>
    @endif

    <!-- CUSTOMER DETAILS CARD -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm h-100">
            <div class="card-header bg-info text-white p-3 font-weight-bold">
                <i class="mdi mdi-account-card-details mr-2"></i> CUSTOMER DETAILS
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-borderless text-dark mb-0">
                        <tbody>
                            <tr>
                                <th class="pl-0 text-muted" style="width: 40%;">Customer Name:</th>
                                <td class="font-weight-bold">{{ $profileRequest->customer->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Customer ID:</th>
                                <td>
                                    <span class="badge badge-light border font-weight-bold">
                                        #{{ $profileRequest->customer->id ?? 'N/A' }}
                                    </span>
                                    @if(hasPermission('customer.view') && $profileRequest->customer)
                                        <a href="{{ route('customers.show', $profileRequest->customer->id) }}" class="ml-2 small text-primary font-weight-bold" target="_blank">
                                            View Full Profile <i class="mdi mdi-open-in-new"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Mobile:</th>
                                <td>{{ $profileRequest->customer->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Email:</th>
                                <td>{{ $profileRequest->customer->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">KYC Status:</th>
                                <td>
                                    <span class="badge badge-success font-weight-bold px-2 py-1">
                                        {{ ucfirst($profileRequest->customer->verification_status ?? 'verified') }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Registration Date:</th>
                                <td>{{ $profileRequest->customer && $profileRequest->customer->created_at ? $profileRequest->customer->created_at->format('d/m/Y h:i A') : 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- REQUEST SUMMARY CARD -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm h-100">
            <div class="card-header bg-dark text-white p-3 font-weight-bold">
                <i class="mdi mdi-clipboard-text mr-2"></i> REQUEST STATUS & REASON
            </div>
            <div class="card-body p-4">
                <div class="table-responsive mb-3">
                    <table class="table table-borderless text-dark mb-0">
                        <tbody>
                            <tr>
                                <th class="pl-0 text-muted" style="width: 40%;">Request Date:</th>
                                <td class="font-weight-bold">
                                    {{ $profileRequest->created_at ? $profileRequest->created_at->format('d/m/Y h:i A') : 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Current Status:</th>
                                <td>
                                    @php
                                        $stBadge = 'badge-secondary';
                                        if($profileRequest->status === 'Pending') $stBadge = 'badge-warning text-dark';
                                        elseif($profileRequest->status === 'Approved') $stBadge = 'badge-success';
                                        elseif($profileRequest->status === 'Rejected') $stBadge = 'badge-danger';
                                    @endphp
                                    <span class="badge {{ $stBadge }} font-weight-bold px-3 py-2">
                                        {{ $profileRequest->status }}
                                    </span>
                                </td>
                            </tr>
                            @if($profileRequest->reviewed_at)
                            <tr>
                                <th class="pl-0 text-muted">Reviewed Date:</th>
                                <td>{{ $profileRequest->reviewed_at->format('d/m/Y h:i A') }}</td>
                            </tr>
                            @endif
                            @if($profileRequest->reviewer)
                            <tr>
                                <th class="pl-0 text-muted">Reviewed By:</th>
                                <td>{{ $profileRequest->reviewer->name }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="border-top pt-3">
                    <label class="text-muted font-weight-bold small d-block">Customer's Reason for Request:</label>
                    <div class="p-3 bg-light rounded border text-dark font-italic">
                        "{{ $profileRequest->reason }}"
                    </div>
                </div>

                @if($profileRequest->admin_remark)
                <div class="mt-3">
                    <label class="text-muted font-weight-bold small d-block">Admin Remark:</label>
                    <div class="p-3 bg-light rounded border text-dark">
                        {{ $profileRequest->admin_remark }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- REQUESTED FIELDS COMPARISON TABLE -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm">
            <div class="card-header bg-primary text-white p-3 font-weight-bold">
                <i class="mdi mdi-compare mr-2"></i> REQUESTED FIELD CHANGES COMPARISON
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-dark">
                        <thead class="bg-light text-dark">
                            <tr>
                                <th class="font-weight-bold" style="width: 30%;">Field Name</th>
                                <th class="font-weight-bold text-danger" style="width: 35%;">Current Value</th>
                                <th class="font-weight-bold text-success" style="width: 35%;">Requested New Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(is_array($profileRequest->requested_changes) && count($profileRequest->requested_changes) > 0)
                                @foreach($profileRequest->requested_changes as $chg)
                                    <tr>
                                        <td class="font-weight-bold text-dark">
                                            {{ $chg['field_label'] ?? $chg['field_name'] }}
                                        </td>
                                        <td class="bg-light text-danger font-weight-bold">
                                            {{ $chg['current_value'] ?? 'N/A' }}
                                        </td>
                                        <td class="bg-light text-success font-weight-bold">
                                            {{ $chg['requested_value'] ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        No specific field changes found in this request.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- ACTION BUTTONS FOR PENDING REQUESTS -->
                @if($profileRequest->status === 'Pending' && hasPermission('customer.edit'))
                    <div class="mt-4 border-top pt-4 d-flex justify-content-end align-items-center">
                        <button type="button" class="btn btn-danger px-4 mr-3 font-weight-bold" data-toggle="modal" data-target="#rejectRequestModal">
                            <i class="mdi mdi-close-circle mr-1"></i> Reject Request
                        </button>
                        <button type="button" class="btn btn-success px-4 font-weight-bold text-white" data-toggle="modal" data-target="#approveRequestModal">
                            <i class="mdi mdi-check-circle mr-1"></i> Approve Request
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- APPROVAL MODAL -->
@if($profileRequest->status === 'Pending')
<div class="modal fade" id="approveRequestModal" tabindex="-1" role="dialog" aria-labelledby="approveRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.profile-update-requests.approve', $profileRequest->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold" id="approveRequestModalLabel">
                        <i class="mdi mdi-check-circle mr-1"></i> Confirm Approval
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-dark">
                    <p class="font-weight-bold mb-3">Are you sure you want to approve these profile changes?</p>
                    <p class="small text-muted mb-3">Approving this request will immediately update the customer's actual profile details with the requested values.</p>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Approval Remark (Optional)</label>
                        <textarea name="admin_remark" class="form-control" rows="2" placeholder="Optional approval remark..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success font-weight-bold text-white">
                        <i class="mdi mdi-check mr-1"></i> Confirm Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- REJECTION MODAL -->
<div class="modal fade" id="rejectRequestModal" tabindex="-1" role="dialog" aria-labelledby="rejectRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.profile-update-requests.reject', $profileRequest->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold" id="rejectRequestModalLabel">
                        <i class="mdi mdi-close-circle mr-1"></i> Confirm Rejection
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-dark">
                    <p class="font-weight-bold mb-3">Are you sure you want to reject this profile update request?</p>
                    <p class="small text-muted mb-3">Rejecting will keep the customer's existing profile unchanged.</p>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="admin_remark" class="form-control" rows="3" required placeholder="Please provide a reason for rejecting this request."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger font-weight-bold">
                        <i class="mdi mdi-close mr-1"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
