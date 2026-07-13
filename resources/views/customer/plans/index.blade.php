<x-customer-layout title="My Plans">
    <div class="page-header flex-wrap d-none d-md-flex">
        <h3 class="mb-0">My Plans</h3>
    </div>
    <div class="d-block d-md-none mb-3">
        <h5 class="font-weight-bold">My Plans</h5>
    </div>

    @if($plans->isEmpty())
        <div class="alert alert-info">You don't have any purchased plans yet.</div>
    @else
        {{-- Desktop Table --}}
        <div class="d-none d-md-block">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Weight</th>
                                    <th>EMI Plan</th>
                                    <th>Monthly EMI</th>
                                    <th>Paid EMI</th>
                                    <th>Remaining</th>
                                    <th>Outstanding</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($plans as $plan)
                                @php $b = $plan['booking']; @endphp
                                <tr>
                                    <td>{{ $b->product?->name }}</td>
                                    <td>{{ number_format($b->gold_weight, 2) }}g</td>
                                    <td>{{ $b->emiPlan?->name }}</td>
                                    <td>₹{{ number_format($plan['monthly_emi'], 0) }}</td>
                                    <td>{{ $plan['paid_emi'] }}</td>
                                    <td>{{ $plan['remaining_emi'] }}</td>
                                    <td class="text-danger font-weight-bold">₹{{ number_format($plan['outstanding'], 0) }}</td>
                                    <td><span class="badge badge-primary">{{ $b->status }}</span></td>
                                    <td><a href="{{ route('customer.plans.show', $b->id) }}" class="btn btn-sm btn-primary">View</a></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="d-block d-md-none">
            @foreach($plans as $plan)
                @include('customer.components.plan-card', ['plan' => $plan, 'compact' => true])
            @endforeach
        </div>
    @endif
</x-customer-layout>
