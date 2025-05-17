<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileService
{
    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->storeAs('uploads', $fileName, 'public');

        // Gather file details
        $fileSize = $file->getSize();
        $fileMimeType = $file->getMimeType();

        // Create and save the File model
        $fileModel = new File();
        $fileModel->filename = $fileName;
        $fileModel->path = $filePath;
        $fileModel->size = $fileSize;
        $fileModel->mime_type = $fileMimeType;
        $fileModel->id_user = auth()->id();
        $fileModel->id_week = $request->id_week;
        $fileModel->is_shown = 1;
        $fileModel->save();

        return $fileModel;
    }

    public function deleteFile(File $file)
    {
        if (! Storage::disk('public')->exists($file->path)) {
            return false;
        }

        // Delete the file and the model
        Storage::disk('public')->delete($file->path);
        $file->delete();

        return true;
    }
}
