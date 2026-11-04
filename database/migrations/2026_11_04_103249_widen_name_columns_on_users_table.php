<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Back to the legacy app's 255: an imported name over 100 characters would otherwise abort
     * the whole legacy import in strict mode. The forms still cap new input at 100.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->string('lastname', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 100)->change();
            $table->string('lastname', 100)->change();
        });
    }
};
