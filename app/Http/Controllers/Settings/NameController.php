<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateNameRequest;
use Illuminate\Http\RedirectResponse;

class NameController extends Controller
{
    public function update(UpdateNameRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return to_route('settings.index')->with(['message' => 'Meno uložené.']);
    }
}
