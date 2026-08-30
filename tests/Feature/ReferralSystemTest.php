<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\EmiPlan;
use App\Models\GoldPrice;
use App\Models\GoldBooking;
use App\Models\CustomerReferral;
use App\Models\SystemSetting;
use App\Models\PaymentTransaction;
use App\Services\BookingService;
use App\Services\PaymentProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReferralSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $bookingService;
    protected $paymentProcessingService;
    protected $referrer;
    protected $customer;
    protected $product;
    protected $plan;
    protected $goldPrice;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->bookingService = app(BookingService::class);
        $this->paymentProcessingService = app(PaymentProcessingService::class);

        // Seed customer role
        \Illuminate\Support\Facades\DB::table('roles')->insertOrIgnore([
            'id' => 4,
            'name' => 'Customer',
            'slug' => 'customer',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Create Referrer
        $this->referrer = User::create([
            'name' => 'Referrer Customer',
            'email' => 'referrer.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => 4,
            'status' => 'active',
        ]);

        // Create Referred Customer
        $this->customer = User::create([
            'name' => 'Referred Customer',
            'email' => 'referred.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => 4,
            'status' => 'active',
        ]);

        // Create Product
        $this->product = Product::create([
            'name' => '10g 24K Gold Coin',
            'slug' => '10g-24k-gold-coin-' . uniqid(),
            'sku' => 'GC24K10G_' . uniqid(),
            'gold_type' => '24K',
            'weight_in_grams' => 10.00,
            'purity' => 999.99,
            'category' => 'coins',
            'status' => 'active',
        ]);

        // Create Gold Price
        $this->goldPrice = GoldPrice::create([
            'price_24k' => 6000.00,
            'price_22k' => 5500.00,
            'price_bullion' => 6000.00,
            'effective_date' => now(),
            'status' => 'active',
        ]);

        // Create Plan
        $this->plan = EmiPlan::create([
            'plan_name' => '10 Month Gold Accumulator',
            'plan_code' => 'ACC10M_' . uniqid(),
            'duration_months' => 10,
            'minimum_booking_amount' => 1000.00,
            'maximum_booking_amount' => 100000.00,
            'minimum_gold_weight' => 1.00,
            'maximum_gold_weight' => 100.00,
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
            'status' => 'active',
        ]);

        // Ensure cashback rate is seeded
        SystemSetting::updateOrCreate(['key' => 'referral_cashback_rate'], ['value' => '500.00', 'description' => 'Referral cashback rate per gram of gold']);
    }

    /**
     * Verify auto generation of referral code on creation.
     */
    public function test_customer_creation_generates_referral_code(): void
    {
        $this->assertNotEmpty($this->referrer->referral_code);
        $this->assertStringStartsWith('CUS', $this->referrer->referral_code);
        $this->assertNotEmpty($this->customer->referral_code);
        $this->assertStringStartsWith('CUS', $this->customer->referral_code);
    }

    /**
     * Test AJAX validation logic.
     */
    public function test_referral_code_validation(): void
    {
        $this->actingAs($this->customer);

        // 1. Validate empty code
        $response = $this->getJson(route('customer.referrals.validate') . '?code=');
        $response->assertJson([
            'success' => false,
            'message' => 'Please enter a referral code.'
        ]);

        // 2. Validate non-existent code
        $response = $this->getJson(route('customer.referrals.validate') . '?code=INVALID123');
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid referral code. Please check and try again.'
        ]);

        // 3. Validate self-referral
        $response = $this->getJson(route('customer.referrals.validate') . '?code=' . $this->customer->referral_code);
        $response->assertJson([
            'success' => false,
            'message' => 'You cannot use your own referral code.'
        ]);

        // 4. Validate valid active referral code
        $response = $this->getJson(route('customer.referrals.validate') . '?code=' . $this->referrer->referral_code);
        $response->assertJson([
            'success' => true,
            'message' => 'Referral code applied successfully.'
        ]);
    }

    /**
     * Test plan-purchase-based referral creation and calculation on payment completion.
     */
    public function test_referral_creation_on_payment_confirmation(): void
    {
        $this->actingAs($this->customer);

        // 1. Create a draft booking with referral code
        $booking = $this->bookingService->createDraftBookingForPayment(
            $this->customer->id,
            $this->product->id,
            $this->plan->id,
            'Simulated booking.',
            null,
            $this->referrer->referral_code
        );

        $this->assertEquals($this->referrer->referral_code, $booking->referral_code);
        $this->assertEquals('Draft', $booking->status);

        // Create mock payment transaction
        $transaction = PaymentTransaction::create([
            'transaction_number' => 'TXN' . uniqid(),
            'booking_id' => $booking->id,
            'customer_id' => $this->customer->id,
            'payment_type' => 'booking',
            'amount_payable' => $booking->monthly_emi,
            'amount' => $booking->monthly_emi,
            'payment_status' => 'Pending',
            'gateway' => 'cashfree',
            'gateway_order_id' => 'order_' . uniqid(),
        ]);

        // 2. Simulate payment completion
        $this->paymentProcessingService->confirmBookingPayment($transaction, [
            'success' => true,
            'status' => 'SUCCESS',
            'payment' => [
                'cf_payment_id' => '12345678',
                'bank_reference' => 'UTR123456',
            ]
        ]);

        $booking->refresh();
        $this->assertEquals('Active', $booking->status);

        // 3. Verify customer referral record is created
        $referral = CustomerReferral::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($referral);
        $this->assertEquals($this->referrer->id, $referral->referrer_id);
        $this->assertEquals($booking->id, $referral->booking_id);
        $this->assertEquals('Pending', $referral->status);

        // Verify pre-calculations
        $expectedCashback = $booking->gold_weight * 500.00; // 10g * 500 = 5000.00
        $this->assertEquals(500.00, $referral->cashback_rate);
        $this->assertEquals($expectedCashback, $referral->cashback_amount);
    }
}
