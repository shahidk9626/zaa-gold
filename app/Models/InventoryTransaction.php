<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_id',
        'transaction_type',
        'quantity',
        'old_qty',
        'new_qty',
        'remarks',
        'created_by_id',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
