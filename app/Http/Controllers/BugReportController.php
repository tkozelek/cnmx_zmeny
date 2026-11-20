<?php

namespace App\Http\Controllers;

use App\Http\Requests\BugReport\StoreBugReportRequest;
use App\Models\BugReport;
use App\Models\Team;
use App\Models\User;
use App\Notifications\BugReportSubmitted;
use App\Services\BugReportAttachments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;
use Throwable;

class BugReportController extends Controller
{
    private const string THANKS_MESSAGE = 'Ďakujem, hlásenie som dostal. Ak bude treba, ozvem sa ti e-mailom.';

    private const array SECRET_ROUTE_PARAMETERS = ['token', 'hash', 'signature'];

    public function create(Request $request): View
    {
        $isMember = $request->user() !== null;
        $errors = $request->session()->get('errors', new ViewErrorBag)->getBag('default')->messages();
        $fileErrorKeys = array_values(array_filter(array_keys($errors), fn (string $key): bool => Str::startsWith($key, 'attachments.')));

        return view('bug-report.create', [
            'page' => $this->previousPage($request),
            'accountEmail' => $request->user()?->email,
            'honeypot' => StoreBugReportRequest::HONEYPOT,
            'descriptionMin' => StoreBugReportRequest::DESCRIPTION_MIN,
            'descriptionMax' => StoreBugReportRequest::DESCRIPTION_MAX,
            'maxFiles' => StoreBugReportRequest::MAX_FILES,
            'maxFileMb' => StoreBugReportRequest::MAX_FILE_MB,
            'maxTotalMb' => StoreBugReportRequest::MAX_TOTAL_MB,
            'retentionMonths' => BugReport::RETENTION_MONTHS,
            'allowsPdf' => $isMember,
            'acceptedTypes' => implode(',', [
                ...array_map(fn (string $extension): string => '.'.$extension, StoreBugReportRequest::acceptedExtensions($isMember)),
                ...StoreBugReportRequest::acceptedMimeTypes($isMember),
            ]),
            'errorFields' => array_fill_keys($fileErrorKeys, 'attachments'),
            'attachmentErrors' => Arr::flatten(Arr::only($errors, ['attachments', ...$fileErrorKeys])),
            'hasErrors' => $errors !== [],
        ]);
    }

    public function store(StoreBugReportRequest $request, BugReportAttachments $attachments): RedirectResponse
    {
        if ($request->filled(StoreBugReportRequest::HONEYPOT)) {
            return to_route('bug-report.create')->with(['message' => self::THANKS_MESSAGE]);
        }

        $report = $this->persist($request, $attachments);

        Notification::route('mail', config('privacy.operator.email'))->notify(new BugReportSubmitted($report));

        Log::info('Nahlásená chyba.', ['bug_report_id' => $report->id, 'attachments' => count($report->attachments)]);

        return to_route('bug-report.create')->with(['message' => self::THANKS_MESSAGE]);
    }

    private function persist(StoreBugReportRequest $request, BugReportAttachments $attachments): BugReport
    {
        $user = $request->user();

        $report = BugReport::create([
            'user_id' => $user?->id,
            'team_id' => $this->currentTeamId($user),
            'email' => $user !== null ? $user->email : $request->validated('email'),
            'description' => $request->validated('description'),
            'page' => $request->validated('page'),
            'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
        ]);

        try {
            $report->update(['attachments' => $attachments->store($report, $request->attachmentFiles())]);
        } catch (Throwable $e) {
            $report->delete();

            throw $e;
        }

        return $report;
    }

    private function currentTeamId(?User $user): ?int
    {
        return $user !== null && app()->bound(Team::class) ? app(Team::class)->id : null;
    }

    /** Path of the previous page with tokens masked, e.g. /zabudnute-heslo/{token}. */
    private function previousPage(Request $request): ?string
    {
        $previous = url()->previous();
        $route = rescue(fn () => app('router')->getRoutes()->match(Request::create($previous)), null, report: false);

        if ($route === null
            || parse_url($previous, PHP_URL_HOST) !== $request->getHost()
            || $route->getName() === 'bug-report.create') {
            return null;
        }

        $path = '/'.ltrim((string) parse_url($previous, PHP_URL_PATH), '/');

        foreach (Arr::only($route->parameters(), self::SECRET_ROUTE_PARAMETERS) as $name => $value) {
            $path = str_replace((string) $value, '{'.$name.'}', $path);
        }

        return $path;
    }
}
