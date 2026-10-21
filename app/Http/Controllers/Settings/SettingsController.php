<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('nastavenia.index', ['user' => $request->user()]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $request->user()->update(['compact_week' => $request->boolean('compact_week')]);

        return to_route('settings.index')
            ->withFragment('zobrazenie')
            ->with(['message' => 'Nastavenia uložené.']);
    }
}
