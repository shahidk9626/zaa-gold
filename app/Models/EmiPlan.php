<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;
use App\Services\EmiCalculationService;

class EmiPlan extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'plan_name',
        'plan_code',
        'duration_months',
        'minimum_booking_amount',
        'maximum_booking_amount',
        'minimum_gold_weight',
        'maximum_gold_weight',
        'processing_fee_type',
        'processing_fee',
        'interest_type',
        'interest_rate',
        'late_fee_type',
        'late_fee',
        'grace_days',
        'auto_terminate_after_missed_emi',
        'maintenance_deduction_percent',
        'description',
        'display_order',
        'status',
        'is_default',
        'gst_on_gold_enabled',
        'gst_on_gold_percent',
        'finance_charge_enabled',
        'finance_charge_type',
        'finance_charge_value',
        'storage_charge_enabled',
        'storage_charge_type',
        'storage_charge_value',
        'gst_on_charges_enabled',
        'gst_on_charges_percent',
        'rounding_type',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'minimum_booking_amount' => 'decimal:2',
        'maximum_booking_amount' => 'decimal:2',
        'minimum_gold_weight' => 'decimal:2',
        'maximum_gold_weight' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'maintenance_deduction_percent' => 'decimal:2',
        'gst_on_gold_enabled' => 'boolean',
        'gst_on_gold_percent' => 'decimal:2',
        'finance_charge_enabled' => 'boolean',
        'finance_charge_value' => 'decimal:2',
        'storage_charge_enabled' => 'boolean',
        'storage_charge_value' => 'decimal:2',
        'gst_on_charges_enabled' => 'boolean',
        'gst_on_charges_percent' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->is_default) {
                // Ensure only one default plan exists
                static::where('id', '!=', $model->id)->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get default active EMI plan
     */
    public static function getDefaultPlan()
    {
        return self::where('status', 'active')
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get all active EMI plans
     */
    public static function getActivePlans()
    {
        return self::where('status', 'active')
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Calculate monthly EMI for an amount using this plan
     */
    public static function calculateMonthlyEMI($planId, $amount)
    {
        $plan = self::find($planId);
        if (!$plan) {
            return 0.00;
        }

        return app(EmiCalculationService::class)->calculateMonthlyInstallment($plan, $amount);
    }
}
