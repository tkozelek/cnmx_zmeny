<?php

namespace App\Models;

use Database\Factories\BugReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Not Loggable: the description must never reach the log.
 *
 * @property list<array{path: string, name: string, mime: string, size: int}> $attachments
 */
class BugReport extends Model
{
    /** @use HasFactory<BugReportFactory> */
    use HasFactory;

    public const string DISK = 'local';

    public const string DIRECTORY = 'bug-reports';

    public const int RETENTION_MONTHS = 6;

    protected $fillable = [
        'user_id',
        'team_id',
        'email',
        'description',
        'page',
        'user_agent',
        'attachments',
        'resolved_at',
    ];

    protected $attributes = [
        'attachments' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(fn (self $report) => DB::afterCommit(
            fn () => Storage::disk(self::DISK)->deleteDirectory($report->directory())
        ));
    }

    public function directory(): string
    {
        return self::DIRECTORY.'/'.$this->id;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
