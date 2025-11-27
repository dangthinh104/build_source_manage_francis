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
        Schema::create('my_site', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 255);
            $table->string('path_log', 2000);
            $table->string('sh_content_dir', 255);
            $table->tinyInteger('last_user_build');
            $table->timestamp('last_build')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_site');
    }
};
