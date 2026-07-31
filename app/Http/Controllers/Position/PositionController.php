<?php

namespace App\Http\Controllers\Position;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\View\View;

/**
 * The position catalogue page. Every write lives in the PositionList Livewire component —
 * a second set of controller actions would be a duplicate write path onto the same table,
 * each needing its own authorization.
 */
class PositionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Position::class);

        return view('positions.index');
    }
}
