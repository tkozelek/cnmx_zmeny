<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unlocking a week used to delete its row, which also silently unpublished its rozpis. The
     * row now outlives an unlock when the week is published, so locking needs its own flag.
     */
    public function up(): void
    {
        Schema::table('week_locks', function (Blueprint $table) {
            $table->boolean('is_locked')->default(true)->after('week_start');
        });
    }

    public function down(): void
    {
        Schema::table('week_locks', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
};
