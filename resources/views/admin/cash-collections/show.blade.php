@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <div class="d-flex align-items-center">
                        <span class="h4 font-weight-bold text-dark mb-0 mr-3">Cash Collection Request #{{ $ccr->collection_number }}</span>
                        @php
                            $badgeClass = 'badge-secondary';
                            switch($ccr->status) {
                                case 'Pending Verification': $badgeClass = 'badge-warning'; break;
                                case 'Verified': $badgeClass = 'badge-success'; break;
                                case 'Rejected': $badgeClass = 'badge-danger'; break;
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $ccr->status }}</span>
                    </div>
                    <p class="text-muted mb-0 mt-2">Amount: <strong>₹{{ number_format($ccr->amount, 2) }}</strong> | Collected on {{ $ccr->collection_date->format('d M Y, h:i A') }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.cash-collections.index') }}" class="btn btn-secondary px-4">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to list
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="col-12 mb-4">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close text-dark" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="col-12 mb-4">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle mr-1"></i> {{ session('error') }}
                <button type="button" class="close text-dark" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif

    <!-- Content Sections -->
    <div class="col-md-8 mb-4">
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Collection & Transaction Details</h5>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Collection Number</label>
                    <span class="font-weight-bold text-dark">{{ $ccr->collection_number }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Transaction Number</label>
                    <span class="font-weight-bold text-dark">{{ $ccr->transaction->transaction_number ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Collected By (Staff)</label>
                    <span class="font-weight-bold text-dark">{{ $ccr->collectedBy->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Collection Date</label>
                    <span class="font-weight-bold text-dark">{{ $ccr->collection_date->format('d M Y, h:i A') }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Amount</label>
                    <span class="font-weight-bold text-success">₹{{ number_format($ccr->amount, 2) }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Receipt Receipt Number</label>
                    @if($ccr->receipt)
                        <span class="font-weight-bold text-dark">{{ $ccr->receipt->receipt_number }}</span>
                    @else
                        <span class="font-weight-bold text-dark">—</span>
                    @endif
                </div>
                @if($ccr->remarks)
                <div class="col-12 mb-3">
                    <label class="small text-muted d-block mb-1">Staff Remark</label>
                    <div class="border rounded bg-light p-2 text-dark">{{ $ccr->remarks }}</div>
                </div>
                @endif

                @if($ccr->status !== 'Pending Verification')
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Verified/Rejected By</label>
                    <span class="font-weight-bold text-dark">{{ $ccr->verifiedBy->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Processed At</label>
                    <span class="font-weight-bold text-dark">{{ $ccr->verified_at ? $ccr->verified_at->format('d M Y, h:i A') : 'N/A' }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Customer & Booking Details</h5>
            <div class="row">
                <div class="col-md-6 border-right">
                    <h6 class="font-weight-bold text-dark mb-3">Customer Information</h6>
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-0">Name</label>
                        <span class="font-weight-bold text-dark">{{ $ccr->customer->name ?? 'N/A' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-0">Email</label>
                        <span class="font-weight-bold text-dark">{{ $ccr->customer->email ?? 'N/A' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-0">Phone</label>
                        <span class="font-weight-bold text-dark">{{ $ccr->customer->phone ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-6 pl-md-4">
                    <h6 class="font-weight-bold text-dark mb-3">Booking Information</h6>
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-0">Booking Number</label>
                        <a href="{{ route('bookings.show', $ccr->booking_id) }}" class="font-weight-bold text-primary">{{ $ccr->booking->booking_number ?? 'N/A' }}</a>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-0">Product</label>
                        <span class="font-weight-bold text-dark">{{ $ccr->booking->product->name ?? 'N/A' }} ({{ $ccr->booking->product->sku ?? 'N/A' }})</span>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-0">EMI Plan</label>
                        <span class="font-weight-bold text-dark">{{ $ccr->booking->emiPlan->name ?? 'N/A' }} ({{ $ccr->booking->duration_months }} Months)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Panel -->
    <div class="col-md-4 mb-4">
        @if($ccr->status === 'Pending Verification')
        <div class="card bg-white border shadow-sm p-4 mb-4 text-center">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Verification Actions</h5>
            <p class="small text-muted mb-4">Verify the cash transaction details before approving. You must enter a remark to proceed.</p>
            
            @if(hasPermission('cash-collection.verify'))
            <button class="btn btn-success btn-block py-2 mb-2 font-weight-bold" onclick="showVerifyPopup()">
                <i class="mdi mdi-check-circle mr-1"></i> Verify Cash Payment
            </button>
            @endif

            @if(hasPermission('cash-collection.reject'))
            <button class="btn btn-danger btn-block py-2 font-weight-bold" onclick="showRejectPopup()">
                <i class="mdi mdi-close-circle mr-1"></i> Reject Cash Payment
            </button>
            @endif
        </div>
        @endif

        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Booking Timeline</h5>
            <div class="timeline-wrapper">
                @php
                    $timeline = \App\Models\ActivityLog::where('module_name', 'gold_booking')
                        ->where('record_id', $ccr->booking_id)
                        ->latest()
                        ->get();
                @endphp
                <ul class="timeline" style="padding-left: 20px;">
                    @forelse($timeline as $log)
                        <li class="timeline-item position-relative mb-4" style="list-style: none;">
                            <div class="timeline-badge bg-primary position-absolute rounded-circle" style="width: 10px; height: 10px; left: -15px; top: 5px;"></div>
                            <div class="ml-3">
                                <span class="font-weight-bold text-dark d-block">{{ $log->description }}</span>
                                <small class="text-muted d-block">{{ $log->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                        </li>
                    @empty
                        <li class="text-muted small">No timeline events recorded.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Forms for Verify & Reject -->
<form id="verifyForm" action="{{ route('admin.cash-collections.verify', $ccr->id) }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="remark" id="verifyRemarkInput">
</form>

<form id="rejectForm" action="{{ route('admin.cash-collections.reject', $ccr->id) }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="reason" id="rejectReasonInput">
</form>
@endsection

@push('scripts')
<script>
    function showVerifyPopup() {
        Swal.fire({
            title: 'Verify & Approve Cash',
            text: 'Enter a remark to verify and approve this cash collection:',
            input: 'textarea',
            inputPlaceholder: 'e.g. Cash received and verified by cashier.',
            inputAttributes: {
                'aria-label': 'Enter verification remark'
            },
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Approve Payment',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'You must enter a remark to verify!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $('#verifyRemarkInput').val(result.value);
                $('#verifyForm').submit();
            }
        });
    }

    function showRejectPopup() {
        Swal.fire({
            title: 'Reject Cash Payment',
            text: 'Provide a reason for rejecting this cash payment:',
            input: 'textarea',
            inputPlaceholder: 'e.g. Counterfeit note detected / Amount mismatch.',
            inputAttributes: {
                'aria-label': 'Enter rejection reason'
            },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Reject Payment',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'You must provide a reason for rejection!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $('#rejectReasonInput').val(result.value);
                $('#rejectForm').submit();
            }
        });
    }
</script>
<style>
    .timeline:before {
        left: 5px !important;
    }
</style>
@endpush
