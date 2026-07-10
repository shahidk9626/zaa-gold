<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingStatusHistory extends Model
{
    // Disable timestamps since we only have created_at
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'old_status',
        'new_status',
        'remarks',
        'changed_by_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
