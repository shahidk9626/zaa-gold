<?php

namespace App\Events;

use App\Models\BookingDelivery;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryApprovedEvent
{
    use Dispatchable, SerializesModels;

    public $delivery;

    public function __construct(BookingDelivery $delivery)
    {
        $this->delivery = $delivery;
    }
}
