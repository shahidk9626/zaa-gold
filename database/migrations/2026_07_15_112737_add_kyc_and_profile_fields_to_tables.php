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
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_status')->default('pending')->after('profile_completed');
        });

        Schema::table('customer_details', function (Blueprint $table) {
            $table->string('emergency_contact')->nullable()->after('alternate_number');
        });

        Schema::table('kycs', function (Blueprint $table) {
            $table->string('document_type')->nullable()->change();
            $table->string('document_number')->nullable()->change();
            $table->string('pan_card')->nullable()->after('selfie');
            $table->string('signature')->nullable()->after('pan_card');
            $table->text('additional_documents')->nullable()->after('signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('verification_status');
        });

        Schema::table('customer_details', function (Blueprint $table) {
            $table->dropColumn('emergency_contact');
        });

        Schema::table('kycs', function (Blueprint $table) {
            $table->string('document_type')->nullable(false)->change();
            $table->string('document_number')->nullable(false)->change();
            $table->dropColumn(['pan_card', 'signature', 'additional_documents']);
        });
    }
};
