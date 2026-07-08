<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldPriceHistory extends Model
{
    protected $fillable = [
        'gold_price_id',
        'gold_type',
        'old_price',
        'new_price',
        'updated_by_id',
    ];

    public function goldPrice()
    {
        return $this->belongsTo(GoldPrice::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
