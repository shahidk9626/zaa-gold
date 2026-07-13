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
use App\Models\BookingDelivery;
use App\Models\DeliveryStatusHistory;
use App\Models\ActivityLog;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\DeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class DeliveryFulfillmentEngineTest extends TestCase
{
    use RefreshDatabase;

    protected $bookingService;
    protected $paymentService;
    protected $deliveryService;
    protected $customer;
    protected $product;
    protected $plan;
    protected $goldPrice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = app(BookingService::class);
        $this->paymentService = app(PaymentService::class);
        $this->deliveryService = app(DeliveryService::class);

        // Fake public storage for Challan PDFs
        Storage::fake('public');

        // Create base customer
        $this->customer = User::create([
            'name' => 'Michael Scott',
            'email' => 'michael.scott@example.com',
            'password' => bcrypt('password'),
            'role_id' => 4,
            'status' => 'active',
        ]);

        $this->customer->customerDetail()->create([
            'phone_number' => '9998887776',
            'address' => 'Scranton Business Park, Scranton',
            'city' => 'Scranton',
            'state' => 'Pennsylvania',
            'pincode' => '18501',
            'country' => 'USA',
            'slug' => 'michael-scott-slug',
        ]);

        $this->product = Product::create([
            'name' => '100g 24K Gold Bar',
            'slug' => '100g-24k-gold-bar',
            'sku' => 'GB24K100G',
            'gold_type' => '24K',
            'weight_in_grams' => 100.00,
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

        // Create 2-month plan for quick test completion
        $this->plan = EmiPlan::create([
            'plan_name' => '2 Month Gold Plan',
            'plan_code' => 'ACC2M',
            'duration_months' => 2,
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
            'late_fee' => 200.00,
            'late_fee_type' => 'fixed',
            'status' => 'active',
        ]);

        $this->actingAs($this->customer);
    }

    /**
     * Test validation checks prevent delivery requests for incomplete/unpaid bookings
     */
    public function test_prevents_delivery_request_for_unpaid_or_incomplete_bookings(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        // Current status: Active (since first EMI was paid automatically). Not Completed.
        // Also has remaining unpaid schedule #2.
        $this->expectException(\Exception::class);
        $this->deliveryService->requestDelivery($booking, [
            'delivery_method' => 'Office Pickup'
        ]);
    }

    /**
     * Test delivery request creation on a completed, fully paid booking
     */
    public function test_delivery_request_creation(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        // Pay remaining installment #2
        $schedules = BookingEmiSchedule::where('booking_id', $booking->id)->get();
        $secondEmi = $schedules->firstWhere('installment_number', 2);
        
        $this->paymentService->collectPayment($booking, $secondEmi, [
            'payment_mode' => 'UPI',
            'transaction_reference' => 'TXN-FINAL'
        ]);

        // Manually complete booking status
        $this->bookingService->changeStatus($booking, 'Completed', 'Plan completed.');

        // Request delivery
        $delivery = $this->deliveryService->requestDelivery($booking, [
            'delivery_method' => 'Office Pickup',
            'remarks' => 'Test pickup'
        ]);

        $this->assertNotNull($delivery);
        $this->assertEquals('Requested', $delivery->delivery_status);
        $this->assertMatchesRegularExpression('/^DEL\d{9}$/', $delivery->delivery_number);

        // Verify status history creation
        $this->assertDatabaseHas('delivery_status_histories', [
            'delivery_id' => $delivery->id,
            'new_status' => 'Requested'
        ]);
    }

    /**
     * Test Office Pickup verification flow with OTP
     */
    public function test_office_pickup_workflow(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        // Pay EMI #2 & Complete Booking
        $secondEmi = BookingEmiSchedule::where('booking_id', $booking->id)->where('installment_number', 2)->first();
        $this->paymentService->collectPayment($booking, $secondEmi, ['payment_mode' => 'Cash']);
        $this->bookingService->changeStatus($booking, 'Completed', 'Done.');

        // Request Office Pickup
        $delivery = $this->deliveryService->requestDelivery($booking, [
            'delivery_method' => 'Office Pickup'
        ]);

        // Approve delivery
        $this->deliveryService->approveDelivery($delivery);
        $delivery->refresh();

        $this->assertEquals('Approved', $delivery->delivery_status);
        $this->assertNotNull($delivery->otp);
        $this->assertNotNull($delivery->pdf_path);
        Storage::disk('public')->assertExists($delivery->pdf_path);

        // Complete delivery with incorrect OTP should fail
        try {
            $this->deliveryService->completeDelivery($delivery, [
                'otp' => '000000',
                'receiver_name' => 'Michael Scott'
            ]);
            $this->fail("Completed pickup with incorrect OTP did not throw exception.");
        } catch (\Exception $e) {
            $this->assertEquals("Invalid OTP code supplied.", $e->getMessage());
        }

        // Complete with correct OTP
        $this->deliveryService->completeDelivery($delivery, [
            'otp' => $delivery->otp,
            'receiver_name' => 'Michael Scott',
            'receiver_mobile' => '9998887776',
            'receiver_id_proof' => 'Aadhar Ref'
        ]);

        $delivery->refresh();
        $this->assertEquals('Delivered', $delivery->delivery_status);
        $this->assertNotNull($delivery->otp_verified_at);

        // Verify status histories
        $this->assertDatabaseHas('delivery_status_histories', [
            'delivery_id' => $delivery->id,
            'new_status' => 'Delivered'
        ]);
    }

    /**
     * Test Courier dispatch workflow
     */
    public function test_courier_delivery_workflow(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        // Pay EMI #2 & Complete
        $secondEmi = BookingEmiSchedule::where('booking_id', $booking->id)->where('installment_number', 2)->first();
        $this->paymentService->collectPayment($booking, $secondEmi, ['payment_mode' => 'UPI']);
        $this->bookingService->changeStatus($booking, 'Completed', 'Done.');

        // Request Courier
        $delivery = $this->deliveryService->requestDelivery($booking, [
            'delivery_method' => 'Courier',
            'delivery_address' => '123 Dunder Mifflin Way'
        ]);

        // Approve
        $this->deliveryService->approveDelivery($delivery);

        // Dispatch
        $this->deliveryService->dispatchDelivery($delivery, [
            'courier_partner' => 'FedEx',
            'tracking_number' => 'FEDEX999',
            'tracking_url' => 'https://fedex.com'
        ]);

        $delivery->refresh();
        $this->assertEquals('Dispatched', $delivery->delivery_status);
        $this->assertEquals('FedEx', $delivery->courier_partner);
        $this->assertEquals('FEDEX999', $delivery->tracking_number);

        // Complete delivery
        $this->deliveryService->completeDelivery($delivery);
        $delivery->refresh();
        $this->assertEquals('Delivered', $delivery->delivery_status);

        // Audit check
        $this->assertDatabaseHas('activity_logs', [
            'record_id' => $booking->id,
            'action_type' => 'delivery_completed'
        ]);
    }
}
