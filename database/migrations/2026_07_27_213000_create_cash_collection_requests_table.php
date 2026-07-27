<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_collection_requests', function (Blueprint $table) {
            $table->id();
            $table->string('collection_number')->unique();
            $table->foreignId('transaction_id')->constrained('payment_transactions')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('gold_bookings')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('booking_payments')->nullOnDelete();
            $table->foreignId('collected_by_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status', 30)->default('Pending Verification');
            $table->dateTime('collection_date');
            $table->text('remarks')->nullable();
            $table->foreignId('verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // Add Module and Permissions
        $module = Module::updateOrCreate(
            ['slug' => 'cash-collection'],
            ['name' => 'Cash Collection', 'status' => 'active']
        );

        $actions = ['view', 'verify', 'reject', 'manage'];
        foreach ($actions as $action) {
            Permission::updateOrCreate(
                ['slug' => 'cash-collection.' . $action],
                [
                    'module_id' => $module->id,
                    'name' => ucfirst($action) . ' Cash Collection',
                ]
            );
        }

        // Assign to Super Admin and Admin
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();

        $permissions = Permission::where('slug', 'like', 'cash-collection.%')->get();

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
        Schema::dropIfExists('cash_collection_requests');

        $permissions = Permission::where('slug', 'like', 'cash-collection.%')->get();
        foreach ($permissions as $p) {
            DB::table('role_permissions')->where('permission_id', $p->id)->delete();
            $p->delete();
        }

        Module::where('slug', 'cash-collection')->delete();
    }
};
