<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchise_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('mobile');
            $table->string('email');
            $table->string('city');
            $table->string('state');
            $table->string('investment_budget'); // Range or Amount
            $table->text('business_experience')->nullable();
            $table->string('current_business')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('New'); // New, Contacted, Meeting Scheduled, Proposal Sent, Approved, Rejected, Closed
            
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('followup_date')->nullable();
            $table->text('remarks')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_enquiries');
    }
};
