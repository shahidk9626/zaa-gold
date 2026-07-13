<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class BookingPayment extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'payment_number',
        'receipt_number',
        'booking_id',
        'emi_schedule_id',
        'customer_id',
        'payment_mode',
        'transaction_reference',
        'amount_paid',
        'principal_paid',
        'interest_paid',
        'late_fee_paid',
        'gst_paid',
        'payment_date',
        'remarks',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount_paid' => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'late_fee_paid' => 'decimal:2',
        'gst_paid' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function emiSchedule()
    {
        return $this->belongsTo(BookingEmiSchedule::class, 'emi_schedule_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
