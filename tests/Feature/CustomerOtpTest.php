<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\EmailOtp;
use App\Mail\CustomerOtpMail;
use App\Mail\WelcomeCustomerMail;
use App\Services\OtpService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;

class CustomerOtpTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $staff;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        // Fetch seeded roles
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $staffRole = Role::where('slug', 'staff')->first();
        $customerRole = Role::where('slug', 'customer')->first();

        // Admin User (already created by seeder, but let's fetch it)
        $this->admin = User::where('email', 'admin@zaagold.com')->first();

        // Create a staff user
        $this->staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@zaagold.com',
            'password' => bcrypt('password'),
            'role_id' => $staffRole->id,
            'status' => 'active',
        ]);

        // Create an unverified customer
        $this->customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@zaagold.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'inactive',
        ]);
        
        $slug = \Illuminate\Support\Str::slug($this->customer->name . '-' . \Illuminate\Support\Str::random(5));
        \App\Models\CustomerDetail::create([
            'user_id' => $this->customer->id,
            'slug' => $slug,
        ]);
    }

    /**
     * Test registration dispatches OTP and redirects to verify view.
     */
    public function test_customer_registration_sends_otp_and_redirects(): void
    {
        Mail::fake();

        $registerData = [
            'name' => 'New Customer',
            'email' => 'newcustomer@zaagold.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ];

        $response = $this->post('/register', $registerData);

        $response->assertRedirect(route('customer.verify-email-view'));
        $this->assertEquals('newcustomer@zaagold.com', session('verify_email'));

        // Verify OTP is generated in DB
        $this->assertDatabaseHas('email_otps', [
            'email' => 'newcustomer@zaagold.com',
            'purpose' => 'registration',
        ]);

        // Verify OTP email is queued
        Mail::assertQueued(CustomerOtpMail::class, function ($mail) {
            return $mail->hasTo('newcustomer@zaagold.com') && $mail->purpose === 'registration';
        });
    }

    /**
     * Test login block for unverified customer.
     */
    public function test_unverified_customer_cannot_login(): void
    {
        $response = $this->post('/login', [
            'email' => 'customer@zaagold.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('customer.verify-email-view'));
        $this->assertEquals('customer@zaagold.com', session('verify_email'));
        $this->assertFalse(Auth::check());
    }

    /**
     * Test successful OTP verification logs the customer in and sends welcome mail.
     */
    public function test_successful_registration_otp_verification(): void
    {
        Mail::fake();

        // Generate OTP
        $otpService = app(OtpService::class);
        $result = $otpService->generateAndSendOtp($this->customer, 'registration');
        $rawOtp = $result['otp'];

        // Set session
        session(['verify_email' => 'customer@zaagold.com']);

        $response = $this->post(route('customer.verify-email'), [
            'otp' => $rawOtp,
        ]);

        $response->assertRedirect(route('customer.dashboard'));

        // Verify customer status updated to active
        $this->customer->refresh();
        $this->assertEquals('active', $this->customer->status);
        $this->assertNotNull($this->customer->email_verified_at);

        // Verify logged in
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->customer->id, Auth::id());

        // Verify welcome email is sent (standard Mail welcome, queued)
        Mail::assertQueued(WelcomeCustomerMail::class, function ($mail) {
            return $mail->hasTo('customer@zaagold.com');
        });

        // Verify OTP record deleted
        $this->assertDatabaseMissing('email_otps', [
            'user_id' => $this->customer->id,
            'purpose' => 'registration',
        ]);
    }

    /**
     * Test verification attempts limit (5 attempts).
     */
    public function test_otp_verification_failed_attempts_throttle(): void
    {
        $otpService = app(OtpService::class);
        $otpService->generateAndSendOtp($this->customer, 'registration');

        session(['verify_email' => 'customer@zaagold.com']);

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('customer.verify-email'), [
                'otp' => '000000', // incorrect
            ]);
            $response->assertSessionHasErrors('otp');
        }

        // OTP should now be invalidated/deleted
        $this->assertDatabaseMissing('email_otps', [
            'user_id' => $this->customer->id,
            'purpose' => 'registration',
        ]);
    }

    /**
     * Test OTP expiry constraint.
     */
    public function test_expired_otp_is_invalid(): void
    {
        $otpService = app(OtpService::class);
        $otpService->generateAndSendOtp($this->customer, 'registration');

        // Move time forward by 11 minutes
        Carbon::setTestNow(Carbon::now()->addMinutes(11));

        session(['verify_email' => 'customer@zaagold.com']);

        $response = $this->post(route('customer.verify-email'), [
            'otp' => '123456',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertFalse(Auth::check());

        Carbon::setTestNow(); // Reset time
    }

    /**
     * Test resend throttling (60 seconds constraint).
     */
    public function test_resend_otp_throttling_and_previous_invalidation(): void
    {
        Mail::fake();

        $otpService = app(OtpService::class);
        $otpService->generateAndSendOtp($this->customer, 'registration');

        // First OTP generated
        $firstOtp = EmailOtp::where('user_id', $this->customer->id)->first();
        $firstOtpHash = $firstOtp->otp;

        session(['verify_email' => 'customer@zaagold.com']);

        // Try to resend immediately (should fail/throttle)
        $response = $this->post(route('customer.resend-email-otp'));
        $response->assertSessionHas('error');

        // Move time forward by 61 seconds
        Carbon::setTestNow(Carbon::now()->addSeconds(61));

        // Resend (should succeed)
        $response = $this->post(route('customer.resend-email-otp'));
        $response->assertSessionHas('status');

        // Verify first OTP was deleted/invalidated and a new one was created
        $this->assertDatabaseMissing('email_otps', [
            'otp' => $firstOtpHash,
        ]);
        $this->assertDatabaseHas('email_otps', [
            'user_id' => $this->customer->id,
            'purpose' => 'registration',
        ]);

        Carbon::setTestNow(); // Reset time
    }

    /**
     * Test Forgot Password OTP flow for Customer.
     */
    public function test_customer_forgot_password_sends_otp(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'customer@zaagold.com',
        ]);

        $response->assertRedirect(route('customer.verify-forgot-password-view'));
        $this->assertEquals('customer@zaagold.com', session('reset_password_email'));

        Mail::assertQueued(CustomerOtpMail::class, function ($mail) {
            return $mail->hasTo('customer@zaagold.com') && $mail->purpose === 'forgot_password';
        });
    }

    /**
     * Test Forgot Password reset updates password successfully.
     */
    public function test_customer_password_reset_flow_completed(): void
    {
        $otpService = app(OtpService::class);
        $result = $otpService->generateAndSendOtp($this->customer, 'forgot_password');
        $rawOtp = $result['otp'];

        session(['reset_password_email' => 'customer@zaagold.com']);

        // Verify OTP
        $response = $this->post(route('customer.verify-forgot-password'), [
            'otp' => $rawOtp,
        ]);

        $response->assertRedirect(route('customer.reset-password-view'));
        $this->assertTrue(session('reset_password_otp_verified'));

        // Reset Password
        $response = $this->post(route('customer.reset-password'), [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('login'));

        // Verify password hash updated
        $this->customer->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->customer->password));

        // Verify activity logs created
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'customer_otp',
            'action_type' => 'password_reset_completed',
            'record_id' => $this->customer->id,
        ]);
    }

    /**
     * Test Admin login and forgot password links are completely unaffected.
     */
    public function test_admin_login_and_forgot_password_are_unaffected(): void
    {
        Mail::fake();

        // 1. Admin login (should succeed without OTP check)
        $response = $this->post('/login', [
            'email' => 'admin@zaagold.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->admin->id, Auth::id());

        // Logout
        Auth::logout();

        // 2. Admin forgot password (should trigger default Breeze token reset)
        $response = $this->post('/forgot-password', [
            'email' => 'admin@zaagold.com',
        ]);

        // Standard Breeze password reset redirects back with status link sent
        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Confirm no OTP record was created for admin in email_otps
        $this->assertDatabaseMissing('email_otps', [
            'email' => 'admin@zaagold.com',
        ]);
    }

    /**
     * Test Staff login and forgot password links are completely unaffected.
     */
    public function test_staff_login_and_forgot_password_are_unaffected(): void
    {
        Mail::fake();

        // 1. Staff login (should succeed without OTP check)
        $response = $this->post('/login', [
            'email' => 'staff@zaagold.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->staff->id, Auth::id());

        // Logout
        Auth::logout();

        // 2. Staff forgot password (should trigger default Breeze token reset)
        $response = $this->post('/forgot-password', [
            'email' => 'staff@zaagold.com',
        ]);

        // Standard Breeze password reset redirects back with status link sent
        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Confirm no OTP record was created for staff in email_otps
        $this->assertDatabaseMissing('email_otps', [
            'email' => 'staff@zaagold.com',
        ]);
    }
}
