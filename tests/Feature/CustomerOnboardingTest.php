<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\EmiPlan;
use App\Models\Product;
use App\Models\GoldPrice;
use App\Models\GoldBooking;
use App\Models\BookingEmiSchedule;
use App\Models\Kyc;
use App\Models\ActivityLog;
use App\Services\CustomerOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;

class CustomerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected $onboardingService;
    protected $customer;
    protected $product;
    protected $plan;
    protected $goldPrice;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->onboardingService = app(CustomerOnboardingService::class);
        Storage::fake('public');

        // Seed Roles & Permissions
        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        // Retrieve seeded roles
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $customerRole = Role::where('slug', 'customer')->first();

        $this->customer = User::create([
            'name' => 'John Doe Onboarding',
            'email' => 'john.onboarding@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'inactive',
            'profile_completed' => 0,
        ]);

        $this->customer->customerDetail()->create([
            'slug' => 'john-doe-onboarding-slug',
        ]);

        $this->product = Product::create([
            'name' => '10g 24K Gold Bar',
            'slug' => '10g-24k-gold-bar',
            'sku' => 'GB24K10G',
            'gold_type' => '24K',
            'weight_in_grams' => 10.00,
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
            'maximum_booking_amount' => 100000.00,
            'minimum_gold_weight' => 1.00,
            'maximum_gold_weight' => 100.00,
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
     * Test browsing, purchasing, and EMI payment works without KYC
     */
    public function test_purchase_and_payments_allowed_without_kyc(): void
    {
        $this->actingAs($this->customer);

        // 1. Browse plans
        $response = $this->get(route('customer.plans.index'));
        $response->assertStatus(200);

        // 2. Booking service is allowed to purchase/create booking
        $booking = app(\App\Services\BookingService::class)->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        $this->assertNotNull($booking);
        $this->assertDatabaseHas('gold_bookings', [
            'id' => $booking->id,
            'customer_id' => $this->customer->id,
            'status' => 'Active',
        ]);

        // 3. Payment of EMI is allowed
        $firstEmi = BookingEmiSchedule::where('booking_id', $booking->id)->where('installment_number', 2)->first();
        $payment = app(\App\Services\PaymentService::class)->collectPayment($booking, $firstEmi, [
            'payment_mode' => 'UPI',
            'transaction_reference' => 'TXN999999',
            'remarks' => 'Test EMI Payment',
            'payment_date' => now(),
        ]);

        $this->assertNotNull($payment);
        $this->assertDatabaseHas('booking_payments', [
            'id' => $payment->id,
            'status' => 'Paid',
        ]);
    }

    /**
     * Test profile completion status transitions
     */
    public function test_profile_completion_updates_fields_and_status(): void
    {
        $this->actingAs($this->customer);

        $this->assertFalse($this->onboardingService->isProfileComplete($this->customer));

        $data = [
            'user' => [
                'phone' => '1122334455',
                'whatsapp_number' => '1122334455',
            ],
            'detail' => [
                'father_name' => 'Father Doe',
                'nominee_name' => 'Nominee Doe',
                'address' => '456 Test Lane',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'pincode' => '400001',
                'occupation' => 'Business',
                'pan_number' => 'ABCDE1234F',
                'aadhar_number' => '123456789012',
                'dob' => '1995-12-10',
                'gender' => 'Male',
                'bank_name' => 'State Bank',
                'account_number' => '9876543210',
                'ifsc_code' => 'SBIN0001234',
                'branch' => 'BKC',
                'emergency_contact' => '9988776655',
            ]
        ];

        $this->onboardingService->completeProfile($this->customer, $data);

        $this->customer->refresh();
        $this->assertTrue($this->onboardingService->isProfileComplete($this->customer));

        // Verify activity log is written
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'customer_onboarding',
            'action_type' => 'profile_completed',
            'record_id' => $this->customer->id,
        ]);
    }

    /**
     * Test KYC submission flow and states
     */
    public function test_kyc_submission_transitions_status(): void
    {
        $this->actingAs($this->customer);

        // Ensure profile complete first
        $this->customer->update(['profile_completed' => 1]);

        $this->assertEquals('Draft', $this->onboardingService->getKycStatus($this->customer));
        $this->assertFalse($this->onboardingService->isKycApproved($this->customer));

        $files = [
            'pan_card' => UploadedFile::fake()->image('pan.jpg'),
            'front_image' => UploadedFile::fake()->image('aadhar_front.jpg'),
            'back_image' => UploadedFile::fake()->image('aadhar_back.jpg'),
            'selfie' => UploadedFile::fake()->image('photo.jpg'),
            'signature' => UploadedFile::fake()->image('sig.jpg'),
        ];

        $kyc = $this->onboardingService->submitKyc($this->customer, $files);

        $this->assertNotNull($kyc);
        $this->assertEquals('Pending Review', $this->onboardingService->getKycStatus($this->customer));
        
        // Verify files in storage
        Storage::disk('public')->assertExists($kyc->pan_card);
        Storage::disk('public')->assertExists($kyc->front_image);
        Storage::disk('public')->assertExists($kyc->back_image);
        Storage::disk('public')->assertExists($kyc->selfie);
        Storage::disk('public')->assertExists($kyc->signature);

        // Check activity log
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'customer_onboarding',
            'action_type' => 'kyc_submitted',
        ]);
    }

    /**
     * Test Delivery Request restriction
     */
    public function test_delivery_request_restricted_until_kyc_approved(): void
    {
        $this->actingAs($this->customer);

        // Create booking
        $booking = app(\App\Services\BookingService::class)->createBooking(
            $this->customer->id,
            $this->product->id,
            $this->plan->id
        );

        // Profile Incomplete, KYC not approved: cannot request delivery
        $this->assertFalse($this->onboardingService->canRequestDelivery($this->customer));

        $response = $this->post(route('customer.deliveries.store_request', $booking->id), [
            'delivery_method' => 'Office Pickup',
        ]);
        $response->assertSessionHas('error');

        // Complete profile, but KYC still not approved
        $this->customer->update(['profile_completed' => 1]);
        $this->assertFalse($this->onboardingService->canRequestDelivery($this->customer));

        $response = $this->post(route('customer.deliveries.store_request', $booking->id), [
            'delivery_method' => 'Office Pickup',
        ]);
        $response->assertSessionHas('error');

        // Approve KYC
        $kyc = Kyc::create([
            'user_id' => $this->customer->id,
            'front_image' => 'front.jpg',
            'document_type' => 'Aadhaar',
            'document_number' => '1234',
            'status' => 'approved',
        ]);

        $this->assertTrue($this->onboardingService->canRequestDelivery($this->customer));

        // Submit delivery request now succeeds
        $response = $this->post(route('customer.deliveries.store_request', $booking->id), [
            'delivery_method' => 'Office Pickup',
        ]);
        $response->assertSessionHasNoErrors();
    }

    /**
     * Test admin actions: Approve, Reject, Resubmit
     */
    public function test_admin_kyc_actions(): void
    {
        // 1. Submit KYC as customer
        $this->actingAs($this->customer);
        $this->customer->update(['profile_completed' => 1]);
        $files = [
            'pan_card' => UploadedFile::fake()->image('pan.jpg'),
            'front_image' => UploadedFile::fake()->image('aadhar_front.jpg'),
            'back_image' => UploadedFile::fake()->image('aadhar_back.jpg'),
            'selfie' => UploadedFile::fake()->image('photo.jpg'),
            'signature' => UploadedFile::fake()->image('sig.jpg'),
        ];
        $kyc = $this->onboardingService->submitKyc($this->customer, $files);

        // 2. Request Resubmission as Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', 'super-admin')->first()->id,
        ]);
        $this->actingAs($admin);

        $response = $this->post(route('kyc.reject', $kyc->id), [
            'rejected_reason' => 'Aadhaar image blurry',
            'action_type' => 'resubmission_required',
        ]);
        $response->assertJson(['success' => 'KYC resubmission requested successfully.']);

        $kyc->refresh();
        $this->assertEquals('resubmission_required', $kyc->status);
        $this->assertEquals('Resubmission Required', $this->onboardingService->getKycStatus($this->customer));

        // 3. Resubmit as customer
        $this->actingAs($this->customer);
        $resubmitFiles = [
            'front_image' => UploadedFile::fake()->image('new_aadhar_front.jpg'),
        ];
        $this->onboardingService->submitKyc($this->customer, $resubmitFiles);

        $kyc->refresh();
        $this->assertEquals('pending', $kyc->status);
        $this->assertEquals('Pending Review', $this->onboardingService->getKycStatus($this->customer));

        // 4. Approve as admin
        $this->actingAs($admin);
        $response = $this->post(route('kyc.approve', $kyc->id));
        $response->assertJson(['success' => 'KYC application has been approved successfully.']);

        $kyc->refresh();
        $this->assertEquals('approved', $kyc->status);
        $this->assertEquals('Approved', $this->onboardingService->getKycStatus($this->customer));
    }

    /**
     * Test that a customer created/verified directly via Admin Panel is automatically considered KYC-approved.
     */
    public function test_admin_created_customer_is_automatically_verified(): void
    {
        $adminRole = Role::where('slug', 'super-admin')->first();
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.verify@example.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
        ]);

        $this->actingAs($admin);

        // Simulate creating a customer from the admin panel
        $customerRole = Role::where('slug', 'customer')->first();
        $newCustomer = User::create([
            'name' => 'Admin Created Customer',
            'email' => 'admin.created@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'active',
            'profile_completed' => 1,
            'verification_status' => 'verified',
        ]);

        $this->assertTrue($this->onboardingService->isProfileComplete($newCustomer));
        $this->assertTrue($this->onboardingService->isKycApproved($newCustomer));
        $this->assertEquals('Approved', $this->onboardingService->getKycStatus($newCustomer));
        $this->assertTrue($this->onboardingService->canRequestDelivery($newCustomer));
    }
}
