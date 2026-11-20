<?php

namespace Tests\Feature;

use App\Models\BugReport;
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

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(BugReport::DISK);
        Notification::fake();
    }

    public function test_a_guest_report_is_stored_and_mailed_with_cleaned_attachments(): void
    {
        $this->withHeader('Referer', route('password.reset', ['token' => 'tajny-token-123']))
            ->get(route('bug-report.create'))
            ->assertSee('value="/zabudnute-heslo/{token}"', false)
            ->assertDontSee('tajny-token-123');

        $photo = $this->jpegWithExif(3000, 1500);
        $this->assertStringContainsString('GPS-SECRET', $photo);

        $this->post(route('bug-report.store'), [
            'email' => 'host@example.com',
            'description' => 'Nefunguje [Otvor faktúru](https://zly.example/x) <b>tučne</b>',
            'page' => '/zabudnute-heslo/{token}',
            'attachments' => [UploadedFile::fake()->createWithContent('foto z mobilu.jpg', $photo)],
        ])->assertRedirect(route('bug-report.create'))->assertSessionHas('message');

        $report = BugReport::sole();
        $stored = Storage::disk(BugReport::DISK)->get($report->attachments[0]['path']);
        $this->assertSame('host@example.com', $report->email);
        $this->assertStringNotContainsString('GPS-SECRET', $stored);
        $this->assertSame(2560, getimagesizefromstring($stored)[0]);

        Notification::assertSentOnDemand(BugReportSubmitted::class, function (BugReportSubmitted $notification, array $channels, AnonymousNotifiable $notifiable): bool {
            $mail = $notification->toMail($notifiable);
            $html = (string) $mail->render();

            return $notifiable->routes['mail'] === config('privacy.operator.email')
                && $mail->replyTo === [['host@example.com', null]]
                && count($mail->rawAttachments) === 1
                && ! str_contains($html, 'href="https://zly.example')
                && ! str_contains($html, '<b>tučne</b>');
        });
    }

    public function test_unsafe_or_broken_attachments_are_refused(): void
    {
        $fullPng = UploadedFile::fake()->image('cela.png', 400, 400)->getContent();

        foreach ([
            UploadedFile::fake()->createWithContent('snimka.png', "MZ\x90\x00 not an image"),
            UploadedFile::fake()->createWithContent('export.pdf', "%PDF-1.4\n%%EOF\n"),
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

    public function test_old_reports_are_pruned_with_their_attachments(): void
    {
        $disk = Storage::disk(BugReport::DISK);
        $old = BugReport::factory()->create(['created_at' => now()->subMonths(BugReport::RETENTION_MONTHS + 1)]);
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
