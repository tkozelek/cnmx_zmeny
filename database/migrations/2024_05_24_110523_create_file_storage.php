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
        Schema::create('file_storage', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type');
            $table->integer('size');
            $table->boolean('is_shown');
            $table->timestamps();
            $table->unsignedMediumInteger('id_user');
            $table->unsignedMediumInteger('id_week')->nullable();

            $table->foreign('id_user')->references('id')->on('users');
            $table->foreign('id_week')->references('id')->on('weeks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_storage');
    }
};
