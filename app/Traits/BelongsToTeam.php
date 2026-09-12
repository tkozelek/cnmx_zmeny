<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\PermissionRegistrar;

/**
 * Scopes a model to the team the request is acting in, and fills `team_id` on create.
 *
 * Goes on every team-owned model, but never on User - users are cross-team by design.
 */
trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('team', function (Builder $query): void {
            if ($teamId = static::currentTeamId()) {
                $query->where($query->getModel()->qualifyColumn('team_id'), $teamId);
            }
        });

        static::creating(function (Model $model): void {
            $model->team_id ??= static::currentTeamId();
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The team the current request acts in.
     *
     * Spatie's registrar already holds this - SetCurrentTeam middleware puts it there
     * so role checks resolve per team - so it is reused as the single source of truth
     * instead of a second, parallel notion of "current team".
     *
     * Null outside a request (console, seeders) unless set explicitly, which leaves
     * queries unscoped. Set it with `setPermissionsTeamId()` in commands and tests.
     */
    protected static function currentTeamId(): ?int
    {
        return app(PermissionRegistrar::class)->getPermissionsTeamId();
    }
}
