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
            $table->unsignedBigInteger('position_id');
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

            // One person may work two different positions on one day, but not the
            // same position twice.
            $table->unique(['user_id', 'date', 'position_id']);

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
