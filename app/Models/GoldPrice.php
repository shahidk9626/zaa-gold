<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;
use Illuminate\Support\Facades\DB;

class GoldPrice extends Model
{
    use LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'price_22k',
        'price_24k',
        'price_bullion',
        'effective_date',
        'remarks',
        'status',
    ];

    protected $casts = [
        'price_22k' => 'decimal:2',
        'price_24k' => 'decimal:2',
        'price_bullion' => 'decimal:2',
        'effective_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $updatedBy = auth()->id() ?? 1;
            foreach (['price_22k', 'price_24k', 'price_bullion'] as $field) {
                DB::table('gold_price_histories')->insert([
                    'gold_price_id' => $model->id,
                    'gold_type' => strtoupper(str_replace('price_', '', $field)),
                    'old_price' => 0.00,
                    'new_price' => $model->$field,
                    'updated_by_id' => $updatedBy,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        static::updating(function ($model) {
            $updatedBy = auth()->id() ?? 1;
            foreach (['price_22k', 'price_24k', 'price_bullion'] as $field) {
                if ($model->isDirty($field)) {
                    DB::table('gold_price_histories')->insert([
                        'gold_price_id' => $model->id,
                        'gold_type' => strtoupper(str_replace('price_', '', $field)),
                        'old_price' => $model->getOriginal($field) ?? 0.00,
                        'new_price' => $model->$field,
                        'updated_by_id' => $updatedBy,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function histories()
    {
        return $this->hasMany(GoldPriceHistory::class);
    }
}
