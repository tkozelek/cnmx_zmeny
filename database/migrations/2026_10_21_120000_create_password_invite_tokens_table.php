<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manager invites (valid 24h) used to share `password_reset_tokens` with forgot-password links
     * (valid 60 min), so the reset had to guess which one it was holding. A table of their own
     * keeps each link to its own lifetime.
     */
    public function up(): void
    {
        Schema::create('password_invite_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_invite_tokens');
    }
};
