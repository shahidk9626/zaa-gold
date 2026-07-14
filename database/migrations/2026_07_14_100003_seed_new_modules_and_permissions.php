<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Define new modules & permissions to add
        $newModules = [
            'Referral' => ['view', 'edit', 'export'],
            'Sell Old Gold' => ['view', 'edit', 'export'],
            'Franchise' => ['view', 'edit', 'export'],
        ];

        // 1. Create/update the new modules and their permissions
        foreach ($newModules as $moduleName => $actions) {
            $slug = Str::slug($moduleName); // referral, sell-old-gold, franchise
            $module = Module::updateOrCreate(
                ['slug' => $slug],
                ['name' => $moduleName, 'status' => 'active']
            );

            foreach ($actions as $action) {
                Permission::updateOrCreate(
                    ['slug' => $slug . '.' . $action],
                    [
                        'module_id' => $module->id,
                        'name' => ucfirst($action) . ' ' . $moduleName,
                    ]
                );
            }
        }

        // 2. Handle Reports module specifically to match "report.view" and "report.export"
        // Let's find or create the Reports module with slug 'report'
        $reportModule = Module::updateOrCreate(
            ['slug' => 'report'],
            ['name' => 'Reports & Analytics', 'status' => 'active']
        );

        foreach (['view', 'export'] as $action) {
            Permission::updateOrCreate(
                ['slug' => 'report.' . $action],
                [
                    'module_id' => $reportModule->id,
                    'name' => ucfirst($action) . ' Reports',
                ]
            );
        }

        // 3. Assign these new permissions to super-admin and admin roles
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();

        $newPermissionSlugs = [
            'referral.view', 'referral.edit', 'referral.export',
            'sell-old-gold.view', 'sell-old-gold.edit', 'sell-old-gold.export',
            'franchise.view', 'franchise.edit', 'franchise.export',
            'report.view', 'report.export'
        ];

        $permissions = Permission::whereIn('slug', $newPermissionSlugs)->get();

        foreach ([$superAdminRole, $adminRole] as $role) {
            if ($role) {
                foreach ($permissions as $permission) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $role->id, 'permission_id' => $permission->id],
                        ['allowed' => 1]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $newPermissionSlugs = [
            'referral.view', 'referral.edit', 'referral.export',
            'sell-old-gold.view', 'sell-old-gold.edit', 'sell-old-gold.export',
            'franchise.view', 'franchise.edit', 'franchise.export',
            'report.view', 'report.export'
        ];
        
        $permissions = Permission::whereIn('slug', $newPermissionSlugs)->get();
        foreach ($permissions as $p) {
            DB::table('role_permissions')->where('permission_id', $p->id)->delete();
            $p->delete();
        }

        Module::whereIn('slug', ['referral', 'sell-old-gold', 'franchise', 'report'])->delete();
    }
};
