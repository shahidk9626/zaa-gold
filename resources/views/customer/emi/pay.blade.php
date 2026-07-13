<x-customer-layout title="Pay EMI">
    <div class="mb-3">
        <a href="{{ route('customer.emi.repay') }}" class="text-muted small"><i class="mdi mdi-arrow-left"></i> Back</a>
        <h5 class="font-weight-bold mt-2">Pay EMI #{{ $schedule->installment_number }}</h5>
    </div>

    <div class="card mobile-card">
        <div class="card-body">
            <p><strong>Plan:</strong> {{ $schedule->booking?->product?->name }}</p>
            <p><strong>Due Date:</strong> {{ $schedule->due_date?->format('d M Y') }}</p>
            <h4 class="text-primary font-weight-bold mb-4">₹{{ number_format($schedule->emi_amount, 2) }}</h4>

            <form action="{{ route('customer.emi.process_pay', $schedule->id) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Payment Mode</label>
                    <select name="payment_mode" class="form-control" required>
                        <option value="UPI">UPI</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Card">Card</option>
                        <option value="Cash">Cash</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transaction Reference</label>
                    <input type="text" name="transaction_reference" class="form-control" placeholder="UPI ref / txn ID">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-mobile-lg">Confirm Payment</button>
            </form>
        </div>
    </div>
</x-customer-layout>
