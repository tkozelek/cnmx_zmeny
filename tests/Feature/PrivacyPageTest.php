<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPageTest extends TestCase
{
    use RefreshDatabase;

    /** The page must keep naming the real operator and contact, and be reachable from registration. */
    public function test_the_privacy_page_names_the_operator_and_is_linked_from_registration(): void
    {
        config(['privacy.operator.email' => 'kontakt@example.com']);

        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee(config('privacy.operator.name'))
            ->assertSee('kontakt@example.com')
            ->assertSee(config('session.cookie'));

        $this->get(route('register'))->assertSee(route('privacy'), false);
    }
}
