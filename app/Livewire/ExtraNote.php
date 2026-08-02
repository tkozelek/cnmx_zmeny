<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * The one "Extra info" field above the week grid.
 *
 * Whatever is typed here is attached to every subsequent signup, and it **stays** until the
 * user clears it - signing up for six days with "od 15:00" means typing it once.
 *
 * It lives in the session rather than being passed down as a prop to the seven DayCards. That
 * is what makes it survive week navigation and a page reload, and it means the cards do not
 * need to be re-rendered every time a character is typed.
 */
class ExtraNote extends Component
{
    /** Shared with DayCard, which reads it when writing an assignment. */
    public const SESSION_KEY = 'assignment_note';

    public const MAX_LENGTH = 255;

    public string $note = '';

    public function mount(): void
    {
        $this->note = (string) session(self::SESSION_KEY, '');
    }

    /**
     * Persist on every keystroke the front end sends. `wire:model.blur` keeps that to one
     * request per field exit rather than one per character.
     */
    public function updatedNote(string $value): void
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            $this->clear();

            return;
        }

        $this->note = mb_substr($trimmed, 0, self::MAX_LENGTH);

        session([self::SESSION_KEY => $this->note]);
    }

    public function clear(): void
    {
        $this->note = '';

        session()->forget(self::SESSION_KEY);
    }

    public function render()
    {
        return view('livewire.extra-note');
    }
}
