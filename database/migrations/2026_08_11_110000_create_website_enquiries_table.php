<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        Schema::create('website_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('subject');
            $table->text('message');
            $table->string('status', 30)->default('New'); // New, In Progress, Contacted, Resolved, Closed
            $table->text('admin_remark')->nullable();
            
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed module & permissions
        $moduleName = 'Website Enquiries';
        $actions = ['view', 'update', 'delete'];
        $slug = Str::slug($moduleName); // website-enquiries

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

        // Assign to Super Admin and Admin roles
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();

        $newPermissionSlugs = [
            'website-enquiries.view',
            'website-enquiries.update',
            'website-enquiries.delete',
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $newPermissionSlugs = [
            'website-enquiries.view',
            'website-enquiries.update',
            'website-enquiries.delete',
        ];
        
        $permissions = Permission::whereIn('slug', $newPermissionSlugs)->get();
        foreach ($permissions as $p) {
            DB::table('role_permissions')->where('permission_id', $p->id)->delete();
            $p->delete();
        }

        Module::where('slug', 'website-enquiries')->delete();

        Schema::dropIfExists('website_enquiries');
    }
};
