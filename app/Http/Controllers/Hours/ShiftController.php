<?php

namespace App\Http\Controllers\Hours;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hours\StoreShiftsRequest;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The JSON side of the hours screen: read a month, save a month.
 */
class ShiftController extends Controller
{
    public function __construct(private readonly ShiftService $shifts) {}

    /**
     * One month of shifts, keyed by date, in the shape the grid expects.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $user = $this->resolveUser($request, $data['user_id'] ?? null);
        [$year, $month] = array_map('intval', explode('-', $data['month']));

        $shifts = $user->shifts()
            ->inMonth($year, $month)
            ->orderBy('starts_at')
            ->get()
            ->mapWithKeys(fn (Shift $shift): array => [
                $shift->payrollDate() => [
                    'start' => $shift->starts_at->format('H:i'),
                    'end' => $shift->ends_at->format('H:i'),
                    'breakToggle' => $shift->break_minutes > 0,
                    'breakTime' => (string) $shift->break_minutes,
                    'workedMinutes' => $shift->workedMinutes(),
                ],
            ]);

        return response()->json($shifts);
    }

    /** Save a whole month at once - the form is a month grid. */
    public function store(StoreShiftsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $count = $this->shifts->syncMonth(
            $request->user(),
            (int) $validated['year'],
            (int) $validated['month'],
            $validated['shifts'] ?? [],
        );

        return response()->json(['message' => 'updated', 'count' => $count]);
    }

    /**
     * Only an admin may read someone else's hours; everyone else gets their own,
     * whatever user_id they send.
     */
    private function resolveUser(Request $request, ?int $userId): User
    {
        if (! $userId || $userId === $request->user()->id) {
            return $request->user();
        }

        $team = app(Team::class);
        abort_unless($request->user()->hasPermissionInTeam('user.view-any', $team), 403);

        return User::findOrFail($userId);
    }
}
