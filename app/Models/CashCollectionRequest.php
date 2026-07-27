<?php

namespace App\Models;

use App\Traits\HasCreatorUpdater;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class CashCollectionRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'collection_number',
        'transaction_id',
        'booking_id',
        'customer_id',
        'payment_id',
        'collected_by_id',
        'amount',
        'status',
        'collection_date',
        'remarks',
        'verified_by_id',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'collection_date' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }

    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function receipt()
    {
        return $this->belongsTo(BookingPayment::class, 'payment_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }
}
