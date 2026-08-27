<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add referral_code to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code')->nullable()->unique()->index()->after('phone');
            }
        });

        // 2. Modify customer_referrals table
        Schema::table('customer_referrals', function (Blueprint $table) {
            // Drop foreign key first to modify the column
            $table->dropForeign(['staff_id']);
            $table->unsignedBigInteger('staff_id')->nullable()->change();
            $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');

            if (!Schema::hasColumn('customer_referrals', 'referrer_id')) {
                $table->foreignId('referrer_id')->nullable()->after('staff_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('customer_referrals', 'referrer_type')) {
                $table->string('referrer_type', 30)->nullable()->after('referrer_id');
            }
        });

        // 3. Backfill existing users with referral codes
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            if (!empty($user->referral_code)) {
                continue;
            }

            // Determine prefix
            $prefix = 'CUS';
            if ($user->role_id) {
                $role = DB::table('roles')->where('id', $user->role_id)->first();
                if ($role && in_array($role->slug, ['super-admin', 'admin', 'staff'], true)) {
                    $prefix = 'STF';
                }
            }

            // Generate unique code based on first name consonants
            $cleanName = preg_replace('/[^A-Za-z]/', '', $user->name);
            preg_match_all('/[BCDFGHJKLMNPQRSTVWXYZbcdfghjklmnpqrstvwxyz]/', $cleanName, $matches);
            $consonants = implode('', $matches[0]);

            $letters = '';
            if (strlen($consonants) >= 2) {
                $letters = substr($consonants, 0, 2);
            } else {
                $letters = substr($cleanName, 0, 2);
            }

            if (strlen($letters) < 2) {
                $letters = str_pad($letters, 2, 'X');
            }

            $letters = strtoupper($letters);

            do {
                $code = $prefix . $letters . mt_rand(1000, 9999);
                $exists = DB::table('users')->where('referral_code', $code)->exists();
            } while ($exists);

            DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
        }

        // 4. Backfill existing customer referrals
        $referrals = DB::table('customer_referrals')->get();
        foreach ($referrals as $ref) {
            DB::table('customer_referrals')->where('id', $ref->id)->update([
                'referrer_id' => $ref->staff_id,
                'referrer_type' => 'staff'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_referrals', function (Blueprint $table) {
            $table->dropColumn(['referrer_id', 'referrer_type']);
            
            $table->dropForeign(['staff_id']);
            $table->unsignedBigInteger('staff_id')->change();
            $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code']);
        });
    }
};
