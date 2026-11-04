<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ChangeEmailRequest;
use App\Services\EmailChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class EmailController extends Controller
{
    public function __construct(private readonly EmailChangeService $emailChanges) {}

    public function update(ChangeEmailRequest $request): RedirectResponse
    {
        $email = $request->string('email')->value();

        // The new address gets a mail on every approved request - capped per address, so this
        // form can't be used to keep mailing somebody else's inbox.
        $sent = RateLimiter::attempt('email-change-target:'.mb_strtolower($email), 3, fn (): bool => true, 3600);

        if (! $sent) {
            throw ValidationException::withMessages(['email' => 'Na túto adresu sme už poslali priveľa odkazov. Skús to neskôr.']);
        }

        $this->emailChanges->request($request->user(), $email);

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
