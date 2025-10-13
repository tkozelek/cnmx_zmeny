<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormInput extends Component
{
    public string $type = 'text';

    public string $name;

    public ?string $id = null;

    public ?string $label;

    public ?string $placeholder = '';

    public ?string $value = '';

    public ?string $icon = null;

    /*
     * @param string $name
     * @param string|null $id
     * @param string|null $label
     * @param string|null $placeholder
     * @param string|null $value
     * @param string|null $icon
     * @param string|null $type
     */
    public function __construct(string $name, ?string $id, ?string $label, ?string $placeholder, ?string $value, ?string $icon, ?string $type = 'text')
    {
        $this->type = $type;
        $this->name = $name;
        $this->id = $id;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->value = $value;
        $this->icon = $icon;
    }

    public function render(): View
    {
        return view('components.form-input');
    }
}
