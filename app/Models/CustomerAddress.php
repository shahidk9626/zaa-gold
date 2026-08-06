<?php

namespace App\Models;

use App\Traits\HasCreatorUpdater;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'customer_id',
        'address_name',
        'mobile',
        'alternate_mobile',
        'house_no',
        'street',
        'area',
        'landmark',
        'city',
        'state',
        'pin_code',
        'country',
        'address_type',
        'is_default',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function deliveries()
    {
        return $this->hasMany(BookingDelivery::class, 'customer_address_id');
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->house_no,
            $this->street,
            $this->area,
            $this->landmark,
            $this->city,
            $this->state,
            $this->pin_code,
            $this->country,
        ])->filter()->implode(', ');
    }
}
