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
        // Check if role column exists, if not create it
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user')->after('email');
            });
        } else {
            // If column exists, just set default
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user')->change();
            });
        }

        // Update existing NULL role values to 'user'
        DB::table('users')
            ->whereNull('role')
            ->orWhere('role', '')
            ->update(['role' => 'user']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't drop the column in down() as it may contain important data
        // Just remove the default constraint
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->nullable()->change();
            });
        }
    }
};
