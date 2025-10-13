<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * GET /api/shifts?user_id=123&month=2025-10
     * Returns shifts for the given user and month as a date-keyed map.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'month' => ['required', 'regex:/^\\d{4}-\\d{2}$/'],
        ]);

        $start = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $shifts = Shift::query()
            ->where('user_id', $data['user_id'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $result = [];
        foreach ($shifts as $shift) {
            $result[$shift->date->format('Y-m-d')] = [
                'start' => Carbon::parse($shift->start)->format('H:i'),
                'end' => Carbon::parse($shift->end)->format('H:i'),
                'breakToggle' => ! is_null($shift->break) && (int) $shift->break > 0,
                'breakTime' => isset($shift->break) ? (string) (int) $shift->break : null,
            ];
        }

        return response()->json($result);
    }

    /**
     * POST /api/shifts/bulk?user_id=123
     * Body is a JSON object keyed by date (Y-m-d) with values: { start, end, breakToggle, breakTime }
     * This upserts shifts for the given user and those dates.
     */
    public function upsert(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        if (! $userId || ! ctype_digit((string) $userId)) {
            return response()->json(['message' => 'user_id is required and must be integer'], 422);
        }

        $payload = $request->json()->all();
        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid payload, expected object keyed by dates'], 422);
        }

        // Basic validation for entries
        foreach ($payload as $date => $entry) {
            if (! is_string($date) || ! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
                return response()->json(['message' => "Invalid date key: $date"], 422);
            }
            if (! is_array($entry)) {
                return response()->json(['message' => "Invalid entry for date $date"], 422);
            }
            foreach (['start', 'end'] as $timeKey) {
                if (! isset($entry[$timeKey]) || ! preg_match('/^\\d{2}:\\d{2}$/', (string) $entry[$timeKey])) {
                    return response()->json(['message' => "Invalid $timeKey for date $date, expected HH:MM"], 422);
                }
            }
            if (isset($entry['breakToggle']) && $entry['breakToggle']) {
                if (! isset($entry['breakTime']) || ! preg_match('/^\\d+$/', (string) $entry['breakTime'])) {
                    return response()->json(['message' => "Invalid breakTime for date $date, expected integer minutes"], 422);
                }
            }
        }

        $dates = array_keys($payload);

        // Upsert each entry, but only write if new or changed
        foreach ($payload as $date => $entry) {
            $break = null;
            if (! empty($entry['breakToggle'])) {
                $break = isset($entry['breakTime']) ? (int) $entry['breakTime'] : null;
            }

            $shift = Shift::firstOrNew([
                'user_id' => (int) $userId,
                'date' => $date,
            ]);

            // Normalize values to prevent false dirty detection
            $shift->start = $entry['start'];
            $shift->end = $entry['end'];
            $shift->break = $break; // can be null or int

            if (! $shift->exists || $shift->isDirty(['start', 'end', 'break'])) {
                $shift->save();
            }
        }

        // Return the up-to-date dataset for the month of the first date provided (if any)
        $month = null;
        if (count($dates) > 0) {
            $month = substr($dates[0], 0, 7);
        } elseif ($request->has('month')) {
            $month = $request->input('month');
        }

        if ($month) {
            $request->merge(['month' => $month]);

            return $this->index($request);
        }

        return response()->json(['message' => 'Shifts saved']);
    }
}
