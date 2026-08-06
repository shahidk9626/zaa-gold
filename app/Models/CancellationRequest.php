<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class CancellationRequest extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'request_number',
        'booking_id',
        'customer_id',
        'cancellation_reason',
        'cancellation_charge_percent',
        'cancellation_charge_amount',
        'total_amount_paid',
        'refund_amount',
        'status',
        'admin_remark',
        'approved_by_id',
        'approved_at',
        'refund_initiated_at',
        'refund_completed_at',
        'refund_transaction_number',
        'refund_date',
        'refund_mode',
        'bank_name',
        'bank_account_number',
        'bank_ifsc',
    ];

    protected $casts = [
        'cancellation_charge_percent' => 'decimal:2',
        'cancellation_charge_amount' => 'decimal:2',
        'total_amount_paid' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'refund_initiated_at' => 'datetime',
        'refund_completed_at' => 'datetime',
        'refund_date' => 'date',
    ];

    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
