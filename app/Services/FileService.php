<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileService
{
    public function uploadFile($file, $week, $folder = 'uploads'): File
    {
        Storage::disk('public')->makeDirectory($folder);
        $fileName = now()->format('YmdHis').'_'.$file->getClientOriginalName();
        $filePath = $file->storeAs($folder, $fileName, 'public');

        // Create and save the File model
        return $this->createAndSaveTheFileModel($fileName, $filePath, $week, 1);
    }

    public function deleteFile(File $file): bool
    {
        if (! Storage::disk('public')->exists($file->path)) {
            if ($file) {
                $file->delete();
            }

            return false;
        }

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return true;
    }

    /**
     * @param  $file
     */
    public function createAndSaveTheFileModel(string $fileName, mixed $filePath, $week, $visible): File
    {
        $fileModel = new File;
        $fileModel->filename = $fileName;
        $fileModel->path = $filePath;
        $fileModel->size = Storage::disk('public')->size($filePath);
        $fileModel->mime_type = Storage::disk('public')->mimeType($filePath);
        $fileModel->id_user = auth()->id();
        $fileModel->id_week = $week;
        $fileModel->is_shown = $visible;
        $fileModel->save();

        return $fileModel;
    }
}
