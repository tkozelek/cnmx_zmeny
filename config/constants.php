<?php

/*
 * Slovak validation messages, shared by every form request.
 *
 * The old `roles` map is gone — roles are Spatie rows now, see App\Enums\Role.
 * `calendar.generate_weeks` and `db` are gone too: weeks are computed rather than
 * generated, and the lookahead lives in team_settings.week_lookahead.
 */

return [
    'messages' => [
        'name.required' => 'Meno je potrebné.',
        'lastname.required' => 'Priezvisko je potrebné.',
        'email.required' => 'Emailová adresa je potrebná.',
        'email.email' => 'Emailová adresa nie je platná.',
        'email.unique' => 'Emailová adresa už bola použitá.',
        'password.required' => 'Heslo je potrebné.',
        'password.confirmed' => 'Heslá sa nezhodujú.',
        'password.min' => 'Heslo je príliš krátke. Musí mať aspoň 8 znakov.',
        'password_confirmation.required' => 'Potvrdenie hesla je potrebné.',
        'current_password.required' => 'Aktuálne heslo je potrebné.',
        'current_password.current_password' => 'Aktuálne heslo je nesprávne.',
        'new_password.required' => 'Nové heslo je potrebné.',
        'new_password.confirmed' => 'Heslá sa nezhodujú.',
        'new_password.min' => 'Heslo je príliš krátke. Musí mať aspoň 8 znakov.',
        'role.required' => 'Vyber rolu.',
        'role.in' => 'Neznáma rola.',
    ],
];
