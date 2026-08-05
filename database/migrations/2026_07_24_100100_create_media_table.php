<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Files uploaded per week (rosters, notes, exports). Replaces `file_storage`.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();

            /** Uploader. */
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            /**
             * Which week this file belongs to, as the week's first day. Nullable for
             * files not tied to a week. No week_id - there is no weeks table.
             */
            $table->date('week_start')->nullable();

            $table->string('disk', 50)->default('local');
            $table->string('path', 500);
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');

            /** Legacy `is_shown`: whether employees see the file, not public web access. */
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['team_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
