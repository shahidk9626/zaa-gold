<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kycs', function (Blueprint $table) {
            $table->string('bank_document')->nullable()->after('signature');
        });
    }

    public function down(): void
    {
        Schema::table('kycs', function (Blueprint $table) {
            $table->dropColumn('bank_document');
        });
    }
};
