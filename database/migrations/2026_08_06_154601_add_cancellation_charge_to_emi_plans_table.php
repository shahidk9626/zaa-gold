<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emi_plans', function (Blueprint $table) {
            $table->decimal('cancellation_charge_percent', 5, 2)->default(0.00)->after('rounding_type');
        });
    }

    public function down(): void
    {
        Schema::table('emi_plans', function (Blueprint $table) {
            $table->dropColumn('cancellation_charge_percent');
        });
    }
};
