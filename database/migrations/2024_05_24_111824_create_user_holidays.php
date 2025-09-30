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
        Schema::create('user_holidays', function (Blueprint $table) {
            $table->mediumIncrements('id')->unsigned();
            $table->string('popis', 255)->nullable();
            $table->dateTime('date_from');
            $table->dateTime('date_to');
            $table->dateTime('date_canceled')->nullable();
            $table->timestamps();
            $table->mediumInteger('id_user')->unsigned();

            $table->foreign('id_user')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_holidays');
    }
};
