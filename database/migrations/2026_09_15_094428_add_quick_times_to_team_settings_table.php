<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-cinema list of "H:i" shift-start times offered as quick-pick chips in the rozpis
     * builder's time picker (see resources/js/app.js). Nullable so
     * TeamSetting::DEFAULT_QUICK_TIMES stays the single place the default list is written down.
     */
    public function up(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            $table->json('quick_times')->nullable()->after('fairness_window_weeks');
        });
    }

    public function down(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            $table->dropColumn('quick_times');
        });
    }
};
