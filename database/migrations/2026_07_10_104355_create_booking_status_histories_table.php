<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('gold_bookings')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('remarks')->nullable();
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_histories');
    }
};
