<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Str;

class HolidayController extends Controller
{
    public function index()
    {
        $absences = auth()->user()->holidays()->with('user')->orderBy('date_to', 'desc')->get();
        $active = null;
        $inactive = null;

        session()->put('form_token', Str::random(40));

        if (auth()->user()->hasRole(3)) {
            $active = $this->getAllActive();
            $inactive = $this->getAllInactive();
        }

        return view('holiday.index', [
            'absences' => $absences,
            'active' => $active,
            'inactive' => $inactive,
        ]);
    }

    public function store(Request $request)
    {
        $formFields = $request->validate([
            'date_from' => 'required',
            'date_to' => 'required',
            'popis' => 'required',
            'form_token' => 'required',
        ], $this->messages());

        $sessionToken = $request->session()->get('form_token');

        if ($sessionToken !== $request->input('form_token')) {
            return back()->with('error', 'Nespravný token, obnov stránku.');
        }

        $request->session()->put('form_token', Str::random(40));
        $formFields['date_from'] = Carbon::parse($request->input('date_from'))->format('Y-m-d');
        $formFields['date_to'] = Carbon::parse($request->input('date_to'))->format('Y-m-d');

        $holiday = new Holiday($formFields);
        $holiday->id_user = auth()->user()->id;
        $holiday->save();

        return back()->with(['message' => 'Absencia vytvorená.']);
    }

    public function destroy(Holiday $holiday)
    {
        // ak neni admin - neni pristup
        if ($holiday->id_user != auth()->user()->id && ! auth()->user()->hasRole(config('constants.roles.admin'))) {
            abort(401, 'Nemáš prístup');
        }
        if (now() <= $holiday->date_to->addWeek() && ! auth()->user()->hasRole(config('constants.roles.admin'))) {
            abort(403, 'Zakázané.');
        }

        $holiday->delete();

        return back()->with(['message' => 'Vymazané.']);
    }

    public function end(Holiday $holiday)
    {
        if ($holiday->id_user != auth()->user()->id) {
            abort(401, 'Nemáš prístup');
        }

        $holiday->date_canceled = Carbon::now();
        $holiday->update();

        return back()->with(['message' => 'Absencia ukončená.']);
    }

    public function messages(): array
    {
        return [
            'popis.required' => 'Popis je potrebný.',
            'date_from.required' => 'Začiatok je potrebný.',
            'date_to.required' => 'Koniec je potrebný.',
        ];
    }

    private function getAllActive()
    {
        return Holiday::with('user')->where('date_to', '>=', Carbon::now()->format('Y-m-d'))
            ->whereNull('date_canceled')
            ->orderBy('date_to', 'desc')
            ->get();
    }

    private function getAllInactive()
    {
        return Holiday::with('user')->where('date_to', '<', Carbon::now()->format('Y-m-d'))
            ->orWhereNotNull('date_canceled')
            ->orderBy('date_to', 'desc')
            ->simplePaginate(15);
    }
}
