<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\EmiPlan;
use App\Models\Product;
use App\Models\GoldPrice;
use App\Models\User;
use App\Models\GoldBooking;
use App\Models\PriceLockCertificate;
use App\Models\BookingStatusHistory;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class GoldBookingTest extends TestCase
{
    use RefreshDatabase;

    protected $bookingService;
    protected $customer;
    protected $product;
    protected $plan;
    protected $goldPrice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = app(BookingService::class);

        // Create mock data
        $this->customer = User::create([
            'name' => 'John Doe',
            'email' => 'john.doe.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => 4, // Customer Role
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => '10g 24K Gold Coin',
            'slug' => '10g-24k-gold-coin-' . uniqid(),
            'sku' => 'GC24K10G_' . uniqid(),
            'gold_type' => '24K',
            'weight_in_grams' => 10.00,
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
            'plan_name' => '10 Month Gold Accumulator',
            'plan_code' => 'ACC10M_' . uniqid(),
            'duration_months' => 10,
            'minimum_booking_amount' => 1000.00,
            'maximum_booking_amount' => 100000.00,
            'minimum_gold_weight' => 1.00,
            'maximum_gold_weight' => 100.00,
            'processing_fee_type' => 'fixed',
            'processing_fee' => 0.00,
            'interest_type' => 'flat',
            'interest_rate' => 0.00,
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

        // Mock auth user
        $this->actingAs($this->customer);
    }

    /**
     * Test successful booking creation
     */
    public function test_booking_creation_locks_price_and_generates_certificate(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id,
            'Test remarks.'
        );

        $this->assertNotNull($booking);
        $this->assertDatabaseHas('gold_bookings', [
            'id' => $booking->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'emi_plan_id' => $this->plan->id,
            'status' => 'Active',
        ]);

        // Verify sequential booking number format
        $this->assertMatchesRegularExpression('/^ZG\d{8}$/', $booking->booking_number);

        // Verify locked prices are static
        $this->assertEquals(6000.00, $booking->locked_price_per_gram);
        $this->assertEquals(60000.00, $booking->locked_gold_value);

        // Check if status histories are automatically logged
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'new_status' => 'Booked',
            'old_status' => null,
        ]);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'new_status' => 'Active',
            'old_status' => 'Booked',
        ]);

        // Check if certificate is generated
        $this->assertNotNull($booking->certificate);
        $this->assertDatabaseHas('price_lock_certificates', [
            'booking_id' => $booking->id,
            'customer_id' => $this->customer->id,
        ]);
        $this->assertMatchesRegularExpression('/^PLC\d{8}$/', $booking->certificate->certificate_number);
    }

    /**
     * Test status change triggers status history logging
     */
    public function test_status_change_creates_history(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        // At creation, we have 2 status histories: null -> Booked, Booked -> Active
        $historyCountBefore = BookingStatusHistory::where('booking_id', $booking->id)->count();
        $this->assertEquals(2, $historyCountBefore);

        // Change status using the service to Completed
        $this->bookingService->changeStatus($booking, 'Completed', 'Plan completed.');

        $this->assertEquals('Completed', $booking->status);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'old_status' => 'Active',
            'new_status' => 'Completed',
            'remarks' => 'Plan completed.',
        ]);

        $latestHistory = BookingStatusHistory::where('booking_id', $booking->id)->latest('id')->first();
        $this->assertInstanceOf(\Carbon\Carbon::class, $latestHistory->created_at);
    }

    /**
     * Test transaction rollback on failure
     */
    public function test_transaction_rollback_on_failure(): void
    {
        $bookingsCountBefore = GoldBooking::count();
        $historiesCountBefore = BookingStatusHistory::count();
        $certificatesCountBefore = PriceLockCertificate::count();

        try {
            DB::transaction(function () {
                $booking = $this->bookingService->createBooking(
                    $this->customer->id,
                    $this->product->id,
                    $this->plan->id
                );

                // Simulate failure after booking is saved by throwing an exception
                throw new \Exception('Simulated crash.');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Simulated crash.', $e->getMessage());
        }

        // Verify no rows were committed to database
        $this->assertEquals($bookingsCountBefore, GoldBooking::count());
        $this->assertEquals($historiesCountBefore, BookingStatusHistory::count());
        $this->assertEquals($certificatesCountBefore, PriceLockCertificate::count());
    }
}
