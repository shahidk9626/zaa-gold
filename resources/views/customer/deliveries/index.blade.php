<x-customer-layout title="Delivery">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">My Deliveries</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">Delivery</h5></div>

    @if(!$canRequestDelivery)
        <div class="alert alert-danger border-0 p-3 mb-4 text-dark" role="alert" style="border-radius: 8px;">
            <h6 class="alert-heading mb-1 font-weight-bold"><i class="mdi mdi-alert-circle mr-1"></i> Delivery Restricted</h6>
            <p class="mb-2 small">Please complete your Profile and KYC verification before requesting Gold Delivery.</p>
            <a href="{{ route('customer.profile.index') }}" class="btn btn-danger btn-sm font-weight-bold px-3 py-2 text-white">Complete Profile & KYC</a>
        </div>
    @endif

    @if($deliveries->isEmpty())
        <div class="alert alert-info">No delivery requests found.</div>
    @else
        @foreach($deliveries as $delivery)
            @include('customer.components.delivery-card', ['delivery' => $delivery])
        @endforeach
    @endif
</x-customer-layout>
