<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->string('receipt_number')->unique();
            $table->foreignId('booking_id')->constrained('gold_bookings')->cascadeOnDelete();
            $table->foreignId('emi_schedule_id')->nullable()->constrained('booking_emi_schedules')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('payment_mode', 30); // Cash, UPI, Bank Transfer, Card, Cheque, Online Gateway
            $table->string('transaction_reference')->nullable();
            
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('principal_paid', 15, 2);
            $table->decimal('interest_paid', 15, 2);
            $table->decimal('late_fee_paid', 15, 2)->default(0.00);
            $table->decimal('gst_paid', 15, 2)->default(0.00);
            
            $table->dateTime('payment_date');
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('Paid'); // Paid, Failed, Refunded, etc.
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Set up the circular foreign key constraint from booking_emi_schedules to booking_payments
        Schema::table('booking_emi_schedules', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('booking_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_emi_schedules', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });
        Schema::dropIfExists('booking_payments');
    }
};
