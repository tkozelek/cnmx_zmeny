<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('days', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->date('date');
            $table->unsignedMediumInteger('id_week');
            $table->timestamps();

            $table->foreign('id_week')->references('id')->on('weeks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('days');
    }
};
