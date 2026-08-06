<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('offer_code')->unique();
            $table->string('offer_name');
            $table->string('offer_type'); // percentage, fixed, emi
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('fixed_amount', 15, 2)->nullable();
            $table->integer('required_emi_count')->nullable();
            $table->integer('free_emi_count')->nullable();
            $table->text('offer_description')->nullable();
            $table->string('banner')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->integer('priority')->default(0);
            $table->string('status', 30)->default('Draft'); // Draft, Active, Inactive, Expired
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('emi_plan_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emi_plan_id')->constrained('emi_plans')->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emi_plan_offers');
        Schema::dropIfExists('offers');
    }
};
