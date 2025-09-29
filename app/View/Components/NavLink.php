<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Route;

class NavLink extends Component
{
    public function __construct(
        public string $route,
        public ?string $icon = null,
        public string $activeClass = "bg-blue-700",
        public bool $isMobile = false
    ) {}

    public function render(): View
    {
        return view('components.nav-link');
    }
}
