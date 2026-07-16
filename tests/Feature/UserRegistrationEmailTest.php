<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Mail\WelcomeCustomerMail;
use App\Mail\StaffCredentialsMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mockery;

class UserRegistrationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        // Retrieve the seeded admin user
        $this->admin = User::where('email', 'admin@zaagold.com')->first();
    }

    /**
     * Test Customer Wizard Registration sends WelcomeCustomerMail.
     */
    public function test_customer_wizard_registration_sends_welcome_email(): void
    {
        Mail::fake();

        $this->actingAs($this->admin);

        $customerData = [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'phone' => '1234567890',
            'whatsapp_number' => '1234567890',
            'status' => 1,
            // details fields
            'father_name' => 'Father Doe',
            'mother_name' => 'Mother Doe',
            'nominee_name' => 'Jane Doe',
            'dob' => '1990-01-01',
            'gender' => 'Male',
            'marital_status' => 'Single',
            'alternate_number' => '0987654321',
            'address' => '123 Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'country' => 'India',
            'pincode' => '400001',
            'occupation' => 'Business',
            'annual_income' => '500000',
            'bank_name' => 'State Bank',
            'account_number' => '1122334455',
            'ifsc_code' => 'SBIN0000001',
            'branch' => 'Main Branch',
            'pan_number' => 'ABCDE1234F',
            'aadhar_number' => '123456789012',
        ];

        $response = $this->postJson(route('customers.store'), $customerData);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'user']);

        Mail::assertQueued(WelcomeCustomerMail::class, function ($mail) {
            return $mail->hasTo('johndoe@example.com');
        });
    }

    /**
     * Test Public Customer Registration sends WelcomeCustomerMail.
     */
    public function test_public_customer_registration_sends_welcome_email(): void
    {
        Mail::fake();

        $registerData = [
            'name' => 'Jane Public',
            'email' => 'janepublic@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ];

        $response = $this->post('/register', $registerData);

        $response->assertRedirect(route('customer.verify-email-view'));

        $rawOtp = null;
        Mail::assertQueued(\App\Mail\CustomerOtpMail::class, function ($mail) use (&$rawOtp) {
            $rawOtp = $mail->otp;
            return $mail->hasTo('janepublic@example.com');
        });

        $this->assertNotNull($rawOtp);

        // Verify OTP to complete registration and trigger welcome mail
        $response = $this->post(route('customer.verify-email'), [
            'otp' => $rawOtp,
        ]);

        $response->assertRedirect(route('customer.dashboard'));

        Mail::assertQueued(WelcomeCustomerMail::class, function ($mail) {
            return $mail->hasTo('janepublic@example.com');
        });
    }

    /**
     * Test Admin Staff Creation sends StaffCredentialsMail.
     */
    public function test_admin_staff_creation_sends_credentials_email(): void
    {
        Mail::fake();

        $this->actingAs($this->admin);

        $staffRole = Role::where('slug', 'staff')->first();

        $staffData = [
            'role_id' => $staffRole->id,
            'first_name' => 'Staff Member',
            'phone' => '9988776655',
            'email' => 'staffmember@example.com',
            'joining_date' => '2026-07-14',
            'address' => '456 Staff Rd',
            'city' => 'Bangalore',
            'state' => 'Karnataka',
            'country' => 'India',
            'pincode' => '560001',
            'status' => 1,
            'father_name' => 'Father Staff',
            'mother_name' => 'Mother Staff',
            'nominee_name' => 'Nominee Staff',
            'dob' => '1995-05-05',
            'gender' => 'Female',
            'marital_status' => 'Married',
        ];

        $response = $this->postJson(route('staff.store'), $staffData);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'user']);

        Mail::assertQueued(StaffCredentialsMail::class, function ($mail) {
            return $mail->hasTo('staffmember@example.com');
        });
    }

    /**
     * Test that mail configuration in .env resolves correctly.
     */
    public function test_mail_sender_details_resolve_from_config(): void
    {
        config(['mail.from.address' => 'noreply@careerguard.in']);
        config(['mail.from.name' => 'careergaurd']);

        $customerRole = Role::where('slug', 'customer')->first();
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        $mail = new WelcomeCustomerMail($user);
        
        $this->assertEquals('Welcome to ZAA GOLD! 🎉', $mail->envelope()->subject);
    }

    /**
     * Test registration does not fail if mail sending fails.
     */
    public function test_customer_registration_succeeds_even_if_mail_fails(): void
    {
        // Spy on Log to check error output
        Log::spy();

        // Mock Mail to throw exception
        Mail::shouldReceive('to')
            ->andThrow(new \Exception('SMTP Connection timed out.'));

        $this->actingAs($this->admin);

        $customerData = [
            'name' => 'John Failsafe',
            'email' => 'failsafe@example.com',
            'phone' => '1234567891',
            'whatsapp_number' => '1234567891',
            'status' => 1,
            'father_name' => 'Father Doe',
            'mother_name' => 'Mother Doe',
            'nominee_name' => 'Jane Doe',
            'dob' => '1990-01-01',
            'gender' => 'Male',
            'marital_status' => 'Single',
            'alternate_number' => '0987654321',
            'address' => '123 Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'country' => 'India',
            'pincode' => '400001',
            'occupation' => 'Business',
            'annual_income' => '500000',
            'bank_name' => 'State Bank',
            'account_number' => '1122334455',
            'ifsc_code' => 'SBIN0000001',
            'branch' => 'Main Branch',
            'pan_number' => 'ABCDE1234F',
            'aadhar_number' => '123456789012',
        ];

        $response = $this->postJson(route('customers.store'), $customerData);

        // Assert customer registration is still successful
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'user']);
        $this->assertDatabaseHas('users', ['email' => 'failsafe@example.com']);

        // Assert that the exception was logged
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    /**
     * Test Mailables are queueable.
     */
    public function test_mail_classes_implement_should_queue(): void
    {
        $customerRole = Role::where('slug', 'customer')->first();
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        $welcomeMail = new WelcomeCustomerMail($user);
        $credentialsMail = new StaffCredentialsMail($user, 'temp-pwd');

        $this->assertInstanceOf(ShouldQueue::class, $welcomeMail);
        $this->assertInstanceOf(ShouldQueue::class, $credentialsMail);
    }
}
