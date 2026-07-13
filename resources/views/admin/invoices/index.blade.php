@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">GST Invoices Directory</h4>
                    <p class="card-description text-muted mb-0">Browse and audit official tax invoices generated automatically upon client EMI payments.</p>
                </div>
                <div>
                    @if(hasPermission('invoice.export'))
                    <a href="{{ route('invoices.export', request()->all()) }}" class="btn btn-success px-4">
                        <i class="mdi mdi-export mr-1"></i> Export Invoices (CSV)
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Invoices</h5>
            <form action="{{ route('invoices.index') }}" method="GET" class="row">
                <!-- Search -->
                <div class="col-md-4 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Invoice No, Customer Name/Email, Booking No..." value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Invoice Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        @foreach(['Generated', 'Draft', 'Cancelled', 'Revised'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Start Date -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">From Date</label>
                    <input type="date" name="start_date" class="form-control bg-white text-dark" value="{{ request('start_date') }}">
                </div>

                <!-- End Date -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">To Date</label>
                    <input type="date" name="end_date" class="form-control bg-white text-dark" value="{{ request('end_date') }}">
                </div>

                <!-- Action Buttons -->
                <div class="col-12 mt-2 d-flex justify-content-end">
                    <a href="{{ route('invoices.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
                    <button type="submit" class="btn btn-info px-4">Search & Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Listings Table Card -->
    <div class="col-12">
        <div class="card bg-white border shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-dark small">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th>Invoice Number</th>
                            <th>Booking Ref</th>
                            <th>Receipt Number</th>
                            <th>Customer Name</th>
                            <th>Product Name</th>
                            <th>Invoice Date</th>
                            <th>Amount (₹)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="font-weight-bold text-primary">{{ $invoice->invoice_number }}</td>
                                <td>
                                    <a href="{{ route('bookings.show', $invoice->booking_id) }}" class="font-weight-bold text-decoration-none">
                                        {{ $invoice->booking->booking_number }}
                                    </a>
                                </td>
                                <td>{{ $invoice->payment->receipt_number ?? 'N/A' }}</td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $invoice->customer_name }}</div>
                                    <small class="text-muted">{{ $invoice->customer_email }}</small>
                                </td>
                                <td>{{ $invoice->product_name }}</td>
                                <td>{{ $invoice->invoice_date->format('d M Y, h:i A') }}</td>
                                <td class="font-weight-bold text-success">₹{{ number_format($invoice->grand_total, 2) }}</td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-secondary';
                                        switch($invoice->invoice_status) {
                                            case 'Generated': $badgeClass = 'badge-success'; break;
                                            case 'Draft': $badgeClass = 'badge-warning'; break;
                                            case 'Cancelled': $badgeClass = 'badge-danger'; break;
                                            case 'Revised': $badgeClass = 'badge-info'; break;
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $invoice->invoice_status }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-sm btn-info px-2 py-1 mr-1" title="View Details">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    @if(hasPermission('invoice.download'))
                                    <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-sm btn-success px-2 py-1 mr-1" title="Download PDF">
                                        <i class="mdi mdi-download"></i>
                                    </a>
                                    @endif
                                    @if(hasPermission('invoice.print'))
                                    <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" class="btn btn-sm btn-primary px-2 py-1" title="Print Invoice">
                                        <i class="mdi mdi-printer"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No GST invoices found matching search filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 d-flex justify-content-end">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
