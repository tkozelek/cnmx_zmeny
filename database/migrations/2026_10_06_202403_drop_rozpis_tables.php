<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * The rozpis builder (positions, slots, fairness, publishing) was removed - managers keep
 * planning from the weekly Excel export. The code lives on in the
 * `feature/rozpis-position-assignment` branch.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Before anything touches `positions`: this FK cascades, so deleting a position would
        // take real signups with it.
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['team_id', 'position_id']);
            $table->dropForeign(['position_slot_id']);
            $table->dropUnique(['position_slot_id']);
        });

        // MySQL keeps the index it created to back the composite FK; SQLite never had one.
        if (Schema::hasIndex('assignments', 'assignments_team_id_position_id_foreign')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->dropIndex('assignments_team_id_position_id_foreign');
            });
        }

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['position_id', 'position_slot_id', 'start_time', 'end_time']);
        });

        Schema::dropIfExists('position_slots');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('position_groups');
        Schema::dropIfExists('activity_log');

        Schema::table('team_settings', function (Blueprint $table) {
            $table->dropColumn(['fairness_day_weights', 'fairness_window_weeks', 'quick_times', 'holiday_weight']);
        });

        Schema::table('week_locks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn('rozpis_published_at');
        });

        // The seeder no longer defines these, but syncing only detaches them from roles.
        Permission::whereIn('name', [
            'assignment.assign-position',
            'assignment.lead-shift',
            'position.view-any',
            'position.create',
            'position.update',
            'position.delete',
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // The rozpis has been removed; restore it from its branch instead.
    }
};
