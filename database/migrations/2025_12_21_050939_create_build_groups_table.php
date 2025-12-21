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
        Schema::create('build_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('build_group_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('build_group_id')->constrained('build_groups')->onDelete('cascade');
            $table->foreignId('my_site_id')->constrained('my_site')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['build_group_id', 'my_site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('build_group_sites');
        Schema::dropIfExists('build_groups');
    }
};
