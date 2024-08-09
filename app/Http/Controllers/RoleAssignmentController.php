<?php

namespace App\Http\Controllers;

use App\Models\Week;
use Illuminate\Http\Request;

class RoleAssignmentController extends Controller
{
    public function show(Week $week) {
        return view('roles.index', [
            'week' => $week
        ]);
    }
}
