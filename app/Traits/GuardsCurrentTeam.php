<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

/**
 * The tenant boundary, for policies.
 *
 * Every policy over a team-owned model has to answer the same question before it answers its own:
 * does this row even belong to the cinema the request is acting in? `BelongsToTeam`'s global scope
 * covers ordinary queries, but a policy is handed a model that route-model binding already
 * resolved - often by id alone, with the scope bypassed - so the check has to be made explicitly
 * here.
 *
 * It was written out by hand in five policies, in three spellings (`!== $team->id`,
 * `!== $team->getKey()`, `=== ...`), which is exactly how one of them ends up inverted during a
 * refactor and nobody notices. One implementation, one direction: true means "safe to go on".
 *
 * `User` is deliberately not covered - it is shared across cinemas through `team_user` rather than
 * owning a `team_id`, so its boundary is membership. See UserPolicy::inCurrentTeam().
 */
trait GuardsCurrentTeam
{
    protected function belongsToCurrentTeam(Model $model): bool
    {
        return $model->getAttribute('team_id') === app(Team::class)->getKey();
    }
}
