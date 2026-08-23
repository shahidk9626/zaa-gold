<x-customer-layout title="Payment History">
    <div class="page-header flex-wrap d-none d-md-flex">
        <h3 class="mb-0 font-weight-bold">Payment History</h3>
    </div>
    <div class="d-block d-md-none mb-3">
        <h5 class="font-weight-bold">Payment History</h5>
    </div>

    <!-- Filters Panel -->
    <div class="card mb-4 bg-white border shadow-sm">
        <div class="card-body">
            <h5 class="font-weight-bold text-dark mb-3 border-bottom pb-2">Filter Payments</h5>
            <form method="GET" action="{{ route('customer.payments.index') }}" class="row align-items-end">
                <!-- From Date -->
                <div class="col-md-3 form-group mb-3 mb-md-0">
                    <label class="text-dark font-weight-bold small">From Date</label>
                    <input type="date" name="start_date" class="form-control bg-white text-dark shadow-sm" value="{{ request('start_date') }}">
                </div>

                <!-- To Date -->
                <div class="col-md-3 form-group mb-3 mb-md-0">
                    <label class="text-dark font-weight-bold small">To Date</label>
                    <input type="date" name="end_date" class="form-control bg-white text-dark shadow-sm" value="{{ request('end_date') }}">
                </div>

                <!-- Booking ID Selector -->
                <div class="col-md-3 form-group mb-3 mb-md-0">
                    <label class="text-dark font-weight-bold small">Booking ID</label>
                    <select name="booking_id" class="form-control bg-white text-dark shadow-sm">
                        <option value="">All Bookings</option>
                        @foreach($bookings as $booking)
                            <option value="{{ $booking->id }}" {{ request('booking_id') == $booking->id ? 'selected' : '' }}>
                                {{ $booking->booking_number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-3 d-flex form-group mb-0">
                    <button type="submit" class="btn btn-primary btn-block mr-2 font-weight-bold py-2 shadow-sm">
                        <i class="mdi mdi-filter mr-1"></i> Filter
                    </button>
                    <a href="{{ route('customer.payments.index') }}" class="btn btn-secondary btn-block mt-0 font-weight-bold py-2 shadow-sm">
                        <i class="mdi mdi-refresh mr-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments DataTable Card -->
    <div class="card bg-white border shadow-sm">
        <div class="card-body">
            @if($transactions->isEmpty())
                <div class="alert alert-info mb-0">No payment records found.</div>
            @else
                <div class="table-responsive">
                    <table id="paymentsTable" class="table table-hover table-striped text-dark">
                        <thead class="bg-light text-dark">
                            <tr>
                                <th>Transaction Number</th>
                                <th>Booking ID</th>
                                <th>EMAP Number / Payment Type</th>
                                <th>Gateway</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                @php
                                    $receipt = $transaction->emi_schedule_id 
                                        ? ($paymentsByEmi[$transaction->emi_schedule_id] ?? null) 
                                        : ($paymentsByBooking[$transaction->booking_id] ?? null);
                                    $invoice = $receipt ? ($invoices[$receipt->id] ?? null) : null;
                                    $badge = match($transaction->payment_status) {
                                        'Success' => 'badge-success',
                                        'Failed', 'Cancelled', 'Rejected' => 'badge-danger',
                                        'Processing', 'Pending Verification' => 'badge-warning',
                                        default => 'badge-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="font-weight-bold">{{ $transaction->transaction_number }}</td>
                                    <td class="font-weight-bold text-primary">{{ $transaction->booking->booking_number ?? 'N/A' }}</td>
                                    <td>
                                        @if($transaction->emiSchedule)
                                            EMI #{{ $transaction->emiSchedule->installment_number }}
                                        @else
                                            {{ $transaction->payment_type === 'booking' ? 'Downpayment' : ucfirst($transaction->payment_type) }}
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($transaction->gateway) }}</td>
                                    <td data-order="{{ $transaction->amount }}" class="font-weight-bold text-dark">₹{{ number_format($transaction->amount, 2) }}</td>
                                    <td data-order="{{ $transaction->paid_at ? $transaction->paid_at->timestamp : $transaction->created_at->timestamp }}">
                                        {{ $transaction->paid_at ? $transaction->paid_at->format('d M Y') : $transaction->created_at->format('d M Y') }}
                                    </td>
                                    <td><span class="badge {{ $badge }}">{{ $transaction->payment_status }}</span></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @if($receipt && $receipt->status === 'Paid')
                                                <a href="{{ route('customer.payments.receipt', $receipt->id) }}" class="btn btn-sm btn-outline-primary mr-1">
                                                    <i class="mdi mdi-download mr-1"></i> Receipt
                                                </a>
                                            @endif
                                            @if($invoice)
                                                <a href="{{ route('customer.certificates.invoice', $invoice->id) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="mdi mdi-file-document mr-1"></i> Invoice
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-customer-layout>

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #212529 !important;
            font-size: 0.875rem;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ced4da;
            background-color: #ffffff;
            color: #212529;
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            outline: none;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #3f50f6 !important;
            color: white !important;
            border: 1px solid #3f50f6 !important;
        }
        .table-responsive {
            overflow-x: auto !important;
            overflow-y: visible !important;
            display: block !important;
            width: 100% !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            if ($('#paymentsTable').length) {
                $('#paymentsTable').DataTable({
                    "paging": true,
                    "searching": true,
                    "ordering": true,
                    "info": true,
                    "responsive": true,
                    "order": [[5, "desc"]], // Default sorting by Payment Date descending
                    "language": {
                        "search": "",
                        "searchPlaceholder": "Quick Search..."
                    }
                });
            }
        });
    </script>
@endpush
