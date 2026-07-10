<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccessControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define Modules and Permissions
        $modules = [
            'Dashboard' => ['view'],
            'Roles' => ['view', 'create', 'edit', 'delete', 'status'],
            'Staff' => ['view', 'create', 'edit', 'delete', 'status', 'export', 'import', 'view_details'],
            'User Permissions' => ['view', 'edit'],
            'Customer' => ['view', 'create', 'edit', 'delete', 'status', 'export', 'import', 'view_details'],
            'Plans' => ['view', 'create', 'edit', 'delete', 'status', 'preview'],
            'Staff Commission' => ['view', 'status', 'export'],
            'Reports' => ['view'],
            'Settings' => ['view'],
            'Product' => ['view', 'create', 'edit', 'delete', 'status'],
            'Gold Price' => ['view', 'create', 'edit', 'delete', 'status', 'history'],
            'Inventory' => ['view', 'create', 'edit', 'delete', 'status', 'adjust'],
            'KYC' => ['view', 'create', 'edit', 'delete', 'approve', 'reject', 'download'],
            'EMI Plan' => ['view', 'create', 'edit', 'delete', 'status', 'view_details'],
            'Purchase Preview' => ['view', 'calculate', 'continue'],
            'EMI Outstanding' => ['export'],
            'EMI Calculator' => ['view'],
            'Booking' => ['view', 'create', 'edit', 'delete', 'view_details', 'export', 'download_certificate', 'change_status'],
        ];

        foreach ($modules as $moduleName => $actions) {
            $module = Module::updateOrCreate(
                ['slug' => Str::slug($moduleName)],
                ['name' => $moduleName, 'status' => 'active']
            );

            foreach ($actions as $action) {
                Permission::updateOrCreate(
                    ['slug' => $module->slug . '.' . $action],
                    [
                        'module_id' => $module->id,
                        'name' => ucfirst($action) . ' ' . $moduleName,
                    ]
                );
            }
        }

        // 2. Create Default Roles
        $superAdminRole = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'System Super Administrator with full privileges',
                'status' => 1,
            ]
        );

        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Administrator with complete access',
                'status' => 1,
            ]
        );

        $staffRole = Role::updateOrCreate(
            ['slug' => 'staff'],
            [
                'name' => 'Staff',
                'description' => 'Staff member with standard operations access',
                'status' => 1,
            ]
        );

        $customerRole = Role::updateOrCreate(
            ['slug' => 'customer'],
            [
                'name' => 'Customer',
                'description' => 'Default Customer Role',
                'status' => 1,
            ]
        );

        // 3. Assign all permissions to Super Admin and Admin Roles
        $allPermissions = Permission::all();
        foreach ([$superAdminRole, $adminRole] as $role) {
            foreach ($allPermissions as $permission) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => $permission->id],
                    ['allowed' => 1]
                );
            }
        }

        // 4. Assign staff permissions to Staff Role
        $staffPermSlugs = [
            'dashboard.view',
            'customer.view',
            'staff-commission.view'
        ];
        $staffPerms = Permission::whereIn('slug', $staffPermSlugs)->get();
        foreach ($staffPerms as $permission) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $staffRole->id, 'permission_id' => $permission->id],
                ['allowed' => 1]
            );
        }

        // 5. Ensure Super Admin / Admin Users exist
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@zaagold.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole->id,
                'status' => 'active',
                'profile_completed' => 1,
                'email_verified_at' => now(),
            ]
        );

        // Update the Test User if exists
        User::where('email', 'test@example.com')->update([
            'role_id' => $staffRole->id,
        ]);
    }
}
