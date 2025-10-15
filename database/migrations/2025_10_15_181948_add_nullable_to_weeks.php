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
        Schema::table('weeks', function (Blueprint $table) {
            $table->dropForeign('weeks_next_week_id_foreign');
            $table->dropForeign('weeks_prev_week_id_foreign');

            $table->foreign('next_week_id')
                ->references('id')
                ->on('weeks')
                ->nullOnDelete();

            $table->foreign('prev_week_id')
                ->references('id')
                ->on('weeks')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weeks', function (Blueprint $table) {
            //
        });
    }
};
