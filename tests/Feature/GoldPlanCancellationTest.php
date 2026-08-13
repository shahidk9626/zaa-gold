<?php

namespace Tests\Feature;

use App\Models\EmiPlan;
use App\Models\GoldBooking;
use App\Models\BookingPayment;
use App\Models\BookingEmiSchedule;
use App\Models\CancellationRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\RefundCalculationService;
use App\Services\CancellationService;
use App\Services\PaymentService;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoldPlanCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected $calcService;
    protected $cancellationService;
    protected $paymentService;
    protected $customerService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->calcService = app(RefundCalculationService::class);
        $this->cancellationService = app(CancellationService::class);
        $this->paymentService = app(PaymentService::class);
        $this->customerService = app(CustomerService::class);
    }

    /**
     * Helper to setup initial plan, product, customer, and booking.
     */
    protected function setupBooking(float $cancellationChargePercent = 4.00): array
    {
        $customer = User::create([
            'name' => 'John Customer',
            'email' => 'john@test.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);

        $product = Product::create([
            'name' => '10g Gold Coin',
            'sku' => 'G-COIN-10G',
            'weight_in_grams' => 10.00,
            'purity' => 99.9,
            'gold_type' => '24K',
            'making_charge_type' => 'fixed',
            'making_charge_value' => 350.00,
            'status' => 'active',
        ]);

        $plan = EmiPlan::create([
            'plan_name' => '10 Months Gold Accumulator',
            'plan_code' => 'GOLD10M',
            'duration_months' => 10,
            'interest_rate' => 0.00,
            'interest_type' => 'flat',
            'processing_fee' => 500.00,
            'processing_fee_type' => 'fixed',
            'minimum_booking_amount' => 500.00,
            'maximum_booking_amount' => 50000.00,
            'minimum_gold_weight' => 1.00,
            'maximum_gold_weight' => 100.00,
            'rounding_type' => 'nearest_rupee',
            'cancellation_charge_percent' => $cancellationChargePercent,
            'status' => 'active',
            'is_default' => false,
            'late_fee_type' => 'fixed',
            'late_fee' => 100.00,
            'grace_days' => 5,
            'auto_terminate_after_missed_emi' => 3,
            'maintenance_deduction_percent' => 0.00,
        ]);

        $booking = GoldBooking::create([
            'booking_number' => 'ZG2600001',
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'emi_plan_id' => $plan->id,
            'gold_weight' => 5.00,
            'locked_price_per_gram' => 6000.00,
            'locked_gold_value' => 30000.00,
            'gst_on_gold_percent' => 3.00,
            'gst_on_gold_amount' => 900.00,
            'grand_total' => 30900.00,
            'monthly_emi' => 3090.00,
            'duration_months' => 10,
            'status' => 'Active',
            'booking_date' => now(),
        ]);

        // Generate EMI schedules
        for ($i = 1; $i <= 10; $i++) {
            BookingEmiSchedule::create([
                'booking_id' => $booking->id,
                'installment_number' => $i,
                'due_date' => now()->addMonths($i),
                'emi_amount' => 3090.00,
                'status' => 'Pending',
            ]);
        }

        return compact('customer', 'product', 'plan', 'booking');
    }

    /**
     * Test calculations inside RefundCalculationService
     */
    public function test_refund_calculation_is_correct()
    {
        $setup = $this->setupBooking(4.00); // 4.00% charge
        $booking = $setup['booking'];

        // Simulate 3 paid EMI installments (3 * 3,090 = 9,270 paid)
        for ($i = 1; $i <= 3; $i++) {
            $schedule = BookingEmiSchedule::where('booking_id', $booking->id)
                ->where('installment_number', $i)
                ->first();
            
            $schedule->update(['status' => 'Paid', 'paid_at' => now()]);

            BookingPayment::create([
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'emi_schedule_id' => $schedule->id,
                'receipt_number' => 'REC-26-00000' . $i,
                'amount_paid' => 3090.00,
                'payment_date' => now(),
                'payment_mode' => 'Online',
                'status' => 'Paid',
            ]);
        }

        $calc = $this->calcService->calculateRefund($booking);

        // Assertions
        $this->assertEquals(9270.00, $calc['total_amount_paid']);
        $this->assertEquals(4.00, $calc['cancellation_charge_percent']);
        
        $expectedCharge = round(9270.00 * 0.04, 2); // 370.80
        $this->assertEquals($expectedCharge, $calc['cancellation_charge_amount']);
        
        $expectedRefund = round(9270.00 - $expectedCharge, 2); // 8899.20
        $this->assertEquals($expectedRefund, $calc['refund_amount']);
    }

    /**
     * Test creating a cancellation request via Customer Portal flow
     */
    public function test_customer_can_submit_cancellation_request()
    {
        $setup = $this->setupBooking(5.00);
        $booking = $setup['booking'];
        $customer = $setup['customer'];

        $this->actingAs($customer);

        // Submit request
        $request = $this->cancellationService->createRequest(
            $booking,
            'I am moving out of the state and cannot continue.'
        );

        $this->assertDatabaseHas('cancellation_requests', [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'status' => 'Requested',
            'cancellation_reason' => 'I am moving out of the state and cannot continue.',
            'cancellation_charge_percent' => 5.00,
        ]);

        $this->assertEquals('Requested', $request->status);
    }

    /**
     * Test Admin Action: Approval blocks payments and freezes outstandings
     */
    public function test_admin_approval_cancels_booking_freezes_payment_and_outstanding()
    {
        $setup = $this->setupBooking(10.00);
        $booking = $setup['booking'];
        $customer = $setup['customer'];

        // Create request
        $request = $this->cancellationService->createRequest($booking, 'Reason');

        // Login as admin/staff
        $admin = User::create(['name' => 'Admin Staff', 'email' => 'admin@test.com', 'password' => 'pass', 'role' => 'admin']);
        $this->actingAs($admin);

        // Approve cancellation
        $this->cancellationService->updateStatus($request, 'Approved', 'Approval confirmed.');

        // 1. Verify Booking status became Cancelled
        $booking->refresh();
        $this->assertEquals('Cancelled', $booking->status);

        // 2. Verify outstanding balance is frozen to 0.00
        $summary = $this->customerService->getFinancialSummary($booking);
        $this->assertEquals(0.00, $summary['outstanding']);

        // 3. Verify future payment collections are blocked
        $nextSchedule = BookingEmiSchedule::where('booking_id', $booking->id)
            ->where('installment_number', 1)
            ->first();

        $this->expectException(\RuntimeException::class);
        $this->paymentService->collectPayment($booking, $nextSchedule, [
            'amount_paid' => 3090.00,
            'payment_mode' => 'Cash',
            'payment_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Test Admin Action: Customer Retained allows submitting new request later
     */
    public function test_customer_retained_keeps_booking_active_and_permits_re_requesting()
    {
        $setup = $this->setupBooking(2.00);
        $booking = $setup['booking'];
        $customer = $setup['customer'];

        // Request 1
        $request1 = $this->cancellationService->createRequest($booking, 'Reason 1');

        $admin = User::create(['name' => 'Admin Staff', 'email' => 'admin2@test.com', 'password' => 'pass', 'role' => 'admin']);
        $this->actingAs($admin);

        // Retain Customer
        $this->cancellationService->updateStatus($request1, 'Customer Retained', 'Offered loyalty discount.');

        // Booking remains active
        $booking->refresh();
        $this->assertEquals('Active', $booking->status);
        $this->assertEquals('Customer Retained', $request1->fresh()->status);

        // Can request cancellation again
        $request2 = $this->cancellationService->createRequest($booking, 'Reason 2');
        $this->assertEquals('Requested', $request2->status);
    }

    /**
     * Test state lock rules (approved/rejected cannot change again)
     */
    public function test_final_states_are_locked_and_cannot_transition()
    {
        $setup = $this->setupBooking(4.00);
        $booking = $setup['booking'];
        $request = $this->cancellationService->createRequest($booking, 'Reason');

        $admin = User::create(['name' => 'Admin Staff', 'email' => 'admin3@test.com', 'password' => 'pass', 'role' => 'admin']);
        $this->actingAs($admin);

        // Reject request
        $this->cancellationService->updateStatus($request, 'Rejected', 'Invalid request.');
        $this->assertEquals('Rejected', $request->status);

        // Attempting to approve rejected request should fail
        $this->expectException(\RuntimeException::class);
        $this->cancellationService->updateStatus($request, 'Approved', 'Try approving now.');
    }

    /**
     * Test plan display status and lifecycle precedence rules
     */
    public function test_plan_lifecycle_display_status_precedence()
    {
        $setup = $this->setupBooking(5.00);
        $booking = $setup['booking'];
        $customer = $setup['customer'];

        // TEST 1: 12/12 EMI paid, No cancellation request -> Completed
        for ($i = 1; $i <= 10; $i++) {
            $schedule = BookingEmiSchedule::where('booking_id', $booking->id)
                ->where('installment_number', $i)
                ->first();
            $schedule->update(['status' => 'Paid', 'paid_at' => now()]);
            BookingPayment::create([
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'emi_schedule_id' => $schedule->id,
                'receipt_number' => 'REC-TEST-' . $i,
                'amount_paid' => 3090.00,
                'payment_date' => now(),
                'payment_mode' => 'Online',
                'status' => 'Paid',
            ]);
        }

        // Trigger completion check
        $financialService = app(\App\Services\FinancialCalculationService::class);
        $financialService->completeIfEligible($booking);

        $booking->refresh();
        $this->assertEquals('Completed', $booking->status);
        $this->assertEquals('Completed', $booking->display_status);

        // TEST 2: 12/12 EMI, Cancellation request Pending -> Cancellation Under Review, NOT Completed
        $request = $this->cancellationService->createRequest($booking, 'Reason');
        $this->assertEquals('Cancellation Under Review', $booking->display_status);

        // TEST 3: 5/12 EMI, Cancellation request Pending -> Cancellation Under Review, NOT Active
        $setup2 = $this->setupBooking(5.00);
        $booking2 = $setup2['booking'];
        for ($i = 1; $i <= 5; $i++) {
            $schedule = BookingEmiSchedule::where('booking_id', $booking2->id)
                ->where('installment_number', $i)
                ->first();
            $schedule->update(['status' => 'Paid', 'paid_at' => now()]);
            BookingPayment::create([
                'booking_id' => $booking2->id,
                'customer_id' => $booking2->customer_id,
                'emi_schedule_id' => $schedule->id,
                'receipt_number' => 'REC-TEST2-' . $i,
                'amount_paid' => 3090.00,
                'payment_date' => now(),
                'payment_mode' => 'Online',
                'status' => 'Paid',
            ]);
        }
        $financialService->completeIfEligible($booking2);
        $booking2->refresh();
        $this->assertEquals('Active', $booking2->status);

        $request2 = $this->cancellationService->createRequest($booking2, 'Reason 2');
        $this->assertEquals('Cancellation Under Review', $booking2->display_status);

        // TEST 4: 12/12 EMI, Cancellation Approved -> Cancelled, NOT Completed
        $admin = User::create(['name' => 'Admin Staff', 'email' => 'admin_test@test.com', 'password' => 'pass', 'role' => 'admin']);
        $this->actingAs($admin);
        $this->cancellationService->updateStatus($request, 'Approved', 'Approved cancellation request.');

        $booking->refresh();
        $this->assertEquals('Cancelled', $booking->status);
        $this->assertEquals('Cancelled', $booking->display_status);

        // TEST 5: 12/12 EMI, Cancellation Rejected -> Completed
        $setup3 = $this->setupBooking(5.00);
        $booking3 = $setup3['booking'];
        for ($i = 1; $i <= 10; $i++) {
            $schedule = BookingEmiSchedule::where('booking_id', $booking3->id)
                ->where('installment_number', $i)
                ->first();
            $schedule->update(['status' => 'Paid', 'paid_at' => now()]);
            BookingPayment::create([
                'booking_id' => $booking3->id,
                'customer_id' => $booking3->customer_id,
                'emi_schedule_id' => $schedule->id,
                'receipt_number' => 'REC-TEST3-' . $i,
                'amount_paid' => 3090.00,
                'payment_date' => now(),
                'payment_mode' => 'Online',
                'status' => 'Paid',
            ]);
        }
        $financialService->completeIfEligible($booking3);
        $booking3->refresh();
        $this->assertEquals('Completed', $booking3->status);

        $request3 = $this->cancellationService->createRequest($booking3, 'Reason 3');
        $this->assertEquals('Cancellation Under Review', $booking3->display_status);

        $this->cancellationService->updateStatus($request3, 'Rejected', 'Rejected cancellation request.');
        $booking3->refresh();
        $this->assertEquals('Completed', $booking3->display_status);

        // TEST 6: 5/12 EMI, Cancellation Rejected -> Active
        $this->cancellationService->updateStatus($request2, 'Rejected', 'Rejected cancellation request.');
        $booking2->refresh();
        $this->assertEquals('Active', $booking2->display_status);

        // TEST 7: Cancelled plan -> Historical payment/EMI records remain intact
        $this->assertEquals('Cancelled', $booking->display_status);
        $this->assertEquals(10, BookingEmiSchedule::where('booking_id', $booking->id)->where('status', 'Paid')->count());
        $this->assertEquals(10, BookingPayment::where('booking_id', $booking->id)->where('status', 'Paid')->count());
    }
}
