<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file uploaded against a week (roster, notes, export). Replaces `file_storage`.
 */
class Media extends Model
{
    use BelongsToTeam;

    protected $fillable = [
        'team_id',
        'user_id',
        'week_start',
        'disk',
        'path',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date:Y-m-d',
            'is_visible' => 'boolean',
            'size' => 'integer',
        ];
    }

    /** The uploader. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function scopeForWeek(Builder $query, CarbonInterface|string $weekStart): Builder
    {
        return $query->where('week_start', $weekStart instanceof CarbonInterface ? $weekStart->toDateString() : $weekStart);
    }

    /** Files employees are allowed to see. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
