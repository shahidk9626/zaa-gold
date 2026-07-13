@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">EMI Payments History</h4>
                    <p class="card-description text-muted mb-0">Browse through all collected EMI installments, payment modes, and download tax receipts.</p>
                </div>
                <div>
                    @if(hasPermission('payment.export'))
                    <a href="{{ route('payments.export', request()->all()) }}" class="btn btn-success px-4">
                        <i class="mdi mdi-export mr-1"></i> Export payments (CSV)
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Payments</h5>
            <form action="{{ route('payments.index') }}" method="GET" class="row">
                <!-- Search -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Payment No, Receipt, Booking..." value="{{ request('search') }}">
                </div>

                <!-- Payment Mode -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Payment Mode</label>
                    <select name="payment_mode" class="form-control bg-white text-dark">
                        <option value="">All Modes</option>
                        @foreach(['Cash', 'UPI', 'Bank Transfer', 'Card', 'Cheque', 'Online Gateway'] as $mode)
                            <option value="{{ $mode }}" {{ request('payment_mode') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        <option value="Paid" {{ request('status') === 'Paid' ? 'selected' : '' }}>Paid</option>
                        <option value="Failed" {{ request('status') === 'Failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <!-- Start Date -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Paid From</label>
                    <input type="date" name="start_date" class="form-control bg-white text-dark" value="{{ request('start_date') }}">
                </div>

                <!-- End Date -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Paid To</label>
                    <input type="date" name="end_date" class="form-control bg-white text-dark" value="{{ request('end_date') }}">
                </div>

                <!-- Actions -->
                <div class="col-12 mt-2 d-flex justify-content-end">
                    <a href="{{ route('payments.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
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
                            <th>Payment No.</th>
                            <th>Receipt No.</th>
                            <th>Booking No.</th>
                            <th>Customer Name</th>
                            <th>Payment Mode</th>
                            <th>Amount Paid</th>
                            <th>Principal Paid</th>
                            <th>Interest Paid</th>
                            <th>Late Fee Paid</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="font-weight-bold text-primary">{{ $payment->payment_number }}</td>
                                <td class="font-weight-bold text-dark">{{ $payment->receipt_number }}</td>
                                <td>
                                    <a href="{{ route('bookings.show', $payment->booking_id) }}" class="text-decoration-none">
                                        {{ $payment->booking->booking_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $payment->booking->customer->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $payment->booking->customer->email ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-outline-dark text-dark">{{ $payment->payment_mode }}</span>
                                </td>
                                <td class="font-weight-bold text-success">₹{{ number_format($payment->amount_paid, 2) }}</td>
                                <td>₹{{ number_format($payment->principal_paid, 2) }}</td>
                                <td>₹{{ number_format($payment->interest_paid, 2) }}</td>
                                <td class="text-danger font-weight-bold">
                                    {{ $payment->late_fee_paid > 0 ? '₹' . number_format($payment->late_fee_paid, 2) : '—' }}
                                </td>
                                <td>{{ $payment->payment_date->format('d M Y, h:i A') }}</td>
                                <td>
                                    <span class="badge badge-success text-dark font-weight-bold px-3 py-2">{{ $payment->status }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-sm btn-info px-2 py-1 mr-1" title="View Details">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    @if(hasPermission('receipt.download'))
                                    <a href="{{ route('receipts.download', $payment->id) }}" class="btn btn-sm btn-success px-2 py-1" title="Download Receipt PDF">
                                        <i class="mdi mdi-download"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No payments found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 d-flex justify-content-end">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
