<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When an employee cannot work. Replaces `user_holidays`.
     *
     * Three columns cover all three cases that _planning/15-absence-system.md
     * modelled with a type enum plus seven date columns:
     *
     *   single day  → date_from == date_to,        day_of_week null
     *   multi-day   → range,                       day_of_week null
     *   recurring   → range + day_of_week set      ("every Tuesday, from X until Y")
     */
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('date_from');

            /**
             * ponytail: NOT NULL on purpose. An open-ended recurring absence writes
             * the Absence::FOREVER sentinel (9999-12-31) rather than null, so the
             * overlap query stays a plain sargable range scan instead of needing
             * "OR date_to IS NULL", which cannot use the index below.
             */
            $table->date('date_to');

            /** 0=Mon … 6=Sun. Set = this is a weekly recurring pattern. */
            $table->unsignedTinyInteger('day_of_week')->nullable();

            $table->string('reason', 500)->nullable();
            $table->timestamps();

            // "Who is away in this range for this team" - equality on team_id, then
            // both range bounds. user_id must NOT sit in the middle here: it is
            // unbound in that query and would break date_from as a range bound.
            $table->index(['team_id', 'date_from', 'date_to']);

            // "My own absences" for the employee's list.
            $table->index(['user_id', 'date_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
