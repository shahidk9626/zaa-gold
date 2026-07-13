<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\EmiPlan;
use App\Models\Product;
use App\Models\GoldPrice;
use App\Models\User;
use App\Models\GoldBooking;
use App\Models\BookingEmiSchedule;
use App\Models\BookingPayment;
use App\Models\ActivityLog;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmiPaymentEngineTest extends TestCase
{
    use RefreshDatabase;

    protected $bookingService;
    protected $paymentService;
    protected $customer;
    protected $product;
    protected $plan;
    protected $goldPrice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = app(BookingService::class);
        $this->paymentService = app(PaymentService::class);

        // Create mock data
        $this->customer = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => 4, // Customer Role
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => '5g 24K Gold Coin',
            'slug' => '5g-24k-gold-coin-' . uniqid(),
            'sku' => 'GC24K5G_' . uniqid(),
            'gold_type' => '24K',
            'weight_in_grams' => 5.00,
            'purity' => 99.99,
            'category' => 'coins',
            'status' => 'active',
        ]);

        $this->goldPrice = GoldPrice::create([
            'price_24k' => 6000.00,
            'price_22k' => 5500.00,
            'price_bullion' => 6000.00,
            'effective_date' => now(),
            'status' => 'active',
        ]);

        $this->plan = EmiPlan::create([
            'plan_name' => '12 Month Flat Interest Plan',
            'plan_code' => 'FLAT12M_' . uniqid(),
            'duration_months' => 12,
            'minimum_booking_amount' => 1000.00,
            'maximum_booking_amount' => 100000.00,
            'minimum_gold_weight' => 1.00,
            'maximum_gold_weight' => 100.00,
            'processing_fee_type' => 'fixed',
            'processing_fee' => 0.00,
            'interest_type' => 'flat',
            'interest_rate' => 10.00, // 10% Flat Rate
            'gst_on_gold_enabled' => false,
            'finance_charge_enabled' => false,
            'storage_charge_enabled' => false,
            'gst_on_charges_enabled' => false,
            'rounding_type' => 'none',
            'late_fee' => 200.00, // Flat 200 rupee late fee
            'late_fee_type' => 'fixed',
            'status' => 'active',
        ]);

        // Mock auth user
        $this->actingAs($this->customer);
    }

    /**
     * Test booking creation triggers schedule generation & automatic payment
     */
    public function test_booking_creation_automatically_pays_first_emi(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        $this->assertNotNull($booking);
        $this->assertEquals('Active', $booking->status);

        // Verify EMI schedules count
        $schedules = BookingEmiSchedule::where('booking_id', $booking->id)->get();
        $this->assertCount(12, $schedules);

        // Verify EMI #1 is Paid
        $firstEmi = $schedules->firstWhere('installment_number', 1);
        $this->assertNotNull($firstEmi);
        $this->assertEquals('Paid', $firstEmi->status);
        $this->assertNotNull($firstEmi->paid_at);
        $this->assertNotNull($firstEmi->payment_id);

        // Verify EMI #2 is Pending
        $secondEmi = $schedules->firstWhere('installment_number', 2);
        $this->assertNotNull($secondEmi);
        $this->assertEquals('Pending', $secondEmi->status);
        $this->assertNull($secondEmi->paid_at);
        $this->assertNull($secondEmi->payment_id);

        // Verify Payment record was created
        $payment = BookingPayment::find($firstEmi->payment_id);
        $this->assertNotNull($payment);
        $this->assertEquals($booking->monthly_emi, $payment->amount_paid);
        $this->assertMatchesRegularExpression('/^PAY\d{9}$/', $payment->payment_number);
        $this->assertMatchesRegularExpression('/^RCP\d{9}$/', $payment->receipt_number);

        // Check activity logs
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'gold_booking',
            'record_id' => $booking->id,
            'action_type' => 'first_emi_paid',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'gold_booking',
            'record_id' => $booking->id,
            'action_type' => 'receipt_generated',
        ]);
    }

    /**
     * Test manual collection of EMI payment including late fee
     */
    public function test_collect_payment_with_late_fee(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        $schedules = BookingEmiSchedule::where('booking_id', $booking->id)->orderBy('installment_number')->get();
        $secondEmi = $schedules->firstWhere('installment_number', 2);

        // Force second EMI due date to be in the past
        $secondEmi->due_date = now()->subDays(5);
        $secondEmi->save();

        // Perform payment collection
        $payment = $this->paymentService->collectPayment($booking, $secondEmi, [
            'payment_mode' => 'UPI',
            'transaction_reference' => 'TXN12345678',
            'remarks' => 'Manual payment for EMI 2',
            'payment_date' => now(),
        ]);

        $this->assertNotNull($payment);
        
        // Expected amount paid is EMI Amount + Late Fee (200)
        $expectedAmountPaid = $secondEmi->emi_amount + 200.00;
        $this->assertEquals($expectedAmountPaid, $payment->amount_paid);
        $this->assertEquals(200.00, $payment->late_fee_paid);

        // Verify EMI Schedule is updated
        $secondEmi->refresh();
        $this->assertEquals('Paid', $secondEmi->status);
        $this->assertEquals(200.00, $secondEmi->late_fee);
        $this->assertEquals($payment->id, $secondEmi->payment_id);

        // Check if late fee activity was logged
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'gold_booking',
            'record_id' => $booking->id,
            'action_type' => 'late_fee_applied',
        ]);
    }
}
