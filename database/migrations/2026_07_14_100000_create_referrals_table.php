<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('referral_code')->unique();
            $table->foreignId('referrer_customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('referred_customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('gold_bookings')->nullOnDelete();
            
            $table->string('reward_type')->default('Cash'); // Cash, Gold Grams, Discount
            $table->decimal('reward_amount', 15, 2)->default(0.00);
            $table->string('reward_status', 30)->default('Pending'); // Pending, Eligible, Rewarded, Rejected
            $table->text('remarks')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
