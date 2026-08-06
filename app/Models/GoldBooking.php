<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class GoldBooking extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'product_id',
        'emi_plan_id',
        'gold_price_id',
        'gold_weight',
        'gold_purity',
        'locked_price_per_gram',
        'locked_gold_value',
        'gst_on_gold_percent',
        'gst_on_gold_amount',
        'finance_charge_percent',
        'finance_charge_amount',
        'storage_charge_percent',
        'storage_charge_amount',
        'gst_on_charges_percent',
        'gst_on_charges_amount',
        'grand_total',
        'monthly_emi',
        'duration_months',
        'booking_date',
        'estimated_completion_date',
        'status',
        'remarks',
        'created_by_id',
        'updated_by_id',
        'offer_id',
        'offer_name',
        'offer_type',
        'offer_value',
        'original_amount',
        'discount_amount',
        'final_amount',
        'savings_amount',
        'waived_emi_count',
        'offer_snapshot',
    ];

    protected $casts = [
        'gold_weight' => 'decimal:2',
        'gold_purity' => 'decimal:2',
        'locked_price_per_gram' => 'decimal:2',
        'locked_gold_value' => 'decimal:2',
        'gst_on_gold_percent' => 'decimal:2',
        'gst_on_gold_amount' => 'decimal:2',
        'finance_charge_percent' => 'decimal:2',
        'finance_charge_amount' => 'decimal:2',
        'storage_charge_percent' => 'decimal:2',
        'storage_charge_amount' => 'decimal:2',
        'gst_on_charges_percent' => 'decimal:2',
        'gst_on_charges_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'monthly_emi' => 'decimal:2',
        'booking_date' => 'datetime',
        'estimated_completion_date' => 'datetime',
        'offer_value' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'savings_amount' => 'decimal:2',
        'waived_emi_count' => 'integer',
        'offer_snapshot' => 'array',
    ];

    public $status_change_remarks = null;

    protected static function boot()
    {
        parent::boot();

        // Automatically log status history on creation
        static::created(function ($booking) {
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => null,
                'new_status' => $booking->status,
                'remarks' => $booking->remarks ?? 'Booking initialized.',
                'changed_by_id' => auth()->id() ?? $booking->created_by_id,
            ]);
        });

        // Automatically log status history on updates
        static::updating(function ($booking) {
            if ($booking->isDirty('status')) {
                $oldStatus = $booking->getOriginal('status');
                $newStatus = $booking->status;

                BookingStatusHistory::create([
                    'booking_id' => $booking->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'remarks' => $booking->status_change_remarks ?? "Status updated from {$oldStatus} to {$newStatus}.",
                    'changed_by_id' => auth()->id() ?? $booking->updated_by_id,
                ]);
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function emiPlan()
    {
        return $this->belongsTo(EmiPlan::class, 'emi_plan_id');
    }

    public function goldPrice()
    {
        return $this->belongsTo(GoldPrice::class, 'gold_price_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(BookingStatusHistory::class, 'booking_id')->orderBy('created_at', 'desc');
    }

    public function certificate()
    {
        return $this->hasOne(PriceLockCertificate::class, 'booking_id');
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'booking_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }

    public function cancellationRequests()
    {
        return $this->hasMany(CancellationRequest::class, 'booking_id')->orderBy('created_at', 'desc');
    }

    public function latestCancellationRequest()
    {
        return $this->hasOne(CancellationRequest::class, 'booking_id')->latestOfMany();
    }

    public function schedules()
    {
        return $this->hasMany(BookingEmiSchedule::class, 'booking_id');
    }

    public function payments()
    {
        return $this->hasMany(BookingPayment::class, 'booking_id');
    }

    public function deliveries()
    {
        return $this->hasMany(BookingDelivery::class, 'booking_id');
    }
}
