<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_bookings', function (Blueprint $table) {
            $table->foreignId('offer_id')->nullable()->after('status')->constrained('offers')->nullOnDelete();
            $table->string('offer_name')->nullable()->after('offer_id');
            $table->string('offer_type')->nullable()->after('offer_name');
            $table->decimal('offer_value', 15, 2)->nullable()->after('offer_type');
            $table->decimal('original_amount', 15, 2)->nullable()->after('offer_value');
            $table->decimal('discount_amount', 15, 2)->nullable()->after('original_amount');
            $table->decimal('final_amount', 15, 2)->nullable()->after('discount_amount');
            $table->decimal('savings_amount', 15, 2)->nullable()->after('final_amount');
            $table->integer('waived_emi_count')->nullable()->after('savings_amount');
            $table->json('offer_snapshot')->nullable()->after('waived_emi_count');
        });
    }

    public function down(): void
    {
        Schema::table('gold_bookings', function (Blueprint $table) {
            $table->dropForeign(['offer_id']);
            $table->dropColumn([
                'offer_id',
                'offer_name',
                'offer_type',
                'offer_value',
                'original_amount',
                'discount_amount',
                'final_amount',
                'savings_amount',
                'waived_emi_count',
                'offer_snapshot',
            ]);
        });
    }
};
