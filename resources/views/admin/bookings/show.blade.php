@extends('layouts.app')

@section('content')
<style>
    .timeline {
        position: relative;
        padding: 10px 0;
        margin-top: 4px;
        list-style: none;
    }
    .timeline:before {
        content: " ";
        position: absolute;
        top: 0;
        bottom: 0;
        left: 20px;
        width: 3px;
        background-color: #e9ecef;
    }
    .timeline-item {
        margin-bottom: 25px;
        position: relative;
    }
    .timeline-item:before, .timeline-item:after {
        content: " ";
        display: table;
    }
    .timeline-item:after {
        clear: both;
    }
    .timeline-badge {
        color: #fff;
        width: 14px;
        height: 14px;
        position: absolute;
        top: 6px;
        left: 15px;
        margin-left: 0;
        background-color: #3f50f6;
        border: 3px solid #fff;
        border-radius: 50%;
        z-index: 100;
        box-shadow: 0 0 0 3px rgba(63, 80, 246, 0.2);
    }
    .timeline-badge.badge-warning {
        background-color: #ffc107;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.2);
    }
    .timeline-badge.badge-success {
        background-color: #24b47e;
        box-shadow: 0 0 0 3px rgba(36, 180, 126, 0.2);
    }
    .timeline-badge.badge-danger {
        background-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
    }
    .timeline-panel {
        width: calc(100% - 50px);
        float: right;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 15px;
        position: relative;
        background: #f8fafc;
    }
    .timeline-title {
        margin-top: 0;
        color: #333;
        font-weight: bold;
    }
    .timeline-panel:before {
        position: absolute;
        top: 6px;
        left: -11px;
        display: inline-block;
        border-top: 11px solid transparent;
        border-right: 11px solid #dee2e6;
        border-left: 0 solid #dee2e6;
        border-bottom: 11px solid transparent;
        content: " ";
    }
    .timeline-panel:after {
        position: absolute;
        top: 7px;
        left: -10px;
        display: inline-block;
        border-top: 10px solid transparent;
        border-right: 10px solid #f8fafc;
        border-left: 0 solid #f8fafc;
        border-bottom: 10px solid transparent;
        content: " ";
    }
</style>

<div class="row text-dark">
    <!-- Header panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <div class="d-flex align-items-center">
                        <span class="h4 font-weight-bold text-dark mb-0 mr-3">Booking #{{ $booking->booking_number }}</span>
                        @php
                            $badgeClass = 'badge-secondary';
                            switch($booking->status) {
                                case 'Draft': $badgeClass = 'badge-secondary'; break;
                                case 'Pending First EMI': $badgeClass = 'badge-warning'; break;
                                case 'Active': $badgeClass = 'badge-primary'; break;
                                case 'Completed': $badgeClass = 'badge-success'; break;
                                case 'Cancelled': $badgeClass = 'badge-danger'; break;
                                case 'Refund Initiated': $badgeClass = 'badge-info'; break;
                                case 'Refunded': $badgeClass = 'badge-dark'; break;
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $booking->status }}</span>
                    </div>
                    <p class="text-muted mb-0 mt-2">Locked Gold Price: <strong>₹{{ number_format($booking->locked_price_per_gram, 2) }} / g</strong> | Booked on {{ $booking->booking_date->format('d M Y, h:i A') }}</p>
                </div>
                <div>
                    <a href="{{ route('bookings.index') }}" class="btn btn-secondary px-4">
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

    <!-- Customer & Product Grid -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm h-100 p-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Customer Details</h5>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Name</label>
                    <span class="font-weight-bold text-dark">{{ $booking->customer->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Email</label>
                    <span class="font-weight-bold text-dark">{{ $booking->customer->email ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Phone Number</label>
                    <span class="font-weight-bold text-dark">{{ $booking->customer->customerDetail->phone_number ?? $booking->customer->phone ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">KYC Status</label>
                    <span class="badge badge-success text-dark font-weight-bold">Approved</span>
                </div>
            </div>
            
            <h5 class="text-primary font-weight-bold mt-3 mb-3 border-bottom pb-2">Product Specifications</h5>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Product Name</label>
                    <span class="font-weight-bold text-dark">{{ $booking->product->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">SKU</label>
                    <span class="font-weight-bold text-dark">{{ $booking->product->sku ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Gold Weight</label>
                    <span class="font-weight-bold text-dark">{{ number_format($booking->gold_weight, 2) }}g [{{ $booking->product->gold_type ?? 'N/A' }}]</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Purity</label>
                    <span class="font-weight-bold text-dark">{{ number_format($booking->gold_purity, 2) }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificate & Lifecycle actions -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm h-100 p-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Price Lock Certificate</h5>
            @if($booking->certificate)
                <div class="d-flex align-items-center flex-wrap mb-4">
                    @if($booking->certificate->qr_code && Storage::disk('public')->exists($booking->certificate->qr_code))
                        <div class="mr-3 border p-2 bg-light rounded text-center">
                            <img src="{{ asset('storage/' . $booking->certificate->qr_code) }}" alt="QR Code" style="width: 80px; height: 80px;">
                            <div class="small text-muted mt-1" style="font-size: 7px;">VERIFICATION QR</div>
                        </div>
                    @endif
                    <div>
                        <div class="font-weight-bold text-dark mb-1">Certificate #{{ $booking->certificate->certificate_number }}</div>
                        <div class="small text-muted mb-2">Issued on: {{ $booking->certificate->issued_at->format('d M Y, h:i A') }}</div>
                        @if(hasPermission('booking.download_certificate'))
                            <a href="{{ route('bookings.download_certificate', $booking->id) }}" class="btn btn-success btn-sm px-3">
                                <i class="mdi mdi-download mr-1"></i> Download PDF Certificate
                            </a>
                        @endif
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block mb-1">Verification Token</label>
                    <span class="font-weight-bold text-dark small" style="word-break: break-all;">{{ $booking->certificate->verification_token }}</span>
                </div>
            @else
                <div class="alert alert-warning mb-3">No active Price Lock Certificate registered.</div>
            @endif

            @if(hasPermission('booking.change_status'))
                <h5 class="text-primary font-weight-bold mt-2 mb-3 border-bottom pb-2">Lifecycle Management</h5>
                <form action="{{ route('bookings.change_status', $booking->id) }}" method="POST" class="row">
                    @csrf
                    <div class="col-md-6 form-group">
                        <label class="small text-muted font-weight-bold">Change Status</label>
                        <select name="status" class="form-control bg-white text-dark" required>
                            @foreach(['Draft', 'Pending First EMI', 'Active', 'Completed', 'Cancelled', 'Refund Initiated', 'Refunded'] as $statusOption)
                                <option value="{{ $statusOption }}" {{ $booking->status === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small text-muted font-weight-bold">Remarks</label>
                        <input type="text" name="remarks" class="form-control bg-white text-dark" placeholder="Specify transition reason...">
                    </div>
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-primary px-4 btn-md">Update Status</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <!-- Reusable Financial Summary Component -->
    <div class="col-12 mb-4">
        @include('admin.partials.financial-summary', [
            'showCustomer' => false,
            'showBooking' => false,
            'outstandingUrl' => route('purchase-preview.outstanding'),
            'pdfUrl' => route('purchase-preview.outstanding.pdf')
        ])
    </div>

    <!-- Status History Timeline -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100" style="max-height: 480px; overflow-y: auto;">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Lifecycle Audit Timeline</h5>
            <ul class="timeline">
                @forelse($booking->statusHistory as $history)
                    @php
                        $timelineBadgeClass = '';
                        switch($history->new_status) {
                            case 'Draft': $timelineBadgeClass = ''; break;
                            case 'Pending First EMI': $timelineBadgeClass = 'badge-warning'; break;
                            case 'Active': $timelineBadgeClass = 'badge-primary'; break;
                            case 'Completed': $timelineBadgeClass = 'badge-success'; break;
                            case 'Cancelled': $timelineBadgeClass = 'badge-danger'; break;
                        }
                    @endphp
                    <li class="timeline-item">
                        <div class="timeline-badge {{ $timelineBadgeClass }}"></div>
                        <div class="timeline-panel">
                            <div class="timeline-heading">
                                <h6 class="timeline-title font-weight-bold text-dark">{{ $history->new_status }}</h6>
                                <p class="mb-2"><small class="text-muted"><i class="mdi mdi-clock-outline"></i> {{ $history->created_at->format('d M Y, h:i A') }} by {{ $history->changedBy->name ?? 'System' }}</small></p>
                            </div>
                            <div class="timeline-body">
                                <p class="mb-0 text-dark small">{{ $history->remarks }}</p>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-muted text-center py-3">No lifecycle updates found.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Activity Log audit trail -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100" style="max-height: 480px; overflow-y: auto;">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Transaction Activity Logs</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered text-dark small">
                    <thead class="bg-light">
                        <tr>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Timestamp</th>
                            <th>Performed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activityLogs as $log)
                            <tr>
                                <td class="font-weight-bold text-uppercase">{{ str_replace('_', ' ', $log->action_type) }}</td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No activity logs recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let selectedCustomerId = "{{ $booking->customer_id }}";
    let selectedProductId = "{{ $booking->product_id }}";
    let selectedEmiPlanId = "{{ $booking->emi_plan_id }}";

    $(document).ready(function() {
        // Hydrate the Reusable Financial Summary Partial
        $('#sumProductSpecs').text("{{ $booking->product->name }} ({{ number_format($booking->gold_weight, 2) }}g, {{ $booking->product->gold_type }})");
        $('#sumPlanName').text("{{ $booking->emiPlan->plan_name }} ({{ $booking->duration_months }} Months)");
        $('#sumGoldRate').text("₹" + parseFloat("{{ $booking->locked_price_per_gram }}").toLocaleString() + "/g");
        $('#sumProductPrice').text("₹" + parseFloat("{{ $booking->locked_gold_value }}").toLocaleString(undefined, {minimumFractionDigits: 2}));

        @if($booking->gst_on_gold_amount > 0 || $booking->finance_charge_amount > 0 || $booking->storage_charge_amount > 0)
            $('#sumProcessingFeeBlock, #sumInterestBlock').attr('style', 'display: none !important;');
            
            @if($booking->gst_on_gold_amount > 0)
                $('#sumGstGoldBlock').attr('style', 'display: block !important;');
                $('#sumGstGold').text("₹" + parseFloat("{{ $booking->gst_on_gold_amount }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            @endif

            @if($booking->finance_charge_amount > 0)
                $('#sumFinanceBlock').attr('style', 'display: block !important;');
                $('#sumFinance').text("₹" + parseFloat("{{ $booking->finance_charge_amount }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            @endif

            @if($booking->storage_charge_amount > 0)
                $('#sumStorageBlock').attr('style', 'display: block !important;');
                $('#sumStorage').text("₹" + parseFloat("{{ $booking->storage_charge_amount }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            @endif

            @if($booking->gst_on_charges_amount > 0)
                $('#sumGstChargesBlock').attr('style', 'display: block !important;');
                $('#sumGstCharges').text("₹" + parseFloat("{{ $booking->gst_on_charges_amount }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            @endif
        @else
            $('#sumProcessingFeeBlock').attr('style', 'display: block !important;');
            $('#sumProcessingFee').text("₹" + parseFloat("{{ $booking->emiPlan->processing_fee }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            
            $('#sumInterestBlock').attr('style', 'display: block !important;');
            $('#sumInterest').text("₹" + parseFloat("{{ $booking->grand_total - $booking->locked_gold_value }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
            
            $('#sumGstGoldBlock, #sumFinanceBlock, #sumStorageBlock, #sumGstChargesBlock').attr('style', 'display: none !important;');
        @endif

        $('#sumEmiAmount').text("₹" + parseFloat("{{ $booking->monthly_emi }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
        $('#sumTotalPayable').text("₹" + parseFloat("{{ $booking->grand_total }}").toLocaleString(undefined, {minimumFractionDigits: 2}));
        $('#sumCompletionDate').text("{{ $booking->estimated_completion_date->format('Y-m-d') }}");
        $('#sumLateFee').text("₹" + parseFloat("{{ $booking->emiPlan->late_fee }}").toLocaleString() + " / Default");

        $('#summaryRow').show();
    });
</script>
@endpush
