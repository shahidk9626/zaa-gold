<?php

namespace App\Events;

use App\Models\CancellationRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundCompleted
{
    use Dispatchable, SerializesModels;

    public $request;

    public function __construct(CancellationRequest $request)
    {
        $this->request = $request;
    }
}
