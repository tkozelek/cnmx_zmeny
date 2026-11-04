<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class NavLink extends Component
{
    public bool $isActive;

    public function __construct(
        public string $route,
        public ?string $icon = null,
        public string $activeClass = 'bg-brand-500/15 text-brand-300',
        public bool $isMobile = false
    ) {
        // "calendar.index" is also active on "calendar.show" (another week), and so on.
        $this->isActive = request()->routeIs($route, Str::beforeLast($route, '.').'.*');
    }

    public function render(): View
    {
        return view('components.nav-link');
    }
}
