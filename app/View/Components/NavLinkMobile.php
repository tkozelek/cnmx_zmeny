<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NavLinkMobile extends Component
{
    public function __construct(public string $route, public bool $isMobile = false) {}

    public function render(): View
    {
        return view('components.nav-link-mobile');
    }
}
