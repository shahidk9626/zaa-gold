<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;
use Illuminate\Support\Facades\DB;

class BookingDelivery extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'delivery_number',
        'booking_id',
        'customer_id',
        'customer_address_id',
        'delivery_method',
        'delivery_status',
        'request_date',
        'approved_date',
        'dispatch_date',
        'expected_delivery_date',
        'ready_for_dispatch_at',
        'in_transit_at',
        'out_for_delivery_at',
        'delivered_date',
        'collected_at',
        'courier_partner',
        'tracking_number',
        'tracking_url',
        'pickup_branch',
        'pickup_date',
        'pickup_time',
        'otp',
        'otp_expires_at',
        'otp_verified_at',
        'receiver_name',
        'receiver_mobile',
        'receiver_id_proof',
        'delivery_address',
        'delivery_address_name',
        'delivery_address_mobile',
        'delivery_address_alternate_mobile',
        'delivery_address_type',
        'delivery_city',
        'delivery_state',
        'delivery_pin_code',
        'delivery_country',
        'remarks',
        'dispatch_remarks',
        'rejection_reason',
        'hold_reason',
        'pdf_path',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'request_date' => 'datetime',
        'approved_date' => 'datetime',
        'dispatch_date' => 'datetime',
        'expected_delivery_date' => 'date',
        'ready_for_dispatch_at' => 'datetime',
        'in_transit_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_date' => 'datetime',
        'collected_at' => 'datetime',
        'pickup_date' => 'date',
        'otp_expires_at' => 'datetime',
        'otp_verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            if ($model->isDirty('delivery_status')) {
                DB::table('delivery_status_histories')->insert([
                    'delivery_id' => $model->id,
                    'old_status' => $model->getOriginal('delivery_status'),
                    'new_status' => $model->delivery_status,
                    'remarks' => $model->remarks ?? 'Status updated.',
                    'changed_by_id' => auth()->id() ?? $model->updated_by_id ?? 1,
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function customerAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(DeliveryStatusHistory::class, 'delivery_id')->orderBy('created_at', 'asc');
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
