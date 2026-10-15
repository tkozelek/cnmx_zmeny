<?php

/*
| Facts the privacy page (/ochrana-osobnych-udajov) states about who runs the app and with whom.
| Leave a provider empty and the page names only its category - never guess one.
*/

return [
    'operator' => [
        'name' => env('PRIVACY_OPERATOR_NAME', 'Tomáš Kozelek'),
        'email' => env('PRIVACY_OPERATOR_EMAIL', 't.kozelek152@gmail.com'),
    ],

    'hosting' => env('PRIVACY_HOSTING', 'HostCreators – WebCreators, s.r.o., Banská Bystrica'),

    // Who sends the app's e-mails. Leave empty until confirmed - the page then names only the category.
    'mail_provider' => env('PRIVACY_MAIL_PROVIDER'),

    // True only once the mail provider above is confirmed to send from the EU.
    'mail_in_eu' => (bool) env('PRIVACY_MAIL_IN_EU', false),
];
