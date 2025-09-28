<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\Request;

class FileUploadService
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
        $fileModel = new File;
        $fileModel->filename = $fileName;
        $fileModel->path = $filePath;
        $fileModel->size = $fileSize;
        $fileModel->mime_type = $fileMimeType;
        $fileModel->id_user = auth()->id();
        $fileModel->id_week = $request->id_week;
        $fileModel->save();

        return $fileModel;
    }
}
