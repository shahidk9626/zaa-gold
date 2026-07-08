<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_prices', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_22k', 15, 2);
            $table->decimal('price_24k', 15, 2);
            $table->decimal('price_bullion', 15, 2);
            $table->dateTime('effective_date');
            $table->string('remarks')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_prices');
    }
};
