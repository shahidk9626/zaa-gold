<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class Product extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'weight',
        'purity',
        'category',
        'description',
        'display_order',
        'thumbnail',
        'gallery_images',
        'status',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'weight' => 'decimal:2',
        'purity' => 'decimal:2',
    ];

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }
}
