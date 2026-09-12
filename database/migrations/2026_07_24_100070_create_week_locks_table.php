<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A row here means that week is frozen for that team - no signup, no absence
     * submission, no plan edits.
     *
     * This is all that survives of the legacy `weeks` table. Weeks are not stored:
     * they are computed from team_settings.week_start_day, so there is nothing to
     * pre-generate and no nightly command to keep future weeks in existence. A lock
     * row appears only when an admin actually locks a week, and unlocking deletes it.
     */
    public function up(): void
    {
        Schema::create('week_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();

            /** First day of the locked week, aligned to the team's week_start_day. */
            $table->date('week_start');

            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['team_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('week_locks');
    }
};
