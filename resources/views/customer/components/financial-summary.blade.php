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
                    @if(isset($financials['savings_amount']) && $financials['savings_amount'] > 0)
                    <tr><td class="text-muted font-weight-bold">Original Plan Value</td><td class="text-right font-weight-bold">₹{{ number_format($financials['original_amount'], 2) }}</td></tr>
                    <tr class="text-danger">
                        <td>
                            Promo Savings
                            @if(!empty($financials['offer_name']))
                                <span class="badge badge-danger ml-1" style="font-size: 0.75rem; vertical-align: middle;">{{ $financials['offer_name'] }}</span>
                            @endif
                        </td>
                        <td class="text-right font-weight-bold">- ₹{{ number_format($financials['savings_amount'], 2) }}</td>
                    </tr>
                    <tr class="border-top"><td class="font-weight-bold text-success">Final Payable Total</td><td class="text-right font-weight-bold text-success">₹{{ number_format($financials['total_booked'], 2) }}</td></tr>
                    @else
                    <tr class="border-top"><td class="font-weight-bold">Grand Total</td><td class="text-right font-weight-bold">₹{{ number_format($financials['total_booked'], 2) }}</td></tr>
                    @endif
                    <tr><td class="text-success">Total Paid</td><td class="text-right text-success font-weight-bold">₹{{ number_format($financials['total_paid'], 2) }}</td></tr>
                    <tr><td class="text-danger font-weight-bold">Outstanding</td><td class="text-right text-danger font-weight-bold">₹{{ number_format($financials['outstanding'], 2) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
