<?php

namespace Tests\Feature;

use App\Models\EmiPlan;
use App\Models\GoldBooking;
use App\Models\BookingPayment;
use App\Models\BookingEmiSchedule;
use App\Models\CancellationRequest;
use App\Models\GstInvoice;
use App\Models\Product;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $booking;
    protected $payment;
    protected $invoice;
    protected $cancellation;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Role & Admin
        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
        ]);
        
        $customerRole = Role::create([
            'name' => 'Customer',
            'slug' => 'customer',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Staff',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role_id' => $adminRole->id,
        ]);

        $this->customer = User::create([
            'name' => 'John Customer',
            'email' => 'john@test.com',
            'password' => bcrypt('password123'),
            'role_id' => $customerRole->id,
        ]);

        // 2. Create Product & Plan
        $product = Product::create([
            'name' => '10g Gold Coin',
            'slug' => '10g-gold-coin',
            'sku' => 'G-COIN-10G',
            'weight_in_grams' => 10.00,
            'purity' => 999.99,
            'category' => 'coins',
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
            'cancellation_charge_percent' => 5.00,
            'status' => 'active',
            'is_default' => false,
            'late_fee_type' => 'fixed',
            'late_fee' => 100.00,
            'grace_days' => 5,
            'auto_terminate_after_missed_emi' => 3,
            'maintenance_deduction_percent' => 0.00,
        ]);

        // 3. Create Booking
        $this->booking = GoldBooking::create([
            'booking_number' => 'ZG2600001',
            'customer_id' => $this->customer->id,
            'product_id' => $product->id,
            'emi_plan_id' => $plan->id,
            'gold_weight' => 5.00,
            'gold_purity' => 999.99,
            'gold_type' => '24K',
            'locked_price_per_gram' => 6000.00,
            'locked_gold_value' => 30000.00,
            'gst_on_gold_percent' => 3.00,
            'gst_on_gold_amount' => 900.00,
            'grand_total' => 30900.00,
            'monthly_emi' => 3090.00,
            'duration_months' => 10,
            'status' => 'Active',
            'booking_date' => now(),
            'estimated_completion_date' => now()->addMonths(10),
        ]);

        // 4. Create EMI Schedule & Payment
        $schedule = BookingEmiSchedule::create([
            'booking_id' => $this->booking->id,
            'installment_number' => 1,
            'due_date' => now()->addMonth(),
            'opening_principal' => 30000.00,
            'principal_amount' => 3000.00,
            'interest_amount' => 90.00,
            'emi_amount' => 3090.00,
            'closing_principal' => 27000.00,
            'outstanding_balance' => 27810.00,
            'status' => 'Paid',
        ]);

        $this->payment = BookingPayment::create([
            'payment_number' => 'PAY260000001',
            'receipt_number' => 'RCP260000001',
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'emi_schedule_id' => $schedule->id,
            'amount_paid' => 3090.00,
            'principal_paid' => 3000.00,
            'interest_paid' => 90.00,
            'late_fee_paid' => 0.00,
            'gst_paid' => 90.00,
            'payment_date' => now(),
            'payment_mode' => 'Online Gateway',
            'transaction_reference' => 'CFPAY12345',
            'status' => 'Paid',
        ]);

        // 5. Create GST Invoice
        $this->invoice = GstInvoice::create([
            'invoice_number' => 'INV260000001',
            'booking_id' => $this->booking->id,
            'payment_id' => $this->payment->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_email' => $this->customer->email,
            'customer_phone' => '9999999999',
            'billing_address' => '123 Test St',
            'shipping_address' => '123 Test St',
            'product_name' => '10g Gold Coin',
            'product_sku' => 'G-COIN-10G',
            'gold_weight' => 5.00,
            'gold_purity' => 999.99,
            'gold_type' => '24K',
            'locked_gold_price' => 6000.00,
            'invoice_date' => now(),
            'gold_value' => 30000.00,
            'gst_on_gold_percent' => 3.00,
            'gst_on_gold_amount' => 900.00,
            'finance_charge' => 0.00,
            'storage_charge' => 0.00,
            'gst_on_charges_percent' => 18.00,
            'gst_on_charges_amount' => 0.00,
            'subtotal' => 30000.00,
            'grand_total' => 30900.00,
            'payment_received' => 30900.00,
            'balance_amount' => 0.00,
            'cgst_percent' => 1.5,
            'cgst_amount' => 450.00,
            'sgst_percent' => 1.5,
            'sgst_amount' => 450.00,
            'igst_percent' => 0.00,
            'igst_amount' => 0.00,
            'invoice_status' => 'Generated',
            'verification_token' => 'mock-token',
        ]);
        
        // Mock permission helper if it is used (since actingAs bypasses default authorization blocks depending on gate setup, let's verify if reports middleware works).
        // Let's assume standard admin permission checks pass as role_id matches an admin role.
    }

    /**
     * Test report dashboard sections load correctly.
     */
    public function test_financial_summary_dashboard_renders(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reports.dashboard', ['report' => 'financial_summary']));

        $response->assertStatus(200);
        $response->assertViewHas('financialStats');
        $response->assertSee('Financial Summary');
        $response->assertSee('Total Gold Value');
        $response->assertSee('Net Collection');
    }

    public function test_charge_breakdown_report_renders(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reports.dashboard', ['report' => 'charge_breakdown']));

        $response->assertStatus(200);
        $response->assertSee('ZG2600001');
        $response->assertSee('John Customer');
    }

    public function test_service_charge_report_renders(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reports.dashboard', ['report' => 'service_charge']));

        $response->assertStatus(200);
        $response->assertSee('ZG2600001');
    }

    public function test_gst_report_renders(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reports.dashboard', ['report' => 'gst_report']));

        $response->assertStatus(200);
        $response->assertSee('INV260000001');
        $response->assertSee('INV260000001');
    }

    public function test_payment_collection_report_renders(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reports.dashboard', ['report' => 'payment_collection']));

        $response->assertStatus(200);
        $response->assertSee('PAY260000001');
        $response->assertSee('CFPAY12345');
    }

    /**
     * Test Excel Export returns valid streaming workbook response.
     */
    public function test_excel_export_returns_streamed_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reports.export_excel', [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertMatchesRegularExpression('/attachment; filename="Financial_Reports_\d{14}\.xlsx"/', $disposition);
    }
}
