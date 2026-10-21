<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormInput extends Component
{
    public string $inputId;

    public ?string $error;

    public ?string $describedBy;

    public function __construct(
        public string $name,
        public ?string $label = null,
        public ?string $placeholder = '',
        public ?string $value = '',
        public ?string $icon = null,
        public ?string $type = 'text',
        public ?string $hint = null,
        ?string $id = null,
    ) {
        $this->type ??= 'text';
        $this->inputId = $id ?? $name;
        $this->error = session('errors')?->first($name) ?: null;
        $this->describedBy = collect([
            $hint ? "{$this->inputId}-hint" : null,
            $this->error ? "{$this->inputId}-error" : null,
        ])->filter()->implode(' ') ?: null;
    }

    public function render(): View
    {
        return view('components.form-input');
    }
}
