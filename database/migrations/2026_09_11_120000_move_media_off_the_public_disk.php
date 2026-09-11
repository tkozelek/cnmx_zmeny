<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Attachments were written to the `public` disk, which roots at storage/app/public - everything
 * `php artisan storage:link` exposes at /storage, with no authentication and no MediaPolicy
 * check. MediaService now writes to the private `local` disk; this moves what is already there,
 * so the existing files are not left behind as the one batch a future storage:link would leak.
 *
 * Rows carry their own `disk`, so an un-moved file would still download correctly - the move is
 * about closing the exposure, not about keeping downloads working.
 *
 * Also flips `is_visible` to default false. Existing rows keep whatever they have: this is about
 * what the next upload does, not about retroactively hiding files a cinema already shared.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->boolean('is_visible')->default(false)->change();
        });

        $this->moveFiles(from: 'public', to: 'local');
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->change();
        });

        $this->moveFiles(from: 'local', to: 'public');
    }

    /**
     * Copy each file across, then point the row at its new home, then drop the original.
     *
     * In that order on purpose: a row is only repointed once the bytes are readable at the
     * destination, and the source is only removed once the row no longer refers to it. A failure
     * anywhere leaves a duplicate file rather than a media row with nothing behind it.
     */
    private function moveFiles(string $from, string $to): void
    {
        $source = Storage::disk($from);
        $target = Storage::disk($to);

        DB::table('media')->where('disk', $from)->orderBy('id')->each(function (object $media) use ($source, $target, $to): void {
            if (! $source->exists($media->path)) {
                // Nothing to move; still repoint, so the row stops naming a disk we no longer write.
                DB::table('media')->where('id', $media->id)->update(['disk' => $to]);

                return;
            }

            if (! $target->exists($media->path)) {
                $stream = $source->readStream($media->path);
                $target->writeStream($media->path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            DB::table('media')->where('id', $media->id)->update(['disk' => $to]);

            $source->delete($media->path);
        });
    }
};
