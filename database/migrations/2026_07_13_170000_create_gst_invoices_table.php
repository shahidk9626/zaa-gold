<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gst_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('booking_id')->constrained('gold_bookings')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('booking_payments')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            
            $table->dateTime('invoice_date');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('billing_address');
            
            $table->string('product_name');
            $table->decimal('gold_weight', 12, 2);
            $table->decimal('gold_purity', 5, 2);
            $table->decimal('locked_gold_price', 15, 2);
            
            $table->decimal('gold_value', 15, 2);
            $table->decimal('gst_on_gold_percent', 5, 2);
            $table->decimal('gst_on_gold_amount', 15, 2);
            
            $table->decimal('finance_charge', 15, 2);
            $table->decimal('storage_charge', 15, 2);
            $table->decimal('gst_on_charges_percent', 5, 2);
            $table->decimal('gst_on_charges_amount', 15, 2);
            
            $table->decimal('subtotal', 15, 2);
            $table->decimal('grand_total', 15, 2);
            $table->decimal('payment_received', 15, 2);
            $table->decimal('balance_amount', 15, 2);
            
            $table->decimal('cgst_percent', 5, 2)->default(0.00);
            $table->decimal('cgst_amount', 15, 2)->default(0.00);
            $table->decimal('sgst_percent', 5, 2)->default(0.00);
            $table->decimal('sgst_amount', 15, 2)->default(0.00);
            $table->decimal('igst_percent', 5, 2)->default(0.00);
            $table->decimal('igst_amount', 15, 2)->default(0.00);
            
            $table->string('invoice_status', 30)->default('Generated'); // Draft, Generated, Cancelled, Revised
            $table->text('remarks')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('verification_token')->unique();
            $table->string('qr_code')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gst_invoices');
    }
};
