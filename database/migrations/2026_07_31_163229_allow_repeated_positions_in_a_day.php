<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A cinema staffs the same position several times in one day — bufet 1, bufet 2, bufet 3.
     *
     * `position_slots` originally allowed one slot per position per day, which forced a second
     * "Bufet 2" *position* into the catalogue just to get a second bufet row. That is the wrong
     * shape: two bufet rows on Friday are two slots of one position, not two positions.
     *
     * Dropping that unique key makes slots repeatable, which in turn means `assignments` can no
     * longer say which row a person occupies by `position_id` alone — three bufet slots would
     * all match. Hence `position_slot_id`: the slot a person is actually standing in.
     *
     * `position_id` stays on `assignments` and stays authoritative for history and fairness
     * scoring — it answers "what work did they do", which outlives the slot row.
     */
    public function up(): void
    {
        Schema::table('position_slots', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'date', 'position_id']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            /**
             * Unique, so a slot holds one person at the database level rather than only by
             * convention in RozpisDay::place(). Nullable columns are exempt from a unique index
             * in MySQL, so the many unplaced signups still coexist freely.
             */
            $table->foreignId('position_slot_id')
                ->nullable()
                ->after('position_id')
                ->unique()
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_slot_id');
        });

        Schema::table('position_slots', function (Blueprint $table) {
            $table->unique(['team_id', 'date', 'position_id']);
        });
    }
};
