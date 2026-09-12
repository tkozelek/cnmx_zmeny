<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('rates');
    }

    public function down(): void
    {
        // Evidencia hodín has been permanently removed.
    }
};
