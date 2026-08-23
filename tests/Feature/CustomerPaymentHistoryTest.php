<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\EmiPlan;
use App\Models\GoldBooking;
use App\Models\PaymentTransaction;
use App\Models\BookingPayment;
use App\Models\GstInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPaymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected $customer1;
    protected $customer2;
    protected $booking1;
    protected $booking2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        $customerRole = Role::where('slug', 'customer')->first();

        // Create a product
        $product = Product::create([
            'name' => '10g 24K Gold Coin',
            'slug' => '10g-24k-gold-coin-' . uniqid(),
            'sku' => 'GC24K10G_' . uniqid(),
            'gold_type' => '24K',
            'weight_in_grams' => 10.00,
            'purity' => 999.99,
            'category' => 'coins',
            'status' => 'active',
        ]);

        // Create an EmiPlan
        $plan = EmiPlan::create([
            'plan_name' => '10 Months Gold Accumulator',
            'plan_code' => 'GOLD10M_' . uniqid(),
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
            'cancellation_charge_percent' => 5.00,
            'status' => 'active',
            'is_default' => false,
            'late_fee_type' => 'fixed',
            'late_fee' => 100.00,
            'grace_days' => 5,
            'auto_terminate_after_missed_emi' => 3,
            'maintenance_deduction_percent' => 0.00,
        ]);

        // Customer 1
        $this->customer1 = User::create([
            'name' => 'John Customer',
            'email' => 'john.payments@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);
        $this->customer1->customerDetail()->create([
            'slug' => 'john-payments',
        ]);

        // Customer 2
        $this->customer2 = User::create([
            'name' => 'Sarah Customer',
            'email' => 'sarah.payments@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);
        $this->customer2->customerDetail()->create([
            'slug' => 'sarah-payments',
        ]);

        // Bookings
        $this->booking1 = GoldBooking::create([
            'booking_number' => 'AG26000001',
            'customer_id' => $this->customer1->id,
            'product_id' => $product->id,
            'emi_plan_id' => $plan->id,
            'gold_weight' => 5.00,
            'gold_purity' => 99.9,
            'gold_type' => '24K',
            'locked_price_per_gram' => 6000.00,
            'locked_gold_value' => 30000.00,
            'gst_on_gold_amount' => 900.00,
            'grand_total' => 30900.00,
            'monthly_emi' => 3090.00,
            'duration_months' => 10,
            'booking_date' => now()->subDays(5),
            'status' => 'Active',
            'estimated_completion_date' => now()->addMonths(12),
        ]);

        $this->booking2 = GoldBooking::create([
            'booking_number' => 'AG26000002',
            'customer_id' => $this->customer2->id,
            'product_id' => $product->id,
            'emi_plan_id' => $plan->id,
            'gold_weight' => 5.00,
            'gold_purity' => 99.9,
            'gold_type' => '24K',
            'locked_price_per_gram' => 6000.00,
            'locked_gold_value' => 30000.00,
            'gst_on_gold_amount' => 900.00,
            'grand_total' => 30900.00,
            'monthly_emi' => 3090.00,
            'duration_months' => 10,
            'booking_date' => now()->subDays(3),
            'status' => 'Active',
            'estimated_completion_date' => now()->addMonths(12),
        ]);

        // Transactions for Customer 1
        $txn1 = PaymentTransaction::create([
            'transaction_number' => 'TXN10001',
            'booking_id' => $this->booking1->id,
            'customer_id' => $this->customer1->id,
            'payment_type' => 'booking',
            'gateway' => 'cashfree',
            'gateway_order_id' => 'CF10001',
            'amount' => 5000.00,
            'payment_status' => 'Success',
            'paid_at' => now()->subDays(4),
        ]);
        \Illuminate\Support\Facades\DB::table('payment_transactions')
            ->where('id', $txn1->id)
            ->update(['created_at' => now()->subDays(4)]);

        $txn2 = PaymentTransaction::create([
            'transaction_number' => 'TXN10002',
            'booking_id' => $this->booking1->id,
            'customer_id' => $this->customer1->id,
            'payment_type' => 'emi',
            'gateway' => 'cashfree',
            'gateway_order_id' => 'CF10002',
            'amount' => 2000.00,
            'payment_status' => 'Failed',
        ]);
        \Illuminate\Support\Facades\DB::table('payment_transactions')
            ->where('id', $txn2->id)
            ->update(['created_at' => now()->subDays(1)]);

        // Transaction for Customer 2
        $txn3 = PaymentTransaction::create([
            'transaction_number' => 'TXN20001',
            'booking_id' => $this->booking2->id,
            'customer_id' => $this->customer2->id,
            'payment_type' => 'booking',
            'gateway' => 'cashfree',
            'gateway_order_id' => 'CF20001',
            'amount' => 8000.00,
            'payment_status' => 'Success',
            'paid_at' => now()->subDays(2),
        ]);
        \Illuminate\Support\Facades\DB::table('payment_transactions')
            ->where('id', $txn3->id)
            ->update(['created_at' => now()->subDays(2)]);
    }

    /**
     * Test payment listing is correct and restricted to customer's own payments
     */
    public function test_customer_can_only_view_own_payments()
    {
        $this->actingAs($this->customer1);

        $response = $this->get(route('customer.payments.index'));

        $response->assertStatus(200);
        $response->assertSee('TXN10001');
        $response->assertSee('TXN10002');
        $response->assertSee('AG26000001');
        
        // Cannot see customer 2's transactions/bookings
        $response->assertDontSee('TXN20001');
        $response->assertDontSee('AG26000002');
    }

    /**
     * Test booking ID filter only allows customer's own bookings and filters correctly
     */
    public function test_booking_id_filter_restrictions_and_correctness()
    {
        $this->actingAs($this->customer1);

        // Filter by booking1 (valid)
        $response = $this->get(route('customer.payments.index', [
            'booking_id' => $this->booking1->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('TXN10001');
        $response->assertSee('TXN10002');

        // Filter by booking2 (which belongs to customer 2)
        // Since server-side filters on booking_id but customer_id is fixed to logged-in user,
        // it will try to find transactions matching booking2 AND customer1, which returns 0 results
        $response2 = $this->get(route('customer.payments.index', [
            'booking_id' => $this->booking2->id,
        ]));

        $response2->assertStatus(200);
        $response2->assertDontSee('TXN10001');
        $response2->assertDontSee('TXN10002');
        $response2->assertDontSee('TXN20001');
    }

    /**
     * Test date range filter working in Asia/Kolkata timezone
     */
    public function test_date_range_filter_works_with_kolkata_timezone()
    {
        $this->actingAs($this->customer1);

        // Filter for transactions within the last 5 to 3 days (matches TXN10001 only)
        $fromDate = now()->subDays(5)->format('Y-m-d');
        $toDate = now()->subDays(3)->format('Y-m-d');

        $response = $this->get(route('customer.payments.index', [
            'start_date' => $fromDate,
            'end_date' => $toDate,
        ]));

        $response->assertStatus(200);
        $response->assertSee('TXN10001');
        $response->assertDontSee('TXN10002'); // created 1 day ago, outside range
    }

    /**
     * Test combination of filters and reset
     */
    public function test_filters_combination_and_reset()
    {
        $this->actingAs($this->customer1);

        // Apply both booking filter and date range filter (matches TXN10001)
        $fromDate = now()->subDays(5)->format('Y-m-d');
        $toDate = now()->subDays(3)->format('Y-m-d');

        $response = $this->get(route('customer.payments.index', [
            'booking_id' => $this->booking1->id,
            'start_date' => $fromDate,
            'end_date' => $toDate,
        ]));

        $response->assertStatus(200);
        $response->assertSee('TXN10001');
        $response->assertDontSee('TXN10002');

        // Reset: requesting without query parameters returns all
        $responseReset = $this->get(route('customer.payments.index'));
        $responseReset->assertStatus(200);
        $responseReset->assertSee('TXN10001');
        $responseReset->assertSee('TXN10002');
    }

    /**
     * Test certificate and invoice index displays correct booking numbers
     */
    public function test_certificates_page_shows_booking_id()
    {
        $this->actingAs($this->customer1);

        // Create a certificate for booking1
        $this->booking1->certificate()->create([
            'certificate_number' => 'PLC10001',
            'qr_code' => 'qr1.png',
            'customer_id' => $this->customer1->id,
            'issued_at' => now(),
            'locked_price' => 6000.00,
            'gold_weight' => 5.00,
            'grand_total' => 30900.00,
            'verification_token' => 'token123',
        ]);

        // Create a payment and an invoice for booking1
        $payment = BookingPayment::create([
            'payment_number' => 'PAY10001',
            'receipt_number' => 'REC10001',
            'booking_id' => $this->booking1->id,
            'customer_id' => $this->customer1->id,
            'payment_mode' => 'Cash',
            'amount_paid' => 5000.00,
            'principal_paid' => 4500.00,
            'interest_paid' => 0.00,
            'late_fee_paid' => 0.00,
            'gst_paid' => 500.00,
            'payment_date' => now(),
            'status' => 'Paid',
        ]);

        GstInvoice::create([
            'invoice_number' => 'INV10001',
            'booking_id' => $this->booking1->id,
            'payment_id' => $payment->id,
            'customer_id' => $this->customer1->id,
            'invoice_date' => now(),
            'customer_name' => $this->customer1->name,
            'customer_email' => $this->customer1->email,
            'customer_phone' => '9999999999',
            'billing_address' => 'Test Address',
            'product_name' => '10g 24K Gold Coin',
            'gold_weight' => 5.00,
            'gold_purity' => 99.9,
            'locked_gold_price' => 6000.00,
            'gold_value' => 30000.00,
            'gst_on_gold_percent' => 3.00,
            'gst_on_gold_amount' => 900.00,
            'finance_charge' => 0.00,
            'storage_charge' => 0.00,
            'gst_on_charges_percent' => 18.00,
            'gst_on_charges_amount' => 0.00,
            'subtotal' => 30900.00,
            'grand_total' => 30900.00,
            'payment_received' => 3090.00,
            'balance_amount' => 27810.00,
            'cgst_percent' => 1.50,
            'cgst_amount' => 450.00,
            'sgst_percent' => 1.50,
            'sgst_amount' => 450.00,
            'igst_percent' => 0.00,
            'igst_amount' => 0.00,
            'invoice_status' => 'Paid',
            'remarks' => '',
            'pdf_path' => '',
            'verification_token' => 'token123',
            'qr_code' => '',
        ]);

        $response = $this->get(route('customer.certificates.index'));

        $response->assertStatus(200);
        $response->assertSee('Booking ID: AG26000001');
        $response->assertSee('PLC10001');
        $response->assertSee('INV10001');
    }
}
