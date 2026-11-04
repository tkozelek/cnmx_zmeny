<?php

namespace App\Http\ViewComposers;

use App\Support\SlovakDate;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class PrivacyComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'operator' => config('privacy.operator'),
            'hostingSuffix' => config('privacy.hosting') ? ' – '.config('privacy.hosting') : '',
            'mailProviderSuffix' => config('privacy.mail_provider') ? ' – '.config('privacy.mail_provider') : '',
            'mailInEu' => (bool) config('privacy.mail_in_eu'),
            'logDays' => config('logging.channels.daily.days'),
            'sessionMinutes' => config('session.lifetime'),
            'sessionCookie' => config('session.cookie'),
            'validFrom' => SlovakDate::full(CarbonImmutable::create(2026, 10, 21)),
        ]);
    }
}
