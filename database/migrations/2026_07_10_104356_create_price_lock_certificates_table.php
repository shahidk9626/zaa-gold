<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lock_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique();
            $table->foreignId('booking_id')->constrained('gold_bookings')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('issued_at');
            $table->decimal('locked_price', 15, 2);
            $table->decimal('gold_weight', 12, 2);
            $table->decimal('grand_total', 15, 2);
            $table->string('pdf_path')->nullable();
            $table->string('verification_token')->unique();
            $table->string('qr_code')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lock_certificates');
    }
};
