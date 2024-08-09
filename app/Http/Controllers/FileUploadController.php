<?php
namespace App\Http\Controllers;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }


    /**
     * Store a newly uploaded file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,xlsx|max:2048',
            'id_week' => 'required|integer',  // Ensure id_week is also validated
        ]);

        $this->fileService->uploadFile($request);

        // Return a success response
        return response()->json(['success' => 'Súbor úspešné nahraný.']);
    }

    /**
     * Download the specified file.
     *
     * @param  \App\Models\File  $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function download(File $file)
    {
        // Check if the file exists
        if (!Storage::disk('public')->exists($file->path)) {
            abort(404, 'File not found.');
        }

        // Return the file as a download response
        return response()->download(storage_path('app/public/' . $file->path), $file->filename);
    }

    /**
     * Remove the specified file from storage.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(File $file)
    {
        if ($this->fileService->deleteFile($file))
            return response()->json(['success' => true, 'message' => 'File deleted successfully']);
        else
            return response()->json(['success' => false, 'message' => 'File was not found']);

    }

    public function show(File $file) {
        if (!Storage::disk('public')->exists($file->path)) {
            abort(404, 'File not found.');
        }
        $file->is_shown = !$file->is_shown;
        $file->save();

        return back()->with(['message' => 'Súbor bol upravený.']);
    }
}
