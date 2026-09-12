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

        // Signing somebody else up is an admin act, and it is what created_by records.
        $isForSomeoneElse = $request->filled('user_id')
            && (int) $request->input('user_id') !== $request->user()->id;

        if ($isForSomeoneElse && ! $request->user()->hasPermissionInTeam('assignment.create', $team)) {
            abort(403, 'Zapísať môžeš iba seba.');
        }

        // Resolved before authorising, because the policy's absence check is about the person
        // being written into the day - not about the manager doing the writing. The request has
        // already confirmed the id belongs to an approved member of this cinema.
        $target = $isForSomeoneElse
            ? $team->users()->findOrFail((int) $request->input('user_id'))
            : $request->user();

        $this->authorize('create', [Assignment::class, $team, $date, $target]);

        $userId = $target->getKey();

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
            ? ['success' => 'Úspešne ste sa zapísali na zmenu.', 'message' => 'Úspešne ste sa zapísali na zmenu.']
            : ['error' => 'V tento deň ste už zapísaný.']);
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        $assignment->delete();

        return back()->with(['success' => 'Úspešne ste sa odpísali zo zmeny.', 'message' => 'Úspešne ste sa odpísali zo zmeny.']);
    }
}
