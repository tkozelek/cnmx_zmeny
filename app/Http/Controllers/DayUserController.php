<?php

namespace App\Http\Controllers;

use App\Models\Day;
use App\Models\User;
use Illuminate\Http\Request;

class DayUserController extends Controller
{
    public function toggleUser(Request $request)
    {
        $dayId = $request->get('day');
        $day = Day::with(['users', 'week'])->find($dayId);
        $this->authorize('toggleUser', $day);

        $popis = $request->input('popis');
        $toggleResult = $day->users()->toggle([
            auth()->id() => ['popis' => $popis],
        ]);

        if (! empty($toggleResult['attached'])) {
            $message = 'User added to day';
            $status = 1;
        } else {
            $message = 'User removed from day';
            $status = 2;
        }

        return response()->json([
            'message' => $message,
            'users' => $day->fresh('users')->users,
            'status' => $status,
        ]);
    }

    public function destroy(Day $day, User $user)
    {
        $this->authorize('removeUser', [$day, $user]);

        if ($user->days()->detach($day->id)) {
            return back()->with(['message' => 'Použivateľ bol vymazaný z daného dňa.']);
        } else {
            return back()->with(['error' => 'Nastala chyba, použivateľ nebol vymazaný.']);
        }
    }
}
