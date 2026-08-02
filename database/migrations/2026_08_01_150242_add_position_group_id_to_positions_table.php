<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which group a position is filed under, if any.
     *
     * Nullable on purpose: grouping is opt-in, and an ungrouped position sorts after the grouped
     * ones rather than being invalid. Deleting a group therefore only unfiles its positions.
     *
     * A plain single-column FK, not the composite (team_id, position_group_id) guard that
     * `positions` carries elsewhere: MySQL refuses ON DELETE SET NULL when any column in the key
     * is NOT NULL (error 1830), and nulling this pair would mean nulling `team_id`. The team
     * pairing is held instead by PositionList's team-scoped exists rule and PositionGroup's
     * global team scope — a group is only reachable through the current team to begin with.
     *
     * Written to survive a re-run: the first attempt at this migration added the column and then
     * failed on the composite constraint, so on that database the column is already here.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('positions', 'position_group_id')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->foreignId('position_group_id')->nullable()->after('color');
            });
        }

        // SQLite cannot add a foreign key to an existing table; the column stands alone there.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('positions', function (Blueprint $table) {
            $table->foreign('position_group_id')
                ->references('id')
                ->on('position_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['position_group_id']);
            }

            $table->dropColumn('position_group_id');
        });
    }
};
