<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailChangeService;
use Illuminate\View\View;

/**
 * Signed links from the e-mail change mails. The old inbox's link only shows buttons, because mail scanners open every link.
 */
class EmailChangeController extends Controller
{
    public function __construct(private readonly EmailChangeService $emailChanges) {}

    public function show(string $id, string $token): View
    {
        $user = $this->pendingUser($id, $token);

        return $this->page($user, $user->pending_email_approved_at ? 'approved' : null);
    }

    public function approve(string $id, string $token): View
    {
        $user = $this->pendingUser($id, $token);

        $this->emailChanges->approve($user);

        return $this->page($user, 'approved');
    }

    public function decline(string $id, string $token): View
    {
        $user = $this->pendingUser($id, $token);

        $this->emailChanges->cancel($user);

        return $this->page($user, 'declined');
    }

    /** Acts on GET like any address verification - it only works after the old inbox approved. */
    public function verify(string $id, string $token): View
    {
        $user = $this->pendingUser($id, $token);

        abort_if($user->pending_email_approved_at === null, 403, 'Zmenu ešte nepotvrdila pôvodná adresa.');

        return $this->page($user, $this->emailChanges->complete($user) ? 'completed' : 'taken');
    }

    private function pendingUser(string $id, string $token): User
    {
        $user = User::findOrFail($id);

        abort_unless($user->is_active, 403, 'Účet je zablokovaný.');
        abort_unless($this->emailChanges->isPending($user, $token), 403, 'Odkaz už neplatí – zmena bola dokončená, zrušená alebo nahradená novou.');

        return $user;
    }

    private function page(User $user, ?string $status): View
    {
        $outcome = match ($status) {
            'approved' => ['icon' => 'fa-envelope-circle-check', 'failed' => false, 'title' => 'Zmena potvrdená', 'text' => 'Ešte otvor overovací odkaz, ktorý sme poslali na novú adresu. Až potom sa začneš prihlasovať ňou.'],
            'declined' => ['icon' => 'fa-shield-halved', 'failed' => false, 'title' => 'Zmena zamietnutá', 'text' => 'E-mail účtu ostáva bez zmeny. Ak o zmene nevieš, prihlás sa a zmeň si heslo.'],
            'completed' => ['icon' => 'fa-circle-check', 'failed' => false, 'title' => 'E-mail zmenený', 'text' => "Odteraz sa prihlasuješ adresou {$user->email}."],
            'taken' => ['icon' => 'fa-circle-xmark', 'failed' => true, 'title' => 'Adresa je už obsadená', 'text' => 'Kým zmena čakala na potvrdenie, adresu začal používať iný účet. Zmenu sme zrušili – skús inú adresu.'],
            null => null,
        };

        return view('email-change.confirm', [
            'user' => $user,
            'outcome' => $outcome,
            'canStillDecline' => $status === 'approved',
        ]);
    }
}
