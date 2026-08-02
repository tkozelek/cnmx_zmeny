<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How a cinema files its positions: Bufet, Uvádzač, Manažment.
     *
     * A table rather than a string column on `positions`, because the cinema orders these
     * itself — the printed rozpis lists bufet before uvádzač, and that order has to be editable
     * without touching every position. A free-text column would also make renaming a group an
     * update of every row that spelled it, and one typo a phantom group.
     *
     * Grouping is optional: a position with no group still works, and sorts after the grouped
     * ones. That keeps this additive for cinemas that never define a single group.
     */
    public function up(): void
    {
        Schema::create('position_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);

            /** Tens, like positions.sort_order, so a manual insert between two has room. */
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'name']);

            // Parent key for the (team_id, position_group_id) composite FK on positions, which
            // stops a row pairing team A with team B's group. Same guard as positions itself.
            $table->unique(['team_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_groups');
    }
};
