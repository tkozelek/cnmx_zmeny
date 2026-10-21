<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ChangeEmailRequest;
use App\Services\EmailChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function __construct(private readonly EmailChangeService $emailChanges) {}

    public function update(ChangeEmailRequest $request): RedirectResponse
    {
        $this->emailChanges->request($request->user(), $request->string('email')->value());

        return to_route('settings.index')
            ->withFragment('email')
            ->with(['message' => 'Potvrď zmenu odkazom, ktorý sme poslali na tvoju súčasnú adresu.']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->emailChanges->cancel($request->user());

        return to_route('settings.index')
            ->withFragment('email')
            ->with(['message' => 'Zmena e-mailu zrušená.']);
    }
}
