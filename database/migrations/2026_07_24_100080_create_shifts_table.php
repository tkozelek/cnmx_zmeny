<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hours actually worked, recorded after the fact. This is the table payroll reads —
     * `assignments` is only the plan.
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();

            // RESTRICT, not CASCADE: deleting a user must not silently erase worked-hours
            // history. Deactivate via users.is_active instead; anonymise before any real delete.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            /** The planned assignment this shift was worked against, if any. */
            $table->foreignId('assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('position_id')->nullable();

            /**
             * DATETIME, not DATE + TIME pair. A cinema shift runs 21:00 → 01:30, which
             * with a TIME pair gives end < start: every duration goes negative and the
             * post-midnight hours land on the wrong payroll day. The business date is
             * DATE(starts_at), so no separate date column is needed.
             */
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            // Blocks accidental double-entry of the same shift.
            $table->unique(['user_id', 'starts_at', 'position_id']);

            // Payroll: one user's hours over a date range.
            $table->index(['team_id', 'user_id', 'starts_at']);

            // Admin statistics: the whole team's hours over a date range. Needed as a
            // separate index — in the composite above, `user_id` is unbound for these
            // queries and sitting in the middle it stops `starts_at` being usable as a
            // range bound, so MySQL would scan every shift for the team.
            $table->index(['team_id', 'starts_at']);

            $table->foreign(['team_id', 'position_id'])
                ->references(['team_id', 'id'])
                ->on('positions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
