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
        Schema::create('gold_inspection_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('address');
            $table->decimal('approx_grams', 10, 2);
            $table->string('gold_type');
            $table->string('gold_location');
            $table->date('preferred_date');
            $table->json('photos')->nullable();
            
            $table->string('status', 30)->default('New'); // New, Contacted, Inspection Scheduled, Inspection Completed, Converted, Rejected
            $table->text('admin_remark')->nullable();
            
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed module & permissions
        $moduleName = 'Inspection';
        $actions = ['view', 'create', 'edit', 'delete'];
        $slug = Str::slug($moduleName); // inspection

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
            'inspection.view',
            'inspection.create',
            'inspection.edit',
            'inspection.delete',
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
            'inspection.view',
            'inspection.create',
            'inspection.edit',
            'inspection.delete',
        ];
        
        $permissions = Permission::whereIn('slug', $newPermissionSlugs)->get();
        foreach ($permissions as $p) {
            DB::table('role_permissions')->where('permission_id', $p->id)->delete();
            $p->delete();
        }

        Module::where('slug', 'inspection')->delete();

        Schema::dropIfExists('gold_inspection_enquiries');
    }
};
