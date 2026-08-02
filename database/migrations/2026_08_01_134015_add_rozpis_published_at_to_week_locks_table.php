<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Publishing is the third phase of a week: employees sign up, the manager locks and builds,
     * then the finished plan is released to everyone.
     *
     * A column on `week_locks` rather than a table of its own, for the same reason the lock is
     * a row and not a flag: a rozpis only exists for a locked week, so there is never a published
     * week without a lock row to hang the timestamp on. Unlocking deletes the row and unpublishes
     * with it, which is the behaviour we want — a reopened week is no longer final.
     */
    public function up(): void
    {
        Schema::table('week_locks', function (Blueprint $table) {
            $table->timestamp('rozpis_published_at')->nullable()->after('locked_by');
            $table->foreignId('published_by')->nullable()->after('rozpis_published_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('week_locks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn('rozpis_published_at');
        });
    }
};
