<x-customer-layout title="Delivery Details">
    <div class="mb-3">
        <a href="{{ route('customer.deliveries.index') }}" class="text-muted small"><i class="mdi mdi-arrow-left"></i> Back</a>
        <h5 class="font-weight-bold mt-2">{{ $delivery->delivery_number }}</h5>
        <span class="badge badge-primary">{{ $delivery->delivery_status }}</span>
    </div>

    <div class="row">
        <div class="col-md-7 grid-margin">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Delivery Information</h5>
                    <p><strong>Booking:</strong> {{ $delivery->booking?->booking_number }}</p>
                    <p><strong>Product:</strong> {{ $delivery->booking?->product?->name }}</p>
                    <p><strong>Method:</strong> {{ $delivery->delivery_method }}</p>
                    @if($delivery->courier_partner)
                    <p><strong>Courier:</strong> {{ $delivery->courier_partner }}</p>
                    <p><strong>Tracking:</strong> {{ $delivery->tracking_number }}
                        @if($delivery->tracking_url)<a href="{{ $delivery->tracking_url }}" target="_blank" class="ml-2">Track <i class="mdi mdi-open-in-new"></i></a>@endif
                    </p>
                    @endif
                    @if($delivery->delivery_address)
                    <p><strong>Address:</strong> {{ $delivery->delivery_address }}</p>
                    @endif
                </div>
            </div>

            @if($delivery->otp && $delivery->delivery_status === 'Dispatched')
            <div class="card bg-light mb-4">
                <div class="card-body text-center">
                    <p class="text-muted mb-1">Delivery OTP</p>
                    <h3 class="font-weight-bold letter-spacing-2">{{ $delivery->otp }}</h3>
                    <p class="text-muted small mb-0">Share this OTP with the delivery agent</p>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-5 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Delivery Timeline</h5>
                    @php
                        $steps = ['Requested', 'Approved', 'Dispatched', 'Out For Delivery', 'Delivered'];
                        $currentIndex = array_search($delivery->delivery_status, $steps);
                        if ($currentIndex === false) $currentIndex = -1;
                        $timelineItems = [];
                        foreach ($delivery->statusHistories as $history) {
                            $timelineItems[] = [
                                'title' => $history->new_status,
                                'description' => $history->remarks,
                                'date' => $history->created_at->format('d M Y, h:i A'),
                                'completed' => true,
                            ];
                        }
                        if (empty($timelineItems)) {
                            foreach ($steps as $i => $step) {
                                $timelineItems[] = [
                                    'title' => $step,
                                    'completed' => $i <= $currentIndex,
                                    'pending' => $i === $currentIndex + 1,
                                ];
                            }
                        }
                    @endphp
                    @include('customer.components.timeline', ['items' => $timelineItems])
                </div>
            </div>
        </div>
    </div>
</x-customer-layout>
