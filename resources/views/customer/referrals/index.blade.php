<x-customer-layout title="Referrals">
    <div class="page-header flex-wrap d-none d-md-flex">
        <h3 class="mb-0 font-weight-bold">Referrals & Cashback</h3>
    </div>
    <div class="d-block d-md-none mb-3">
        <h5 class="font-weight-bold">Referrals & Cashback</h5>
    </div>

    <!-- Referral Info and Code -->
    <div class="card mb-4 bg-white border shadow-sm" style="border-radius: 8px;">
        <div class="card-body text-center py-4">
            <h5 class="font-weight-bold text-dark mb-2">My Referral Code</h5>
            <div class="d-inline-flex align-items-center justify-content-center bg-light p-3 rounded border mb-3">
                <span id="myReferralCode" class="font-weight-bold text-primary mr-3" style="font-size: 1.5rem; letter-spacing: 1px;">{{ $user->referral_code }}</span>
                <button type="button" class="btn btn-sm btn-primary py-2 px-3 font-weight-bold" onclick="copyReferralCode()">
                    <i class="mdi mdi-content-copy mr-1"></i> Copy
                </button>
            </div>
            <p class="text-muted mb-0 max-width-600 mx-auto" style="font-size: 0.9rem;">
                Share your referral code with friends and family and earn cashback when they purchase a Gold Plan.
            </p>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card bg-success text-white shadow-sm" style="border-radius: 8px;">
                <div class="card-body text-center py-3">
                    <span class="small d-block mb-1">Total Cashback Earned</span>
                    <h3 class="font-weight-bold mb-0">₹{{ number_format($totalEarnedCashback, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-warning text-dark shadow-sm" style="border-radius: 8px;">
                <div class="card-body text-center py-3">
                    <span class="small d-block mb-1">Pending Cashback</span>
                    <h3 class="font-weight-bold mb-0">₹{{ number_format($pendingCashback, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Referral History Table -->
    <div class="card bg-white border shadow-sm" style="border-radius: 8px;">
        <div class="card-body">
            <h5 class="font-weight-bold text-dark mb-3 border-bottom pb-2">Referral History</h5>
            @if($referrals->isEmpty())
                <div class="alert alert-info mb-0">You haven't referred anyone yet. Share your code to start earning!</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped text-dark mb-0">
                        <thead class="bg-light text-dark">
                            <tr>
                                <th>Referral Date</th>
                                <th>Referred Customer</th>
                                <th>Booking / Plan</th>
                                <th>Gold Weight</th>
                                <th>Cashback Rate</th>
                                <th>Estimated Cashback</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($referrals as $ref)
                                @php
                                    $statusClass = match($ref->status) {
                                        'Completed' => 'badge-success',
                                        'Pending' => 'badge-warning text-dark',
                                        'Under Review' => 'badge-info',
                                        'Approved' => 'badge-primary',
                                        'Rejected' => 'badge-danger',
                                        default => 'badge-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $ref->referred_at ? $ref->referred_at->format('d M Y') : $ref->created_at->format('d M Y') }}</td>
                                    <td>
                                        <span class="font-weight-bold">{{ $ref->customer->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if($ref->booking)
                                            <span class="d-block font-weight-bold text-primary">#{{ $ref->booking->booking_number }}</span>
                                            <small class="text-muted">{{ $ref->booking->product?->name }} ({{ $ref->booking->duration_months }} mo)</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $ref->gold_weight ? number_format($ref->gold_weight, 3) . ' g' : 'N/A' }}</td>
                                    <td>₹{{ number_format($ref->cashback_rate ?? 0, 2) }} / g</td>
                                    <td class="font-weight-bold">₹{{ number_format($ref->cashback_amount ?? 0, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $statusClass }} font-weight-bold">{{ $ref->status }}</span>
                                        @if($ref->status === 'Completed' && $ref->payment_reference_number)
                                            <small class="d-block text-muted mt-1">Ref: {{ $ref->payment_reference_number }}</small>
                                        @endif
                                    </td>
                                </tr>
                                @if($ref->admin_remark || ($ref->status === 'Completed' && $ref->payment_reference_number))
                                    <tr class="table-info">
                                        <td colspan="7" class="small py-2">
                                            @if($ref->admin_remark)
                                                <strong>Note:</strong> {{ $ref->admin_remark }}
                                            @endif
                                            @if($ref->status === 'Completed' && $ref->payment_reference_number)
                                                &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Payment Ref:</strong> {{ $ref->payment_reference_number }}
                                                @if($ref->completed_at)
                                                    (Paid on: {{ \Carbon\Carbon::parse($ref->completed_at)->format('d M Y') }})
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-customer-layout>

@push('scripts')
<script>
    function copyReferralCode() {
        const codeElement = document.getElementById('myReferralCode');
        const codeText = codeElement.innerText;
        navigator.clipboard.writeText(codeText).then(() => {
            alert('Referral code copied to clipboard!');
        }).catch(err => {
            console.error('Error copying text: ', err);
        });
    }
</script>
@endpush
