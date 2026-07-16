<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->json('changed_fields')->nullable()->after('new_data');
            $table->string('performed_by_type')->nullable()->after('created_by_id');
            $table->string('device')->nullable()->after('browser');
            $table->string('platform')->nullable()->after('device');
            $table->string('url')->nullable()->after('user_agent');
            $table->string('http_method', 20)->nullable()->after('url');
            $table->string('request_id')->nullable()->after('http_method');

            $table->index(['module_name', 'record_id']);
            $table->index('action_type');
            $table->index('created_by_id');
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['module_name', 'record_id']);
            $table->dropIndex(['action_type']);
            $table->dropIndex(['created_by_id']);
            $table->dropIndex(['request_id']);

            $table->dropColumn([
                'changed_fields',
                'performed_by_type',
                'device',
                'platform',
                'url',
                'http_method',
                'request_id',
            ]);
        });
    }
};
