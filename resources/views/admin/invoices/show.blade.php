@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <div class="d-flex align-items-center">
                        <span class="h4 font-weight-bold text-dark mb-0 mr-3">Invoice #{{ $invoice->invoice_number }}</span>
                        @php
                            $badgeClass = 'badge-secondary';
                            switch($invoice->invoice_status) {
                                case 'Generated': $badgeClass = 'badge-success'; break;
                                case 'Draft': $badgeClass = 'badge-warning'; break;
                                case 'Cancelled': $badgeClass = 'badge-danger'; break;
                                case 'Revised': $badgeClass = 'badge-info'; break;
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2 mr-3">{{ $invoice->invoice_status }}</span>
                    </div>
                    <p class="text-muted mb-0 mt-2">Generated on {{ $invoice->invoice_date->format('d M Y, h:i A') }} | Linked Payment Ref: <strong>{{ $invoice->payment->payment_number ?? 'N/A' }}</strong></p>
                </div>
                <div>
                    <a href="{{ route('invoices.index') }}" class="btn btn-secondary px-4 mr-2">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to list
                    </a>
                    @if(hasPermission('invoice.download'))
                    <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-success px-4 mr-2">
                        <i class="mdi mdi-download mr-1"></i> Download PDF
                    </a>
                    @endif
                    @if(hasPermission('invoice.print'))
                    <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" class="btn btn-primary px-4 mr-2">
                        <i class="mdi mdi-printer mr-1"></i> Print
                    </a>
                    @endif
                    @if($invoice->invoice_status !== 'Cancelled' && hasPermission('invoice.cancel'))
                    <button type="button" class="btn btn-danger px-4" data-toggle="modal" data-target="#cancelInvoiceModal">
                        <i class="mdi mdi-close-circle mr-1"></i> Cancel Invoice
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

    <!-- Cancel Invoice Modal -->
    @if($invoice->invoice_status !== 'Cancelled' && hasPermission('invoice.cancel'))
    <div class="modal fade" id="cancelInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="cancelInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('invoices.cancel', $invoice->id) }}" method="POST" class="modal-content bg-white text-dark">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-weight-bold text-danger" id="cancelInvoiceModalLabel">Cancel GST Invoice</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert mr-1"></i> <strong>Warning:</strong> Cancelling an invoice is irreversible. The invoice status will be permanently set to "Cancelled".
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Reason for Cancellation <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control bg-white text-dark" rows="3" placeholder="Explain the reason for cancelling this tax invoice..." required></textarea>
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

    <!-- Information Columns -->
    <div class="col-md-6 mb-4">
        <!-- Billing Details -->
        <div class="card bg-white border shadow-sm p-4 mb-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Customer & Billing Details (Snapshot)</h5>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Customer Name</label>
                    <span class="font-weight-bold text-dark">{{ $invoice->customer_name }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Customer Email</label>
                    <span class="font-weight-bold text-dark">{{ $invoice->customer_email }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Customer Phone</label>
                    <span class="font-weight-bold text-dark">{{ $invoice->customer_phone }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label class="small text-muted d-block mb-1">Booking Reference</label>
                    <a href="{{ route('bookings.show', $invoice->booking_id) }}" class="font-weight-bold text-primary">
                        {{ $invoice->booking->booking_number }}
                    </a>
                </div>
                <div class="col-12 mb-3">
                    <label class="small text-muted d-block mb-1">Billing Address</label>
                    <span class="font-weight-bold text-dark">{{ $invoice->billing_address }}</span>
                </div>
            </div>
        </div>

        <!-- Product Specs -->
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Gold Product Specifications (Snapshot)</h5>
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="small text-muted d-block mb-1">Product Description</label>
                    <span class="font-weight-bold text-dark">{{ $invoice->product_name }}</span>
                </div>
                <div class="col-4 mb-3">
                    <label class="small text-muted d-block mb-1">Gold Weight</label>
                    <span class="font-weight-bold text-dark">{{ number_format($invoice->gold_weight, 2) }}g</span>
                </div>
                <div class="col-4 mb-3">
                    <label class="small text-muted d-block mb-1">Gold Purity</label>
                    <span class="font-weight-bold text-dark">{{ number_format($invoice->gold_purity, 2) }}%</span>
                </div>
                <div class="col-4 mb-3">
                    <label class="small text-muted d-block mb-1">Price Lock / Gram</label>
                    <span class="font-weight-bold text-dark">₹{{ number_format($invoice->locked_gold_price, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <!-- Financial Summary -->
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Financial Snapshot Summary</h5>
            
            <table class="table table-bordered text-dark small mb-4">
                <thead class="bg-light">
                    <tr>
                        <th>Financial Component</th>
                        <th class="text-right">Value (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gold Value Portion (Taxable)</td>
                        <td class="text-right">₹{{ number_format($invoice->gold_value, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Finance Charges</td>
                        <td class="text-right">₹{{ number_format($invoice->finance_charge, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Storage Charges</td>
                        <td class="text-right">₹{{ number_format($invoice->storage_charge, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Late Payment Fee</td>
                        <td class="text-right text-danger">₹{{ number_format($invoice->subtotal - $invoice->gold_value - $invoice->finance_charge - $invoice->storage_charge, 2) }}</td>
                    </tr>
                    <tr class="font-weight-bold bg-light">
                        <td>Taxable Subtotal</td>
                        <td class="text-right">₹{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    
                    <!-- GST breakdown details -->
                    @if($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0)
                        <tr>
                            <td>CGST [{{ number_format($invoice->cgst_percent, 2) }}%]</td>
                            <td class="text-right">₹{{ number_format($invoice->cgst_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>SGST [{{ number_format($invoice->sgst_percent, 2) }}%]</td>
                            <td class="text-right">₹{{ number_format($invoice->sgst_amount, 2) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>IGST [{{ number_format($invoice->igst_percent, 2) }}%]</td>
                            <td class="text-right text-primary">₹{{ number_format($invoice->igst_amount, 2) }}</td>
                        </tr>
                    @endif
                    
                    <tr class="font-weight-bold bg-light text-success" style="font-size: 1.1em;">
                        <td>Invoice Grand Total</td>
                        <td class="text-right">₹{{ number_format($invoice->grand_total, 2) }}</td>
                    </tr>
                    <tr class="font-weight-bold text-success">
                        <td>Payment Received</td>
                        <td class="text-right">₹{{ number_format($invoice->payment_received, 2) }}</td>
                    </tr>
                    <tr class="font-weight-bold text-danger">
                        <td>Remaining Balance</td>
                        <td class="text-right">₹{{ number_format($invoice->balance_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="row align-items-center bg-light p-3 border rounded">
                <div class="col-8">
                    <label class="small text-muted d-block mb-1">Verification Token</label>
                    <span class="small font-weight-bold text-dark" style="word-break: break-all;">{{ $invoice->verification_token }}</span>
                </div>
                <div class="col-4 text-center">
                    @if($invoice->qr_code && Storage::disk('public')->exists($invoice->qr_code))
                        <img src="{{ asset('storage/' . $invoice->qr_code) }}" alt="Verification QR" style="width: 80px; height: 80px;" class="border p-1 bg-white rounded">
                    @endif
                </div>
            </div>
            
            @if($invoice->remarks)
                <div class="alert alert-danger mt-3 mb-0">
                    <strong>Cancellation Notes:</strong> {{ $invoice->remarks }}
                </div>
            @endif
        </div>
    </div>

    <!-- Related Activity Logs -->
    <div class="col-12 mt-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Invoice Audit Activity Trail</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered text-dark small">
                    <thead class="bg-light">
                        <tr>
                            <th>Action Type</th>
                            <th>Description</th>
                            <th>Date & Time</th>
                            <th>Performed By</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activityLogs as $log)
                            <tr>
                                <td class="font-weight-bold text-uppercase">{{ str_replace('_', ' ', $log->action_type) }}</td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td>{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No specific audit logs recorded for this invoice yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
