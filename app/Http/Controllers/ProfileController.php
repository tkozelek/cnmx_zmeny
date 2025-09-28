<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\HolidayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProfileController extends Controller
{
    private HolidayService $holidayService;

    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    public function index()
    {
        $user = auth()->user();
        $userChartArray = $this->getUserDayCountForEachDay($user);

        return view('profile.index', [
            'user' => $user,
            'daysCount' => $this->getCountOfDaysInTime($user),
            'arr' => $userChartArray,
            'activeAbsences' => $this->holidayService->getAllActive($user),
            'inactiveAbsences' => $this->holidayService->getAllInactive($user),
        ]);
    }

    public function show(User $user, Request $request)
    {
        $date = null;
        if ($request->has('date')) {
            $date = $this->transformDate($request->input('date'));
        }

        $userChartData = $this->getUserDayStatistics($user, $date);

        return view('profile.index', [
            'user' => $user,
            'daysCount' => array_sum($userChartData),
            'arr' => $userChartData,
            'activeAbsences' => $this->holidayService->getAllActive($user),
            'inactiveAbsences' => $this->holidayService->getAllInactive($user),
        ]);
    }

    private function getUserDayStatistics(User $user, ?\Carbon\Carbon $startDate): array
    {
        $cacheKey = "user:{$user->id}:day_stats:".($startDate?->toDateString() ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $startDate) {
            $dayCounts = $user->days()
                ->when($startDate, fn ($query, $date) => $query->where('date', '>=', $date))
                ->selectRaw('WEEKDAY(date) as day_of_week, COUNT(*) as count')
                ->groupBy('day_of_week')
                ->orderBy('day_of_week')
                ->pluck('count', 'day_of_week');

            $chartArray = array_fill(0, 7, 0);
            foreach ($dayCounts as $dayOfWeek => $count) {
                $chartArray[$dayOfWeek] = $count;
            }

            return $chartArray;
        });
    }

    public function getCountOfDaysInTime(User $user, $date = null)
    {
        $cacheKey = "user:{$user->id}:days_count:".($date?->toDateString() ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $date) {
            return $date
                ? $user->days()->whereDate('date', '>=', $date->toDateString())->count()
                : $user->days()->count();
        });
    }

    private function transformDate($dateString)
    {
        return match ($dateString) {
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => null,
        };
    }
}
