@props(['financials'])

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Financial Summary</h5>
        <div class="table-responsive">
            <table class="table table-borderless">
                <tbody>
                    <tr><td class="text-muted">Gold Value</td><td class="text-right font-weight-bold">₹{{ number_format($financials['gold_value'], 2) }}</td></tr>
                    <tr><td class="text-muted">GST on Gold</td><td class="text-right">₹{{ number_format($financials['gst_on_gold'], 2) }}</td></tr>
                    <tr><td class="text-muted">Finance Charge</td><td class="text-right">₹{{ number_format($financials['finance_charge'], 2) }}</td></tr>
                    <tr><td class="text-muted">Storage Charge</td><td class="text-right">₹{{ number_format($financials['storage_charge'], 2) }}</td></tr>
                    <tr><td class="text-muted">GST on Charges</td><td class="text-right">₹{{ number_format($financials['gst_on_charges'], 2) }}</td></tr>
                    <tr class="border-top"><td class="font-weight-bold">Grand Total</td><td class="text-right font-weight-bold">₹{{ number_format($financials['total_booked'], 2) }}</td></tr>
                    <tr><td class="text-success">Total Paid</td><td class="text-right text-success font-weight-bold">₹{{ number_format($financials['total_paid'], 2) }}</td></tr>
                    <tr><td class="text-danger font-weight-bold">Outstanding</td><td class="text-right text-danger font-weight-bold">₹{{ number_format($financials['outstanding'], 2) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
