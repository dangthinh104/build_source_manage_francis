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
            $table->unique('site_name');
            $table->unique('port_pm2');
            $table->unique('path_source_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('my_site', function (Blueprint $table) {
            $table->dropUnique(['site_name']);
            $table->dropUnique(['port_pm2']);
            $table->dropUnique(['path_source_code']);
        });
    }
};
