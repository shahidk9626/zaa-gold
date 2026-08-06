<?php

namespace App\Events;

use App\Models\BookingDelivery;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryReadyForDispatchEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public BookingDelivery $delivery)
    {
    }
}
