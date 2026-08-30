@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">Referral Details</h4>
                    <p class="card-description text-muted mb-0">Detailed view of Customer Referral record #{{ $referral->id }}</p>
                </div>
                <div>
                    @if(hasPermission('referral.edit'))
                    <a href="{{ route('referrals.edit', $referral->id) }}" class="btn btn-primary px-4 mr-2">
                        <i class="mdi mdi-lead-pencil mr-1"></i> Edit / Process Referral
                    </a>
                    @endif
                    <a href="{{ route('referrals.index') }}" class="btn btn-secondary px-4">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to Referral Listing
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- REFERRAL DETAILS & STATUS -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm">
            <div class="card-header bg-primary text-white p-3 font-weight-bold">
                <i class="mdi mdi-ticket-confirmation mr-2"></i> REFERRAL DETAILS
            </div>
            <div class="card-body p-4">
                @php
                    $statusBadge = match($referral->status) {
                        'Completed' => 'badge-success',
                        'Pending' => 'badge-warning text-dark',
                        'Under Review' => 'badge-info',
                        'Approved' => 'badge-primary',
                        'Rejected' => 'badge-danger',
                        default => 'badge-secondary',
                    };
                @endphp
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="text-muted font-weight-bold d-block">Referral Code Used</label>
                        <span class="h5 font-weight-bold text-primary mb-0">{{ $referral->referral_code }}</span>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="text-muted font-weight-bold d-block">Referral Date</label>
                        <span class="h6 text-dark font-weight-bold mb-0">
                            {{ $referral->referred_at ? $referral->referred_at->format('d/m/Y h:i A') : 'N/A' }}
                        </span>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="text-muted font-weight-bold d-block">Cashback Status</label>
                        <span class="badge {{ $statusBadge }} font-weight-bold px-3 py-2">
                            {{ $referral->status }}
                        </span>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="text-muted font-weight-bold d-block">Payment Reference (UTR)</label>
                        <span class="h6 text-dark font-weight-bold mb-0">
                            {{ $referral->payment_reference_number ?? 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CASHBACK CALCULATIONS -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm">
            <div class="card-header bg-success text-white p-3 font-weight-bold">
                <i class="mdi mdi-cash-multiple mr-2"></i> CASHBACK CALCULATIONS
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="text-muted font-weight-bold d-block">Gold Weight</label>
                        <span class="h5 font-weight-bold text-dark mb-0">{{ $referral->gold_weight ? number_format($referral->gold_weight, 3) . ' g' : 'N/A' }}</span>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted font-weight-bold d-block">Cashback Rate</label>
                        <span class="h5 font-weight-bold text-dark mb-0">{{ $referral->cashback_rate ? '₹' . number_format($referral->cashback_rate, 2) . ' / g' : 'N/A' }}</span>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted font-weight-bold d-block">Calculated Cashback</label>
                        <span class="h4 font-weight-bold text-success mb-0">{{ $referral->cashback_amount ? '₹' . number_format($referral->cashback_amount, 2) : 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- REFERRER DETAILS CARD -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm h-100">
            <div class="card-header bg-dark text-white p-3 font-weight-bold">
                <i class="mdi mdi-account-star mr-2"></i> REFERRER DETAILS (Recommender)
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-borderless text-dark mb-0">
                        <tbody>
                            <tr>
                                <th class="pl-0 text-muted" style="width: 45%;">Referrer Name:</th>
                                <td class="font-weight-bold">{{ $referral->referrer->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Referrer Type:</th>
                                <td>
                                    @php
                                        $refType = $referral->referrer_type ? ucfirst($referral->referrer_type) : ($referral->staff_id ? 'Staff' : 'Customer');
                                        $typeBadge = $refType === 'Staff' ? 'badge-info' : 'badge-primary';
                                    @endphp
                                    <span class="badge {{ $typeBadge }} font-weight-bold">{{ $refType }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Referrer User ID:</th>
                                <td>
                                    <span class="badge badge-light border font-weight-bold">
                                        #{{ $referral->referrer->id ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Referrer Code:</th>
                                <td>
                                    <span class="badge badge-outline-dark font-weight-bold">
                                        {{ $referral->referrer->referral_code ?? ($referral->referrer->staffDetail->emp_code ?? 'N/A') }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Referrer Email:</th>
                                <td>{{ $referral->referrer->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Referrer Mobile:</th>
                                <td>{{ $referral->referrer->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Referrer Status:</th>
                                <td>
                                    @php
                                        $refStatus = $referral->referrer->status ?? 'inactive';
                                        $refBadge = $refStatus === 'active' ? 'badge-success' : 'badge-danger';
                                    @endphp
                                    <span class="badge {{ $refBadge }} font-weight-bold px-2 py-1">
                                        {{ ucfirst($refStatus) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- REFERRED CUSTOMER DETAILS CARD -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm h-100">
            <div class="card-header bg-info text-white p-3 font-weight-bold">
                <i class="mdi mdi-account-check mr-2"></i> REFERRED CUSTOMER DETAILS (Purchaser)
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-borderless text-dark mb-0">
                        <tbody>
                            <tr>
                                <th class="pl-0 text-muted" style="width: 45%;">Customer Name:</th>
                                <td class="font-weight-bold">{{ $referral->customer->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Customer User ID:</th>
                                <td>
                                    <span class="badge badge-light border font-weight-bold">
                                        #{{ $referral->customer->id ?? 'N/A' }}
                                    </span>
                                    @if(hasPermission('customer.view') && $referral->customer)
                                    <a href="{{ route('customers.show', $referral->customer->id) }}" class="ml-2 small text-primary font-weight-bold" target="_blank">
                                        View Full Profile <i class="mdi mdi-open-in-new"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Customer Email:</th>
                                <td>{{ $referral->customer->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Customer Mobile:</th>
                                <td>{{ $referral->customer->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Registration Date:</th>
                                <td>{{ $referral->customer && $referral->customer->created_at ? $referral->customer->created_at->format('d/m/Y h:i A') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Customer Status:</th>
                                <td>
                                    @php
                                        $custStatus = $referral->customer->status ?? 'inactive';
                                        $custBadge = $custStatus === 'active' ? 'badge-success' : 'badge-danger';
                                    @endphp
                                    <span class="badge {{ $custBadge }} font-weight-bold px-2 py-1">
                                        {{ ucfirst($custStatus) }}
                                    </span>
                                </td>
                            </tr>
                            @if(isset($referral->customer->verification_status))
                            <tr>
                                <th class="pl-0 text-muted">Verification Status:</th>
                                <td>
                                    <span class="badge badge-outline-info font-weight-bold">
                                        {{ ucfirst($referral->customer->verification_status) }}
                                    </span>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOKING LINKAGE DETAILS -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm">
            <div class="card-header bg-secondary text-dark p-3 font-weight-bold">
                <i class="mdi mdi-shopping mr-2"></i> ASSOCIATED BOOKING DETAILS
            </div>
            <div class="card-body p-4">
                @if($referral->booking)
                    <div class="table-responsive">
                        <table class="table table-borderless text-dark mb-0">
                            <tbody>
                                <tr>
                                    <th class="pl-0 text-muted" style="width: 45%;">Booking Reference:</th>
                                    <td class="font-weight-bold">#{{ $referral->booking->booking_number }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0 text-muted">Product Purchased:</th>
                                    <td>{{ $referral->booking->product?->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0 text-muted">Plan Duration:</th>
                                    <td>{{ $referral->booking->duration_months }} Months Plan</td>
                                </tr>
                                <tr>
                                    <th class="pl-0 text-muted">Booking Date:</th>
                                    <td>{{ $referral->booking->booking_date ? $referral->booking->booking_date->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0 text-muted">Booking Grand Total:</th>
                                    <td class="font-weight-bold text-dark">₹{{ number_format($referral->booking->grand_total, 2) }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0 text-muted">Booking Status:</th>
                                    <td>
                                        <span class="badge badge-light border font-weight-bold">{{ $referral->booking->status }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">No booking linked to this referral. Associate a booking by clicking the Edit button above.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- AUDIT & ADMINISTRATIVE INFO -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm">
            <div class="card-header bg-light text-dark p-3 font-weight-bold">
                <i class="mdi mdi-information-outline mr-2"></i> AUDIT & ADMINISTRATIVE LOGS
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-borderless text-dark mb-0">
                        <tbody>
                            <tr>
                                <th class="pl-0 text-muted" style="width: 45%;">Admin Processed By:</th>
                                <td>{{ $referral->processor->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Payout Completed Date:</th>
                                <td>{{ $referral->completed_at ? $referral->completed_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="pl-0 text-muted">Admin Description / Remarks:</th>
                                <td>
                                    <p class="mb-0 text-dark small" style="white-space: pre-line;">{{ $referral->admin_remark ?? 'No remarks added.' }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
