<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class Referral extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'referral_code',
        'referrer_customer_id',
        'referred_customer_id',
        'booking_id',
        'reward_type',
        'reward_amount',
        'reward_status',
        'remarks',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_customer_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_customer_id');
    }

    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
