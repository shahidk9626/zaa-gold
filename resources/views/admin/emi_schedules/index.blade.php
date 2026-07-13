@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header & Action Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="card-title text-dark font-weight-bold mb-1">EMI Repayment Schedules</h4>
                    <p class="card-description text-muted mb-0">Track and manage upcoming, due, and paid installments across all active gold bookings.</p>
                </div>
                <div>
                    @if(hasPermission('emi-schedule.export'))
                    <a href="{{ route('emi-schedules.export', request()->all()) }}" class="btn btn-success px-4">
                        <i class="mdi mdi-export mr-1"></i> Export schedules (CSV)
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Filter Installments</h5>
            <form action="{{ route('emi-schedules.index') }}" method="GET" class="row">
                <!-- Search -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Search Query</label>
                    <input type="text" name="search" class="form-control bg-white text-dark" placeholder="Booking No, Customer..." value="{{ request('search') }}">
                </div>

                <!-- Status Filter -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">EMI Status</label>
                    <select name="status" class="form-control bg-white text-dark">
                        <option value="">All Statuses</option>
                        @foreach(['Pending', 'Paid', 'Partial', 'Overdue'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Customer Filter -->
                <div class="col-md-3 form-group">
                    <label class="text-dark font-weight-bold">Customer</label>
                    <select name="customer_id" class="form-control bg-white text-dark select2-selector">
                        <option value="">All Customers</option>
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>{{ $cust->name }} ({{ $cust->email }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Booking Filter -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Booking</label>
                    <select name="booking_id" class="form-control bg-white text-dark select2-selector">
                        <option value="">All Bookings</option>
                        @foreach($bookings as $bk)
                            <option value="{{ $bk->id }}" {{ request('booking_id') == $bk->id ? 'selected' : '' }}>{{ $bk->booking_number }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Start Date -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Due From</label>
                    <input type="date" name="start_date" class="form-control bg-white text-dark" value="{{ request('start_date') }}">
                </div>

                <!-- End Date -->
                <div class="col-md-2 form-group">
                    <label class="text-dark font-weight-bold">Due To</label>
                    <input type="date" name="end_date" class="form-control bg-white text-dark" value="{{ request('end_date') }}">
                </div>

                <!-- Actions -->
                <div class="col-12 mt-2 d-flex justify-content-end">
                    <a href="{{ route('emi-schedules.index') }}" class="btn btn-secondary px-4 mr-2">Clear Filters</a>
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
                            <th>Booking No.</th>
                            <th>Customer Name</th>
                            <th>Installment #</th>
                            <th>Due Date</th>
                            <th>Opening Principal</th>
                            <th>EMI Amount</th>
                            <th>Principal Paid</th>
                            <th>Interest Paid</th>
                            <th>Late Fee</th>
                            <th>Closing Principal</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $row)
                            <tr>
                                <td class="font-weight-bold text-primary">
                                    <a href="{{ route('bookings.show', $row->booking_id) }}" class="text-decoration-none">
                                        {{ $row->booking->booking_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $row->booking->customer->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $row->booking->customer->email ?? 'N/A' }}</small>
                                </td>
                                <td class="text-center font-weight-bold text-dark">{{ $row->installment_number }}</td>
                                <td>{{ $row->due_date->format('d M Y') }}</td>
                                <td>₹{{ number_format($row->opening_principal, 2) }}</td>
                                <td class="font-weight-bold text-primary">₹{{ number_format($row->emi_amount, 2) }}</td>
                                <td>₹{{ number_format($row->principal_amount, 2) }}</td>
                                <td>₹{{ number_format($row->interest_amount, 2) }}</td>
                                <td class="text-danger font-weight-bold">
                                    {{ $row->late_fee > 0 ? '₹' . number_format($row->late_fee, 2) : '—' }}
                                </td>
                                <td>₹{{ number_format($row->closing_principal, 2) }}</td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-secondary';
                                        switch($row->status) {
                                            case 'Pending': $badgeClass = 'badge-warning'; break;
                                            case 'Paid': $badgeClass = 'badge-success'; break;
                                            case 'Partial': $badgeClass = 'badge-info'; break;
                                            case 'Overdue': $badgeClass = 'badge-danger'; break;
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }} text-dark font-weight-bold px-3 py-2">{{ $row->status }}</span>
                                </td>
                                <td>
                                    @if($row->status !== 'Paid' && hasPermission('payment.collect'))
                                    <a href="{{ route('payments.collect_form', [$row->booking_id, $row->id]) }}" class="btn btn-sm btn-primary px-3">
                                        <i class="mdi mdi-cash-multiple"></i> Collect
                                    </a>
                                    @elseif($row->status === 'Paid')
                                        @if($row->payment_id)
                                        <a href="{{ route('payments.show', $row->payment_id) }}" class="btn btn-sm btn-info px-2 py-1 mr-1" title="View Payment Details">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        @endif
                                        @if($row->payment_id && hasPermission('receipt.download'))
                                        <a href="{{ route('receipts.download', $row->payment_id) }}" class="btn btn-sm btn-success px-2 py-1" title="Download Receipt PDF">
                                            <i class="mdi mdi-download"></i>
                                        </a>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-alert mr-1"></i> No EMI schedules found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 d-flex justify-content-end">
                {{ $schedules->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
