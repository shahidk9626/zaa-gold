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
use App\Models\BookingPayment;
use App\Models\CashCollectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CashCollectionPaymentFlowTest extends TestCase
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

        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        // Add Module and Permissions since custom migrations won't run unless ran in sqlite memory
        $module = \App\Models\Module::updateOrCreate(
            ['slug' => 'cash-collection'],
            ['name' => 'Cash Collection', 'status' => 'active']
        );

        $actions = ['view', 'verify', 'reject', 'manage'];
        foreach ($actions as $action) {
            \App\Models\Permission::updateOrCreate(
                ['slug' => 'cash-collection.' . $action],
                [
                    'module_id' => $module->id,
                    'name' => ucfirst($action) . ' Cash Collection',
                ]
            );
        }

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $customerRole = Role::where('slug', 'customer')->first();

        $permissions = \App\Models\Permission::where('slug', 'like', 'cash-collection.%')->get();
        if ($superAdminRole) {
            foreach ($permissions as $permission) {
                \Illuminate\Support\Facades\DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $superAdminRole->id, 'permission_id' => $permission->id],
                    ['allowed' => 1]
                );
            }
        }

        // Create Admin
        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin.cash@example.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'status' => 'active',
        ]);

        // Create Customer
        $this->customer = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane.cash@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);
        $this->customer->customerDetail()->create([
            'slug' => 'jane-cash',
        ]);

        // Product
        $this->product = Product::create([
            'name' => '10g 24K Coin',
            'slug' => '10g-24k-coin-cash',
            'sku' => 'COIN10GCASH',
            'gold_type' => '24K',
            'weight_in_grams' => 10.00,
            'purity' => 999.99,
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
            'plan_code' => 'SAV6MCASH',
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

    public function test_admin_can_create_booking_via_cash_payment(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('bookings.store'), [
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'emi_plan_id' => $this->plan->id,
            'payment_method' => 'cash',
            'remarks' => 'Cash downpayment testing.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message', 'redirect_url']);

        // Check if Booking was created with status 'Booked'
        $booking = GoldBooking::latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals('Booked', $booking->status);

        // Check if PaymentTransaction was created with status 'Pending Verification'
        $transaction = PaymentTransaction::latest()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('Pending Verification', $transaction->payment_status);
        $this->assertEquals('cash', $transaction->gateway);

        // Check if BookingPayment (Receipt) was created with status 'Pending Verification'
        $receipt = BookingPayment::latest()->first();
        $this->assertNotNull($receipt);
        $this->assertEquals('Pending Verification', $receipt->status);
        $this->assertEquals('Cash', $receipt->payment_mode);

        // Check if CashCollectionRequest was created
        $ccr = CashCollectionRequest::latest()->first();
        $this->assertNotNull($ccr);
        $this->assertEquals('Pending Verification', $ccr->status);
        $this->assertEquals($booking->id, $ccr->booking_id);
        $this->assertEquals($receipt->id, $ccr->payment_id);
    }

    public function test_admin_can_verify_cash_collection_request(): void
    {
        $this->actingAs($this->admin);

        // Create booking
        $this->postJson(route('bookings.store'), [
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'emi_plan_id' => $this->plan->id,
            'payment_method' => 'cash',
            'remarks' => 'Cash verification test.',
        ]);

        $ccr = CashCollectionRequest::latest()->first();

        // Verify request
        $verifyResponse = $this->post(route('admin.cash-collections.verify', $ccr->id), [
            'remark' => 'Verified and deposited into vault.',
        ]);

        $verifyResponse->assertRedirect(route('admin.cash-collections.show', $ccr->id));

        // Reload & Check Statuses
        $ccr->refresh();
        $this->assertEquals('Verified', $ccr->status);
        $this->assertEquals($this->admin->id, $ccr->verified_by_id);
        $this->assertEquals('Verified and deposited into vault.', $ccr->remarks);

        $receipt = BookingPayment::find($ccr->payment_id);
        $this->assertEquals('Paid', $receipt->status);

        $transaction = PaymentTransaction::find($ccr->transaction_id);
        $this->assertEquals('Success', $transaction->payment_status);

        $booking = GoldBooking::find($ccr->booking_id);
        $this->assertEquals('Paid', $booking->status);

        // Verify invoice was created
        $this->assertTrue(\App\Models\GstInvoice::where('booking_id', $booking->id)->exists());
    }

    public function test_admin_can_reject_cash_collection_request(): void
    {
        $this->actingAs($this->admin);

        // Create booking
        $this->postJson(route('bookings.store'), [
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'emi_plan_id' => $this->plan->id,
            'payment_method' => 'cash',
            'remarks' => 'Cash rejection test.',
        ]);

        $ccr = CashCollectionRequest::latest()->first();

        // Reject request
        $rejectResponse = $this->post(route('admin.cash-collections.reject', $ccr->id), [
            'reason' => 'Counterfeit note detected.',
        ]);

        $rejectResponse->assertRedirect(route('admin.cash-collections.show', $ccr->id));

        // Reload & Check Statuses
        $ccr->refresh();
        $this->assertEquals('Rejected', $ccr->status);
        $this->assertEquals($this->admin->id, $ccr->verified_by_id);
        $this->assertEquals('Counterfeit note detected.', $ccr->remarks);

        $receipt = BookingPayment::find($ccr->payment_id);
        $this->assertEquals('Rejected', $receipt->status);

        $transaction = PaymentTransaction::find($ccr->transaction_id);
        $this->assertEquals('Rejected', $transaction->payment_status);
    }
}
