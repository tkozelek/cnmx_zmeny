<?php

namespace App\Traits;

use App\Enums\AbsenceStatus;
use App\Models\Absence;

/**
 * Shared "Stav" and "Akcie" column rendering for the absence data tables
 * (AbsencesDataTable, MyAbsencesDataTable) - identical in both.
 */
trait FormatsAbsenceColumns
{
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
        $user = auth()->user();
        $buttons = '';

        if ($row->isActive() && $user?->can('end', $row)) {
            $buttons .= '<button wire:click="endAbsence('.$row->id.')" wire:confirm="Deaktivovať túto absenciu? Odo dneška už nebude platiť." title="Deaktivovať absenciu" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-700 bg-neutral-800 hover:bg-neutral-700 text-neutral-100 font-semibold px-3 py-1.5 text-xs transition"><i class="fa-solid fa-power-off text-xs text-neutral-400" aria-hidden="true"></i> Deaktivovať</button>';
        }

        if ($user?->can('delete', [$row, $asManager])) {
            $buttons .= '<button wire:click="deleteAbsence('.$row->id.')" wire:confirm="Natrvalo vymazať túto absenciu?" title="Vymazať absenciu" aria-label="Vymazať absenciu" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold transition"><i class="fa-solid fa-trash text-xs" aria-hidden="true"></i></button>';
        }

        return '<div class="flex items-center justify-start gap-2">'.($buttons ?: '-').'</div>';
    }
}
