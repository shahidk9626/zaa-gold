@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div>
                <h4 class="card-title text-dark font-weight-bold mb-1">Tax Invoices & Receipts</h4>
                <p class="card-description text-muted mb-0">Browse through, audit, and download PDF receipts generated for booking EMI repayments.</p>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Receipts</h5>
            <form action="{{ route('receipts.index') }}" method="GET" class="row">
                <!-- Search Input -->
                <div class="col-md-9 form-group mb-0">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Receipt Number, Payment Number, Booking Number, Customer Name..." value="{{ request('search') }}">
                </div>

                <!-- Submit Button -->
                <div class="col-md-3 form-group mb-0 d-flex align-items-end">
                    <button type="submit" class="btn btn-info px-4 w-100 py-3" style="height: 48px;">
                        <i class="mdi mdi-magnify mr-1"></i> Search & Filter
                    </button>
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
                            <th>Receipt Number</th>
                            <th>Payment Number</th>
                            <th>Booking Number</th>
                            <th>Customer Name</th>
                            <th>EMI Number</th>
                            <th>Total Amount Paid</th>
                            <th>Payment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="font-weight-bold text-success">{{ $payment->receipt_number }}</td>
                                <td class="font-weight-bold text-primary">{{ $payment->payment_number }}</td>
                                <td>
                                    <a href="{{ route('bookings.show', $payment->booking_id) }}" class="text-decoration-none font-weight-bold">
                                        {{ $payment->booking->booking_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $payment->booking->customer->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $payment->booking->customer->email ?? 'N/A' }}</small>
                                </td>
                                <td class="text-center font-weight-bold text-dark">
                                    #{{ $payment->emiSchedule->installment_number ?? 'N/A' }}
                                </td>
                                <td class="font-weight-bold text-success">
                                    ₹{{ number_format($payment->amount_paid, 2) }}
                                </td>
                                <td>{{ $payment->payment_date->format('d M Y, h:i A') }}</td>
                                <td>
                                    @if(hasPermission('receipt.download'))
                                    <a href="{{ route('receipts.download', $payment->id) }}" class="btn btn-sm btn-success px-3">
                                        <i class="mdi mdi-download mr-1"></i> Download PDF
                                    </a>
                                    @else
                                    —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No receipts found matching your search.
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
