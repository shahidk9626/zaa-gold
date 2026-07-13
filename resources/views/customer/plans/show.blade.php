<x-customer-layout title="Plan Details">
    <div class="page-header flex-wrap d-none d-md-flex">
        <h3 class="mb-0">Booking #{{ $booking->booking_number }}</h3>
        <a href="{{ route('customer.plans.index') }}" class="btn btn-secondary btn-sm"><i class="mdi mdi-arrow-left"></i> Back</a>
    </div>
    <div class="d-block d-md-none mb-3">
        <a href="{{ route('customer.plans.index') }}" class="text-muted small"><i class="mdi mdi-arrow-left"></i> Back to Plans</a>
        <h5 class="font-weight-bold mt-2">#{{ $booking->booking_number }}</h5>
        <span class="badge badge-primary">{{ $booking->status }}</span>
    </div>

    <ul class="nav nav-tabs border-0 bg-light rounded p-1 mb-4" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#financial">Financial</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#schedule">EMI Schedule</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#payments">Payments</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#documents">Documents</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#delivery">Delivery</a></li>
    </ul>

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
                                <tr>
                                    <td>{{ $emi->installment_number }}</td>
                                    <td>{{ $emi->due_date?->format('d M Y') }}</td>
                                    <td>₹{{ number_format($emi->emi_amount, 2) }}</td>
                                    <td><span class="badge badge-{{ $emi->status === 'Paid' ? 'success' : ($emi->status === 'Overdue' ? 'danger' : 'warning') }}">{{ $emi->status }}</span></td>
                                    <td>{{ $emi->paid_at?->format('d M Y') ?? '—' }}</td>
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
                        <a href="{{ route('customer.certificates.price_lock', $booking->id) }}" class="btn btn-sm btn-primary">Download PDF</a>
                    </div>
                </div>
                @endif
                @foreach($invoices as $invoice)
                <div class="col-md-4 grid-margin">
                    <div class="card text-center p-4">
                        <i class="mdi mdi-file-document text-success" style="font-size: 3rem;"></i>
                        <h6 class="mt-3">GST Invoice</h6>
                        <p class="text-muted small">{{ $invoice->invoice_number }}</p>
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
                <button class="btn btn-primary" data-toggle="modal" data-target="#requestDeliveryModal">Request Delivery</button>
                @endif
            @endif
        </div>
    </div>

    @if(!$delivery && in_array($booking->status, ['Active', 'Completed']))
    <div class="modal fade" id="requestDeliveryModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('customer.deliveries.store_request', $booking->id) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Request Delivery</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Delivery Method</label>
                        <select name="delivery_method" class="form-control" required>
                            <option value="Office Pickup">Office Pickup</option>
                            <option value="Courier">Courier</option>
                            <option value="Branch Pickup">Branch Pickup</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</x-customer-layout>
