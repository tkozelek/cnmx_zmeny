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
        Schema::table('file_storage', function (Blueprint $table) {
            $table->dropForeign('file_storage_id_user_foreign');
            $table->dropForeign('file_storage_id_week_foreign');

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_week')->references('id')->on('weeks')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_storage', function (Blueprint $table) {
            //
        });
    }
};
