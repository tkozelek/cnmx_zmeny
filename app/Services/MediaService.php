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
    /** ponytail: the `public` disk, as before. Move to S3 by changing this constant. */
    private const DISK = 'public';

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
