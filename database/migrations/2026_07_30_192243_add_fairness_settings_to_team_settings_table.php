<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How fair is fair — per cinema, not per codebase.
     *
     * Both knobs feed FairnessService, which ranks who gets offered a Friday/weekend slot
     * first in the rozpis builder. Added in one migration because they are only ever
     * reasoned about together.
     */
    public function up(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            /**
             * Seven floats, Monday-indexed (matches TeamSettingController's `weekDays`).
             * Nullable so TeamSetting::DEFAULT_FAIRNESS_DAY_WEIGHTS stays the single place
             * the default is written down — Fri/Sat/Sun weighted up, being the shifts
             * nobody volunteers for.
             */
            $table->json('fairness_day_weights')->nullable()->after('week_lookahead');

            /** How far back FairnessService counts assignments. */
            $table->unsignedTinyInteger('fairness_window_weeks')->default(12)->after('fairness_day_weights');
        });
    }

    public function down(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            $table->dropColumn(['fairness_day_weights', 'fairness_window_weeks']);
        });
    }
};
