<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;
use Illuminate\Support\Facades\DB;

class Inventory extends Model
{
    use LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'product_id',
        'available_qty',
        'reserved_qty',
        'sold_qty',
        'current_qty',
        'min_stock',
        'max_stock',
        'remarks',
        'status',
    ];

    protected $casts = [
        'available_qty' => 'decimal:2',
        'reserved_qty' => 'decimal:2',
        'sold_qty' => 'decimal:2',
        'current_qty' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->current_qty = $model->available_qty + $model->reserved_qty;
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Reusable logic to log inventory transaction movements
     */
    public function logTransaction($type, $qty, $remarks = null)
    {
        $oldQty = $this->available_qty;
        $createdBy = auth()->id() ?? 1;

        if ($type === 'purchase') {
            $this->available_qty += $qty;
        } elseif ($type === 'reserve') {
            $this->available_qty -= $qty;
            $this->reserved_qty += $qty;
        } elseif ($type === 'release') {
            $this->reserved_qty -= $qty;
            $this->available_qty += $qty;
        } elseif ($type === 'sale') {
            $this->reserved_qty -= $qty;
            $this->sold_qty += $qty;
        } elseif ($type === 'adjustment') {
            $this->available_qty = $qty;
        }

        $this->save();

        DB::table('inventory_transactions')->insert([
            'inventory_id' => $this->id,
            'transaction_type' => $type,
            'quantity' => $qty,
            'old_qty' => $oldQty,
            'new_qty' => $this->available_qty,
            'remarks' => $remarks,
            'created_by_id' => $createdBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
