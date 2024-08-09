<?php

namespace App\Http\Controllers;

use App\Models\Bug;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BugReportController extends Controller
{
    protected $fileUploadService;

    /**
     * @param $fileUploadService
     */
    public function __construct(FileService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }


    public function index() {
        return view('bugreport.index', [
            'bugs' => auth()->user()->hasRole(config('constants.roles.admin')) ?
                Bug::with('user')->get() : null
        ]);
    }

    public function store(Request $request) {
        $form = $request->validate([
            'subject' => ['required', 'string'],
            'where' => ['required'],
            'description' => ['required'],
            'file' => ['sometimes', 'file', 'nullable', 'mimes:jpg,jpeg,png,bmp', 'max:2048']
        ]);

        $file = null;

        if ($request->file('file')) {
            $file = $this->fileUploadService->uploadFile($request);
        }

        Bug::create([
            'subject' => $form['subject'],
            'where' => $form['where'],
            'description' => $form['description'],
            'id_file' => $file->id ?? null,
            'id_user' => auth()->user()->id
        ]);

        return back()->with(['message' => 'Úspešne nahlásené. Ďakujeme.']);
    }

    public function destroy(Bug $bug)
    {
        $file = File::find($bug->id_file);
        if ($file) {
            if (!Storage::disk('public')->exists($file->path)) {
                abort(404, 'File not found.');
            }

            // Delete the file and the model
            Storage::disk('public')->delete($file->path);
            $bug->delete();
            $file->delete();
        } else
            $bug->delete();

        // Return a success response
        return back()->with(['message' => 'Nahlásenie úspešne zmazaný.']);
    }
}
