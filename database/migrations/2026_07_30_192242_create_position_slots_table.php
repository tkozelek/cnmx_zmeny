<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "This position is offered on this day at this time" - independent of who, if anyone,
     * ends up placed in it.
     *
     * A slot is the drop target of the rozpis builder, and what "copy positions from another
     * day / last week" duplicates. Filling one writes `position_id`/`start_time` onto the
     * existing `assignments` row, so there is deliberately no FK back from here to an
     * assignment: the slot is the target, the assignment stays the single source of truth for
     * who actually works.
     *
     * The 2026-07-24 rework dropped the old `plan_slots`/`plan_templates` machinery in favour
     * of pure self-signup. This is the minimum that makes a day's layout copyable - no
     * headcount requirement, no template library.
     */
    public function up(): void
    {
        Schema::create('position_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('position_id');
            $table->date('date');

            /**
             * Suggested shift window, copied onto the assignment when someone is placed here.
             * Same TIME-pair caveat as `assignments`: an overnight slot has
             * end_time < start_time and is display-only.
             */
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();

            /** One slot per position per day - the same position twice in a day is a rename, not a slot. */
            $table->unique(['team_id', 'date', 'position_id']);

            // Serves the builder: one team's week of slots. Equality on team_id, range on date.
            $table->index(['team_id', 'date']);

            // Same composite FK as assignments/shifts: stops team A pairing with team B's position.
            $table->foreign(['team_id', 'position_id'])
                ->references(['team_id', 'id'])
                ->on('positions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_slots');
    }
};
