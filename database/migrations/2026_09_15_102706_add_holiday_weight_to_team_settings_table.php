<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How hard a Slovak public holiday (sviatok) is to staff, independent of whatever weekday
     * it happens to fall on that year - see App\Support\SlovakHolidays and
     * FairnessService::dayWeight(). Nullable so TeamSetting::DEFAULT_HOLIDAY_WEIGHT stays the
     * single place the default is written down.
     */
    public function up(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            $table->float('holiday_weight')->nullable()->after('quick_times');
        });
    }

    public function down(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            $table->dropColumn('holiday_weight');
        });
    }
};
