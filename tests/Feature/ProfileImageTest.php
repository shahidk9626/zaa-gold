<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileImageTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $customerRole = Role::where('slug', 'customer')->first();

        // Create Admin
        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin.profile@example.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'status' => 'active',
        ]);

        // Create Customer
        $this->customer = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane.profile@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);
        $this->customer->customerDetail()->create([
            'slug' => 'jane-profile',
        ]);

        Storage::fake('public');
    }

    /**
     * Test admin can upload profile image
     */
    public function test_admin_can_upload_profile_image()
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this->post(route('profile.image.upload'), [
            'profile_image' => $file,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'profile-image-updated');

        $this->admin->refresh();
        $this->assertNotNull($this->admin->profile_image);
        
        // Assert file exists in storage
        Storage::disk('public')->assertExists($this->admin->profile_image);
        $this->assertStringContainsString('profile-images/users', $this->admin->profile_image);
    }

    /**
     * Test admin can remove profile image
     */
    public function test_admin_can_remove_profile_image()
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);
        $path = $file->store('profile-images/users', 'public');
        $this->admin->update(['profile_image' => $path]);

        Storage::disk('public')->assertExists($path);

        $response = $this->delete(route('profile.image.remove'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'profile-image-removed');

        $this->admin->refresh();
        $this->assertNull($this->admin->profile_image);
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Test customer can upload profile image
     */
    public function test_customer_can_upload_profile_image()
    {
        $this->actingAs($this->customer);

        $file = UploadedFile::fake()->image('avatar.png', 400, 400);

        $response = $this->post(route('customer.profile.image.upload'), [
            'profile_image' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Profile image updated successfully.');

        $this->customer->refresh();
        $this->assertNotNull($this->customer->profile_image);
        
        Storage::disk('public')->assertExists($this->customer->profile_image);
        $this->assertStringContainsString('profile-images/customers', $this->customer->profile_image);
    }

    /**
     * Test customer can remove profile image
     */
    public function test_customer_can_remove_profile_image()
    {
        $this->actingAs($this->customer);

        $file = UploadedFile::fake()->image('avatar.png', 400, 400);
        $path = $file->store('profile-images/customers', 'public');
        $this->customer->update(['profile_image' => $path]);

        Storage::disk('public')->assertExists($path);

        $response = $this->delete(route('customer.profile.image.remove'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Profile image removed successfully.');

        $this->customer->refresh();
        $this->assertNull($this->customer->profile_image);
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Test file validation rejects non-images and large files
     */
    public function test_validation_rejects_invalid_files()
    {
        $this->actingAs($this->admin);

        // Test 1: File size above 2MB (2048 KB)
        $largeFile = UploadedFile::fake()->create('large_image.jpg', 3000, 'image/jpeg'); // 3MB

        $response = $this->post(route('profile.image.upload'), [
            'profile_image' => $largeFile,
        ]);

        $response->assertSessionHasErrors('profile_image');
        $this->assertNull($this->admin->profile_image);

        // Test 2: Invalid extension/mime
        $textFile = UploadedFile::fake()->create('script.sh', 100, 'application/x-sh');

        $response2 = $this->post(route('profile.image.upload'), [
            'profile_image' => $textFile,
        ]);

        $response2->assertSessionHasErrors('profile_image');
        $this->assertNull($this->admin->profile_image);
    }

    /**
     * Test guests cannot modify profile images
     */
    public function test_guest_cannot_modify_profile_images()
    {
        // Guests trying to upload admin profile image
        $response1 = $this->post(route('profile.image.upload'), [
            'profile_image' => UploadedFile::fake()->image('avatar.jpg'),
        ]);
        $response1->assertRedirect(route('login'));

        // Guests trying to remove admin profile image
        $response2 = $this->delete(route('profile.image.remove'));
        $response2->assertRedirect(route('login'));

        // Guests trying to upload customer profile image
        $response3 = $this->post(route('customer.profile.image.upload'), [
            'profile_image' => UploadedFile::fake()->image('avatar.png'),
        ]);
        $response3->assertRedirect(route('login'));

        // Guests trying to remove customer profile image
        $response4 = $this->delete(route('customer.profile.image.remove'));
        $response4->assertRedirect(route('login'));
    }
}
