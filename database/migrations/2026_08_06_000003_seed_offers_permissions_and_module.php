<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create/update the Offers module
        $module = Module::updateOrCreate(
            ['slug' => 'offers'],
            ['name' => 'Offers & Discounts', 'status' => 'active']
        );

        // 2. Define the permissions
        $permissionsData = [
            'offers.view' => 'Offer View',
            'offers.create' => 'Offer Create',
            'offers.edit' => 'Offer Edit',
            'offers.delete' => 'Offer Delete',
            'offers.status' => 'Offer Status Change',
        ];

        $permissionIds = [];
        foreach ($permissionsData as $slug => $name) {
            $permission = Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'module_id' => $module->id,
                    'name' => $name,
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // 3. Assign to super-admin and admin roles
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();

        foreach ([$superAdminRole, $adminRole] as $role) {
            if ($role) {
                foreach ($permissionIds as $permId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $role->id, 'permission_id' => $permId],
                        ['allowed' => 1]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $slugs = ['offers.view', 'offers.create', 'offers.edit', 'offers.delete', 'offers.status'];
        $permissions = Permission::whereIn('slug', $slugs)->get();

        foreach ($permissions as $p) {
            DB::table('role_permissions')->where('permission_id', $p->id)->delete();
            $p->delete();
        }

        Module::where('slug', 'offers')->delete();
    }
};
