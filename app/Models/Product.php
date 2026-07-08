<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;
use App\Services\ProductPricingService;

class Product extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'gold_type',
        'weight_in_grams',
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
        'weight_in_grams' => 'decimal:2',
        'purity' => 'decimal:2',
    ];

    protected $appends = [
        'calculated_price',
    ];

    public function getCurrentPrice()
    {
        return app(ProductPricingService::class)->calculatePrice($this);
    }

    public function getCalculatedPriceAttribute()
    {
        return $this->getCurrentPrice();
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }
}
