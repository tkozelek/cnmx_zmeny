<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\RozpisDay;
use App\Models\Assignment;
use App\Models\Position;
use App\Models\PositionSlot;
use App\Models\Team;
use App\Models\User;
use App\Services\AiRozpisSuggestionService;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Resources\GenerativeModel;
use Gemini\Responses\GenerativeModel\GenerateContentResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * The AI layer is a draft overlay, and the checking is the feature - so these tests are about
 * what leaves the app and what is refused on the way back in, not about suggestion quality.
 *
 * Every request is faked: a test suite must never make a billed API call.
 */
class AiRozpisSuggestionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The service salts each request with Str::random(), which Laravel lets a test freeze. With
     * the salt fixed, a test can compute the same token the service will send and reply with it.
     */
    private const string SALT = 'test-salt';

    protected function setUp(): void
    {
        parent::setUp();

        config(['gemini.api_key' => 'test-key']);

        Str::createRandomStringsUsing(fn (): string => self::SALT);
    }

    protected function tearDown(): void
    {
        Str::createRandomStringsNormally();

        parent::tearDown();
    }

    /** The privacy promise, asserted against the actual outbound payload. */
    public function test_the_outbound_payload_carries_no_personal_data(): void
    {
        $team = $this->tenant();
        $employee = User::factory()->memberOf($team, Role::Employee->value)->create([
            'name' => 'Jana',
            'lastname' => 'Nováková',
            'email' => 'jana@kino.test',
        ]);

        $date = $this->workday($team);
        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $this->signUp($team, $employee, $date);

        $this->fakeReply([['token' => $this->tokenFor($employee), 'slot_id' => $slot->id]]);

        app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date));

        Gemini::assertSent(GenerativeModel::class, callback: function (string $method, array $args) use ($employee): bool {
            $sent = $args[0];

            $this->assertSame('generateContent', $method);
            $this->assertStringNotContainsString('Jana', $sent);
            $this->assertStringNotContainsString('Nováková', $sent);
            $this->assertStringNotContainsString('jana@kino.test', $sent);

            // The hash goes instead of anything identifying.
            $this->assertStringContainsString($this->tokenFor($employee), $sent);
            $this->assertStringNotContainsString('user_id', $sent);

            return true;
        });
    }

    public function test_a_valid_reply_becomes_a_suggestion(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $date = $this->workday($team);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $assignment = $this->signUp($team, $employee, $date);

        $this->fakeReply([['token' => $this->tokenFor($employee), 'slot_id' => $slot->id]]);

        $this->assertSame(
            [['assignment_id' => $assignment->id, 'slot_id' => $slot->id]],
            app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date)),
        );
    }

    /** An unknown token or position is dropped rather than guessed at. */
    public function test_tokens_and_positions_outside_this_request_are_dropped(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $date = $this->workday($team);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $this->signUp($team, $employee, $date);

        $this->fakeReply([
            ['token' => 'deadbeefdeadbeef', 'slot_id' => $slot->id],
            ['token' => $this->tokenFor($employee), 'slot_id' => 987654],
        ]);

        $this->assertSame([], app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date)));
    }

    /**
     * A token is only ever resolved through this request's own map, so a hash built with any
     * other salt - which is what every other request uses - means nothing here.
     */
    public function test_a_token_from_another_request_is_dropped(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $date = $this->workday($team);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $this->signUp($team, $employee, $date);

        $stale = substr(hash('sha256', 'some-other-salt|'.$employee->id), 0, 16);

        $this->fakeReply([['token' => $stale, 'slot_id' => $slot->id]]);

        $this->assertSame([], app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date)));
    }

    /**
     * The signed-up invariant, enforced server-side rather than merely requested in the prompt:
     * a real user with no signup for this date can never be suggested.
     */
    public function test_a_real_user_without_a_signup_for_that_date_is_dropped(): void
    {
        $team = $this->tenant();
        $signedUp = $this->member($team);
        $notSignedUp = $this->member($team);
        $date = $this->workday($team);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $otherSlot = PositionSlot::factory()->forPosition($position)->on($date)->create();

        $this->signUp($team, $signedUp, $date);
        // This one signed up for a different day entirely.
        $this->signUp($team, $notSignedUp, CarbonImmutable::parse($date)->addDay()->toDateString());

        $this->fakeReply([
            ['token' => $this->tokenFor($signedUp), 'slot_id' => $slot->id],
            ['token' => $this->tokenFor($notSignedUp), 'slot_id' => $otherSlot->id],
        ]);

        $suggestions = app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date));

        $this->assertCount(1, $suggestions, 'Only the person who signed up for this date may be suggested.');
    }

    public function test_a_token_used_twice_is_only_honoured_once(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $date = $this->workday($team);

        // Two bufet rows on one day: the same position, two independent slots.
        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $otherSlot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $this->signUp($team, $employee, $date);

        $this->fakeReply([
            ['token' => $this->tokenFor($employee), 'slot_id' => $slot->id],
            ['token' => $this->tokenFor($employee), 'slot_id' => $otherSlot->id],
        ]);

        $this->assertCount(1, app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date)));
    }

    /**
     * Nobody sits out for having a low score: once the people with the strongest claim are
     * placed, the leftover rows still get filled rather than being left empty.
     */
    public function test_everyone_offered_a_leftover_slot_is_kept(): void
    {
        $team = $this->tenant();
        $date = $this->workday($team);

        // Three bufet rows: one position, three slots - exactly the case that used to need
        // three numbered positions in the catalogue.
        $position = Position::factory()->create(['team_id' => $team->id, 'name' => 'Bufet']);

        $slots = collect(range(1, 3))->map(
            fn (): PositionSlot => PositionSlot::factory()->forPosition($position)->on($date)->create()
        )->values();

        // Three people, none of whom has any history - every priorityScore is 0.
        $people = collect(range(1, 3))->map(function () use ($team, $date): User {
            $user = $this->member($team);
            $this->signUp($team, $user, $date);

            return $user;
        })->values();

        $this->fakeReply(
            $people->map(fn (User $user, int $i): array => [
                'token' => $this->tokenFor($user),
                'slot_id' => $slots[$i]->id,
            ])->all()
        );

        $this->assertCount(3, app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date)));
    }

    /** A blocked or non-JSON reply is not a crash, just no suggestion. */
    public function test_a_malformed_reply_yields_no_suggestion(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $date = $this->workday($team);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $this->signUp($team, $employee, $date);

        Gemini::fake([$this->reply('I am afraid I cannot do that.')]);

        $this->assertSame([], app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date)));
    }

    public function test_a_transport_failure_yields_no_suggestion(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $date = $this->workday($team);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $this->signUp($team, $employee, $date);

        Gemini::fake([new RuntimeException('Gemini is unreachable.')]);

        $this->assertSame([], app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date)));
    }

    public function test_no_request_is_made_without_an_api_key(): void
    {
        config(['gemini.api_key' => null]);

        $team = $this->tenant();
        $employee = $this->member($team);
        $date = $this->workday($team);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $this->signUp($team, $employee, $date);

        Gemini::fake();

        $this->assertSame([], app(AiRozpisSuggestionService::class)->suggest($team, CarbonImmutable::parse($date)));
        Gemini::assertNothingSent();
    }

    /**
     * Asking is itself a manager action on a locked week - the billed call must not even be
     * attempted by someone who could not act on the answer.
     */
    public function test_asking_for_a_suggestion_is_denied_on_an_unlocked_week(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);
        $date = $this->workday($team);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();
        $assignment = $this->signUp($team, $employee, $date);

        $this->fakeReply([['token' => $this->tokenFor($employee), 'slot_id' => $slot->id]]);

        // The week is deliberately left unlocked.
        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('suggest')
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'position_id' => null]);
        Gemini::assertNothingSent();
    }

    public function test_a_manager_accepts_a_suggestion_through_the_normal_write_path(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create(['start_time' => '17:00:00']);
        $assignment = $this->signUp($team, $employee, $date);

        $this->fakeReply([['token' => $this->tokenFor($employee), 'slot_id' => $slot->id]]);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('suggest')
            // Still a draft at this point - nothing is written until it is accepted.
            ->assertSet('suggestions', [['assignment_id' => $assignment->id, 'slot_id' => $slot->id]])
            ->call('acceptSuggestion', $assignment->id)
            ->assertSet('suggestions', []);

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'position_id' => $position->id,
            'position_slot_id' => $slot->id,
            'start_time' => '17:00:00',
        ]);
    }

    /** The token the service will send for this user, given the frozen salt. */
    private function tokenFor(User $user): string
    {
        return substr(hash('sha256', self::SALT.'|'.$user->id), 0, 16);
    }

    /**
     * @param  list<array{token: string, slot_id: int}>  $placements
     */
    private function fakeReply(array $placements): void
    {
        Gemini::fake([$this->reply(json_encode(['placements' => $placements], JSON_THROW_ON_ERROR))]);
    }

    private function reply(string $text): GenerateContentResponse
    {
        return GenerateContentResponse::fake([
            'candidates' => [
                ['content' => ['parts' => [['text' => $text]]]],
            ],
        ]);
    }

    private function workday(Team $team): string
    {
        return app(WeekService::class)->start($team, CarbonImmutable::now())->addDay()->toDateString();
    }

    private function lockWeekOf(Team $team, string $date): void
    {
        $team->weekLocks()->create([
            'week_start' => app(WeekService::class)->start($team, CarbonImmutable::parse($date))->toDateString(),
        ]);
    }

    private function signUp(Team $team, User $user, string $date): Assignment
    {
        return Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'position_id' => null,
            'date' => $date,
        ]);
    }
}
