<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A phone-only display preference: the week as one compact list instead of seven cards. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('compact_week')->default(false)->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('compact_week');
        });
    }
};
