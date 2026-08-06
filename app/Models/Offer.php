<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class Offer extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'offer_code',
        'offer_name',
        'offer_type',
        'percentage',
        'fixed_amount',
        'required_emi_count',
        'free_emi_count',
        'offer_description',
        'banner',
        'start_date',
        'end_date',
        'priority',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'required_emi_count' => 'integer',
        'free_emi_count' => 'integer',
        'priority' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check() && !$model->created_by) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function emiPlans()
    {
        return $this->belongsToMany(EmiPlan::class, 'emi_plan_offers', 'offer_id', 'emi_plan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        $now = now();
        return $query->where('status', 'Active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now);
            });
    }
}
