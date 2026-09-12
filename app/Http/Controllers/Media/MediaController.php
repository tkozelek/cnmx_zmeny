<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Week attachments. Replaces the legacy FileUploadController.
 */
class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $this->authorize('create', Media::class);

        $this->media->store($request->file('file'), $request->input('week_start'));

        return back()->with(['message' => 'Súbor nahraný.']);
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        $this->media->delete($media);

        return back()->with(['message' => 'Súbor vymazaný.']);
    }

    /**
     * Streamed through the app rather than linked directly, so an invisible file cannot be
     * fetched by guessing its public URL.
     */
    public function download(Media $media): StreamedResponse
    {
        $this->authorize('download', $media);
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404, 'Súbor neexistuje.');

        return Storage::disk($media->disk)->download($media->path, $media->original_name);
    }

    public function toggleVisibility(Media $media): RedirectResponse
    {
        $this->authorize('toggleVisibility', $media);

        $media->update(['is_visible' => ! $media->is_visible]);

        return back()->with(['message' => 'Súbor bol upravený.']);
    }
}
