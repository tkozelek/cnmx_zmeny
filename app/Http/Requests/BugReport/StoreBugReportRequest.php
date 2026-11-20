<?php

namespace App\Http\Requests\BugReport;

use App\Services\BugReportAttachments;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBugReportRequest extends FormRequest
{
    /** Not `website`: browsers autofill that for real people. */
    public const string HONEYPOT = 'hp_check';

    public const int DESCRIPTION_MIN = 10;

    public const int DESCRIPTION_MAX = 5000;

    public const int MAX_FILES = 5;

    public const int MAX_FILE_MB = 5;

    public const int MAX_TOTAL_MB = 10;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $page = $this->input('page');

        $this->merge([
            'page' => is_string($page) && preg_match('#^/(?!/)#', $page) ? mb_substr($page, 0, 255) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'min:'.self::DESCRIPTION_MIN, 'max:'.self::DESCRIPTION_MAX],
            'email' => [Rule::excludeIf($this->user() !== null), 'required', 'string', 'email:rfc', 'max:255'],
            'page' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'list', 'max:'.self::MAX_FILES],
            'attachments.*' => [
                'file',
                'max:'.self::MAX_FILE_MB * 1024,
                'extensions:'.implode(',', self::acceptedExtensions($this->user() !== null)),
                'mimetypes:'.implode(',', self::acceptedMimeTypes($this->user() !== null)),
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $totalBytes = array_sum(array_map(fn (UploadedFile $file): int => (int) $file->getSize(), $this->attachmentFiles()));

                if ($totalBytes > self::MAX_TOTAL_MB * 1024 * 1024) {
                    $validator->errors()->add('attachments', 'Prílohy môžu mať spolu najviac '.self::MAX_TOTAL_MB.' MB.');
                }
            },
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function attachmentFiles(): array
    {
        return array_values($this->file('attachments', []));
    }

    /**
     * @return list<string>
     */
    public static function acceptedExtensions(bool $isMember): array
    {
        return $isMember ? [...BugReportAttachments::IMAGE_EXTENSIONS, 'pdf'] : BugReportAttachments::IMAGE_EXTENSIONS;
    }

    /**
     * @return list<string>
     */
    public static function acceptedMimeTypes(bool $isMember): array
    {
        $images = array_keys(BugReportAttachments::IMAGE_TYPES);

        return $isMember ? [...$images, BugReportAttachments::PDF_TYPE] : $images;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $allowedTypes = $this->user() !== null ? 'obrázok (JPG, PNG, WebP) alebo PDF' : 'obrázok (JPG, PNG alebo WebP)';

        return [
            ...config('constants.messages'),
            'description.required' => 'Popíš, čo sa stalo.',
            'description.min' => 'Popis je príliš krátky – napíš aspoň :min znakov.',
            'description.max' => 'Popis môže mať najviac :max znakov.',
            'attachments.max' => 'Pripojiť môžeš najviac :max súborov.',
            'attachments.*.uploaded' => 'Prílohu č. :position sa nepodarilo nahrať.',
            'attachments.*.file' => 'Prílohu č. :position sa nepodarilo nahrať.',
            'attachments.*.max' => 'Príloha č. :position je väčšia ako '.self::MAX_FILE_MB.' MB.',
            'attachments.*.extensions' => "Príloha č. :position musí byť {$allowedTypes}.",
            'attachments.*.mimetypes' => "Príloha č. :position musí byť {$allowedTypes}.",
        ];
    }
}
