<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add referral_code to gold_bookings
        Schema::table('gold_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('gold_bookings', 'referral_code')) {
                $table->string('referral_code', 50)->nullable()->after('remarks');
            }
        });

        // 2. Add columns to customer_referrals
        Schema::table('customer_referrals', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_referrals', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->after('customer_id')->constrained('gold_bookings')->nullOnDelete();
            }
            if (!Schema::hasColumn('customer_referrals', 'gold_weight')) {
                $table->decimal('gold_weight', 10, 3)->nullable()->after('booking_id');
            }
            if (!Schema::hasColumn('customer_referrals', 'cashback_rate')) {
                $table->decimal('cashback_rate', 15, 2)->nullable()->after('gold_weight');
            }
            if (!Schema::hasColumn('customer_referrals', 'cashback_amount')) {
                $table->decimal('cashback_amount', 15, 2)->nullable()->after('cashback_rate');
            }
            if (!Schema::hasColumn('customer_referrals', 'status')) {
                $table->string('status', 30)->default('Pending')->after('cashback_amount')->index();
            }
            if (!Schema::hasColumn('customer_referrals', 'payment_reference_number')) {
                $table->string('payment_reference_number', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('customer_referrals', 'admin_remark')) {
                $table->text('admin_remark')->nullable()->after('payment_reference_number');
            }
            if (!Schema::hasColumn('customer_referrals', 'processed_by')) {
                $table->foreignId('processed_by')->nullable()->after('admin_remark')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('customer_referrals', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('processed_by');
            }
        });

        // 3. Seed referral cashback rate setting
        DB::table('system_settings')->insertOrIgnore([
            'key' => 'referral_cashback_rate',
            'value' => '500.00',
            'description' => 'Referral cashback rate per gram of gold',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::table('customer_referrals', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['processed_by']);
            $table->dropColumn([
                'booking_id',
                'gold_weight',
                'cashback_rate',
                'cashback_amount',
                'status',
                'payment_reference_number',
                'admin_remark',
                'processed_by',
                'completed_at'
            ]);
        });

        Schema::table('gold_bookings', function (Blueprint $table) {
            $table->dropColumn(['referral_code']);
        });

        DB::table('system_settings')->where('key', 'referral_cashback_rate')->delete();
    }
};
