<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();

            /**
             * Which weekday the cinema's work week starts on: 0=Mon … 6=Sun.
             * Default 3 (Thursday) matches the legacy hardcoded behaviour, but every
             * team can now pick its own. Weeks are computed from this — there is no
             * weeks table.
             *
             * ponytail: treat as immutable once a team has week_locks rows; changing it
             * re-aligns week boundaries and orphans existing locks. Add a re-align
             * command if a team ever actually needs to change it.
             */
            $table->unsignedTinyInteger('week_start_day')->default(3);

            /** How many weeks forward the employee calendar may navigate. */
            $table->unsignedTinyInteger('week_lookahead')->default(5);

            /** Hours before the first absent day that a submission is still allowed. */
            $table->unsignedSmallInteger('absence_deadline_hours')->default(48);

            $table->string('timezone', 50)->default('Europe/Bratislava');
            $table->string('locale', 10)->default('sk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_settings');
    }
};
