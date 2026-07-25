<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who is planned to work, on which date, in which position.
     *
     * Replaces the whole legacy `weeks` → `days` → `user_days` chain. There is no
     * Day row to create first — signup keys straight off `date`. Written both by
     * employee self-signup (created_by null) and by an admin assigning someone
     * (created_by set).
     */
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            /**
             * Nullable: employees sign up for a *day*, not for a position. The signup UI is a
             * single button, so nothing picks a position at that moment — an admin fills it in
             * afterwards when building the plan.
             *
             * The composite FK below still guarantees that a position, once set, belongs to the
             * same team. It simply is not enforced while the column is NULL, which is what
             * "not decided yet" should mean.
             */
            $table->unsignedBigInteger('position_id')->nullable();

            $table->date('date');

            /**
             * Optional shift window for display only — the plan, not the payroll.
             *
             * ponytail: TIME pair, so an overnight assignment (17:00–01:30) has
             * end_time < start_time and must be rendered as "+1 day" by the UI.
             * Fine because nothing computes duration from this table; `shifts` holds
             * the money and uses DATETIME. If assignment overlap detection ever needs
             * to be exact, promote these to DATETIME too.
             */
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->string('note', 255)->nullable();

            /** Null = the employee signed themselves up. Set = an admin assigned them. */
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            /**
             * One signup per person per day.
             *
             * This was `(user_id, date, position_id)`, which allowed the same person on two
             * different positions in one day. That only made sense while signup chose a
             * position; with a single button it does not, and a nullable `position_id` in a
             * unique index gives no protection at all — MySQL treats NULLs as distinct, so a
             * double-clicked button would insert two rows.
             *
             * If per-position assignment comes back with the Filament plan builder, this is the
             * index to widen.
             */
            $table->unique(['user_id', 'date']);

            // Serves the main screen: one team's week. Equality on team_id, range on date.
            $table->index(['team_id', 'date']);

            $table->foreign(['team_id', 'position_id'])
                ->references(['team_id', 'id'])
                ->on('positions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
