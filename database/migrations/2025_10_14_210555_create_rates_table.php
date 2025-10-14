<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->decimal('weekday');
            $table->decimal('saturday');
            $table->decimal('sunday');
            $table->decimal('break');
            $table->unsignedMediumInteger('user_id')->unique();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
