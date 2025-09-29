<?php

return [
    'roles' => [
        'brigadnik' => 2,
        'admin' => 3,
        'zablokovany' => 4,
        'neovereny' => 1,
    ],
    'calendar' => [
        'generate_weeks' => 5,
    ],
    'db' => [
        'string' => 50,
        'integer' => 10,
    ],
    'messages' => [
        'name.required' => 'Meno je potrebné.',
        'lastname.required' => 'Priezvisko je potrebné.',
        'username.required' => 'Prihlásovacie meno je potrebné.',
        'username.unique' => 'Prihlásovacie meno už je obsadené.',
        'email.required' => 'Emailová adresa je potrebná.',
        'email.unique' => 'Emailová adresa už bola použitá.',
        'password.required' => 'Heslo je potrebné.',
        'password_confirmation.required' => 'Potvrdenie heslá je potrebné.',
        'password.confirmed' => 'Hesla sa nezhoduju.',
        'password.min' => 'Heslo je moc kratke. Musi mat aspon 5 znakov.',
        'new_password' => 'Nové heslo je potrebné.',
        'new_password.min' => 'Heslo je moc kratke. Musi mat aspon 5 znakov.',
        'current_password' => 'Aktuálne heslo je potrebné.',
        'new_password.confirmed' => 'Hesla sa nezhoduju.',
    ]
];
