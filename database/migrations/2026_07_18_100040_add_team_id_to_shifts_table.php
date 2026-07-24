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
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['date', 'user_id']);

            $table->unsignedMediumInteger('team_id')->after('user_id');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');

            $table->unique(['date', 'user_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['date', 'user_id', 'team_id']);
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');

            $table->unique(['date', 'user_id']);
        });
    }
};
