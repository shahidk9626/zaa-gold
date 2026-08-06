<?php

namespace Tests\Feature;

use App\Models\BookingEmiSchedule;
use App\Models\BookingPayment;
use App\Models\EmiPlan;
use App\Models\GoldBooking;
use App\Models\GoldPrice;
use App\Models\Product;
use App\Models\User;
use App\Services\EmiCalculationService;
use App\Services\FinancialCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialCalculationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_emi_schedule_sum_matches_grand_total_with_final_emi_adjustment(): void
    {
        $plan = $this->plan();
        $schedule = app(EmiCalculationService::class)->generateSchedule($plan, 18141.09, now());

        $this->assertCount(12, $schedule);
        $this->assertEquals(1511.76, $schedule[0]['emi_amount']);
        $this->assertEquals(1511.73, $schedule[11]['emi_amount']);
        $this->assertEquals(18141.09, app(FinancialCalculationService::class)->sumScheduleAmounts($schedule));
    }

    public function test_tiny_negative_outstanding_normalizes_and_completes_booking(): void
    {
        $customer = User::create([
            'name' => 'Rounding Customer',
            'email' => 'rounding@example.com',
            'password' => bcrypt('password'),
            'role_id' => 4,
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Rounding Gold',
            'slug' => 'rounding-gold',
            'sku' => 'ROUND-GOLD',
            'gold_type' => '24K',
            'weight_in_grams' => 3.00,
            'purity' => 999.99,
            'category' => 'coins',
            'status' => 'active',
        ]);

        $price = GoldPrice::create([
            'price_24k' => 6047.03,
            'price_22k' => 5540.00,
            'price_bullion' => 6047.03,
            'effective_date' => now(),
            'status' => 'active',
        ]);

        $booking = GoldBooking::create([
            'booking_number' => 'ZG26009999',
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'emi_plan_id' => $this->plan()->id,
            'gold_price_id' => $price->id,
            'gold_weight' => 3.00,
            'gold_purity' => 999.99,
            'locked_price_per_gram' => 6047.03,
            'locked_gold_value' => 18141.09,
            'grand_total' => 18141.09,
            'monthly_emi' => 1511.76,
            'duration_months' => 12,
            'booking_date' => now(),
            'estimated_completion_date' => now()->addMonths(12),
            'status' => 'Active',
            'original_amount' => 18141.09,
            'final_amount' => 18141.09,
            'savings_amount' => 0.00,
        ]);

        for ($i = 1; $i <= 12; $i++) {
            $schedule = BookingEmiSchedule::create([
                'booking_id' => $booking->id,
                'installment_number' => $i,
                'due_date' => now()->addMonths($i - 1),
                'opening_principal' => 0,
                'principal_amount' => 1511.76,
                'interest_amount' => 0,
                'emi_amount' => 1511.76,
                'closing_principal' => 0,
                'outstanding_balance' => 0,
                'status' => 'Paid',
                'paid_at' => now(),
            ]);

            BookingPayment::create([
                'payment_number' => 'PAY26' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'receipt_number' => 'RCP26' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'booking_id' => $booking->id,
                'emi_schedule_id' => $schedule->id,
                'customer_id' => $customer->id,
                'payment_mode' => 'Cash',
                'amount_paid' => 1511.76,
                'principal_paid' => 1511.76,
                'interest_paid' => 0,
                'payment_date' => now(),
                'status' => 'Paid',
            ]);
        }

        $financialService = app(FinancialCalculationService::class);

        $this->assertEquals(-0.03, $financialService->rawOutstanding($booking));
        $this->assertEquals(0.00, $financialService->outstanding($booking));
        $this->assertEquals(18141.09, $financialService->displayPaidTotal($booking));
        $this->assertTrue($financialService->completeIfEligible($booking));

        $booking->refresh();
        $this->assertEquals('Completed', $booking->status);
    }

    protected function plan(): EmiPlan
    {
        return EmiPlan::firstOrCreate(
            ['plan_code' => 'ROUND12'],
            [
                'plan_name' => 'Rounding 12 Month Plan',
                'duration_months' => 12,
                'minimum_booking_amount' => 1000.00,
                'maximum_booking_amount' => 1000000.00,
                'minimum_gold_weight' => 1.00,
                'maximum_gold_weight' => 1000.00,
                'processing_fee_type' => 'fixed',
                'processing_fee' => 0.00,
                'interest_type' => 'flat',
                'interest_rate' => 0.00,
                'gst_on_gold_enabled' => false,
                'finance_charge_enabled' => false,
                'storage_charge_enabled' => false,
                'gst_on_charges_enabled' => false,
                'rounding_type' => 'none',
                'late_fee' => 0.00,
                'late_fee_type' => 'fixed',
                'status' => 'active',
            ]
        );
    }
}
