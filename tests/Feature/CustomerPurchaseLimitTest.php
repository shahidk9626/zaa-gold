<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\EmiPlan;
use App\Models\Product;
use App\Models\GoldPrice;
use App\Models\GoldBooking;
use App\Models\SystemSetting;
use App\Models\ActivityLog;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class CustomerPurchaseLimitTest extends TestCase
{
    use RefreshDatabase;

    protected $bookingService;
    protected $customer;
    protected $admin;
    protected $product10g;
    protected $product50g;
    protected $plan;
    protected $goldPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = app(BookingService::class);

        // Seed Roles & Permissions
        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        $customerRole = Role::where('slug', 'customer')->first();
        $superAdminRole = Role::where('slug', 'super-admin')->first();

        // Create Customer
        $this->customer = User::create([
            'name' => 'Jane Limit User',
            'email' => 'jane.limit@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'active',
            'profile_completed' => 1,
            'verification_status' => 'verified',
        ]);
        $this->customer->customerDetail()->create([
            'slug' => 'jane-limit-slug',
        ]);

        // Create Admin
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.limit@example.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'status' => 'active',
        ]);

        // Products
        $this->product10g = Product::create([
            'name' => '10g Gold Bar',
            'slug' => '10g-gold-bar',
            'sku' => 'GB10G',
            'gold_type' => '24K',
            'weight_in_grams' => 10.00,
            'purity' => 99.99,
            'category' => 'bars',
            'status' => 'active',
        ]);

        $this->product50g = Product::create([
            'name' => '50g Gold Bar',
            'slug' => '50g-gold-bar',
            'sku' => 'GB50G',
            'gold_type' => '24K',
            'weight_in_grams' => 50.00,
            'purity' => 99.99,
            'category' => 'bars',
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
            'plan_name' => '6 Month Standard Plan',
            'plan_code' => 'STD6M',
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
     * Test updating settings configuration
     */
    public function test_updating_purchase_limit_configurations(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('settings.update'), [
            'settings' => [
                'customer_max_purchase_grams' => '120.00',
            ]
        ]);
        $response->assertJson(['success' => 'Configurations updated successfully.']);

        $this->assertEquals('120.00', SystemSetting::get('customer_max_purchase_grams'));

        // Check Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'system_settings',
            'action_type' => 'purchase_limit_changed',
            'description' => 'Purchase limit changed from 100.00g to 120.00g.',
        ]);
    }

    /**
     * Test purchase limit logic checks (getPurchasedWeightForFinancialYear, getRemainingPurchaseLimit, canPurchaseGold)
     */
    public function test_purchase_limit_logic_checks(): void
    {
        $this->actingAs($this->customer);

        // Limit initially 100g
        SystemSetting::updateOrCreate(['key' => 'customer_max_purchase_grams'], ['value' => '100.00']);

        // No purchases yet
        $this->assertEquals(0.00, $this->bookingService->getPurchasedWeightForFinancialYear($this->customer->id));
        $this->assertEquals(100.00, $this->bookingService->getRemainingPurchaseLimit($this->customer->id));
        $this->assertTrue($this->bookingService->canPurchaseGold($this->customer->id, 50.00));

        // Create booking of 50g
        $booking1 = $this->bookingService->createBooking($this->customer->id, $this->product50g->id, $this->plan->id);
        $this->assertNotNull($booking1);

        // Check again
        $this->assertEquals(50.00, $this->bookingService->getPurchasedWeightForFinancialYear($this->customer->id));
        $this->assertEquals(50.00, $this->bookingService->getRemainingPurchaseLimit($this->customer->id));
        $this->assertTrue($this->bookingService->canPurchaseGold($this->customer->id, 50.00));
        $this->assertFalse($this->bookingService->canPurchaseGold($this->customer->id, 50.01));

        // Create another booking of 50g
        $booking2 = $this->bookingService->createBooking($this->customer->id, $this->product50g->id, $this->plan->id);
        $this->assertNotNull($booking2);

        // Check again: remaining is 0
        $this->assertEquals(100.00, $this->bookingService->getPurchasedWeightForFinancialYear($this->customer->id));
        $this->assertEquals(0.00, $this->bookingService->getRemainingPurchaseLimit($this->customer->id));
        $this->assertFalse($this->bookingService->canPurchaseGold($this->customer->id, 10.00));
    }

    /**
     * Test cancelled/rejected bookings are excluded from the total purchased weight calculation
     */
    public function test_cancelled_and_rejected_bookings_are_excluded(): void
    {
        $this->actingAs($this->customer);

        // Create 50g booking
        $booking = $this->bookingService->createBooking($this->customer->id, $this->product50g->id, $this->plan->id);
        $this->assertEquals(50.00, $this->bookingService->getPurchasedWeightForFinancialYear($this->customer->id));

        // Change status to Cancelled
        $booking->status = 'Cancelled';
        $booking->save();

        // Total weight should now be 0g
        $this->assertEquals(0.00, $this->bookingService->getPurchasedWeightForFinancialYear($this->customer->id));
    }

    /**
     * Test plan catalog view shows remaining limit
     */
    public function test_catalog_shows_purchase_limit_metrics(): void
    {
        $this->actingAs($this->customer);
        
        $response = $this->get(route('customer.plans.index'));
        $response->assertStatus(200);
        $response->assertSee('Annual Purchase Limit');
        $response->assertSee('100.00');
    }

    /**
     * Test purchase validation blocks purchases that exceed the limit
     */
    public function test_purchase_validation_blocks_exceeded_booking(): void
    {
        $this->actingAs($this->customer);

        // Create 50g booking
        $this->bookingService->createBooking($this->customer->id, $this->product50g->id, $this->plan->id);

        // Attempting to buy another 50g product (total 100g) is allowed because limit is 100g
        // But attempting to buy 10g more would exceed 100g limit and fail!
        $this->bookingService->createBooking($this->customer->id, $this->product50g->id, $this->plan->id);

        $response = $this->post(route('customer.plans.book'), [
            'product_id' => $this->product10g->id,
            'emi_plan_id' => $this->plan->id,
            'terms' => '1',
        ]);

        $response->assertSessionHas('purchase_limit_error');

        // Verify Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'gold_booking',
            'action_type' => 'purchase_blocked_limit',
        ]);
    }

    /**
     * Test admin details page displays limit stats
     */
    public function test_admin_details_page_displays_limit(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('customers.show', $this->customer->id));
        $response->assertStatus(200);
        $response->assertSee('FY Gold Purchase Limit');
    }

    /**
     * Test reports list and CSV exports for purchase limit
     */
    public function test_reports_show_and_export_purchase_limit(): void
    {
        $this->actingAs($this->admin);

        // Access Reports Dashboard
        $response = $this->get(route('reports.dashboard', ['report' => 'purchase_limit']));
        $response->assertStatus(200);
        $response->assertSee('Jane Limit User');

        // Export Report to CSV
        $response = $this->get(route('reports.export', ['type' => 'purchase_limit']));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=Purchase limit_Report_' . now()->format('YmdHis') . '.csv');
    }
}
