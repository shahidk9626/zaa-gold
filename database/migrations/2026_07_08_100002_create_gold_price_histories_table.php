<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gold_price_id')->constrained('gold_prices')->onDelete('cascade');
            $table->string('gold_type');
            $table->decimal('old_price', 15, 2);
            $table->decimal('new_price', 15, 2);
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_price_histories');
    }
};
