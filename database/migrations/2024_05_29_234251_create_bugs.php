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
        Schema::create('bugs', function (Blueprint $table) {
            $table->mediumIncrements('id');

            $table->string('subject');
            $table->string('where');
            $table->text('description');

            $table->unsignedMediumInteger('id_user');
            $table->foreign('id_user')->references('id')->on('users');

            $table->unsignedMediumInteger('id_file')->nullable();
            $table->foreign('id_file')->references('id')->on('file_storage');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bugs');
    }
};
