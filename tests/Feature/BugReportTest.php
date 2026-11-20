<?php

namespace Tests\Feature;

use App\Models\BugReport;
use App\Models\User;
use App\Notifications\BugReportSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BugReportTest extends TestCase
{
    use RefreshDatabase;

    private const string PDF = "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(BugReport::DISK);
        Notification::fake();
    }

    public function test_a_guest_report_is_stored_and_mailed_with_cleaned_attachments(): void
    {
        $original = $this->jpegWithExif(3000, 1500);
        $this->assertStringContainsString('GPS-SECRET', $original);

        $this->post(route('bug-report.store'), [
            'email' => 'host@example.com',
            'description' => 'Po kliknutí na Zapísať sa sa nič nestane.',
            'page' => '/tyzdne',
            'attachments' => [
                UploadedFile::fake()->createWithContent('foto z mobilu.jpg', $original),
                UploadedFile::fake()->image('snimka.png', 800, 600),
            ],
        ])->assertRedirect(route('bug-report.create'))->assertSessionHas('message');

        $report = BugReport::sole();
        $this->assertNull($report->user_id);
        $this->assertSame('host@example.com', $report->email);
        $this->assertSame('/tyzdne', $report->page);
        $this->assertSame(['foto z mobilu.jpg', 'snimka.png'], array_column($report->attachments, 'name'));

        $photo = Storage::disk(BugReport::DISK)->get($report->attachments[0]['path']);
        $this->assertStringNotContainsString('GPS-SECRET', $photo);
        $this->assertSame(2560, getimagesizefromstring($photo)[0]);

        Notification::assertSentOnDemand(BugReportSubmitted::class, function (BugReportSubmitted $notification, array $channels, AnonymousNotifiable $notifiable) use ($report): bool {
            $mail = $notification->toMail($notifiable);

            return $notifiable->routes['mail'] === config('privacy.operator.email')
                && $notification->report->is($report)
                && $mail->replyTo === [['host@example.com', null]]
                && count($mail->rawAttachments) === 2;
        });
    }

    public function test_a_member_report_uses_the_account_and_current_cinema(): void
    {
        $team = $this->tenant();
        $member = $this->member($team);

        $this->actingAs($member)->post(route('bug-report.store'), [
            'email' => 'ignorovany@example.com',
            'description' => 'Excel export týždňa sa nestiahne.',
            'attachments' => [UploadedFile::fake()->createWithContent('export.pdf', self::PDF)],
        ])->assertSessionHasNoErrors();

        $report = BugReport::sole();
        $this->assertSame($member->id, $report->user_id);
        $this->assertSame($team->id, $report->team_id);
        $this->assertSame($member->email, $report->email);
        $this->assertSame('application/pdf', $report->attachments[0]['mime']);
    }

    public function test_an_account_without_a_cinema_can_report(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('bug-report.store'), [
            'description' => 'Po prihlásení vidím len prázdnu stránku.',
        ])->assertSessionHasNoErrors();

        $this->assertNull(BugReport::sole()->team_id);
    }

    public function test_unsafe_or_broken_attachments_are_refused(): void
    {
        $fullPng = UploadedFile::fake()->image('cela.png', 400, 400)->getContent();

        foreach ([
            UploadedFile::fake()->createWithContent('snimka.png', "MZ\x90\x00 not an image"),
            UploadedFile::fake()->createWithContent('export.pdf', self::PDF),
            UploadedFile::fake()->createWithContent('zlomena.png', substr($fullPng, 0, 60)),
        ] as $file) {
            $this->post(route('bug-report.store'), [
                'email' => 'host@example.com',
                'description' => 'Pozri si prílohu, prosím.',
                'attachments' => [$file],
            ])->assertSessionHasErrors('attachments.0');
        }

        $this->assertSame(0, BugReport::count());
        $this->assertSame([], Storage::disk(BugReport::DISK)->allFiles());
        Notification::assertNothingSent();
    }

    public function test_a_reset_token_in_the_previous_page_is_not_kept(): void
    {
        $this->withHeader('Referer', route('password.reset', ['token' => 'tajny-token-123']))
            ->get(route('bug-report.create'))
            ->assertOk()
            ->assertSee('value="/zabudnute-heslo/{token}"', false)
            ->assertDontSee('tajny-token-123');
    }

    public function test_the_mail_attaches_only_what_fits_the_size_budget(): void
    {
        $report = BugReport::factory()->create();
        $attachments = [];

        foreach (['prva.png', 'druha.png'] as $name) {
            $path = $report->directory().'/'.$name;
            Storage::disk(BugReport::DISK)->put($path, 'x');
            $attachments[] = ['path' => $path, 'name' => $name, 'mime' => 'image/png', 'size' => 10 * 1024 * 1024];
        }

        $report->update(['attachments' => $attachments]);

        $mail = (new BugReportSubmitted($report))->toMail(new AnonymousNotifiable);

        $this->assertCount(1, $mail->rawAttachments);
        $this->assertStringContainsString('druha.png', (string) $mail->render());
    }

    public function test_a_filled_honeypot_sends_nothing(): void
    {
        $this->post(route('bug-report.store'), [
            'email' => 'bot@example.com',
            'description' => 'Buy cheap watches today!!!',
            'hp_check' => 'https://spam.example',
        ])->assertRedirect(route('bug-report.create'))->assertSessionHas('message');

        $this->assertSame(0, BugReport::count());
        Notification::assertNothingSent();
    }

    public function test_markdown_in_a_description_does_not_become_a_link_in_the_mail(): void
    {
        $report = BugReport::factory()->create(['description' => "Ahoj\n[Otvor faktúru](https://zly.example/x) <b>tučne</b>"]);

        $html = (string) (new BugReportSubmitted($report))->toMail(new AnonymousNotifiable)->render();

        $this->assertStringNotContainsString('href="https://zly.example', $html);
        $this->assertStringNotContainsString('<b>tučne</b>', $html);
        $this->assertStringContainsString('[Otvor faktúru](https://zly.example/x)', $html);
    }

    public function test_old_reports_are_pruned_with_their_attachments(): void
    {
        $disk = Storage::disk(BugReport::DISK);
        $old = BugReport::factory()->create(['created_at' => now()->subMonths(7)]);
        $recent = BugReport::factory()->create();
        $disk->put($old->directory().'/a.jpg', 'x');
        $disk->put($recent->directory().'/b.jpg', 'x');
        $disk->put(BugReport::DIRECTORY.'/999/orphan.jpg', 'x');

        $this->artisan('app:prune-personal-data')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
        $this->assertSame([$recent->directory().'/b.jpg'], $disk->allFiles(BugReport::DIRECTORY));
    }

    private function jpegWithExif(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagejpeg($image);
        $jpeg = (string) ob_get_clean();

        $exif = "Exif\0\0MM\0\x2A\0\0\0\x08\0\0\0\0\0\0GPS-SECRET";

        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
    }
}
