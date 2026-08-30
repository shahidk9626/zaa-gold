<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class CustomerReferral extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'staff_id',
        'referrer_id',
        'referrer_type',
        'customer_id',
        'referral_code',
        'referred_at',
        'booking_id',
        'gold_weight',
        'cashback_rate',
        'cashback_amount',
        'status',
        'payment_reference_number',
        'admin_remark',
        'processed_by',
        'completed_at',
    ];

    protected $casts = [
        'referred_at' => 'datetime',
        'gold_weight' => 'decimal:3',
        'cashback_rate' => 'decimal:2',
        'cashback_amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the referrer user (Staff or Customer).
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the staff user who referred the customer.
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Get the referred customer user.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the booking associated with the referral.
     */
    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    /**
     * Get the user who processed the referral.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
