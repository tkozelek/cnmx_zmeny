<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Html as HtmlWriter;
use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;

class FileService
{
    public function uploadFile($file, $week, $folder = 'uploads'): File
    {
        Storage::disk('public')->makeDirectory($folder);
        $fileName = now()->format('YmdHis').'_'.$file->getClientOriginalName();
        $filePath = $file->storeAs($folder, $fileName, 'public');

        // Create and save the File model
        return $this->createAndSaveTheFileModel($fileName, $filePath, $week, 0);
    }

    public function createScreenshot($extension, $filepath, $week, $filename): void
    {
        if ($extension === 'xlsx') {
            $reader = new XlsxReader;
        } elseif ($extension === 'xls') {
            $reader = new XlsReader;
        }
        $spreadsheet = $reader->load(Storage::disk('public')->path($filepath));

        $page = $spreadsheet->getActiveSheet();

        $highestDataCol = $page->getHighestDataColumn(4);

        try {
            $columnIndex = Coordinate::columnIndexFromString($highestDataCol);
        } catch (Exception $e) {

        }
        $page->removeColumnByIndex($columnIndex + 1, 7);

        $writer = new HtmlWriter($spreadsheet);
        $writer->setSheetIndex(0);
        $htmlContent = $writer->generateHtmlAll();

        $imageName = $filename.'.png';
        $imagePath = 'excel_screenshots/'.$imageName;

        Storage::disk('public')->makeDirectory('excel_screenshots');
        $fullImagePath = Storage::disk('public')->path($imagePath);

        try {
            Browsershot::html($htmlContent)
                ->noSandbox()
                ->fullPage()
                ->save($fullImagePath);
        } catch (CouldNotTakeBrowsershot $e) {
            return;
        }

        $this->createAndSaveTheFileModel($imageName, $imagePath, $week, true);
    }

    public function deleteFile(File $file): bool
    {
        if (! Storage::disk('public')->exists($file->path)) {
            return false;
        }

        // Delete the file and the model
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
