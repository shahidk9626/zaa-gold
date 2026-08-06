<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('booking_id')->constrained('gold_bookings')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->text('cancellation_reason');
            $table->decimal('cancellation_charge_percent', 5, 2);
            $table->decimal('cancellation_charge_amount', 12, 2);
            $table->decimal('total_amount_paid', 12, 2);
            $table->decimal('refund_amount', 12, 2);
            $table->string('status')->default('Requested'); // Requested, Under Review, Customer Retained, Approved, Refund Initiated, Refund Completed, Rejected
            $table->text('admin_remark')->nullable();
            
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamp('refund_initiated_at')->nullable();
            $table->timestamp('refund_completed_at')->nullable();
            $table->string('refund_transaction_number')->nullable();
            $table->date('refund_date')->nullable();
            $table->string('refund_mode')->nullable();
            
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_requests');
    }
};
