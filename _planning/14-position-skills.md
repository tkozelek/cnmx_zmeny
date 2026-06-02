# 14 — Position Skills & Training Hierarchy

## Purpose

Not every employee is qualified for every position. A new hire starts as **Zaucovaný** (trainee), progresses to **Uvádzač**, and eventually to **Bufet** or beyond. The system tracks each employee's certified positions and uses this when building the work plan — filtering assignment dropdowns to qualified people and warning on mismatches.

---

## The Hierarchy Model

Positions have an inherent progression tier. The key insight is that **higher tiers include lower tiers** — a Bufet-certified employee can also cover Uvádzač or Zaucovaný shifts. This mirrors how cinema staff actually cross-cover.

Example default hierarchy:

| Tier | Position name | Can also cover |
|---|---|---|
| 1 | Zaucovaný (trainee) | — |
| 2 | Uvádzač | Zaucovaný |
| 3 | Bufet | Uvádzač, Zaucovaný |
| 4 | Pokladňa | (independent — no lower tiers, cross-train separately) |
| 5 | Vedúci (manager) | everything |

**Important:** tiers are not a single linear chain across all positions. Some positions (e.g. Pokladňa) are lateral specialisations — trained independently rather than promoted into. The `tier_group` column handles this: positions in the same group form a progression chain; positions in different groups are independent.

---

## DB Changes

### Add to `positions`

```sql
ALTER TABLE positions ADD COLUMN tier       tinyint  NOT NULL DEFAULT 1;
ALTER TABLE positions ADD COLUMN tier_group tinyint  NOT NULL DEFAULT 1;
-- tier_group = 1: main floor progression (Zaucovaný → Uvádzač → Bufet)
-- tier_group = 2: box office (Pokladňa — standalone)
-- tier_group = 3: management (Vedúci — standalone)
-- tier_group = 0: no hierarchy at all (one-off positions)
```

### New table: `user_positions`

```sql
id              bigint      PK
user_id         bigint      FK users.id CASCADE DELETE
position_id     bigint      FK positions.id CASCADE DELETE
certified_at    date        nullable   -- when they completed training
certified_by    bigint      nullable FK users.id SET NULL  -- who signed off
notes           string(255) nullable
created_at      timestamp
updated_at      timestamp

UNIQUE (user_id, position_id)
INDEX  (user_id)
```

---

## Qualification Logic

```php
// app/Services/PositionSkillService.php

class PositionSkillService
{
    /**
     * Can this user be assigned to this position?
     * Returns true if the user holds a certification for this position
     * OR for a higher-tier position in the same tier_group.
     */
    public function isQualified(User $user, Position $position): bool
    {
        // Manager override: Vedúci can do anything
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            return true;
        }

        $certified = $user->certifiedPositions;  // eager-loaded collection

        // Exact match
        if ($certified->contains('id', $position->id)) {
            return true;
        }

        // Higher-tier match within the same group (covers down the chain)
        if ($position->tier_group !== 0) {
            return $certified
                ->where('tier_group', $position->tier_group)
                ->where('tier', '>=', $position->tier)
                ->isNotEmpty();
        }

        return false;
    }

    /**
     * The highest position this user is certified for, per tier_group.
     * Used as a summary label ("Max. pozícia: Bufet").
     */
    public function highestCertification(User $user): Collection
    {
        return $user->certifiedPositions
            ->groupBy('tier_group')
            ->map(fn ($positions) => $positions->sortByDesc('tier')->first());
    }
}
```

---

## Model Relationships

```php
// User model additions

public function certifiedPositions(): BelongsToMany
{
    return $this->belongsToMany(Position::class, 'user_positions')
                ->withPivot(['certified_at', 'certified_by', 'notes'])
                ->withTimestamps();
}

public function isQualifiedFor(Position $position): bool
{
    return app(PositionSkillService::class)->isQualified($this, $position);
}
```

---

## Effect on Plan Assignment

In `MarketplaceService::getEligibleEmployees()` and the Filament `AssignUserToSlotAction` modal, the employee select is filtered:

```php
// Default: only qualified employees
$employees = User::role('employee')
    ->where('is_active', true)
    ->get()
    ->filter(fn ($u) => $u->isQualifiedFor($slot->position));

// Admin can toggle "Zobraziť všetkých" to see unqualified employees
// Selecting an unqualified employee shows a yellow warning:
// "⚠ Tento zamestnanec nie je certifikovaný pre pozíciu Bufet."
```

The warning is **soft** — admin can override and assign anyone. It is logged: `"Admin assigned uncertified user [name] to position [position]."` in the activity log.

---

## Trainee Mode (Zaucovaný)

When an employee is assigned to a **Zaucovaný** slot, they should be supervised. The plan builder automatically checks that a certified Uvádzač or higher is also assigned on the same day:

```php
// In PlanConflictService
$traineeSlots = $day->planSlots->where('position.name', 'Zaucovaný');
foreach ($traineeSlots as $slot) {
    if ($slot->assignments->whereNotNull('user_id')->isNotEmpty()) {
        $hasSupervisor = $day->planSlots
            ->where('position.tier_group', $slot->position->tier_group)
            ->where('position.tier', '>', $slot->position->tier)
            ->flatMap->assignments
            ->whereNotNull('user_id')
            ->isNotEmpty();

        if (! $hasSupervisor) {
            // Add to conflicts: "⚠ Zaucovaný bez supervízora v deň [date]"
        }
    }
}
```

This surfaces as a soft warning in `WeekPlanPage`, not a lock blocker (unlike the missing-manager check).

---

## Filament: Managing Employee Skills

### In `UserResource` edit form

Add a "Certifikácie" repeater section:

```
── Certifikácie ────────────────────────────────────────────
  [+ Pridať pozíciu]

  ┌─────────────────────────────────────────────────────┐
  │ Pozícia: [Uvádzač ▼]                                │
  │ Certifikovaný dňa: [12.3.2025]                      │
  │ Certifikoval: [Mgr. Novák ▼]                        │
  │ Poznámka: [Prešiel tréningom, OK]                   │
  └─────────────────────────────────────────────────────┘
  ┌─────────────────────────────────────────────────────┐
  │ Pozícia: [Bufet ▼]   (derived from Uvádzač tier)    │
  │ ...                                                 │
  └─────────────────────────────────────────────────────┘
```

The form validates that you cannot certify someone for a higher tier without first having the prerequisite tier in the same group (configurable — can be disabled per tenant setting `enforce_skill_prerequisites`).

### In `UserResource` table

Add a "Pozície" column showing coloured badges per certified position, e.g.:

```
Martin K.  |  🟢 Bufet  🟡 Uvádzač  |  ...
Jana N.    |  🟡 Uvádzač             |  ...
Nový stážista |  🔴 Zaucovaný        |  ...
```

Badges use the `positions.color` field.

### Quick skill upgrade via `PositionResource`

From the Positions list, click "Zobraziť zamestnancov" → see all users certified for that position and their certification date. Useful for the training manager to see who needs progression.

---

## Employee View

On the employee-facing `ProfileSettings` page, show a read-only "Moje certifikácie" section:

```
Moje certifikácie
  ● Bufet (od 1.4.2025)
  ● Uvádzač (od 15.1.2025)
  ○ Zaucovaný (vstupná úroveň)
```

They cannot edit this — only admin/manager can certify.

---

## Tenant Settings Addition

```sql
-- Add to tenant_settings:
enforce_skill_prerequisites  boolean  DEFAULT true
-- If false: admin can certify Bufet without first certifying Uvádzač (skip prerequisite check)
```

---

## Data Migration Note

On first setup (or data migration from old app), all existing employees default to **no certifications**. Admin must manually assign certifications via Filament. Alternatively, a "Bulk assign position" action on the `UserResource` list allows admin to select multiple users and assign them all a given position at once — useful for the initial setup.

---

## Implementation Checklist

- [ ] Add `tier` and `tier_group` columns to `positions` migration
- [ ] `user_positions` pivot table migration
- [ ] Update `positions` seeder with default tiers (Zaucovaný=1, Uvádzač=2, Bufet=3, Pokladňa tier_group=2, Vedúci tier_group=3)
- [ ] `PositionSkillService::isQualified()` + `highestCertification()`
- [ ] `User::certifiedPositions()` relationship
- [ ] `User::isQualifiedFor()` helper
- [ ] Prerequisite validation in `UserPosition` creation
- [ ] Assignment modal filtering by qualification (with "show all" override)
- [ ] Unqualified assignment warning + activity log
- [ ] Trainee-without-supervisor conflict detection in `PlanConflictService`
- [ ] Filament `UserResource` edit form: certifications repeater
- [ ] Filament `UserResource` table: position badges column
- [ ] Filament `PositionResource`: "View employees" relation
- [ ] Bulk assign positions action on `UserResource`
- [ ] Employee `ProfileSettings`: read-only certifications display
- [ ] `enforce_skill_prerequisites` tenant setting + form field
- [ ] Feature tests: qualification logic, tier inheritance, prerequisite enforcement, unqualified warning
