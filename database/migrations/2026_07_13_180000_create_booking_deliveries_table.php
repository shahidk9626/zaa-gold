<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique();
            $table->foreignId('booking_id')->constrained('gold_bookings')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('delivery_method', 50); // Office Pickup, Courier, Branch Pickup
            $table->string('delivery_status', 50)->default('Requested'); // Requested, Approved, Ready For Dispatch, Dispatched, Out For Delivery, Delivered, Cancelled, Returned
            
            $table->dateTime('request_date')->nullable();
            $table->dateTime('approved_date')->nullable();
            $table->dateTime('dispatch_date')->nullable();
            $table->dateTime('delivered_date')->nullable();
            
            $table->string('courier_partner')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            
            $table->string('pickup_branch')->nullable();
            $table->date('pickup_date')->nullable();
            $table->time('pickup_time')->nullable();
            
            $table->string('otp', 10)->nullable();
            $table->dateTime('otp_expires_at')->nullable();
            $table->dateTime('otp_verified_at')->nullable();
            
            $table->string('receiver_name')->nullable();
            $table->string('receiver_mobile')->nullable();
            $table->string('receiver_id_proof')->nullable();
            
            $table->text('delivery_address')->nullable();
            $table->text('remarks')->nullable();
            $table->string('pdf_path')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_deliveries');
    }
};
