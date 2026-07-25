<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\StoreAssignmentRequest;
use App\Models\Assignment;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

/**
 * Signing up for a day and being taken off it again.
 *
 * ponytail: plain POST/DELETE with a redirect back, not the legacy AJAX toggle that
 * returned rendered HTML. One request per intent, and the browser handles the rest.
 */
class AssignmentController extends Controller
{
    public function store(StoreAssignmentRequest $request, Team $team): RedirectResponse
    {
        $date = CarbonImmutable::parse($request->date('date'))->startOfDay();

        $this->authorize('create', [Assignment::class, $team, $date]);

        // Signing somebody else up is an admin act, and it is what created_by records.
        $isForSomeoneElse = $request->filled('user_id')
            && (int) $request->input('user_id') !== $request->user()->id;

        if ($isForSomeoneElse && ! $request->user()->hasRole('admin')) {
            abort(403, 'Zapísať môžeš iba seba.');
        }

        $userId = $isForSomeoneElse ? (int) $request->input('user_id') : $request->user()->id;

        // Keyed on (user_id, date), matching the table's unique index: one signup per person
        // per day, so a resubmitted form is idempotent rather than an integrity error.
        $assignment = Assignment::firstOrCreate(
            [
                'user_id' => $userId,
                'date' => $date->toDateString(),
            ],
            [
                'position_id' => $request->integer('position_id') ?: null,
                'note' => $request->input('note'),
                'created_by' => $isForSomeoneElse ? $request->user()->id : null,
            ],
        );

        return back()->with($assignment->wasRecentlyCreated
            ? ['message' => 'Zapísaný.']
            : ['error' => 'V tento deň si už zapísaný.']);
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        $assignment->delete();

        return back()->with(['message' => 'Odpísaný.']);
    }
}
