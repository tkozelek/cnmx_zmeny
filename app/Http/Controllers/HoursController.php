<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HoursController extends Controller
{
    public function index()
    {
        return view('hours.index');
    }

    public function store(Request $request) {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'workData' => 'required|array'
        ]);

        dd($request->all());
    }
}
