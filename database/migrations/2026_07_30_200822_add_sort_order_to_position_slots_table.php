<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-day ordering for the rozpis rows.
     *
     * `positions.sort_order` is the cinema-wide default order, so dragging Thursday's rows into a
     * new order would silently reorder every other day too. A day's layout is its own thing - the
     * reference schedule lists positions in a different order on Thursday than on Friday - so the
     * order belongs on the slot.
     *
     * Existing rows default to 0, which keeps them on the position's default order until somebody
     * drags them.
     */
    public function up(): void
    {
        Schema::table('position_slots', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('position_id');
        });
    }

    public function down(): void
    {
        Schema::table('position_slots', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
