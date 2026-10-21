<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/** Final state of the original rozpis migrations, now behind TeamFeature::Rozpis. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'name']);
            $table->unique(['team_id', 'id']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 10)->nullable();
            $table->string('color', 7)->nullable();
            $table->foreignId('position_group_id')->nullable()->constrained()->nullOnDelete();

            /** Marks the mandatory manager slot - a day's plan should have one. */
            $table->boolean('is_manager')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'name']);

            // Parent key for the (team_id, position_id) composite FKs on assignments and
            // position_slots, which stop a row pairing team A with team B's position.
            $table->unique(['team_id', 'id']);
        });

        Schema::create('position_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('position_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->date('date');

            /**
             * Suggested shift window, copied onto the assignment when someone is placed here. An
             * overnight slot has end_time < start_time and is display-only.
             */
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'date']);

            $table->foreign(['team_id', 'position_id'])
                ->references(['team_id', 'id'])
                ->on('positions')
                ->cascadeOnDelete();
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable()->after('user_id');

            // Unique, so a slot holds one person at the database level. Nullable columns are
            // exempt from a unique index, so the many unplaced signups still coexist freely.
            $table->foreignId('position_slot_id')->nullable()->after('position_id')
                ->unique()->constrained()->nullOnDelete();

            $table->time('start_time')->nullable()->after('date');
            $table->time('end_time')->nullable()->after('start_time');

            // Cascades: deleting a position takes its placements with it. PositionList only ever
            // deactivates positions, never deletes them.
            $table->foreign(['team_id', 'position_id'])
                ->references(['team_id', 'id'])
                ->on('positions')
                ->cascadeOnDelete();
        });

        Schema::table('team_settings', function (Blueprint $table) {
            /** Seven floats, Monday-indexed. Null = TeamSetting::DEFAULT_FAIRNESS_DAY_WEIGHTS. */
            $table->json('fairness_day_weights')->nullable()->after('week_lookahead');
            $table->unsignedTinyInteger('fairness_window_weeks')->default(12)->after('fairness_day_weights');
            $table->json('quick_times')->nullable()->after('fairness_window_weeks');
            $table->float('holiday_weight')->nullable()->after('quick_times');
        });

        Schema::table('week_locks', function (Blueprint $table) {
            $table->timestamp('rozpis_published_at')->nullable()->after('locked_by');
            $table->foreignId('published_by')->nullable()->after('rozpis_published_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });

        (new RolePermissionSeeder)->run();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');

        Schema::table('week_locks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn('rozpis_published_at');
        });

        Schema::table('team_settings', function (Blueprint $table) {
            $table->dropColumn(['fairness_day_weights', 'fairness_window_weeks', 'quick_times', 'holiday_weight']);
        });

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
};
