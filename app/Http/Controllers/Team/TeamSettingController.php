<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\UpdateTeamSettingRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamSettingController extends Controller
{
    public function edit(Request $request): View
    {
        $team = app(Team::class);
        $this->authorize('viewSettings', $team);

        $canEdit = $request->user()->can('updateSettings', $team);
        $settings = $team->settings()->firstOrCreate([]);

        return view('team.settings', [
            'team' => $team,
            'settings' => $settings,
            'canEdit' => $canEdit,
            'weekDays' => [
                0 => 'Pondelok',
                1 => 'Utorok',
                2 => 'Streda',
                3 => 'Štvrtok',
                4 => 'Piatok',
                5 => 'Sobota',
                6 => 'Nedeľa',
            ],
        ]);
    }

    public function update(UpdateTeamSettingRequest $request): RedirectResponse
    {
        $team = app(Team::class);
        $data = $request->validated();

        $team->update(['name' => $data['name']]);
        $team->settings()->updateOrCreate([], $data);

        return back()->with('message', 'Nastavenia kina boli úspešne uložené.');
    }
}
