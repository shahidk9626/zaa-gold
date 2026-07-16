<?php

namespace App\Models;

use App\Traits\HasCreatorUpdater;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'transaction_number',
        'booking_id',
        'emi_schedule_id',
        'customer_id',
        'payment_type',
        'gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_reference',
        'payment_token',
        'payment_url',
        'amount',
        'currency',
        'payment_status',
        'link_status',
        'gateway_request',
        'gateway_response',
        'webhook_payload',
        'failure_reason',
        'generated_at',
        'expires_at',
        'opened_at',
        'verified_at',
        'paid_at',
        'webhook_processed_at',
        'generated_by_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_request' => 'array',
        'gateway_response' => 'array',
        'webhook_payload' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'opened_at' => 'datetime',
        'verified_at' => 'datetime',
        'paid_at' => 'datetime',
        'webhook_processed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function emiSchedule()
    {
        return $this->belongsTo(BookingEmiSchedule::class, 'emi_schedule_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }

    public function isSuccessful(): bool
    {
        return $this->payment_status === 'Success';
    }

    public function isActiveLink(): bool
    {
        return $this->link_status === 'Pending'
            && (!$this->expires_at || $this->expires_at->isFuture())
            && !$this->isSuccessful();
    }
}
