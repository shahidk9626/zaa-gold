<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emi_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name');
            $table->string('plan_code')->unique();
            $table->integer('duration_months');
            $table->decimal('minimum_booking_amount', 15, 2)->default(0.00);
            $table->decimal('maximum_booking_amount', 15, 2)->default(0.00);
            $table->decimal('minimum_gold_weight', 12, 2)->default(0.00);
            $table->decimal('maximum_gold_weight', 12, 2)->default(0.00);
            $table->string('processing_fee_type', 20)->default('fixed'); // percent, fixed
            $table->decimal('processing_fee', 15, 2)->default(0.00);
            $table->string('interest_type', 20)->default('flat'); // flat, reducing
            $table->decimal('interest_rate', 5, 2)->default(0.00);
            $table->string('late_fee_type', 20)->default('fixed'); // percent, fixed
            $table->decimal('late_fee', 15, 2)->default(0.00);
            $table->integer('grace_days')->default(0);
            $table->integer('auto_terminate_after_missed_emi')->default(0);
            $table->decimal('maintenance_deduction_percent', 5, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emi_plans');
    }
};
