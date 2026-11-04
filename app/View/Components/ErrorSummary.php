<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ErrorSummary extends Component
{
    /** @var list<array{id: string, message: string}> */
    public array $items;

    /**
     * @param  array<string, string>  $fields  error key => input id, where the two differ
     */
    public function __construct(
        public string $title = 'Formulár sa nepodarilo uložiť:',
        array $fields = [],
    ) {
        $messages = session('errors')?->getBag('default')->messages() ?? [];

        $this->items = array_map(
            fn (string $field): array => [
                'id' => $fields[$field] ?? str_replace('.', '_', $field),
                'message' => $messages[$field][0],
            ],
            array_keys($messages),
        );
    }

    public function shouldRender(): bool
    {
        return $this->items !== [];
    }

    public function render(): View
    {
        return view('components.error-summary');
    }
}
