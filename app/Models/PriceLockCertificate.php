<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PriceLockCertificate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'certificate_number',
        'booking_id',
        'customer_id',
        'issued_at',
        'locked_price',
        'gold_weight',
        'grand_total',
        'pdf_path',
        'verification_token',
        'qr_code',
        'created_by_id',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'locked_price' => 'decimal:2',
        'gold_weight' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function goldBooking()
    {
        return $this->belongsTo(GoldBooking::class, 'booking_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
