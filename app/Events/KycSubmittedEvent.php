<?php

namespace App\Events;

use App\Models\Kyc;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycSubmittedEvent
{
    use Dispatchable, SerializesModels;

    public $kyc;

    public function __construct(Kyc $kyc)
    {
        $this->kyc = $kyc;
    }
}
