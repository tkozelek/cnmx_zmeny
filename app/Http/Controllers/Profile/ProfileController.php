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
        return $this->profile($request->user(), null);
    }

    public function show(Request $request, User $user): View
    {
        $this->authorize('view', $user);

        return $this->profile($user, $this->periodStart($request->input('date')));
    }

    private function profile(User $user, ?CarbonImmutable $since): View
    {
        $byWeekday = $this->assignmentsPerWeekday($user, $since);

        return view('profile.index', [
            'user' => $user,
            'daysCount' => array_sum($byWeekday),
            'arr' => $byWeekday,
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
        $counts = $user->assignments()
            ->when($since, fn ($query) => $query->where('date', '>=', $since->toDateString()))
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->pluck(
                DB::raw('COUNT(DISTINCT date) as count'),
                DB::raw('WEEKDAY(date) as day_of_week'),
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
