<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardWithIcon extends Component
{
    public string $title;

    public string $icon;

    public string $text;

    public function __construct(string $title, string $text, string $icon)
    {
        $this->title = $title;
        $this->text = $text;
        $this->icon = $icon;
    }

    public function render(): View
    {
        return view('components.card-with-icon');
    }
}
