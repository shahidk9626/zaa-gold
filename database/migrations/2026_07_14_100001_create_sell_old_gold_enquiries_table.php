<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sell_old_gold_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('mobile');
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('gold_type'); // 18K, 22K, 24K, etc.
            $table->decimal('estimated_weight', 12, 2);
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('New'); // New, Contacted, Inspection Scheduled, Quoted, Accepted, Rejected, Closed
            
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('followup_date')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sell_old_gold_enquiries');
    }
};
