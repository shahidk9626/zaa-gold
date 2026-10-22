<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $staff;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        $this->superAdmin = $this->makeUser('super-admin', 'root.owner@example.com', 'Current-pass1');
        $this->admin = $this->makeUser('admin', 'ops.admin@example.com', 'Admin-pass1');
        $this->staff = $this->makeUser('staff', 'desk.staff@example.com', 'Staff-pass1');
        $this->customer = $this->makeUser('customer', 'buyer@example.com', 'Customer-pass1');
    }

    protected function makeUser(string $roleSlug, string $email, string $password): User
    {
        return User::create([
            'name' => ucfirst($roleSlug) . ' User',
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => Role::where('slug', $roleSlug)->value('id'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    protected function snapshot(User $user): array
    {
        return $user->fresh()->only(['email', 'password', 'role_id', 'status', 'email_verified_at']);
    }

    public function test_super_admin_can_access_settings_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.super-admin-settings.index'))
            ->assertOk()
            ->assertSee('root.owner@example.com')
            ->assertDontSee($this->superAdmin->password);
    }

    public function test_settings_menu_is_visible_only_to_super_admin(): void
    {
        $this->actingAs($this->superAdmin)->get(route('admin.super-admin-settings.index'))->assertSee('Super Admin account');
        $this->actingAs($this->admin)->get(route('bookings.index'))->assertDontSee('Super Admin account');
    }

    public function test_other_users_cannot_access_or_submit_settings(): void
    {
        $before = $this->snapshot($this->superAdmin);

        foreach ([$this->admin, $this->staff] as $user) {
            $this->actingAs($user)->get(route('admin.super-admin-settings.index'))->assertForbidden();
            $this->actingAs($user)->put(route('admin.super-admin-settings.email'), [
                'email' => 'hijack@example.com',
                'current_password' => 'Admin-pass1',
            ])->assertForbidden();
            $this->actingAs($user)->put(route('admin.super-admin-settings.password'), [
                'current_password' => 'Admin-pass1',
                'password' => 'Hijacked-pass1',
                'password_confirmation' => 'Hijacked-pass1',
            ])->assertForbidden();
        }

        // Customers are redirected away from /admin by RedirectCustomersFromAdmin.
        $this->actingAs($this->customer)->get(route('admin.super-admin-settings.index'))->assertRedirect(route('customer.dashboard'));
        $this->actingAs($this->customer)->put(route('admin.super-admin-settings.email'), [
            'email' => 'hijack@example.com',
            'current_password' => 'Customer-pass1',
        ])->assertRedirect();

        $this->assertSame($before, $this->snapshot($this->superAdmin));
        $this->assertDatabaseMissing('users', ['email' => 'hijack@example.com']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.super-admin-settings.index'))->assertRedirect(route('login'));
        $this->put(route('admin.super-admin-settings.email'), ['email' => 'x@example.com'])->assertRedirect(route('login'));
        $this->put(route('admin.super-admin-settings.password'), [])->assertRedirect(route('login'));
    }

    public function test_super_admin_can_update_email_with_correct_password(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.super-admin-settings.email'), [
                'email' => 'new.owner@example.com',
                'current_password' => 'Current-pass1',
            ])
            ->assertRedirect(route('admin.super-admin-settings.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $fresh = $this->superAdmin->fresh();
        $this->assertSame('new.owner@example.com', $fresh->email);
        // Follows ProfileController convention; admin login does not require verification.
        $this->assertNull($fresh->email_verified_at);
        $this->assertTrue($fresh->isSuperAdmin());
        $this->assertTrue(Auth::validate(['email' => 'new.owner@example.com', 'password' => 'Current-pass1']));
        $this->assertDatabaseHas('activity_logs', ['action_type' => 'super_admin_email_changed', 'record_id' => $fresh->id]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.super-admin-settings.email'), [
                'email' => 'ops.admin@example.com',
                'current_password' => 'Current-pass1',
            ])
            ->assertSessionHasErrorsIn('updateEmail', ['email']);

        $this->assertSame('root.owner@example.com', $this->superAdmin->fresh()->email);
    }

    public function test_invalid_email_is_rejected(): void
    {
        foreach (['not-an-email', '', str_repeat('a', 250) . '@example.com'] as $email) {
            $this->actingAs($this->superAdmin)
                ->put(route('admin.super-admin-settings.email'), [
                    'email' => $email,
                    'current_password' => 'Current-pass1',
                ])
                ->assertSessionHasErrorsIn('updateEmail', ['email']);
        }

        $this->assertSame('root.owner@example.com', $this->superAdmin->fresh()->email);
    }

    public function test_email_update_with_wrong_password_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.super-admin-settings.email'), [
                'email' => 'new.owner@example.com',
                'current_password' => 'Wrong-pass1',
            ])
            ->assertSessionHasErrorsIn('updateEmail', ['current_password']);

        $this->assertSame('root.owner@example.com', $this->superAdmin->fresh()->email);
    }

    public function test_super_admin_can_change_password(): void
    {
        $oldRememberToken = $this->superAdmin->remember_token;

        $this->actingAs($this->superAdmin)
            ->put(route('admin.super-admin-settings.password'), [
                'current_password' => 'Current-pass1',
                'password' => 'Brand-new-pass2',
                'password_confirmation' => 'Brand-new-pass2',
            ])
            ->assertRedirect(route('admin.super-admin-settings.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $fresh = $this->superAdmin->fresh();
        $this->assertNotSame('Brand-new-pass2', $fresh->password);
        $this->assertTrue(Hash::check('Brand-new-pass2', $fresh->password));
        $this->assertNotSame($oldRememberToken, $fresh->remember_token);

        // Current session stays signed in.
        $this->assertAuthenticatedAs($fresh);

        Auth::logout();
        $this->assertFalse(Auth::attempt(['email' => 'root.owner@example.com', 'password' => 'Current-pass1']));
        $this->assertTrue(Auth::attempt(['email' => 'root.owner@example.com', 'password' => 'Brand-new-pass2']));

        $log = ActivityLog::where('action_type', 'super_admin_password_changed')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('Brand-new-pass2', json_encode($log->toArray()));
    }

    public function test_password_change_signs_out_only_other_sessions_of_super_admin(): void
    {
        config(['session.driver' => 'database']);
        $row = fn (string $id, int $userId) => [
            'id' => $id, 'user_id' => $userId, 'ip_address' => '127.0.0.1', 'user_agent' => 'test',
            'payload' => base64_encode(serialize([])), 'last_activity' => time(),
        ];
        DB::table('sessions')->insert([
            $row('super-admin-other-device', $this->superAdmin->id),
            $row('admin-device', $this->admin->id),
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.super-admin-settings.password'), [
                'current_password' => 'Current-pass1',
                'password' => 'Brand-new-pass2',
                'password_confirmation' => 'Brand-new-pass2',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'super-admin-other-device']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin-device']);
        $this->assertAuthenticatedAs($this->superAdmin->fresh());
    }

    public function test_password_change_with_wrong_current_password_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.super-admin-settings.password'), [
                'current_password' => 'Wrong-pass1',
                'password' => 'Brand-new-pass2',
                'password_confirmation' => 'Brand-new-pass2',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['current_password']);

        $this->assertTrue(Hash::check('Current-pass1', $this->superAdmin->fresh()->password));
    }

    public function test_password_confirmation_and_minimum_length_are_enforced(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.super-admin-settings.password'), [
                'current_password' => 'Current-pass1',
                'password' => 'Brand-new-pass2',
                'password_confirmation' => 'Different-pass2',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['password']);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.super-admin-settings.password'), [
                'current_password' => 'Current-pass1',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['password']);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.super-admin-settings.password'), [
                'current_password' => 'Current-pass1',
                'password' => 'Current-pass1',
                'password_confirmation' => 'Current-pass1',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['password']);

        $this->assertTrue(Hash::check('Current-pass1', $this->superAdmin->fresh()->password));
    }

    public function test_no_other_user_credentials_or_roles_are_modified(): void
    {
        $others = collect([$this->admin, $this->staff, $this->customer])
            ->mapWithKeys(fn (User $u) => [$u->id => $this->snapshot($u)]);
        $superAdminRoleId = $this->superAdmin->role_id;

        $this->actingAs($this->superAdmin)->put(route('admin.super-admin-settings.email'), [
            'email' => 'new.owner@example.com',
            'current_password' => 'Current-pass1',
            // Extra fields must be ignored.
            'role_id' => Role::where('slug', 'customer')->value('id'),
            'id' => $this->admin->id,
        ]);
        $this->actingAs($this->superAdmin->fresh())->put(route('admin.super-admin-settings.password'), [
            'current_password' => 'Current-pass1',
            'password' => 'Brand-new-pass2',
            'password_confirmation' => 'Brand-new-pass2',
            'user_id' => $this->admin->id,
        ]);

        foreach ($others as $id => $snapshot) {
            $this->assertSame($snapshot, $this->snapshot(User::find($id)));
        }
        $this->assertSame($superAdminRoleId, $this->superAdmin->fresh()->role_id);
    }
}
