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
        Schema::table('user_days', function (Blueprint $table) {
            $table->dropForeign('user_days_id_user_foreign');
            $table->dropForeign('user_days_id_day_foreign');

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_day')->references('id')->on('days')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_days', function (Blueprint $table) {
            //
        });
    }
};
