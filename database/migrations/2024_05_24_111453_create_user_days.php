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
        Schema::create('user_days', function (Blueprint $table) {
            $table->mediumIncrements('id')->unsigned();
            $table->string('popis', config('constants.db.string'))->nullable();
            $table->mediumInteger('id_user')->unsigned();
            $table->mediumInteger('id_day')->unsigned();

            $table->foreign('id_user')->references('id')->on('users');
            $table->foreign('id_day')->references('id')->on('days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_days');
    }
};
