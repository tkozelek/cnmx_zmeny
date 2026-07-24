<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Hourly pay rate per user per team. Read together with `shifts` for payroll. */
    public function up(): void
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();

            // RESTRICT for the same reason as shifts — pay history is not disposable.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->decimal('weekday', 8, 2);
            $table->decimal('saturday', 8, 2);
            $table->decimal('sunday', 8, 2);

            // Renamed from the legacy reserved-word column `break`.
            $table->decimal('break_deduction', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
