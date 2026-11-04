<?php

namespace App\Traits;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Support\SlovakDate;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * Shared column rendering for the absence data tables (AbsencesDataTable, MyAbsencesDataTable),
 * including the stacked phone layout.
 */
trait FormatsAbsenceColumns
{
    /**
     * Phones get one stacked cell per row instead of the six columns, which needed a sideways
     * scroll and left the "Deaktivovať" button out of sight at the far end. Every other column
     * is collapseOnMobile(); this one shows only below md.
     */
    protected function mobileCardColumn(bool $asManager = false): Column
    {
        return Column::make('Absencia')
            ->label(fn (Absence $row): string => $this->formatMobileCard($row, $asManager))
            ->html();
    }

    /**
     * For setThAttributes()/setTdAttributes(): the phone column is hidden from md up and may wrap.
     *
     * @return array{class?: string}
     */
    protected function mobileCardAttributes(Column $column, bool $cell): array
    {
        if ($column->getTitle() !== 'Absencia') {
            return [];
        }

        return ['class' => $cell ? 'md:hidden !whitespace-normal' : 'md:hidden'];
    }

    protected function formatMobileCard(Absence $row, bool $asManager): string
    {
        $name = $asManager
            ? '<p class="font-bold text-white">'.e(trim(($row->user->name ?? '').' '.($row->user->lastname ?? '')) ?: '-').'</p>'
            : '';
        $period = SlovakDate::full($row->date_from).' – '.($row->isOpenEnded() ? 'trvalá' : SlovakDate::full($row->date_to));
        $recurrence = $row->isRecurring()
            ? '<p class="flex items-center gap-1 text-xs font-medium text-brand-300"><i class="fa-solid fa-repeat text-[10px]" aria-hidden="true"></i> '.$row->recurrenceLabel().'</p>'
            : '';
        $reason = $row->reason ? '<p class="break-words text-neutral-300">'.e($row->reason).'</p>' : '';
        $buttons = $this->actionButtons($row, $asManager);

        return '<div class="space-y-1.5">'.$name
            .'<div class="flex flex-wrap items-center gap-x-2 gap-y-1"><span class="font-semibold text-neutral-100">'.$period.'</span>'.$this->formatStatusColumn($row).'</div>'
            .$recurrence.$reason
            .($buttons !== '' ? '<div class="flex items-center gap-2 pt-1">'.$buttons.'</div>' : '')
            .'</div>';
    }

    protected function formatEndColumn(Absence $row): string
    {
        $end = '<span class="text-neutral-300">'.($row->isOpenEnded() ? 'Trvalá' : SlovakDate::full($row->date_to)).'</span>';

        if (! $row->isRecurring()) {
            return $end;
        }

        return $end.'<span class="mt-0.5 flex items-center gap-1 text-xs font-medium text-brand-300"><i class="fa-solid fa-repeat text-[10px]" aria-hidden="true"></i> '.$row->recurrenceLabel().'</span>';
    }

    protected function formatStatusColumn(Absence $row): string
    {
        if ($row->status === AbsenceStatus::Cancelled) {
            return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-neutral-800 text-neutral-400 border border-neutral-700">Deaktivovaná</span>';
        }

        if ($row->isActive()) {
            return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-brand-500/10 text-brand-300 border border-brand-500/30">Aktívna</span>';
        }

        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-neutral-800 text-neutral-400 border border-neutral-700">Vypršaná</span>';
    }

    protected function formatActionsColumn(Absence $row, bool $asManager = false): string
    {
        return '<div class="flex items-center justify-start gap-2">'.($this->actionButtons($row, $asManager) ?: '-').'</div>';
    }

    private function actionButtons(Absence $row, bool $asManager): string
    {
        $user = auth()->user();
        $buttons = '';
        // Several rows carry the same buttons - the name says which absence each one acts on.
        $range = e(SlovakDate::full($row->date_from).' – '.($row->isOpenEnded() ? 'trvalá' : SlovakDate::full($row->date_to)));

        if ($row->isActive() && $user?->can('end', $row)) {
            $buttons .= '<button wire:click="endAbsence('.$row->id.')" wire:confirm="Deaktivovať túto absenciu? Odo dneška už nebude platiť." title="Deaktivovať absenciu" aria-label="Deaktivovať absenciu '.$range.'" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-700 bg-neutral-800 hover:bg-neutral-700 text-neutral-100 font-semibold px-3 py-1.5 text-xs transition"><i class="fa-solid fa-power-off text-xs text-neutral-400" aria-hidden="true"></i> Deaktivovať</button>';
        }

        if ($user?->can('delete', [$row, $asManager])) {
            $buttons .= '<button wire:click="deleteAbsence('.$row->id.')" wire:confirm="Natrvalo vymazať túto absenciu?" title="Vymazať absenciu" aria-label="Vymazať absenciu '.$range.'" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold transition"><i class="fa-solid fa-trash text-xs" aria-hidden="true"></i></button>';
        }

        return $buttons;
    }
}
