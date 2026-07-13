<x-customer-layout title="Repay EMI">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">Repay EMI</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">Upcoming EMI</h5></div>

    @if($upcomingEmis->isEmpty())
        <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> All EMIs are up to date. No pending payments.</div>
    @else
        @foreach($upcomingEmis as $schedule)
            @include('customer.components.emi-card', ['schedule' => $schedule])
        @endforeach
    @endif
</x-customer-layout>
