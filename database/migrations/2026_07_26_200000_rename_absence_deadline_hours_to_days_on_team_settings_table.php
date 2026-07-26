<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('absence_deadline_days')->default(2)->after('absence_deadline_hours');
        });

        // Convert existing hour-based values instead of losing them to the column drop.
        // Plain PHP rather than a SQL CEIL() call — SQLite (used in tests) has no such function.
        foreach (DB::table('team_settings')->select('id', 'absence_deadline_hours')->get() as $row) {
            DB::table('team_settings')->where('id', $row->id)->update([
                'absence_deadline_days' => (int) ceil($row->absence_deadline_hours / 24),
            ]);
        }

        Schema::table('team_settings', function (Blueprint $table) {
            $table->dropColumn('absence_deadline_hours');
        });
    }

    public function down(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('absence_deadline_hours')->default(48)->after('week_lookahead');
        });

        DB::table('team_settings')->update([
            'absence_deadline_hours' => DB::raw('absence_deadline_days * 24'),
        ]);

        Schema::table('team_settings', function (Blueprint $table) {
            $table->dropColumn('absence_deadline_days');
        });
    }
};
