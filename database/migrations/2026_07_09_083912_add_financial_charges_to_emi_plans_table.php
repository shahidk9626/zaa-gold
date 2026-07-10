<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('emi_plans', function (Blueprint $table) {
            $table->boolean('gst_on_gold_enabled')->nullable();
            $table->decimal('gst_on_gold_percent', 5, 2)->nullable()->default(3.00);

            $table->boolean('finance_charge_enabled')->nullable();
            $table->string('finance_charge_type', 20)->nullable();
            $table->decimal('finance_charge_value', 15, 2)->nullable();

            $table->boolean('storage_charge_enabled')->nullable();
            $table->string('storage_charge_type', 20)->nullable();
            $table->decimal('storage_charge_value', 15, 2)->nullable();

            $table->boolean('gst_on_charges_enabled')->nullable();
            $table->decimal('gst_on_charges_percent', 5, 2)->nullable()->default(18.00);

            $table->string('rounding_type', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emi_plans', function (Blueprint $table) {
            $table->dropColumn([
                'gst_on_gold_enabled',
                'gst_on_gold_percent',
                'finance_charge_enabled',
                'finance_charge_type',
                'finance_charge_value',
                'storage_charge_enabled',
                'storage_charge_type',
                'storage_charge_value',
                'gst_on_charges_enabled',
                'gst_on_charges_percent',
                'rounding_type',
            ]);
        });
    }
};
