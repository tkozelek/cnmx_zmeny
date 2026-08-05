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
            return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30">Deaktivovaná</span>';
        }

        if ($row->isActive()) {
            return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">Aktívna</span>';
        }

        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-neutral-800 text-neutral-400 border border-neutral-700">Vypršaná</span>';
    }

    protected function formatActionsColumn(Absence $row, bool $asManager = false): string
    {
        $user = auth()->user();
        $buttons = '';

        if ($row->isActive() && $user?->can('end', $row)) {
            $buttons .= '<button wire:click="endAbsence('.$row->id.')" title="Deaktivovať absenciu" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-semibold px-3 py-1.5 text-xs shadow-sm transition"><i class="fa-solid fa-power-off text-xs"></i> Deaktivovať</button>';
        }

        if ($user?->can('delete', [$row, $asManager])) {
            $buttons .= '<button wire:click="deleteAbsence('.$row->id.')" title="Vymazať absenciu" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold shadow-sm transition"><i class="fa-solid fa-trash text-xs"></i></button>';
        }

        return '<div class="flex items-center justify-start gap-2">'.($buttons ?: '-').'</div>';
    }
}
