<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\EmiPlan;
use App\Services\EmiCalculationService;

class EmiScheduleTest extends TestCase
{
    protected $emiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emiService = app(EmiCalculationService::class);
    }

    /**
     * Test Outstanding Schedule for Flat Interest Plan
     */
    public function test_flat_interest_schedule_generation(): void
    {
        $plan = new EmiPlan([
            'plan_name' => 'Flat Test Plan',
            'duration_months' => 6,
            'interest_type' => 'flat',
            'interest_rate' => 12.00, // 12% per annum
            'processing_fee_type' => 'fixed',
            'processing_fee' => 0.00,
            'gst_on_gold_enabled' => false,
            'finance_charge_enabled' => false,
            'storage_charge_enabled' => false,
            'gst_on_charges_enabled' => false,
            'rounding_type' => 'none',
        ]);

        $amount = 6000.00;
        // Total Interest = 6000 * 12% * (6/12) = 360
        // Grand Total = 6360
        // Monthly EMI = 6360 / 6 = 1060
        // Principal portion per month = 1000
        // Interest portion per month = 60

        $schedule = $this->emiService->generateOutstandingSchedule($plan, $amount);

        $this->assertCount(6, $schedule);
        $this->assertEquals(1060.00, $schedule[0]['monthly_emi']);
        $this->assertEquals(1000.00, $schedule[0]['principal_amount']);
        $this->assertEquals(60.00, $schedule[0]['interest_amount']);
        $this->assertEquals(5000.00, $schedule[0]['closing_principal']);
        $this->assertEquals(5300.00, $schedule[0]['running_balance']);

        // Check last month closing balance
        $lastMonth = end($schedule);
        $this->assertEquals(6, $lastMonth['month_no']);
        $this->assertEquals(0.00, $lastMonth['closing_principal']);
        $this->assertEquals(0.00, $lastMonth['running_balance']);
    }

    /**
     * Test Outstanding Schedule for Reducing Interest Plan
     */
    public function test_reducing_interest_schedule_generation(): void
    {
        $plan = new EmiPlan([
            'plan_name' => 'Reducing Test Plan',
            'duration_months' => 3,
            'interest_type' => 'reducing',
            'interest_rate' => 12.00, // 1% monthly rate
            'processing_fee_type' => 'fixed',
            'processing_fee' => 0.00,
            'gst_on_gold_enabled' => false,
            'finance_charge_enabled' => false,
            'storage_charge_enabled' => false,
            'gst_on_charges_enabled' => false,
            'rounding_type' => 'none',
        ]);

        $amount = 3000.00;
        // EMI calculations:
        // Monthly rate r = 0.01, n = 3
        // EMI = (3000 * 0.01 * (1.01)^3) / ((1.01)^3 - 1) = 1020.10
        // Month 1:
        // Opening Principal = 3000
        // Interest = 3000 * 1% = 30
        // Principal = 1020.10 - 30 = 990.10
        // Closing Principal = 2009.90

        $schedule = $this->emiService->generateOutstandingSchedule($plan, $amount);

        $this->assertCount(3, $schedule);
        $this->assertEquals(3000.00, $schedule[0]['opening_principal']);
        $this->assertEquals(30.00, $schedule[0]['interest_amount']);
        $this->assertEquals(990.07, $schedule[0]['principal_amount']);
        $this->assertEquals(2009.93, $schedule[0]['closing_principal']);

        // Last month should round to 0 closing principal
        $lastMonth = end($schedule);
        $this->assertEquals(0.00, $lastMonth['closing_principal']);
        $this->assertEquals(0.00, $lastMonth['running_balance']);
    }

    /**
     * Test Outstanding Schedule for Financial Engine Plan (GST, charges, etc.)
     */
    public function test_financial_engine_schedule_generation(): void
    {
        $plan = new EmiPlan([
            'plan_name' => 'Financial Engine Test Plan',
            'duration_months' => 5,
            'interest_type' => 'flat',
            'interest_rate' => 0.00,
            'processing_fee_type' => 'fixed',
            'processing_fee' => 0.00,
            'gst_on_gold_enabled' => true,
            'gst_on_gold_percent' => 3.00,
            'finance_charge_enabled' => true,
            'finance_charge_type' => 'percentage',
            'finance_charge_value' => 5.00,
            'storage_charge_enabled' => true,
            'storage_charge_type' => 'percentage',
            'storage_charge_value' => 2.00,
            'gst_on_charges_enabled' => true,
            'gst_on_charges_percent' => 18.00,
            'rounding_type' => 'none',
        ]);

        $amount = 10000.00;
        // Gold Value = 10000
        // GST on Gold = 300
        // Finance Charge = 500
        // Storage Charge = 200
        // Total Charges = 700
        // GST on Charges = 126
        // Grand Total = 10000 + 300 + 700 + 126 = 11126
        // EMI = 11126 / 5 = 2225.20
        // Monthly Principal = 10000 / 5 = 2000
        // Monthly Interest/Charges = 2225.20 - 2000 = 225.20

        $schedule = $this->emiService->generateOutstandingSchedule($plan, $amount);

        $this->assertCount(5, $schedule);
        $this->assertEquals(2225.20, $schedule[0]['monthly_emi']);
        $this->assertEquals(2000.00, $schedule[0]['principal_amount']);
        $this->assertEquals(225.20, $schedule[0]['interest_amount']);
        $this->assertEquals(8000.00, $schedule[0]['closing_principal']);
        
        $lastMonth = end($schedule);
        $this->assertEquals(0.00, $lastMonth['closing_principal']);
        $this->assertEquals(0.00, $lastMonth['running_balance']);
    }
}
