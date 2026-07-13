@extends('layouts.app')

@section('content')
<style>
    .delivery-timeline {
        position: relative;
        padding-left: 30px;
        list-style: none;
    }
    .delivery-timeline:before {
        content: " ";
        position: absolute;
        top: 0;
        bottom: 0;
        left: 9px;
        width: 2px;
        background-color: #dee2e6;
    }
    .delivery-timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    .delivery-timeline-badge {
        position: absolute;
        left: -26px;
        top: 2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #3f50f6;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px rgba(63, 80, 246, 0.3);
    }
    .delivery-timeline-badge.badge-success {
        background-color: #24b47e;
        box-shadow: 0 0 0 2px rgba(36, 180, 126, 0.3);
    }
    .delivery-timeline-badge.badge-warning {
        background-color: #ffc107;
        box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.3);
    }
    .delivery-timeline-badge.badge-danger {
        background-color: #dc3545;
        box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.3);
    }
</style>

<div class="row text-dark">
    <!-- Header panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <div class="d-flex align-items-center">
                        <span class="h4 font-weight-bold text-dark mb-0 mr-3">Delivery Request #{{ $delivery->delivery_number }}</span>
                        @php
                            $badgeClass = 'badge-secondary';
                            switch($delivery->delivery_status) {
                                case 'Requested': $badgeClass = 'badge-warning'; break;
                                case 'Approved': $badgeClass = 'badge-info'; break;
                                case 'Ready For Dispatch': $badgeClass = 'badge-primary'; break;
                                case 'Dispatched': $badgeClass = 'badge-secondary'; break;
                                case 'Out For Delivery': $badgeClass = 'badge-dark'; break;
                                case 'Delivered': $badgeClass = 'badge-success'; break;
                                case 'Cancelled': $badgeClass = 'badge-danger'; break;
                                case 'Returned': $badgeClass = 'badge-light text-dark'; break;
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2 mr-3">{{ $delivery->delivery_status }}</span>
                    </div>
                    <p class="text-muted mb-0 mt-2">Requested on {{ $delivery->request_date ? $delivery->request_date->format('d M Y, h:i A') : '—' }} | Method: <strong>{{ $delivery->delivery_method }}</strong></p>
                </div>
                <div>
                    <a href="{{ route('deliveries.index') }}" class="btn btn-secondary px-4 mr-2">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to list
                    </a>
                    @if($delivery->pdf_path && hasPermission('delivery.download'))
                    <a href="{{ route('deliveries.download', $delivery->id) }}" class="btn btn-success px-4 mr-2">
                        <i class="mdi mdi-download mr-1"></i> Download Challan PDF
                    </a>
                    @endif
                    @if($delivery->delivery_status !== 'Cancelled' && $delivery->delivery_status !== 'Delivered' && hasPermission('delivery.cancel'))
                    <button type="button" class="btn btn-danger px-4" data-toggle="modal" data-target="#cancelDeliveryModal">
                        <i class="mdi mdi-close-circle mr-1"></i> Cancel Delivery
                    </button>
                    @endif
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

    <!-- Cancel Modal -->
    @if($delivery->delivery_status !== 'Cancelled' && $delivery->delivery_status !== 'Delivered' && hasPermission('delivery.cancel'))
    <div class="modal fade" id="cancelDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="cancelDeliveryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('deliveries.cancel', $delivery->id) }}" method="POST" class="modal-content bg-white text-dark">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-weight-bold text-danger" id="cancelDeliveryModalLabel">Cancel Gold Delivery Request</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Reason for Cancellation <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control bg-white text-dark" rows="3" placeholder="Provide cancellation reason..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Main Grid Content -->
    <div class="col-md-7 mb-4">
        <!-- Delivery Workflow Action Forms -->
        @if($delivery->delivery_status === 'Requested' && hasPermission('delivery.approve'))
            <!-- Approval Form -->
            <div class="card bg-white border border-info shadow-sm p-4 mb-4">
                <h5 class="text-info font-weight-bold mb-3"><i class="mdi mdi-checkbox-marked-circle-outline mr-1"></i> Review & Approve Request</h5>
                <p class="text-muted small">Verify that the client has completed all payments and booking matches required conditions. Click below to approve delivery and generate Challan PDF.</p>
                <form action="{{ route('deliveries.approve', $delivery->id) }}" method="POST" class="d-inline">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold small">Approval Remarks (Optional)</label>
                        <input type="text" name="remarks" class="form-control bg-white text-dark" placeholder="Specify approval remarks...">
                    </div>
                    <button type="submit" class="btn btn-info px-4">Approve Delivery Order</button>
                </form>
            </div>
        @endif

        @if($delivery->delivery_status === 'Approved' && $delivery->delivery_method === 'Courier' && hasPermission('delivery.dispatch'))
            <!-- Dispatch Courier Form -->
            <div class="card bg-white border border-primary shadow-sm p-4 mb-4">
                <h5 class="text-primary font-weight-bold mb-3"><i class="mdi mdi-truck-delivery mr-1"></i> Assign Courier & Dispatch</h5>
                <form action="{{ route('deliveries.dispatch', $delivery->id) }}" method="POST" class="row">
                    @csrf
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold small">Courier Partner <span class="text-danger">*</span></label>
                        <input type="text" name="courier_partner" class="form-control bg-white text-dark" placeholder="e.g. DHL, BlueDart" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold small">Tracking ID Number <span class="text-danger">*</span></label>
                        <input type="text" name="tracking_number" class="form-control bg-white text-dark" placeholder="e.g. AWB123456" required>
                    </div>
                    <div class="col-12 form-group">
                        <label class="font-weight-bold small">Tracking Website URL</label>
                        <input type="url" name="tracking_url" class="form-control bg-white text-dark" placeholder="e.g. https://dhl.com/track">
                    </div>
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-primary px-4">Confirm Dispatch</button>
                    </div>
                </form>
            </div>
        @endif

        @if(in_array($delivery->delivery_status, ['Approved', 'Dispatched']) && hasPermission('delivery.complete'))
            <!-- Completion Form -->
            <div class="card bg-white border border-success shadow-sm p-4 mb-4">
                <h5 class="text-success font-weight-bold mb-3"><i class="mdi mdi-checkbox-marked-circle mr-1"></i> Complete Gold Delivery Handover</h5>
                <form action="{{ route('deliveries.complete', $delivery->id) }}" method="POST" class="row">
                    @csrf
                    
                    @if(in_array($delivery->delivery_method, ['Office Pickup', 'Branch Pickup']))
                        <!-- OTP verification block -->
                        <div class="col-12 form-group">
                            <div class="alert alert-info p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="mdi mdi-key mr-1"></i> Pickup Verification OTP Code: <strong>{{ $delivery->otp }}</strong><br>
                                    <small class="text-muted">OTP Expires: {{ $delivery->otp_expires_at->format('d M Y, h:i A') }}</small>
                                </div>
                                <div>
                                    <a href="{{ route('deliveries.regenerate_otp', $delivery->id) }}" onclick="event.preventDefault(); document.getElementById('regen-otp-form').submit();" class="btn btn-sm btn-outline-info bg-white text-dark">Regenerate OTP</a>
                                </div>
                            </div>
                            <label class="font-weight-bold small">Submit Customer 6-Digit Verification OTP <span class="text-danger">*</span></label>
                            <input type="text" name="otp" class="form-control bg-white text-dark" placeholder="Enter 6-digit OTP code" required maxlength="6" style="letter-spacing: 3px; font-size: 1.2em; font-weight: bold; text-align: center;">
                        </div>
                    @endif

                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold small">Receiver's Name <span class="text-muted">(Default: Customer)</span></label>
                        <input type="text" name="receiver_name" class="form-control bg-white text-dark" value="{{ $delivery->customer->name }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold small">Receiver's Mobile Number</label>
                        <input type="text" name="receiver_mobile" class="form-control bg-white text-dark" value="{{ $delivery->customer->phone }}">
                    </div>
                    <div class="col-12 form-group">
                        <label class="font-weight-bold small">Receiver's ID Proof Details (PAN/Aadhar/Passport)</label>
                        <input type="text" name="receiver_id_proof" class="form-control bg-white text-dark" placeholder="e.g. Aadhar No / ID type">
                    </div>
                    <div class="col-12 form-group">
                        <label class="font-weight-bold small">Delivery Closing Remarks</label>
                        <input type="text" name="remarks" class="form-control bg-white text-dark" placeholder="Specify final handover remarks...">
                    </div>
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-success px-4">Complete Handover & Mark Delivered</button>
                    </div>
                </form>

                <form id="regen-otp-form" action="{{ route('deliveries.regenerate_otp', $delivery->id) }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        @endif

        <!-- General Parameters Card -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Customer & Booking Details</h5>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Customer Name</label>
                    <span class="font-weight-bold text-dark">{{ $delivery->customer->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Customer Email</label>
                    <span class="font-weight-bold text-dark">{{ $delivery->customer->email ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Gold Product Description</label>
                    <span class="font-weight-bold text-dark">{{ $delivery->booking->product->name ?? 'N/A' }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Gold Purity & Weight</label>
                    <span class="font-weight-bold text-dark">{{ number_format($delivery->booking->gold_weight, 2) }}g ({{ $delivery->booking->product->gold_type ?? 'N/A' }})</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Locked Price per Gram</label>
                    <span class="font-weight-bold text-dark">₹{{ number_format($delivery->booking->locked_price_per_gram, 2) }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Price Lock Certificate</label>
                    <span class="font-weight-bold text-dark">{{ $delivery->booking->certificate->certificate_number ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Shipping details Card -->
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Fulfillment Mode & Tracking</h5>
            @if($delivery->delivery_method === 'Courier')
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="small text-muted d-block mb-1">Delivery Address</label>
                        <span class="font-weight-bold text-dark">{{ $delivery->delivery_address ?? 'N/A' }}</span>
                    </div>
                    <div class="col-4 mb-3">
                        <label class="small text-muted d-block mb-1">Courier Partner</label>
                        <span class="font-weight-bold text-dark">{{ $delivery->courier_partner ?? 'Not Dispatched' }}</span>
                    </div>
                    <div class="col-4 mb-3">
                        <label class="small text-muted d-block mb-1">Tracking ID Number</label>
                        <span class="font-weight-bold text-dark">{{ $delivery->tracking_number ?? 'Not Dispatched' }}</span>
                    </div>
                    <div class="col-4 mb-3">
                        <label class="small text-muted d-block mb-1">Tracking Link</label>
                        @if($delivery->tracking_url)
                            <a href="{{ $delivery->tracking_url }}" target="_blank" class="font-weight-bold text-primary">Track Shipments <i class="mdi mdi-open-in-new"></i></a>
                        @else
                            <span class="font-weight-bold text-dark">—</span>
                        @endif
                    </div>
                </div>
            @elseif($delivery->delivery_method === 'Branch Pickup')
                <div class="row">
                    <div class="col-4 mb-3">
                        <label class="small text-muted d-block mb-1">Pickup Branch</label>
                        <span class="font-weight-bold text-dark">{{ $delivery->pickup_branch }}</span>
                    </div>
                    <div class="col-4 mb-3">
                        <label class="small text-muted d-block mb-1">Scheduled Pickup Date</label>
                        <span class="font-weight-bold text-dark">{{ $delivery->pickup_date ? $delivery->pickup_date->format('d M Y') : '—' }}</span>
                    </div>
                    <div class="col-4 mb-3">
                        <label class="small text-muted d-block mb-1">Scheduled Pickup Time</label>
                        <span class="font-weight-bold text-dark">{{ $delivery->pickup_time ?? '—' }}</span>
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <p class="mb-0 text-dark">This delivery is scheduled for **Office Pickup** at BKC Mumbai corporate office.</p>
                    </div>
                </div>
            @endif

            @if($delivery->receiver_name)
                <div class="row mt-3 border-top pt-3">
                    <div class="col-4 mb-3">
                        <label class="small text-muted d-block mb-1">Receiver's Name</label>
                        <span class="font-weight-bold text-dark">{{ $delivery->receiver_name }}</span>
                    </div>
                    <div class="col-4 mb-3">
                        <label class="small text-muted d-block mb-1">Receiver's Mobile</label>
                        <span class="font-weight-bold text-dark">{{ $delivery->receiver_mobile }}</span>
                    </div>
                    <div class="col-4 mb-3">
                        <label class="small text-muted d-block mb-1">Receiver's ID Proof</label>
                        <span class="font-weight-bold text-dark">{{ $delivery->receiver_id_proof ?? '—' }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Right Column (Timeline & Logs) -->
    <div class="col-md-5 mb-4">
        <!-- Status History Timeline -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Delivery Status History Timeline</h5>
            <ul class="delivery-timeline">
                @forelse($delivery->statusHistories as $history)
                    @php
                        $badgeClass = '';
                        switch($history->new_status) {
                            case 'Requested': $badgeClass = 'badge-warning'; break;
                            case 'Approved': $badgeClass = 'badge-info'; break;
                            case 'Dispatched': $badgeClass = ''; break;
                            case 'Delivered': $badgeClass = 'badge-success'; break;
                            case 'Cancelled': $badgeClass = 'badge-danger'; break;
                        }
                    @endphp
                    <li class="delivery-timeline-item">
                        <div class="delivery-timeline-badge {{ $badgeClass }}"></div>
                        <div class="font-weight-bold text-dark small">{{ $history->new_status }}</div>
                        <div class="small text-muted mb-1">{{ $history->created_at->format('d M Y, h:i A') }} by {{ $history->changedBy->name ?? 'System' }}</div>
                        @if($history->remarks)
                            <div class="small text-dark p-2 bg-light rounded mt-1 border">{{ $history->remarks }}</div>
                        @endif
                    </li>
                @empty
                    <li class="text-muted py-2 small">No history logged yet.</li>
                @endforelse
            </ul>
        </div>

        <!-- Activity Logs Card -->
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Related Fulfillment Audit Logs</h5>
            <div class="table-responsive">
                <table class="table table-bordered text-dark small">
                    <thead class="bg-light">
                        <tr>
                            <th>Action</th>
                            <th>Date</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activityLogs as $log)
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-uppercase" style="font-size: 9px;">{{ str_replace('_', ' ', $log->action_type) }}</div>
                                    <small class="text-muted" style="font-size: 9px;">{{ $log->description }}</small>
                                </td>
                                <td style="font-size: 9px;">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                <td style="font-size: 9px;">{{ $log->user->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted small py-3">No activity logs recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
