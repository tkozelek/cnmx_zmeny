# 25 — Bufet Inventory: Daily Stock Count

> Date: 2026-09-15
> Status: Planned — not yet implemented

---

## Context

The buffet (bufet) is a concession stand — already modeled as a `Position` staff can be scheduled to (`_planning/07-work-planning.md`, `_planning/14-position-skills.md`). This doc covers a separate, unrelated need: a **daily physical stock count** for the goods sold at the buffet (snacks, drinks, etc.), not shift scheduling. No prior schema or planning doc touches inventory/stock at all — this is a new domain.

**How the cinema runs this on paper today:**

1. **Morning** — today's opening stock = yesterday's closing stock, copied forward.
2. **During the day** — the bufetár (buffet worker) asks the manager for what they need; the manager physically prepares it from the warehouse; the **bufetár** is the one who writes down what they drew (`nafasoval`) into the sheet — not the manager. The manager's role here is physical handover only.
3. **Also during the day** — deductions for defective/broken/wasted items, also logged by the bufetár.
4. **Evening** — the bufetár recounts the entire physical stock (xy items) and writes down the closing count. This is allowed to be wrong on the first try — they recount and overwrite it as many times as needed until it looks right to them.
5. **Afterwards** — the manager reviews the computed difference (expected vs. counted) and tells the bufetár whether it balances, or by how much they're over/short.
6. Needs to print cleanly on a single A4 sheet.

**Confirmed with the user:** the entire data-entry form (receiving, deductions, closing count) is filled in by the **bufetár**. The manager is read-only during the day and only confirms/closes the day at the end.

---

## Data model

Three tables, team-scoped (one buffet's stock is one tenant's data — same `team_id` pattern as every other top-level table, see `_planning/02-database.md`).

### 1. `bufet_items` (new)

The product catalog, per team.

```
id, team_id, name, unit (string: ks/kg/l...), sort_order, active (boolean, default true), timestamps
```

`BufetItem` model: `BelongsToTeam`. CRUD is basic team-admin settings work (add/rename/deactivate a product) — no separate design needed, same shape as `Position` CRUD.

### 2. `bufet_inventories` (new)

One row per team per calendar date — the "day" the count belongs to.

```
id, team_id, date, closed_at (nullable timestamp), closed_by_user_id (nullable FK users), timestamps
unique(team_id, date)
```

`BufetInventory` model: `BelongsToTeam`, `belongsTo(User::class, 'closed_by_user_id')`, `hasMany(BufetInventoryLine::class)`. Cast `date` as `date:Y-m-d` (same reasoning as `Assignment`/`WeekLock` — a plain `date` cast breaks the unique key, see `app/Models/Assignment.php:44-47`).

`closed_at` is the only lock: while null, the bufetár can freely rewrite every field on every line. Once the manager confirms, further edits are blocked (policy check, not a DB constraint).

### 3. `bufet_inventory_lines` (new)

One row per item per day.

```
id, bufet_inventory_id, bufet_item_id, opening_qty (decimal 8,2, default 0), received_qty (decimal 8,2, default 0), deducted_qty (decimal 8,2, default 0), closing_qty (decimal 8,2, nullable), note (text, nullable), timestamps
unique(bufet_inventory_id, bufet_item_id)
```

`BufetInventoryLine` model. Two computed accessors, **not stored columns** — always derived, so they can never drift out of sync with the raw counts:

```php
protected function expectedQty(): Attribute
{
    return Attribute::get(fn () => $this->opening_qty + $this->received_qty - $this->deducted_qty);
}

protected function diffQty(): Attribute
{
    return Attribute::get(fn () => $this->closing_qty === null ? null : $this->closing_qty - $this->expected_qty);
}
```

`decimal(8,2)` rather than integer because some buffet goods are sold by weight/volume (kg, l), not just piece count — matches the `unit` field on `bufet_items`.

No separate "receipts log" or "deductions log" table for v1 — `received_qty`/`deducted_qty` are running totals the bufetár increments through the day. A per-entry audit trail is a real gap (see Open questions) but the user didn't ask for it and it doubles the schema for no requested benefit.

---

## Workflow

### Opening a day — `BufetInventoryService::openDay(Team $team, CarbonImmutable $date)`

Called lazily on first visit to today's inventory page (no scheduler/cron needed — same lazy-creation pattern used elsewhere in this app, e.g. `WeekService::generateWeek()`):

```php
public function openDay(Team $team, CarbonImmutable $date): BufetInventory
{
    return DB::transaction(function () use ($team, $date) {
        $inventory = BufetInventory::firstOrCreate([
            'team_id' => $team->id,
            'date' => $date->toDateString(),
        ]);

        if ($inventory->wasRecentlyCreated) {
            $yesterday = BufetInventoryLine::query()
                ->whereRelation('inventory', 'team_id', $team->id)
                ->whereRelation('inventory', 'date', $date->subDay()->toDateString())
                ->get()
                ->keyBy('bufet_item_id');

            foreach ($team->bufetItems()->active()->get() as $item) {
                $inventory->lines()->create([
                    'bufet_item_id' => $item->id,
                    'opening_qty' => $yesterday->get($item->id)?->closing_qty ?? 0,
                ]);
            }
        }

        return $inventory;
    });
}
```

Idempotent — `firstOrCreate` means re-visiting the page never duplicates rows. If an item was added to the catalog after the day was opened, it simply won't have a line; re-running with a small "sync new items" step is a two-line addition if that turns out to matter in practice, skipped for v1.

### During the day

- **Bufetár logs what they drew from the manager:** increments `received_qty` on the relevant line(s). Simple numeric input, `+=` on submit (not an overwrite) so entering "5" twice during the day adds up correctly without the bufetár having to do the running-total math themselves.
- **Bufetár logs deductions:** same `+=` pattern on `deducted_qty`, with an optional `note` (vadný kus, rozbité, prešlá záruka...).
- Both blocked once `bufet_inventories.closed_at` is set.

### Evening — closing count

Bufetár walks the physical stock and enters `closing_qty` per line. This is a plain **overwrite**, not `+=` (it's a fresh physical count, not an increment) — freely re-editable, no confirmation step, no versioning. `expected_qty`/`diff_qty` recompute live as soon as `closing_qty` changes (Livewire reactivity, no page reload).

### Manager review & close

Manager sees the same table read-only, with `diff_qty` highlighted (green ≈ 0, red = shortage, amber = surplus — exact thresholds a UI detail, not a schema concern). Confirms via a "Uzavrieť deň" action that sets `closed_at`/`closed_by_user_id`. After this, the day is frozen; correcting a closed day is a deliberate re-open action restricted to managers (clears `closed_at`), not something the bufetár can casually trigger.

---

## UI

One Livewire component per day, same pattern as `RozpisDay`/`DayCard` — a single page keyed by team + date, defaulting to today with prev/next day navigation (a stock count is a daily ritual, not something you jump around a calendar for).

Table:

| Položka | Ranný stav | Príjem | Odpočet | Očakávané | Večerný stav | Rozdiel |
|---|---|---|---|---|---|---|

- Ranný stav, Očakávané: read-only, computed/carried.
- Príjem, Odpočet, Večerný stav: editable inputs for the bufetár; read-only for everyone else.
- Rozdiel: read-only, colored, only populated once `closing_qty` is set.

### Print (single A4 sheet)

Plain `@media print` CSS on the same view — no PDF library needed, browser print (or "Save as PDF") covers "vytlačiť na 1 A4". Hide nav chrome/buttons in print, force the table to one page (`@page { size: A4; margin: 1cm; }`, tight row padding). Matches how the app already avoids pulling in a PDF/export package where CSS print suffices; `Maatwebsite Excel` stays reserved for the existing `RozpisExport`-style spreadsheet exports, not needed here.

### Permissions

Reuse existing Spatie role/permission setup (`_planning/04-spatie-permissions.md`), no new gate concepts:

- `bufet.count` (bufetár + manager + admin): edit `received_qty`/`deducted_qty`/`closing_qty` on an open day.
- `bufet.close` (manager + admin only): close/re-open a day, edit `bufet_items` catalog.

---

## Open questions / deliberately skipped for v1

- **Per-entry audit trail** — who added how much and when to `received_qty`/`deducted_qty` isn't recorded, only the running total. Add a `bufet_inventory_line_events` table (line_id, delta, type receive/deduct, user_id, note, timestamps) if the manager ever needs to dispute *which* entry was wrong, not just the final number.
- **Multi-warehouse / multi-buffet per team** — out of scope; one buffet = one inventory per team per day, matching how the cinema actually runs it.
- **Pricing / cost value of the difference** — the user only asked for quantity difference ("o koľko sú +/-"), not a currency value. `bufet_items` has no price column; add one plus a `value_diff` accessor if that's wanted later.
