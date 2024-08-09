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
        Schema::create('weeks', function (Blueprint $table) {
            $table->mediumIncrements('id')->unsigned();
            $table->date('date_from');
            $table->date('date_to');
            $table->boolean('locked');
            $table->mediumInteger('next_week_id')->unsigned()->nullable();
            $table->mediumInteger('prev_week_id')->unsigned()->nullable();

            $table->foreign('next_week_id')->references('id')->on('weeks');
            $table->foreign('prev_week_id')->references('id')->on('weeks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weeks');
    }
};
