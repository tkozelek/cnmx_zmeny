<?php

namespace App\Services;

use App\Models\BugReport;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Images are re-encoded, which strips EXIF/GPS; PDFs are stored as uploaded. */
class BugReportAttachments
{
    public const array IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const string PDF_TYPE = 'application/pdf';

    public const int MAX_MEGAPIXELS = 25;

    private const int MAX_SIDE = 2560;

    private const int QUALITY = 85;

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{path: string, name: string, mime: string, size: int}>
     *
     * @throws ValidationException
     */
    public function store(BugReport $report, array $files): array
    {
        $stored = [];

        foreach ($files as $index => $file) {
            $stored[] = $this->storeOne($report, $file, "attachments.{$index}", $index + 1);
        }

        return $stored;
    }

    /**
     * @return array{path: string, name: string, mime: string, size: int}
     */
    private function storeOne(BugReport $report, UploadedFile $file, string $field, int $position): array
    {
        $mime = (string) $file->getMimeType();

        if ($mime === self::PDF_TYPE) {
            $extension = 'pdf';
            $contents = $file->getContent();
        } else {
            $extension = self::IMAGE_TYPES[$mime] ?? throw new RuntimeException("Nepodporovaný typ prílohy {$mime}.");
            $contents = $this->reencode($file->getRealPath(), $mime)
                ?? throw ValidationException::withMessages([
                    $field => "Príloha č. {$position} sa nedá otvoriť alebo má viac ako ".self::MAX_MEGAPIXELS.' megapixelov.',
                ]);
        }

        $path = $report->directory().'/'.Str::random(40).'.'.$extension;

        // The local disk returns false instead of throwing.
        if (! Storage::disk(BugReport::DISK)->put($path, $contents)) {
            throw new RuntimeException('Prílohu sa nepodarilo uložiť.');
        }

        return [
            'path' => $path,
            'name' => $this->displayName($file, $extension),
            'mime' => $mime,
            'size' => strlen($contents),
        ];
    }

    private function reencode(string $source, string $mime): ?string
    {
        $size = rescue(fn () => getimagesize($source), false, report: false);

        if ($size === false || self::MAX_MEGAPIXELS * 1_000_000 < $size[0] * $size[1]) {
            return null;
        }

        $image = rescue(fn () => imagecreatefromstring((string) file_get_contents($source)), false, report: false);

        if (! $image instanceof GdImage) {
            return null;
        }

        if ($mime === 'image/jpeg') {
            $image = $this->upright($image, $source);
        }

        $image = $this->fitWithin($image);
        imagesavealpha($image, true);

        ob_start();

        match ($mime) {
            'image/png' => imagepng($image, null, 6),
            'image/webp' => imagewebp($image, null, self::QUALITY),
            default => imagejpeg($image, null, self::QUALITY),
        };

        return (string) ob_get_clean();
    }

    private function upright(GdImage $image, string $source): GdImage
    {
        $orientation = (int) (rescue(fn () => exif_read_data($source), [], report: false)['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }

    private function fitWithin(GdImage $image): GdImage
    {
        imagepalettetotruecolor($image);

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = self::MAX_SIDE / max($width, $height);

        if ($scale >= 1) {
            return $image;
        }

        return imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale))) ?: $image;
    }

    private function displayName(UploadedFile $file, string $extension): string
    {
        $name = (string) preg_replace('/[\x00-\x1F\x7F"\/\\\\]+/u', '', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = trim(Str::limit($name, 80, ''));

        return ($name !== '' ? $name : 'priloha').'.'.$extension;
    }
}
