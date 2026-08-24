<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Referral Module & Permissions
        $referralModule = Module::updateOrCreate(
            ['slug' => 'referral'],
            ['name' => 'Customer Referrals', 'status' => 'active']
        );

        $referralPermissions = [
            'referral.view' => 'View Customer Referrals',
            'referral.edit' => 'Edit Customer Referrals',
            'referral.export' => 'Export Customer Referrals',
        ];

        foreach ($referralPermissions as $slug => $name) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                ['module_id' => $referralModule->id, 'name' => $name]
            );
        }

        // 2. Profile Update Requests Module & Permissions
        $profileUpdateModule = Module::updateOrCreate(
            ['slug' => 'profile-update-requests'],
            ['name' => 'Profile Update Requests', 'status' => 'active']
        );

        $profileUpdatePermissions = [
            'profile-update-requests.view' => 'View Profile Update Requests',
            'profile-update-requests.approve' => 'Approve Profile Update Requests',
            'profile-update-requests.reject' => 'Reject Profile Update Requests',
        ];

        foreach ($profileUpdatePermissions as $slug => $name) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                ['module_id' => $profileUpdateModule->id, 'name' => $name]
            );
        }

        // 3. Assign Permissions to Super Admin and Admin Roles
        $roles = Role::whereIn('slug', ['super-admin', 'admin'])->get();
        $allSlugs = array_merge(array_keys($referralPermissions), array_keys($profileUpdatePermissions));
        $permissions = Permission::whereIn('slug', $allSlugs)->get();

        foreach ($roles as $role) {
            foreach ($permissions as $permission) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => $permission->id],
                    ['allowed' => 1, 'updated_at' => now()]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $allSlugs = [
            'referral.view', 'referral.edit', 'referral.export',
            'profile-update-requests.view', 'profile-update-requests.approve', 'profile-update-requests.reject'
        ];

        $permissions = Permission::whereIn('slug', $allSlugs)->get();
        foreach ($permissions as $p) {
            DB::table('role_permissions')->where('permission_id', $p->id)->delete();
            $p->delete();
        }

        Module::whereIn('slug', ['referral', 'profile-update-requests'])->delete();
    }
};
