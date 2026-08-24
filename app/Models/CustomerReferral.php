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
        'customer_id',
        'referral_code',
        'referred_at',
    ];

    protected $casts = [
        'referred_at' => 'datetime',
    ];

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
}
