<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cancellation_requests', function (Blueprint $table) {
            // Historical snapshot of the original total plan amount (grand_total)
            // that the cancellation charge was calculated against. Nullable so
            // existing records remain untouched; new records populate it.
            $table->decimal('original_plan_amount', 12, 2)->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('cancellation_requests', function (Blueprint $table) {
            $table->dropColumn('original_plan_amount');
        });
    }
};
