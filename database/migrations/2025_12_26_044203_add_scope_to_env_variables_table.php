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
        Schema::table('env_variables', function (Blueprint $table) {
            // Add scoping columns
            $table->string('group_name')->nullable()->after('variable_name');
            $table->unsignedBigInteger('my_site_id')->nullable()->after('group_name');

            // Add foreign key constraint
            $table->foreign('my_site_id')
                  ->references('id')
                  ->on('my_site')
                  ->onDelete('cascade');

            // Add composite unique index to prevent duplicate variable names within the same scope
            // This replaces any existing unique constraint on variable_name alone
            $table->unique(['variable_name', 'group_name', 'my_site_id'], 'env_variables_scope_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('env_variables', function (Blueprint $table) {
            // Drop foreign key and unique index
            $table->dropForeign(['my_site_id']);
            $table->dropUnique('env_variables_scope_unique');

            // Drop columns
            $table->dropColumn(['group_name', 'my_site_id']);
        });
    }
};
