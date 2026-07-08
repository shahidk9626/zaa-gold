<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('gold_type', 10)->after('sku')->default('24K');
        });

        Schema::table('gold_prices', function (Blueprint $table) {
            $table->dropColumn('price_per_gram');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('gold_type');
        });

        Schema::table('gold_prices', function (Blueprint $table) {
            $table->decimal('price_per_gram', 15, 2)->after('id')->default(0.00);
        });
    }
};
