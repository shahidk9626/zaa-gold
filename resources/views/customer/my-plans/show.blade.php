<x-customer-layout title="Plan Details">
@php
    $hasActiveCancellation = $booking->cancellationRequests()
        ->whereIn('status', ['Requested', 'Under Review', 'Approved', 'Refund Initiated', 'Refund Completed'])
        ->exists();
    
    $canCancelPlan = in_array($booking->status, ['Active', 'Booked', 'Pending First EMI', 'Pending'])
        && !$hasActiveCancellation
        && !$booking->deliveries()->where('delivery_status', 'Delivered')->exists();
@endphp

    <div class="page-header flex-wrap d-none d-md-flex">
        <h3 class="mb-0">Booking #{{ $booking->booking_number }}</h3>
        <div>
            @if($booking->status === 'Draft')
                <form action="{{ route('customer.my-plans.destroy', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this draft booking?');" style="display:inline-block;" class="mr-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="mdi mdi-delete"></i> Delete Booking</button>
                </form>
            @endif
            @if($canCancelPlan)
                <button type="button" class="btn btn-danger btn-sm mr-2" data-toggle="modal" data-target="#cancellationModal" id="btnRequestCancellation"><i class="mdi mdi-close-circle"></i> Request Cancellation</button>
            @endif
            <a href="{{ route('customer.my-plans.index') }}" class="btn btn-secondary btn-sm"><i class="mdi mdi-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="d-block d-md-none mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="{{ route('customer.my-plans.index') }}" class="text-muted small"><i class="mdi mdi-arrow-left"></i> Back to Plans</a>
            @if($booking->status === 'Draft')
                <form action="{{ route('customer.my-plans.destroy', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this draft booking?');" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-xs"><i class="mdi mdi-delete"></i> Delete</button>
                </form>
            @endif
            @if($canCancelPlan)
                <button type="button" class="btn btn-danger btn-xs mr-2" data-toggle="modal" data-target="#cancellationModal" id="btnRequestCancellationMobile"><i class="mdi mdi-close-circle"></i> Cancel Plan</button>
            @endif
        </div>
        <h5 class="font-weight-bold">#{{ $booking->booking_number }}</h5>
        <span class="badge badge-primary">{{ $booking->status }}</span>
    </div>

    <ul class="nav nav-tabs border-0 bg-light rounded p-1 mb-4" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#financial">Financial</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#schedule">EMI Schedule</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#payments">Payments</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#documents">Documents</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#delivery">Delivery</a></li>
    </ul>

    @php
        $latestCancellation = $booking->latestCancellationRequest;
    @endphp

    @if($latestCancellation)
    <div class="card mb-4 border-danger">
        <div class="card-body">
            <h5 class="text-danger font-weight-bold mb-3"><i class="mdi mdi-alert-circle"></i> Plan Cancellation Status: {{ $latestCancellation->status }}</h5>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <p class="mb-1 text-muted small">Request Number</p>
                    <h6 class="font-weight-bold">{{ $latestCancellation->request_number }}</h6>
                </div>
                <div class="col-md-3 mb-2">
                    <p class="mb-1 text-muted small">Amount Paid</p>
                    <h6 class="font-weight-bold">₹{{ number_format($latestCancellation->total_amount_paid, 2) }}</h6>
                </div>
                <div class="col-md-3 mb-2">
                    <p class="mb-1 text-muted small">Cancellation Charge ({{ number_format($latestCancellation->cancellation_charge_percent, 2) }}%)</p>
                    <h6 class="font-weight-bold text-danger">₹{{ number_format($latestCancellation->cancellation_charge_amount, 2) }}</h6>
                </div>
                <div class="col-md-3 mb-2">
                    <p class="mb-1 text-muted small">Refund Amount</p>
                    <h6 class="text-success font-weight-bold">₹{{ number_format($latestCancellation->refund_amount, 2) }}</h6>
                </div>
            </div>
            <hr class="my-2">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <p class="mb-1 text-muted small">Your Reason</p>
                    <p class="font-italic mb-0 text-dark">"{{ $latestCancellation->cancellation_reason }}"</p>
                </div>
                <div class="col-md-6 mb-2">
                    @if(in_array($latestCancellation->status, ['Approved', 'Refund Initiated']))
                        <p class="mb-1 text-muted small">Refund Status</p>
                        <div class="alert alert-warning py-2 mb-0 text-dark" style="border-radius: 6px;">
                            <strong>Refund Pending:</strong> Refund will be processed within 7–10 Working Days.
                        </div>
                    @elseif($latestCancellation->status === 'Refund Completed')
                        <p class="mb-1 text-muted small">Refund Completed</p>
                        <div class="alert alert-success py-2 mb-0 text-dark" style="border-radius: 6px;">
                            <strong>Completed At:</strong> {{ $latestCancellation->refund_completed_at?->format('d M Y') }}<br>
                            <strong>Transaction:</strong> {{ $latestCancellation->refund_transaction_number }} (Mode: {{ $latestCancellation->refund_mode }})
                        </div>
                    @elseif($latestCancellation->status === 'Rejected')
                        <p class="mb-1 text-muted small">Request Rejected</p>
                        <div class="alert alert-danger py-2 mb-0 text-dark" style="border-radius: 6px;">
                            <strong>Remark:</strong> {{ $latestCancellation->admin_remark }}
                        </div>
                    @elseif($latestCancellation->status === 'Customer Retained')
                        <p class="mb-1 text-muted small">Customer Retained</p>
                        <div class="alert alert-success py-2 mb-0 text-dark" style="border-radius: 6px;">
                            You agreed to continue with the plan. Remark: "{{ $latestCancellation->admin_remark }}"
                        </div>
                    @else
                        <p class="mb-1 text-muted small">Admin Remark / Updates</p>
                        <p class="text-muted mb-0 small">{{ $latestCancellation->admin_remark ?? 'Under review by our operations team.' }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="tab-content">
        <div class="tab-pane fade show active" id="financial">
            <div class="row">
                <div class="col-md-6 grid-margin">
                    @include('customer.components.financial-summary', ['financials' => $financials])
                </div>
                <div class="col-md-6 grid-margin">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Plan Overview</h5>
                            <p><strong>Product:</strong> {{ $booking->product?->name }}</p>
                            <p><strong>Weight:</strong> {{ number_format($booking->gold_weight, 2) }}g</p>
                            <p><strong>EMI Plan:</strong> {{ $booking->emiPlan?->name }}</p>
                            <p><strong>Monthly EMI:</strong> ₹{{ number_format($booking->monthly_emi, 2) }}</p>
                            <p><strong>Duration:</strong> {{ $booking->duration_months }} months</p>
                            <p><strong>Locked Price:</strong> ₹{{ number_format($booking->locked_price_per_gram, 2) }}/g</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="schedule">
            <div class="card">
                <div class="card-body">
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover">
                            <thead class="bg-light">
                                <tr><th>#</th><th>Due Date</th><th>EMI Amount</th><th>Status</th><th>Paid At</th></tr>
                            </thead>
                            <tbody>
                                @foreach($schedule as $emi)
                                <tr class="{{ $emi->status === 'Waived' ? 'table-success text-success font-weight-bold' : '' }}">
                                    <td>{{ $emi->installment_number }}</td>
                                    <td>{{ $emi->due_date?->format('d M Y') }}</td>
                                    <td>
                                        @if($emi->status === 'Waived')
                                            <span class="text-success font-weight-bold">Waived</span>
                                        @else
                                            ₹{{ number_format($emi->emi_amount, 2) }}
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = 'warning';
                                            if ($emi->status === 'Paid' || $emi->status === 'Waived') {
                                                $badgeClass = 'success';
                                            } elseif ($emi->status === 'Overdue') {
                                                $badgeClass = 'danger';
                                            }
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }}">{{ $emi->status }}</span>
                                    </td>
                                    <td>{{ $emi->paid_at?->format('d M Y') ?? ($emi->status === 'Waived' ? 'Offer Benefit' : '—') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-block d-md-none">
                        @foreach($schedule as $emi)
                            @include('customer.components.emi-card', ['schedule' => $emi, 'showPayButton' => true])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="payments">
            @forelse($payments as $payment)
                @include('customer.components.payment-card', ['payment' => $payment])
            @empty
                <div class="alert alert-info">No payments recorded yet.</div>
            @endforelse
        </div>

        <div class="tab-pane fade" id="documents">
            <div class="row">
                @if($booking->certificate)
                <div class="col-md-4 grid-margin">
                    <div class="card text-center p-4">
                        <i class="mdi mdi-certificate text-primary" style="font-size: 3rem;"></i>
                        <h6 class="mt-3">Price Lock Certificate</h6>
                        <p class="text-muted small">{{ $booking->certificate->certificate_number }}</p>
                        <!-- <a href="{{ route('customer.certificates.price_lock_preview', $booking->id) }}" target="_blank" class="btn btn-sm btn-outline-primary btn-block mb-2">View & Print</a> -->
                        <a href="{{ route('customer.certificates.price_lock', $booking->id) }}" class="btn btn-sm btn-primary btn-block">Download PDF</a>
                    </div>
                </div>
                @endif
                @foreach($invoices as $invoice)
                @php
                    $desc = 'Downpayment / Booking';
                    if ($invoice->payment && $invoice->payment->emiSchedule) {
                        $desc = 'EMI #' . $invoice->payment->emiSchedule->installment_number;
                    }
                @endphp
                <div class="col-md-4 grid-margin">
                    <div class="card text-center p-4">
                        <i class="mdi mdi-file-document text-success" style="font-size: 3rem;"></i>
                        <h6 class="mt-3 font-weight-bold">GST Invoice ({{ $desc }})</h6>
                        <p class="text-muted small">Invoice No: {{ $invoice->invoice_number }}</p>
                        <a href="{{ route('customer.certificates.invoice', $invoice->id) }}" class="btn btn-sm btn-primary">Download PDF</a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="tab-pane fade" id="delivery">
            @if($delivery)
                @include('customer.components.delivery-card', ['delivery' => $delivery])
                <a href="{{ route('customer.deliveries.show', $delivery->id) }}" class="btn btn-primary">View Delivery Timeline</a>
            @else
                <div class="alert alert-info">No delivery request yet.</div>
                @if(in_array($booking->status, ['Active', 'Completed']))
                    @if($canRequestDelivery)
                        <button class="btn btn-primary" data-toggle="modal" data-target="#requestDeliveryModal">Request Delivery</button>
                    @else
                        <div class="alert alert-danger border-0 p-3 mb-3 text-dark" role="alert" style="border-radius: 8px;">
                            <h6 class="alert-heading mb-1 font-weight-bold"><i class="mdi mdi-alert-circle mr-1"></i> Delivery Restricted</h6>
                            <p class="mb-2 small">Please complete your Profile and KYC verification before requesting Gold Delivery.</p>
                            <a href="{{ route('customer.profile.index') }}" class="btn btn-danger btn-sm font-weight-bold px-3 py-2 text-white">Complete Profile & KYC</a>
                        </div>
                        <button class="btn btn-primary" disabled>Request Delivery</button>
                    @endif
                @endif
            @endif
        </div>
    </div>

    @if(!$delivery && in_array($booking->status, ['Active', 'Completed']))
    <div class="modal fade" id="requestDeliveryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('customer.deliveries.store_request', $booking->id) }}" method="POST" class="modal-content" id="deliveryRequestForm">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Request Delivery</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold">Delivery Method</label>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="card h-100 border p-3 mb-0">
                                    <span class="d-flex align-items-start">
                                        <input type="radio" name="delivery_method" value="Branch Pickup" class="mr-2 mt-1 delivery-method-radio" checked>
                                        <span><strong>Branch Pickup</strong><small class="text-muted d-block">Collect from branch after admin verification.</small></span>
                                    </span>
                                </label>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="card h-100 border p-3 mb-0">
                                    <span class="d-flex align-items-start">
                                        <input type="radio" name="delivery_method" value="Courier" class="mr-2 mt-1 delivery-method-radio">
                                        <span><strong>Courier Delivery</strong><small class="text-muted d-block">Ship to a saved customer address.</small></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="branchPickupFields">
                        <div class="form-group">
                            <label class="font-weight-bold">Select Preferred Pickup Date <span class="text-danger">*</span></label>
                            <input type="date" name="preferred_pickup_date" class="form-control" min="{{ now()->toDateString() }}">
                        </div>
                        <div class="alert alert-info">Our team will confirm your appointment after verification.</div>
                    </div>

                    <div id="courierFields" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold mb-0">Select Delivery Address</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#addAddressModal"><i class="mdi mdi-plus"></i> Add New Address</button>
                        </div>
                        <div class="row" id="addressCardList">
                            @forelse($customerAddresses ?? collect() as $address)
                                <div class="col-md-6 mb-3 address-card-wrapper">
                                    <label class="card border h-100 p-3 mb-0">
                                        <span class="d-flex align-items-start">
                                            <input type="radio" name="customer_address_id" value="{{ $address->id }}" class="mr-2 mt-1 address-radio" {{ $address->is_default || $loop->first ? 'checked' : '' }}>
                                            <span>
                                                <strong>{{ $address->address_name }}</strong>
                                                <span class="badge badge-light text-dark ml-1">{{ $address->address_type }}</span>
                                                @if($address->is_default)<span class="badge badge-success ml-1">Default</span>@endif
                                                <small class="d-block text-muted mt-1">{{ $address->full_address }}</small>
                                                <small class="d-block text-dark">Mobile: {{ $address->mobile }}</small>
                                                <small class="d-block text-muted">{{ $address->city }}, {{ $address->state }} - {{ $address->pin_code }}</small>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            @empty
                                <div class="col-12" id="noAddressAlert"><div class="alert alert-warning">No saved addresses found. Add a new address to request courier delivery.</div></div>
                            @endforelse
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit Delivery Request</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addAddressModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('customer.addresses.store') }}" method="POST" class="modal-content" id="addAddressForm">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add New Address</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="addressFormError"></div>
                    <div class="row">
                        <div class="col-md-6 form-group"><label>Name <span class="text-danger">*</span></label><input type="text" name="address_name" class="form-control" required></div>
                        <div class="col-md-3 form-group"><label>Mobile <span class="text-danger">*</span></label><input type="text" name="mobile" class="form-control" required></div>
                        <div class="col-md-3 form-group"><label>Alternate Mobile</label><input type="text" name="alternate_mobile" class="form-control"></div>
                        <div class="col-md-4 form-group"><label>House No <span class="text-danger">*</span></label><input type="text" name="house_no" class="form-control" required></div>
                        <div class="col-md-4 form-group"><label>Street</label><input type="text" name="street" class="form-control"></div>
                        <div class="col-md-4 form-group"><label>Area</label><input type="text" name="area" class="form-control"></div>
                        <div class="col-md-4 form-group"><label>Landmark</label><input type="text" name="landmark" class="form-control"></div>
                        <div class="col-md-4 form-group"><label>City <span class="text-danger">*</span></label><input type="text" name="city" class="form-control" required></div>
                        <div class="col-md-4 form-group"><label>State <span class="text-danger">*</span></label><input type="text" name="state" class="form-control" required></div>
                        <div class="col-md-4 form-group"><label>PIN Code <span class="text-danger">*</span></label><input type="text" name="pin_code" class="form-control" required></div>
                        <div class="col-md-4 form-group"><label>Country <span class="text-danger">*</span></label><input type="text" name="country" class="form-control" value="India" required></div>
                        <div class="col-md-4 form-group">
                            <label>Address Type <span class="text-danger">*</span></label>
                            <select name="address_type" class="form-control" required><option value="Home">Home</option><option value="Office">Office</option><option value="Other">Other</option></select>
                        </div>
                        <div class="col-md-4 form-group d-flex align-items-end">
                            <label class="form-check-label mb-2"><input type="checkbox" name="is_default" value="1" class="form-check-input"> Mark as default <i class="input-helper"></i></label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Address</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Cancellation Preview Modal -->
    <div class="modal fade text-dark" id="cancellationModal" tabindex="-1" role="dialog" aria-labelledby="cancellationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('customer.my-plans.cancel', $booking->id) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold text-white" id="cancellationModalLabel"><i class="mdi mdi-close-circle"></i> Plan Cancellation Preview</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-dark">
                    <div id="cancellationLoading" class="text-center p-4">
                        <div class="spinner-border text-danger" role="status">
                            <span class="sr-only">Loading calculations...</span>
                        </div>
                        <p class="mt-2 text-muted">Calculating refund amount...</p>
                    </div>

                    <div id="cancellationError" class="alert alert-danger d-none"></div>

                    <div id="cancellationContent" class="d-none">
                        <table class="table table-bordered mb-4">
                            <tbody>
                                <tr>
                                    <th class="bg-light text-dark font-weight-bold">Total Amount Paid</th>
                                    <td align="right" class="font-weight-bold text-dark"><span id="lblTotalPaid">₹0.00</span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-dark font-weight-bold">Cancellation Charges (<span id="lblChargePercent">0</span>%)</th>
                                    <td align="right" class="text-danger font-weight-bold">- <span id="lblChargeAmount">₹0.00</span></td>
                                </tr>
                                <tr class="table-success text-success">
                                    <th class="bg-light font-weight-bold text-dark">Refund Amount</th>
                                    <td align="right" class="font-weight-bold text-success" style="font-size: 1.1rem;"><span id="lblRefundAmount">₹0.00</span></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">Cancellation Reason <span class="text-danger">*</span></label>
                            <textarea name="cancellation_reason" id="cancellation_reason" class="form-control bg-white text-dark border" rows="3" placeholder="Please tell us why you want to cancel (maximum 500 characters)..." required maxlength="500"></textarea>
                            <small class="text-muted"><span id="charCount">0</span> / 500 characters</small>
                        </div>

                        <!-- Optional Bank Details -->
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body p-3">
                                <h6 class="font-weight-bold mb-2 text-dark"><i class="mdi mdi-bank"></i> Refund Bank Account (Optional)</h6>
                                <p class="small text-muted mb-2">Provide these details in case payment gateway automated refund fails.</p>
                                <div class="form-group mb-2">
                                    <input type="text" name="bank_name" class="form-control bg-white text-dark py-1 border" placeholder="Bank Name">
                                </div>
                                <div class="row">
                                    <div class="col-md-7 form-group mb-0">
                                        <input type="text" name="bank_account_number" class="form-control bg-white text-dark py-1 border" placeholder="Account Number">
                                    </div>
                                    <div class="col-md-5 form-group mb-0">
                                        <input type="text" name="bank_ifsc" class="form-control bg-white text-dark py-1 border" placeholder="IFSC Code">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <label class="form-check-label text-dark font-weight-bold">
                                <input type="checkbox" name="terms" value="1" required class="form-check-input">
                                I understand that once the cancellation request is submitted, it cannot be edited.
                                <i class="input-helper"></i>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitCancellation" class="btn btn-danger" disabled>Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function syncDeliveryMethodFields() {
                var method = document.querySelector('input[name="delivery_method"]:checked')?.value;
                var isCourier = method === 'Courier';
                $('#courierFields').toggle(isCourier);
                $('#branchPickupFields').toggle(!isCourier);
                $('input[name="preferred_pickup_date"]').prop('required', !isCourier);
                $('input[name="customer_address_id"]').prop('required', isCourier);
            }

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function(char) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
                });
            }

            $('.delivery-method-radio').on('change', syncDeliveryMethodFields);
            syncDeliveryMethodFields();

            $('#addAddressForm').on('submit', function(event) {
                event.preventDefault();
                var form = this;
                $('#addressFormError').addClass('d-none').text('');

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new FormData(form)
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data: data })))
                .then(result => {
                    if (!result.ok || !result.data.success) {
                        var message = result.data.message || 'Unable to save address.';
                        if (result.data.errors) {
                            message = Object.values(result.data.errors).flat().join(' ');
                        }
                        $('#addressFormError').text(message).removeClass('d-none');
                        return;
                    }

                    var address = result.data.address;
                    var badgeDefault = address.is_default ? '<span class="badge badge-success ml-1">Default</span>' : '';
                    $('#noAddressAlert').remove();
                    $('.address-radio').prop('checked', false);
                    $('#addressCardList').prepend(`
                        <div class="col-md-6 mb-3 address-card-wrapper">
                            <label class="card border h-100 p-3 mb-0">
                                <span class="d-flex align-items-start">
                                    <input type="radio" name="customer_address_id" value="${address.id}" class="mr-2 mt-1 address-radio" checked>
                                    <span>
                                        <strong>${escapeHtml(address.address_name)}</strong>
                                        <span class="badge badge-light text-dark ml-1">${escapeHtml(address.address_type)}</span>
                                        ${badgeDefault}
                                        <small class="d-block text-muted mt-1">${escapeHtml(address.full_address)}</small>
                                        <small class="d-block text-dark">Mobile: ${escapeHtml(address.mobile)}</small>
                                        <small class="d-block text-muted">${escapeHtml(address.city)}, ${escapeHtml(address.state)} - ${escapeHtml(address.pin_code)}</small>
                                    </span>
                                </span>
                            </label>
                        </div>
                    `);
                    form.reset();
                    $('input[name="country"]', form).val('India');
                    $('#addAddressModal').modal('hide');
                    $('#requestDeliveryModal').modal('show');
                    syncDeliveryMethodFields();
                })
                .catch(() => {
                    $('#addressFormError').text('Unable to save address. Please try again.').removeClass('d-none');
                });
            });

            var btnCancel = document.querySelectorAll('#btnRequestCancellation, #btnRequestCancellationMobile');
            
            btnCancel.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    $('#cancellationLoading').removeClass('d-none');
                    $('#cancellationContent').addClass('d-none');
                    $('#cancellationError').addClass('d-none');
                    $('#btnSubmitCancellation').prop('disabled', true);

                    var url = "{{ route('customer.my-plans.cancellation_preview', $booking->id) }}";
                    fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        $('#cancellationLoading').addClass('d-none');
                        if (data.success) {
                            $('#lblTotalPaid').text('₹' + Number(data.total_amount_paid).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            $('#lblChargePercent').text(data.cancellation_charge_percent);
                            $('#lblChargeAmount').text('₹' + Number(data.cancellation_charge_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            $('#lblRefundAmount').text('₹' + Number(data.refund_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            
                            $('#cancellationContent').removeClass('d-none');
                            $('#btnSubmitCancellation').prop('disabled', false);
                        } else {
                            $('#cancellationError').text(data.error || 'Failed to compute refund.').removeClass('d-none');
                        }
                    })
                    .catch(err => {
                        $('#cancellationLoading').addClass('d-none');
                        $('#cancellationError').text('An error occurred while loading refund calculations.').removeClass('d-none');
                    });
                });
            });

            var txtReason = document.getElementById('cancellation_reason');
            if (txtReason) {
                txtReason.addEventListener('input', function() {
                    var len = this.value.length;
                    document.getElementById('charCount').textContent = len;
                });
            }
        });
    </script>
</x-customer-layout>
