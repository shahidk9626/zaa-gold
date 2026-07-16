<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\EmiPlan;
use App\Models\Product;
use App\Models\GoldPrice;
use App\Models\GoldBooking;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminPlanPurchaseGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $product;
    protected $plan;
    protected $goldPrice;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Roles & Permissions
        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $customerRole = Role::where('slug', 'customer')->first();

        // Create Admin
        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin.payment@example.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'status' => 'active',
        ]);

        // Create Customer
        $this->customer = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane.payment@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);
        $this->customer->customerDetail()->create([
            'slug' => 'jane-payment',
        ]);

        // Product
        $this->product = Product::create([
            'name' => '10g 24K Coin',
            'slug' => '10g-24k-coin',
            'sku' => 'COIN10G',
            'gold_type' => '24K',
            'weight_in_grams' => 10.00,
            'purity' => 99.99,
            'category' => 'coins',
            'status' => 'active',
        ]);

        // Live Price
        $this->goldPrice = GoldPrice::create([
            'price_24k' => 6000.00,
            'price_22k' => 5500.00,
            'price_bullion' => 6000.00,
            'effective_date' => now(),
            'status' => 'active',
        ]);

        // EMI Plan
        $this->plan = EmiPlan::create([
            'plan_name' => '6 Month Saver',
            'plan_code' => 'SAV6M',
            'duration_months' => 6,
            'minimum_booking_amount' => 1000.00,
            'maximum_booking_amount' => 1000000.00,
            'minimum_gold_weight' => 1.00,
            'maximum_gold_weight' => 1000.00,
            'processing_fee_type' => 'fixed',
            'processing_fee' => 0.00,
            'interest_type' => 'flat',
            'interest_rate' => 0.00,
            'gst_on_gold_enabled' => true,
            'gst_on_gold_percent' => 3.00,
            'finance_charge_enabled' => false,
            'storage_charge_enabled' => false,
            'gst_on_charges_enabled' => false,
            'rounding_type' => 'none',
            'status' => 'active',
        ]);
    }

    /**
     * Test booking with Pay Now redirecting to checkout session
     */
    public function test_admin_booking_pay_now_redirects_to_checkout(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('bookings.store'), [
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'emi_plan_id' => $this->plan->id,
            'payment_method' => 'pay_now',
            'remarks' => 'Admin Pay Now test.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message', 'checkout_url']);

        $checkoutUrl = $response->json('checkout_url');

        // Retrieve transaction and check statuses
        $transaction = PaymentTransaction::latest()->first();
        $this->assertEquals('booking', $transaction->payment_type);
        $this->assertEquals('Processing', $transaction->payment_status);

        $booking = $transaction->booking;
        $this->assertEquals('Draft', $booking->status);

        // Access Checkout Page
        $checkoutResponse = $this->get($checkoutUrl);
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('Opening Secure Cashfree Checkout');
    }

    /**
     * Test booking with Generate Link returning public payment url
     */
    public function test_admin_booking_generate_link_returns_payment_url(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('bookings.store'), [
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'emi_plan_id' => $this->plan->id,
            'payment_method' => 'generate_link',
            'remarks' => 'Admin Generate Link test.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message', 'payment_url']);

        $paymentUrl = $response->json('payment_url');

        $transaction = PaymentTransaction::latest()->first();
        $this->assertNotNull($transaction->payment_token);
        $this->assertEquals($paymentUrl, $transaction->payment_url);

        // Verify link is listed in Payment Links list
        $linksResponse = $this->actingAs($this->admin)->get(route('payment-links.index'));
        $linksResponse->assertStatus(200);
        $linksResponse->assertSee($transaction->transaction_number);

        // Access the public payment url (no auth needed)
        auth()->logout();

        $payResponse = $this->get($paymentUrl);
        $payResponse->assertStatus(200);
        $payResponse->assertSee('Opening Secure Cashfree Checkout');
    }
}
