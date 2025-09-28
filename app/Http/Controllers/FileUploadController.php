<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Store a newly uploaded file.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,xlsx,xls,jpg,jpeg,png,gif|max:2048',
            'id_week' => 'required|integer',
        ]);

        $fileModel = $this->fileService->uploadFile($validated['file'], $request->id_week);
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();

        // if ($ext === 'xlsx' || $ext === 'xls') {
        // $this->fileService->createScreenshot($ext, $fileModel->path, $request->id_week, basename($file->getClientOriginalName(), '.'.$ext));
        // }

        return response()->json(['success' => 'Súbor úspešné nahraný.', 'status' => 200]);
    }

    /**
     * Download the specified file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function download(File $file)
    {
        // Check if the file exists
        if (! Storage::disk('public')->exists($file->path)) {
            abort(404, 'File not found.');
        }

        // Return the file as a download response
        return response()->download(storage_path('app/public/'.$file->path), $file->filename);
    }

    /**
     * Remove the specified file from storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(File $file)
    {
        if ($this->fileService->deleteFile($file)) {
            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
                'status' => 200,
            ]);
        } else {
            return response()->json(['success' => false, 'error' => 'File was not found', 'status' => 404]);
        }

    }

    public function show(File $file)
    {
        if (! Storage::disk('public')->exists($file->path)) {
            abort(404, 'File not found.');
        }
        $file->is_shown = ! $file->is_shown;
        $file->save();

        return back()->with(['message' => 'Súbor bol upravený.']);
    }
}
