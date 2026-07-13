<x-customer-layout title="Delivery">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">My Deliveries</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">Delivery</h5></div>

    @if($deliveries->isEmpty())
        <div class="alert alert-info">No delivery requests found.</div>
    @else
        @foreach($deliveries as $delivery)
            @include('customer.components.delivery-card', ['delivery' => $delivery])
        @endforeach
    @endif
</x-customer-layout>
