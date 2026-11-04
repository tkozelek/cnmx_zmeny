<?php

namespace App\Http\Controllers\Team;

use App\Enums\TeamFeature;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\UpdateTeamSettingRequest;
use App\Models\Team;
use App\Services\SignupRateService;
use App\Support\SlovakPlural;
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
        $weekDays = [
            0 => 'Pondelok',
            1 => 'Utorok',
            2 => 'Streda',
            3 => 'Štvrtok',
            4 => 'Piatok',
            5 => 'Sobota',
            6 => 'Nedeľa',
        ];
        $weights = $team->fairnessDayWeights();

        return view('team.settings', [
            'team' => $team,
            'settings' => $settings,
            'canEdit' => $canEdit,
            'weekDays' => $weekDays,
            'hasRozpis' => $team->hasFeature(TeamFeature::Rozpis),
            'fairnessDays' => collect($weekDays)->map(fn (string $name, int $index): array => [
                'index' => $index,
                'name' => $name,
                'short' => mb_substr($name, 0, 3).'.',
                'weight' => $weights[$index],
            ])->values(),
            'fairnessWindowWeeks' => $team->fairnessWindowWeeks(),
            'holidayWeight' => $team->holidayWeight(),
            'quickTimes' => $team->quickTimes(),
            ...$this->occupancy($team),
        ]);
    }

    /**
     * @return array{occupancyMembers: int, occupancyMembersLabel: string, occupancyWeeks: int, occupancyChart: array{rates: list<float>, labels: list<string>}, occupancy: list<array{short: string, name: string, rate: float, label: string}>}
     */
    private function occupancy(Team $team): array
    {
        $service = app(SignupRateService::class);
        $members = $service->memberCounts([$team->getKey()]);
        $rates = $service->byWeekday($members)[$team->getKey()] ?? array_fill(0, 7, 0.0);
        $names = ['Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota', 'Nedeľa'];

        $memberCount = $members[$team->getKey()] ?? 0;

        return [
            'occupancyMembers' => $memberCount,
            'occupancyMembersLabel' => SlovakPlural::of($memberCount, 'člena', 'členov', 'členov'),
            'occupancyWeeks' => SignupRateService::WEEKS,
            'occupancyChart' => ['rates' => $rates, 'labels' => $names],
            'occupancy' => array_map(fn (int $i): array => [
                'short' => mb_substr($names[$i], 0, 2),
                'name' => $names[$i],
                'rate' => $rates[$i],
                'label' => number_format($rates[$i], 0, ',', ' ').' %',
            ], range(0, 6)),
        ];
    }

    public function update(UpdateTeamSettingRequest $request): RedirectResponse
    {
        $team = app(Team::class);
        $data = $request->validated();

        // Removing every row in the editor submits no quick_times[] input at all, which would
        // otherwise leave a previously-saved list untouched instead of clearing it back to
        // TeamSetting::DEFAULT_QUICK_TIMES (see Team::quickTimes()'s empty-array fallback).
        if ($team->hasFeature(TeamFeature::Rozpis)) {
            $data['quick_times'] ??= [];
        }

        $team->update(['name' => $data['name']]);
        $team->settings()->updateOrCreate([], $data);

        return back()->with('message', 'Nastavenia kina boli úspešne uložené.');
    }
}
