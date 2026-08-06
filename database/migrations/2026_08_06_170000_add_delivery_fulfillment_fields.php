<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('address_name');
            $table->string('mobile', 20);
            $table->string('alternate_mobile', 20)->nullable();
            $table->string('house_no')->nullable();
            $table->string('street')->nullable();
            $table->string('area')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('pin_code', 20);
            $table->string('country')->default('India');
            $table->string('address_type', 20)->default('Home');
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('booking_deliveries', function (Blueprint $table) {
            $table->foreignId('customer_address_id')->nullable()->after('customer_id')->constrained('customer_addresses')->nullOnDelete();
            $table->string('delivery_address_name')->nullable()->after('delivery_address');
            $table->string('delivery_address_mobile', 20)->nullable()->after('delivery_address_name');
            $table->string('delivery_address_alternate_mobile', 20)->nullable()->after('delivery_address_mobile');
            $table->string('delivery_address_type', 20)->nullable()->after('delivery_address_alternate_mobile');
            $table->string('delivery_city')->nullable()->after('delivery_address_type');
            $table->string('delivery_state')->nullable()->after('delivery_city');
            $table->string('delivery_pin_code', 20)->nullable()->after('delivery_state');
            $table->string('delivery_country')->nullable()->after('delivery_pin_code');
            $table->date('expected_delivery_date')->nullable()->after('dispatch_date');
            $table->dateTime('ready_for_dispatch_at')->nullable()->after('expected_delivery_date');
            $table->dateTime('in_transit_at')->nullable()->after('ready_for_dispatch_at');
            $table->dateTime('out_for_delivery_at')->nullable()->after('in_transit_at');
            $table->dateTime('collected_at')->nullable()->after('out_for_delivery_at');
            $table->text('dispatch_remarks')->nullable()->after('remarks');
            $table->text('rejection_reason')->nullable()->after('dispatch_remarks');
            $table->text('hold_reason')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('booking_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_address_id');
            $table->dropColumn([
                'delivery_address_name',
                'delivery_address_mobile',
                'delivery_address_alternate_mobile',
                'delivery_address_type',
                'delivery_city',
                'delivery_state',
                'delivery_pin_code',
                'delivery_country',
                'expected_delivery_date',
                'ready_for_dispatch_at',
                'in_transit_at',
                'out_for_delivery_at',
                'collected_at',
                'dispatch_remarks',
                'rejection_reason',
                'hold_reason',
            ]);
        });

        Schema::dropIfExists('customer_addresses');
    }
};
