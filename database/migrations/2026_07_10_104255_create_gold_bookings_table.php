<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('emi_plan_id')->constrained('emi_plans')->cascadeOnDelete();
            $table->foreignId('gold_price_id')->nullable()->constrained('gold_prices')->nullOnDelete();
            
            $table->decimal('gold_weight', 12, 2);
            $table->decimal('gold_purity', 5, 2);
            $table->decimal('locked_price_per_gram', 15, 2);
            $table->decimal('locked_gold_value', 15, 2);
            
            $table->decimal('gst_on_gold_percent', 5, 2)->default(0.00);
            $table->decimal('gst_on_gold_amount', 15, 2)->default(0.00);
            $table->decimal('finance_charge_percent', 5, 2)->default(0.00);
            $table->decimal('finance_charge_amount', 15, 2)->default(0.00);
            $table->decimal('storage_charge_percent', 5, 2)->default(0.00);
            $table->decimal('storage_charge_amount', 15, 2)->default(0.00);
            $table->decimal('gst_on_charges_percent', 5, 2)->default(0.00);
            $table->decimal('gst_on_charges_amount', 15, 2)->default(0.00);
            
            $table->decimal('grand_total', 15, 2);
            $table->decimal('monthly_emi', 15, 2);
            $table->integer('duration_months');
            
            $table->dateTime('booking_date');
            $table->dateTime('estimated_completion_date');
            $table->string('status', 30)->default('Pending First EMI');
            $table->text('remarks')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_bookings');
    }
};
