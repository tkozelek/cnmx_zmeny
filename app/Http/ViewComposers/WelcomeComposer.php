<?php

namespace App\Http\ViewComposers;

use App\Support\SlovakDate;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

/** The welcome page's copy and its sample week. */
class WelcomeComposer
{
    public function compose(View $view): void
    {
        // A sample of the real calendar's next week (the default team week starts on Thursday).
        $weekStart = CarbonImmutable::now()->next(CarbonImmutable::THURSDAY);
        $weekEnd = $weekStart->addDays(6);

        $view->with([
            // One entry per word, so each can slide up from behind its own mask; `true` paints it in the brand color.
            'headline' => [
                ['Tvoje', false], ['zmeny', false], ["v\u{00A0}kine,", false],
                ['na', true], ['jedno', true], ['ťuknutie.', true],
            ],
            'registrationSteps' => [
                ['icon' => 'fa-user-plus', 'title' => 'Založíš si účet', 'text' => 'Vyplníš krátky formulár: meno, e-mail, heslo.'],
                ['icon' => 'fa-envelope-circle-check', 'title' => 'Potvrdíš e-mail', 'text' => 'Klikneš na odkaz, ktorý ti pošleme, aby sme vedeli, že účet je tvoj.'],
                ['icon' => 'fa-user-check', 'title' => 'Počkáš na schválenie', 'text' => 'Manažér kina ti účet potvrdí, potom sa už len prihlásiš.'],
                ['icon' => 'fa-calendar-days', 'title' => 'Zapíšeš si zmeny', 'text' => "Označíš dni, kedy môžeš pracovať, a\u{00A0}vidíš, kto ďalší je zapísaný."],
            ],
            'sampleWeekLabel' => 'Týždeň '.SlovakDate::range($weekStart, $weekEnd),
            'sampleDays' => $this->sampleDays($weekStart),
            'sampleAbsenceLabel' => SlovakDate::short($weekStart->addDays(5)).' – '.SlovakDate::short($weekEnd),
            'sceneSteps' => [
                ['title' => 'Vyberieš si dni', 'text' => 'Pri dni, kedy môžeš, ťukneš na „Zapísať sa“. Zmeníš plány? Odpíšeš sa rovnako rýchlo.'],
                ['title' => "Vidíš, kto ide s\u{00A0}tebou", 'text' => 'Pri každom dni sú mená kolegov, ktorí sú zapísaní. Celý tím vidí ten istý týždeň.'],
                ['title' => 'Absenciu nahlásiš vopred', 'text' => "Dovolenku či skúškové zapíšeš raz a\u{00A0}manažér ju uvidí priamo pri týždni."],
                ['title' => 'Týždeň sa zamkne', 'text' => "Keď je rozpis hotový, manažér týždeň zamkne. Zápisy potom platia a\u{00A0}nič sa už nepresúva."],
            ],
        ]);
    }

    /**
     * `mine` = the visitor signed up, `others` = teammates.
     *
     * @return list<array{dayName: string, date: string, mine: bool, others: list<string>}>
     */
    private function sampleDays(CarbonImmutable $weekStart): array
    {
        $signups = [
            [true, ['Lucia K.', 'Matej P.']],
            [false, ['Simona D.', 'Adam R.', 'Nina V.']],
            [true, ['Adam R.']],
            [true, ['Lucia K.', 'Peter H.']],
            [false, ['Matej P.']],
            [false, ['Nina V.']],
            [false, ['Simona D.', 'Peter H.']],
        ];

        return array_map(fn (int $i): array => [
            'dayName' => SlovakDate::dayName($weekStart->addDays($i)),
            'date' => SlovakDate::short($weekStart->addDays($i)),
            'mine' => $signups[$i][0],
            'others' => $signups[$i][1],
        ], array_keys($signups));
    }
}
