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
        // 1. Create/update the Cancellations module
        $module = Module::updateOrCreate(
            ['slug' => 'cancellations'],
            ['name' => 'Cancellations & Refunds', 'status' => 'active']
        );

        // 2. Define the permissions
        $permissionsData = [
            'cancellations.view' => 'Cancellation View',
            'cancellations.approve' => 'Cancellation Approve',
            'cancellations.reject' => 'Cancellation Reject',
            'cancellations.refund' => 'Cancellation Refund Process',
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
        $slugs = ['cancellations.view', 'cancellations.approve', 'cancellations.reject', 'cancellations.refund'];
        $permissions = Permission::whereIn('slug', $slugs)->get();

        foreach ($permissions as $p) {
            DB::table('role_permissions')->where('permission_id', $p->id)->delete();
            $p->delete();
        }

        Module::where('slug', 'cancellations')->delete();
    }
};
