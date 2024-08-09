<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index() {
        return view('nastavenia.index');
    }

    public function editPassword() {
        return view('nastavenia.editPassword');
    }
}
