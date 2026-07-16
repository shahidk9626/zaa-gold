<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class BookingEmiSchedule extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'booking_id',
        'installment_number',
        'due_date',
        'opening_principal',
        'principal_amount',
        'interest_amount',
        'emi_amount',
        'closing_principal',
        'outstanding_balance',
        'late_fee',
        'status',
        'paid_at',
        'payment_id',
        'remarks',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'opening_principal' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'emi_amount' => 'decimal:2',
        'closing_principal' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function payment()
    {
        return $this->belongsTo(BookingPayment::class, 'payment_id');
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'emi_schedule_id');
    }
}
