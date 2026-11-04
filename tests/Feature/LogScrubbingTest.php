<?php

namespace Tests\Feature;

use App\Logging\Fingerprint;
use App\Logging\ScrubPersonalData;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use PDOException;
use Tests\TestCase;

class LogScrubbingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['logging.default' => 'stack']);

        foreach (['stack', 'security'] as $channel) {
            config(["logging.channels.{$channel}" => [
                'driver' => 'monolog',
                'handler' => TestHandler::class,
                'tap' => [ScrubPersonalData::class],
            ]]);
        }
    }

    public function test_personal_data_and_secrets_never_reach_the_log(): void
    {
        $user = User::factory()->create(['email' => 'jana.novakova@kino.sk']);

        Log::error('Reset for jana.novakova@kino.sk from 203.0.113.7 via /zabudnute-heslo/abc123secret?signature=deadbeef', [
            'password' => 'heslo1234',
            'reason' => 'u lekára',
            'user' => $user,
        ]);

        $record = collect($this->handler('stack')->getRecords())->last();
        $written = $record->message.json_encode($record->context);

        $this->assertStringNotContainsString('jana.novakova', $written);
        $this->assertStringNotContainsString('203.0.113.7', $written);
        $this->assertStringNotContainsString('abc123secret', $written);
        $this->assertStringNotContainsString('deadbeef', $written);
        $this->assertStringNotContainsString('heslo1234', $written);
        $this->assertStringNotContainsString('u lekára', $written);
        $this->assertSame(User::class.'#'.$user->id, $record->context['user']);
    }

    public function test_a_failed_query_is_logged_without_its_bound_values(): void
    {
        $exception = new QueryException('mysql', 'insert into users (name, lastname) values (?, ?)', ['Jana', 'Nováková'], new PDOException('Data too long'));

        report($exception);

        $record = $this->handler('stack')->getRecords()[0];

        $this->assertStringContainsString('values (?, ?)', $record->message);
        $this->assertStringNotContainsString('Nováková', $record->message.json_encode($record->context));
    }

    public function test_a_failed_login_is_logged_by_fingerprint_not_address(): void
    {
        $this->post(route('login.auth'), ['email' => 'nikto@kino.sk', 'password' => 'zle-heslo']);

        $record = collect($this->handler('security')->getRecords())->firstWhere('message', 'Neúspešné prihlásenie.');

        $this->assertNotNull($record);
        $this->assertSame(Fingerprint::of('nikto@kino.sk'), $record->context['email_hash']);
        $this->assertStringNotContainsString('nikto@kino.sk', json_encode($record->toArray()));
    }

    private function handler(string $channel): TestHandler
    {
        return Log::channel($channel)->getLogger()->getHandlers()[0];
    }
}
