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
        \DB::table('parameters')->where('key', 'build_manager_path')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::table('parameters')->insert([
            'key' => 'build_manager_path',
            'value' => env('PATH_PROJECT', '/var/www/html') . '/build_source_manage',
            'type' => 'path',
            'description' => 'Path to the build manager application',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
