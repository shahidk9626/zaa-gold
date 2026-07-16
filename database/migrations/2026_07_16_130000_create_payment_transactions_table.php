<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('booking_id')->nullable()->constrained('gold_bookings')->nullOnDelete();
            $table->foreignId('emi_schedule_id')->nullable()->constrained('booking_emi_schedules')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('payment_type', 40)->default('booking');
            $table->string('gateway', 40)->default('cashfree');
            $table->string('gateway_order_id')->unique();
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('payment_token')->nullable()->unique();
            $table->text('payment_url')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('payment_status', 30)->default('Pending');
            $table->string('link_status', 30)->nullable();
            $table->json('gateway_request')->nullable();
            $table->json('gateway_response')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('webhook_processed_at')->nullable();
            $table->foreignId('generated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'payment_type']);
            $table->index(['booking_id', 'payment_status']);
            $table->index(['emi_schedule_id', 'link_status']);
            $table->index(['gateway', 'gateway_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
