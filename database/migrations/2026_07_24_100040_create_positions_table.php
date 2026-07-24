<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Named work roles each cinema defines for itself (Uvádzač, Bufet, Pokladňa, RN, …).
     * Replaces the legacy free-text `user_days.popis` — this is what makes signup
     * universal instead of hardcoded per cinema.
     */
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 10)->nullable();
            $table->string('color', 7)->nullable();

            /** Marks the mandatory manager slot — a day's plan should have one. */
            $table->boolean('is_manager')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'name']);

            // Parent key for the (team_id, position_id) composite FKs on assignments
            // and shifts, which stop a row pairing team A with team B's position.
            $table->unique(['team_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
