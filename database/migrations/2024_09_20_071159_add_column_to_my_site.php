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
        Schema::table('my_site', function (Blueprint $table) {
            $table->timestamp('last_build_success')->nullable()->default(null);
            $table->timestamp('last_build_fail')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('my_site', function (Blueprint $table) {
            $table->dropColumn('last_build_success');
            $table->dropColumn('last_build_fail');
        });
    }
};
