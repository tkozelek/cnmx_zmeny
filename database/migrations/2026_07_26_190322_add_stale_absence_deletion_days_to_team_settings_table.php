<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('stale_absence_deletion_days')->default(30)->after('absence_deadline_hours');
        });
    }

    public function down(): void
    {
        Schema::table('team_settings', function (Blueprint $table) {
            $table->dropColumn('stale_absence_deletion_days');
        });
    }
};
