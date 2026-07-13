<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class GstInvoice extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'invoice_number',
        'booking_id',
        'payment_id',
        'customer_id',
        'invoice_date',
        'customer_name',
        'customer_email',
        'customer_phone',
        'billing_address',
        'product_name',
        'gold_weight',
        'gold_purity',
        'locked_gold_price',
        'gold_value',
        'gst_on_gold_percent',
        'gst_on_gold_amount',
        'finance_charge',
        'storage_charge',
        'gst_on_charges_percent',
        'gst_on_charges_amount',
        'subtotal',
        'grand_total',
        'payment_received',
        'balance_amount',
        'cgst_percent',
        'cgst_amount',
        'sgst_percent',
        'sgst_amount',
        'igst_percent',
        'igst_amount',
        'invoice_status',
        'remarks',
        'pdf_path',
        'verification_token',
        'qr_code',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'gold_weight' => 'decimal:2',
        'gold_purity' => 'decimal:2',
        'locked_gold_price' => 'decimal:2',
        'gold_value' => 'decimal:2',
        'gst_on_gold_percent' => 'decimal:2',
        'gst_on_gold_amount' => 'decimal:2',
        'finance_charge' => 'decimal:2',
        'storage_charge' => 'decimal:2',
        'gst_on_charges_percent' => 'decimal:2',
        'gst_on_charges_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'payment_received' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'cgst_percent' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_percent' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_percent' => 'decimal:2',
        'igst_amount' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function payment()
    {
        return $this->belongsTo(BookingPayment::class, 'payment_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
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
