<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'delivery_status_histories';

    protected $fillable = [
        'delivery_id',
        'old_status',
        'new_status',
        'remarks',
        'changed_by_id',
    ];

    public function delivery()
    {
        return $this->belongsTo(BookingDelivery::class, 'delivery_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
