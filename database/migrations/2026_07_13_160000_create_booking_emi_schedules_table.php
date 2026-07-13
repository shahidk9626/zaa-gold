<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_emi_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('gold_bookings')->cascadeOnDelete();
            $table->integer('installment_number');
            $table->date('due_date');
            
            $table->decimal('opening_principal', 15, 2);
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_amount', 15, 2);
            $table->decimal('emi_amount', 15, 2);
            $table->decimal('closing_principal', 15, 2);
            $table->decimal('outstanding_balance', 15, 2);
            $table->decimal('late_fee', 15, 2)->default(0.00);
            
            $table->string('status', 30)->default('Pending'); // Pending, Paid, Partial, Overdue
            $table->dateTime('paid_at')->nullable();
            
            // payment_id will reference booking_payments(id) once that table is created
            $table->unsignedBigInteger('payment_id')->nullable();
            
            $table->text('remarks')->nullable();
            
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_emi_schedules');
    }
};
