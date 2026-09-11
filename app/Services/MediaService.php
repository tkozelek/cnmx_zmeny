<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Week attachments. One class in place of the legacy FileService + FileUploadService.
 */
class MediaService
{
    /**
     * A **private** disk. Move to S3 by changing this constant.
     *
     * Not `public`: that disk roots at storage/app/public, which `php artisan storage:link`
     * exposes wholesale at /storage - and that command is routine on Herd and on Laravel Cloud.
     * Attachments are only ever meant to be reachable through media.download, which runs
     * MediaPolicy; a symlink would hand every file out unauthenticated and the policy would
     * never be consulted. `media.disk` was already declared `default('local')` in the schema -
     * this restores what the column always intended.
     */
    private const DISK = 'local';

    private const FOLDER = 'uploads';

    public function store(UploadedFile $file, ?string $weekStart = null): Media
    {
        $filename = now()->format('YmdHis').'_'.$file->hashName();
        $path = $file->storeAs(self::FOLDER, $filename, self::DISK);

        return Media::create([
            'user_id' => auth()->id(),
            'week_start' => $weekStart,
            'disk' => self::DISK,
            'path' => $path,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),

            // Manager-only until somebody deliberately shares it. The column used to default to
            // true, so a payroll sheet was readable by the whole cinema the moment it landed.
            'is_visible' => false,
        ]);
    }

    /**
     * Delete the row and the file behind it. A row whose file is already gone is still
     * deleted - a dangling record is worse than a missing file.
     */
    public function delete(Media $media): bool
    {
        $existed = Storage::disk($media->disk)->exists($media->path);

        if ($existed) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();

        return $existed;
    }
}
