<?php

namespace App\Http\Controllers;

class SettingsController extends Controller
{
    public function index()
    {
        return view('nastavenia.index');
    }

    public function editPassword()
    {
        return view('nastavenia.editPassword');
    }
}
