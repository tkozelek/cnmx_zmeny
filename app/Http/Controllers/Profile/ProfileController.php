<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AbsenceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Per-person statistics: which weekdays someone works, and their absence history.
 */
class ProfileController extends Controller
{
    public function __construct(private readonly AbsenceService $absences) {}

    public function index(Request $request): View
    {
        return $this->profile($request, $request->user());
    }

    public function show(Request $request, User $user): View
    {
        $this->authorize('view', $user);

        return $this->profile($request, $user);
    }

    private function profile(Request $request, User $user): View
    {
        $user->loadMissing(['roles', 'teams']);

        // Track external origin for the "Späť" button so browsing the profile never overrides where the user came from
        $referrer = (string) $request->headers->get('referer', '');
        $currentHost = $request->getHost();

        $isInternal = empty($referrer)
            || ! str_contains($referrer, $currentHost)
            || str_contains($referrer, '/profil')
            || str_contains($referrer, '/login')
            || str_contains($referrer, '/heslo');

        if (! $isInternal) {
            session(['profile_origin_url' => $referrer]);
        }

        $backUrl = session('profile_origin_url');
        if (! $backUrl || str_contains($backUrl, '/profil')) {
            $backUrl = (auth()->id() !== $user->id && auth()->user()->can('viewAny', User::class))
                ? route('admin.users.index')
                : route('calendar.index');
        }

        $weekdays = ['Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota', 'Nedeľa'];

        $allData = $this->assignmentsPerWeekday($user, null);
        $monthData = $this->assignmentsPerWeekday($user, CarbonImmutable::now()->subMonth());
        $yearData = $this->assignmentsPerWeekday($user, CarbonImmutable::now()->subYear());

        $periods = [
            'all' => [
                'counts' => $allData,
                'total' => array_sum($allData),
                'max' => max($allData),
                'mostActiveDay' => max($allData) > 0 ? $weekdays[array_search(max($allData), $allData)] : '—',
                'label' => 'Za celé obdobie',
            ],
            'month' => [
                'counts' => $monthData,
                'total' => array_sum($monthData),
                'max' => max($monthData),
                'mostActiveDay' => max($monthData) > 0 ? $weekdays[array_search(max($monthData), $monthData)] : '—',
                'label' => 'Za posledný mesiac',
            ],
            'year' => [
                'counts' => $yearData,
                'total' => array_sum($yearData),
                'max' => max($yearData),
                'mostActiveDay' => max($yearData) > 0 ? $weekdays[array_search(max($yearData), $yearData)] : '—',
                'label' => 'Za posledný rok',
            ],
        ];

        $initialPeriod = (string) $request->input('date', 'all');
        if (! array_key_exists($initialPeriod, $periods)) {
            $initialPeriod = 'all';
        }

        return view('profile.index', [
            'user' => $user,
            'periods' => $periods,
            'activePeriod' => $initialPeriod,
            'daysCount' => $periods[$initialPeriod]['total'],
            'arr' => $periods[$initialPeriod]['counts'],
            'weekdays' => $weekdays,
            'mostActiveDay' => $periods[$initialPeriod]['mostActiveDay'],
            'backUrl' => $backUrl,
            'activeAbsences' => $this->absences->activeFor($user),
            'inactiveAbsences' => $this->absences->pastFor($user),
        ]);
    }

    /**
     * How many days this person worked on each weekday, Monday first.
     *
     * Counts distinct dates, not assignment rows: working two positions on one Friday is
     * still one Friday. MySQL's WEEKDAY() is already 0=Mon..6=Sun.
     *
     * @return array<int, int>
     */
    private function assignmentsPerWeekday(User $user, ?CarbonImmutable $since): array
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $dayOfWeekExpr = $isSqlite
            ? "((CAST(strftime('%w', date) AS INTEGER) + 6) % 7)"
            : 'WEEKDAY(date)';

        $counts = $user->assignments()
            ->when($since, fn ($query) => $query->where('date', '>=', $since->toDateString()))
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->pluck(
                DB::raw('COUNT(DISTINCT date) as count'),
                DB::raw("{$dayOfWeekExpr} as day_of_week"),
            );

        return array_replace(array_fill(0, 7, 0), $counts->all());
    }

    private function periodStart(?string $period): ?CarbonImmutable
    {
        return match ($period) {
            'month' => CarbonImmutable::now()->subMonth(),
            'year' => CarbonImmutable::now()->subYear(),
            default => null,
        };
    }
}
