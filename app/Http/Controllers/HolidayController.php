<?php

namespace App\Http\Controllers;

use App\Http\Requests\HolidayStoreRequest;
use App\Models\Holiday;
use App\Services\HolidayService;
use Carbon\Carbon;
use Str;

class HolidayController extends Controller
{
    public function __construct(private readonly HolidayService $holidayService) {}

    public function index()
    {
        $absences = auth()->user()->holidays()->with('user')->orderBy('date_to', 'desc')->get();
        $active = null;
        $inactive = null;

        session()->put('form_token', Str::random(40));

        if (auth()->user()->hasRole(3)) {
            $active = $this->holidayService->getAllActive();
            $inactive = $this->holidayService->getAllInactive();
        }

        return view('holiday.index', [
            'absences' => $absences,
            'active' => $active,
            'inactive' => $inactive,
        ]);
    }

    public function store(HolidayStoreRequest $request)
    {
        $formFields = $request->validated();

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
        $this->authorize('delete', $holiday);

        $holiday->delete();

        return back()->with(['message' => 'Absencia vymazaná.']);
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
}
