<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\EmiPlan;
use App\Models\Product;
use App\Models\GoldPrice;
use App\Models\User;
use App\Models\GoldBooking;
use App\Models\BookingPayment;
use App\Models\GstInvoice;
use App\Models\ActivityLog;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class GstInvoiceEngineTest extends TestCase
{
    use RefreshDatabase;

    protected $bookingService;
    protected $paymentService;
    protected $invoiceService;
    protected $customer;
    protected $product;
    protected $plan;
    protected $goldPrice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = app(BookingService::class);
        $this->paymentService = app(PaymentService::class);
        $this->invoiceService = app(InvoiceService::class);

        // Fake storage for testing PDFs
        Storage::fake('public');

        // Create base customer
        $this->customer = User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => bcrypt('password'),
            'role_id' => 4,
            'status' => 'active',
        ]);

        $this->customer->customerDetail()->create([
            'phone_number' => '9876543210',
            'address' => '123 Test Street, Mumbai',
            'city' => 'Mumbai',
            'state' => 'Maharashtra', // Matching company default Maharashtra
            'pincode' => '400001',
            'country' => 'India',
            'slug' => 'john-doe-slug',
        ]);

        $this->product = Product::create([
            'name' => '10g Gold Coin',
            'slug' => '10g-gold-coin',
            'sku' => 'GC24K10G',
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
            'plan_name' => '10 Month Tax Plan',
            'plan_code' => 'TAX10M',
            'duration_months' => 10,
            'minimum_booking_amount' => 1000.00,
            'maximum_booking_amount' => 100000.00,
            'minimum_gold_weight' => 1.00,
            'maximum_gold_weight' => 100.00,
            'processing_fee_type' => 'fixed',
            'processing_fee' => 0.00,
            'interest_type' => 'flat',
            'interest_rate' => 10.00,
            'gst_on_gold_enabled' => true,
            'gst_on_gold_percent' => 3.00, // 3% Gold GST
            'finance_charge_enabled' => true,
            'finance_charge_type' => 'fixed',
            'finance_charge_amount' => 1000.00, // Proportional finance charge
            'storage_charge_enabled' => false,
            'gst_on_charges_enabled' => true,
            'gst_on_charges_percent' => 18.00, // 18% Charges GST
            'rounding_type' => 'none',
            'late_fee' => 200.00,
            'late_fee_type' => 'fixed',
            'status' => 'active',
        ]);

        $this->actingAs($this->customer);
    }

    /**
     * Test booking creation triggers invoice auto-generation for EMI #1
     */
    public function test_booking_creation_automatically_generates_invoice(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        $payment = BookingPayment::where('booking_id', $booking->id)->first();
        $this->assertNotNull($payment);

        // Verify GST Invoice was generated automatically
        $invoice = GstInvoice::where('payment_id', $payment->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('Generated', $invoice->invoice_status);
        $this->assertMatchesRegularExpression('/^INV\d{9}$/', $invoice->invoice_number);

        // Verify snapshot values
        $this->assertEquals('John Doe', $invoice->customer_name);
        $this->assertEquals('10g Gold Coin', $invoice->product_name);
        $this->assertEquals(10.00, $invoice->gold_weight);
        $this->assertEquals(6000.00, $invoice->locked_gold_price);

        // Verify PDF and QR code storage files exist
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('public')->assertExists($invoice->pdf_path);
        $this->assertNotNull($invoice->qr_code);
        Storage::disk('public')->assertExists($invoice->qr_code);

        // Verify activity log
        $this->assertDatabaseHas('activity_logs', [
            'record_id' => $booking->id,
            'action_type' => 'invoice_generated'
        ]);
    }

    /**
     * Test Intra-State split: Maharashtra company vs. Maharashtra customer
     */
    public function test_intra_state_billing_splits_cgst_and_sgst(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        $invoice = GstInvoice::where('booking_id', $booking->id)->first();
        $this->assertNotNull($invoice);

        // Tax should be split between CGST and SGST
        $this->assertGreaterThan(0.00, $invoice->cgst_amount);
        $this->assertGreaterThan(0.00, $invoice->sgst_amount);
        $this->assertEquals(0.00, $invoice->igst_amount);
        
        $totalGst = $invoice->cgst_amount + $invoice->sgst_amount;
        $this->assertEquals($invoice->gst_on_gold_amount + $invoice->gst_on_charges_amount, $totalGst);
    }

    /**
     * Test Inter-State assignment: Maharashtra company vs. Delhi customer
     */
    public function test_inter_state_billing_assigns_to_igst(): void
    {
        // Update customer state to Delhi
        $this->customer->customerDetail->state = 'Delhi';
        $this->customer->customerDetail->save();

        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        $invoice = GstInvoice::where('booking_id', $booking->id)->first();
        $this->assertNotNull($invoice);

        // Tax should go to IGST only
        $this->assertEquals(0.00, $invoice->cgst_amount);
        $this->assertEquals(0.00, $invoice->sgst_amount);
        $this->assertGreaterThan(0.00, $invoice->igst_amount);
        
        $totalGst = $invoice->gst_on_gold_amount + $invoice->gst_on_charges_amount;
        $this->assertEquals($totalGst, $invoice->igst_amount);
    }

    /**
     * Test Invoice Immutability: changing customer details does not alter invoice
     */
    public function test_invoice_is_immutable_regardless_of_customer_updates(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        $invoice = GstInvoice::where('booking_id', $booking->id)->first();
        $originalName = $invoice->customer_name;

        // Change customer name and email
        $this->customer->name = 'Jane Doe Modified';
        $this->customer->email = 'jane.modified@example.com';
        $this->customer->save();

        // Refresh and check invoice snapshot did NOT change
        $invoice->refresh();
        $this->assertEquals($originalName, $invoice->customer_name);
        $this->assertEquals('John Doe', $invoice->customer_name);
    }

    /**
     * Test duplicate invoice prevention for same payment
     */
    public function test_prevents_duplicate_invoices_for_same_payment(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        $payment = BookingPayment::where('booking_id', $booking->id)->first();

        // Attempting to generate a second invoice should throw exception
        $this->expectException(\Exception::class);
        $this->invoiceService->generateInvoice($payment);
    }

    /**
     * Test invoice cancellation and audit trails
     */
    public function test_invoice_cancellation(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        $invoice = GstInvoice::where('booking_id', $booking->id)->first();
        $this->assertEquals('Generated', $invoice->invoice_status);

        // Cancel
        $this->invoiceService->cancelInvoice($invoice, 'Customer returned gold');

        $invoice->refresh();
        $this->assertEquals('Cancelled', $invoice->invoice_status);
        $this->assertEquals('Customer returned gold', $invoice->remarks);

        // Verify cancelled activity log
        $this->assertDatabaseHas('activity_logs', [
            'record_id' => $booking->id,
            'action_type' => 'invoice_cancelled'
        ]);
    }
}
