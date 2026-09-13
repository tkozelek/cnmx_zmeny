# 24 — Manager Analytics: DB Audit & Roadmap

## Why this doc exists, and why it isn't doc 16

Docs [10-reports.md](10-reports.md) and [16-analytics.md](16-analytics.md) already propose an
analytics dashboard, but both were written against the *old* planned schema and stack:
`Shift`/`Rate` (pay-rate tracking), `Week`/`Day`/`PlanSlot`, Filament `ChartWidget`s, and a shift
marketplace. None of that matches the app as it actually stands today:

- **`Shift` and `Rate` were removed entirely** — dropped by
  `2026_09_12_130000_drop_shifts_and_rates_tables.php`. There is no hours/pay tracking left in the
  schema. Any stat involving money needs a real product decision first (see Tier 3), not just a
  query.
- **Filament is not installed.** The app is still Livewire 3 + Blade. Charts have to be built as a
  Livewire page, same as every other admin screen.
- **The shift marketplace (doc 12) was never built.** No `marketplace_listings` table exists, so
  Section 4 of doc 16 is aspirational, not gap-analysis.
- **`Assignment` and `PositionSlot` now carry `start_time`/`end_time`** (the position-assignment
  work, see [23-rozpis-position-assignment.md](23-rozpis-position-assignment.md)), which doc 16
  didn't know about and which makes an hours estimate possible again — just not a payroll one.

This doc replaces 16 as the thing to build from. Treat 10/16 as historical context only.

**Chart.js is already bundled** (`resources/js/app.js` registers it globally for the profile
chart) and **`maatwebsite/excel` is already installed and used** (`WeeklyScheduleExport`,
`RozpisExport`). Both are reusable as-is — no new dependency needed for charts or Excel export.

---

## What the DB actually holds today

Audited directly against the live schema (`mysql`, current migrations), not the old doc 20/02
drafts:

| Table | What it gives us | Notable limits |
|---|---|---|
| `assignments` | who worked what position on what date, `start_time`/`end_time` (nullable — only set once a manager places someone via `RozpisDay::place()`), `created_by` (null = self-signup) | No "did they actually show up" — this is the *plan*, not attendance. Times are null for anyone never placed into a slot. |
| `position_slots` | the day's offered positions — the actual capacity/demand truth (`date`, `position_id`, `start_time`/`end_time`) | No fill state stored; fill % has to be computed by joining against `assignments.position_slot_id`. |
| `positions` / `position_groups` | position catalogue, `is_manager` flag, active/inactive | No skill tier (doc 14, unbuilt) — can't yet report "trained vs untrained" coverage. |
| `absences` | `date_from`/`date_to`, `day_of_week` (recurring), `reason`, `status` (Active/Cancelled) | Late submissions are **rejected outright** by `StoreAbsenceRequest` (team's `absence_deadline_days`) — a too-late request never becomes a row, so "how often people try to submit late" isn't in this table at all today. |
| `team_user` | `approved_at` (null = pending) alongside `users.created_at`/`email_verified_at` | Full registration → verify → approve funnel is already timestamped. |
| `users` | `last_login_at`, `is_active`, `email_verified_at` | `is_active` is a bare boolean — no `deactivated_at`, no reason. Can't build turnover/reason stats without it. |
| `week_locks` | `created_at` (lock time), `locked_by`, `rozpis_published_at`, `published_by` | Gives planning lead time (publish date vs. `week_start`) for free. |
| `activity_log` (Spatie) | every `Assignment`/`PositionSlot` create/update/delete, who did it, old→new values (`LogsRozpisActivity`) | Queryable — this is the one place doc 16's "audit trail" idea is *already* fully buildable. |
| `App\Traits\Loggable` file log | same for `User`, `Team`, `TeamSetting`, `WeekLock`, `Media`, `Position`, `PositionGroup` | Log-file only (by design, see the trait's own docblock) — not queryable from the DB, so it can back a text audit view but not a chart. |
| — | **`FairnessService::scores()` already computes, per person, per team, over a rolling window**: total days worked, average day-weight, `earnedCredit`, `hardDayDebt` | Computed on the fly, nothing persisted — cheap to compute for a report, not free if you want history *before* the configured lookback window. |

---

## Tier 1 — Easy wins (today's schema, no new tracking, just a query + a page)

Ordered roughly by "least code for the most value."

1. **Fairness leaderboard.** `FairnessService::scores()` is fully built and already used inside the
   rozpis builder. Surfacing it as a per-team table (`totalDays`, `avgWeight`, `earnedCredit`,
   `hardDayDebt`) is a read-only view over an existing service call. Zero new logic.

2. **Position fill-rate / open-slots report.** `PositionSlot` is the offered capacity;
   `Assignment.position_slot_id` is what got filled. `LEFT JOIN` the two over a date range, group by
   `position_id`, and you get "Bufet was unfilled on 8 of 40 offered slots this month" — exactly
   doc 16's Section 2, just against the real tables (`position_slots`/`assignments`, not the old
   `PlanSlot`).

3. **Absence patterns.** Frequency per employee, active vs. past, concentration by `day_of_week`
   (recurring absences already carry this), busiest month. All one table, no joins beyond `user`.

4. **Self-signup vs. manager-assigned ratio.** `assignments.created_by IS NULL` vs. not, per
   position or per week. Tells a manager how much of the schedule fills itself vs. needs hand
   placement — a genuinely new metric doc 16 never proposed, and it's a single `WHERE` clause.

5. **Planning lead time.** `week_locks.rozpis_published_at` minus `week_start` (or minus
   `created_at` for lock lead time) tells a manager whether schedules go out with enough notice.
   Trivial aggregate over `week_locks`.

6. **Registration funnel latency.** `users.created_at` → `email_verified_at` → `team_user.approved_at`.
   Average time-to-verify and time-to-approve, plus a raw count of accounts stuck at each stage —
   useful for spotting an approval bottleneck.

7. **Dormant-employee flag.** `users.last_login_at` older than N days, joined against `is_active`
   and current team membership. Cheap, immediately actionable ("these 4 people haven't logged in
   in 60 days").

8. **Rough hours-per-person.** `TIME_TO_SEC(end_time) - TIME_TO_SEC(start_time)` summed over
   `assignments` in a range, for rows where both times are set. **Caveat that must ship with the
   number**: an assignment with no slot-derived time (never placed, or placed before this feature
   existed) contributes zero, so this is "hours for assignments that carry a time," not "total
   hours worked" — label it that way in the UI rather than implying payroll-grade accuracy.

9. **In-app audit trail.** `activity_log` already has everything doc 16 wanted from a "User
   Activity" report (who changed what, old → new) for the rozpis models. A filterable table view
   (date range, causer, subject type) is a straight read of an existing table — no new logging.

None of Tier 1 needs a migration. All of it can be one new Livewire page (e.g. `/spravakina/statistiky`,
gated by a new permission such as `reports.view`) with a handful of read-only queries, reusing
Chart.js for the fill-rate/hours charts and `maatwebsite/excel` for a "stiahnuť" export button —
both already in the app.

---

## Tier 2 — Valuable, but needs a small schema/tracking addition first

1. **No-show / attendance tracking.** `assignments` is the *plan*; nothing marks whether someone
   actually worked the shift. Needs a new field (e.g. `assignments.attended_at` / an enum) and a
   UI action for a manager to mark it — realistically a same-day or next-day confirmation step.
   Without this, "reliability" and "no-show rate" are not measurable at all today.

2. **Late-absence-request rate.** Because `StoreAbsenceRequest` rejects a too-late submission
   before it's ever saved, there is currently no record that someone *tried* to submit late. To
   report this, log the rejection itself (a simple event/log line with `user_id`, attempted dates,
   and how many days past the deadline) rather than trying to mine it from `absences` after the
   fact.

3. **Turnover / offboarding reasons.** `users.is_active` is a bare boolean with no `deactivated_at`
   or reason. Add both (a nullable timestamp + a short enum/free-text reason) at the same point
   `Admin\UserController::destroy()` flips `is_active` to `false`, and turnover-over-time /
   reason-breakdown reports become possible.

4. **AI-suggestion acceptance rate.** `AiRozpisSuggestionService` proposes placements but nothing
   records whether a manager accepted, edited, or ignored a given suggestion — `RozpisDay::place()`
   looks identical whether the placement came from a suggestion or a manual drag. Needs one new
   signal (e.g. a `source` column on `Assignment`, or a lightweight `ai_suggestion_events` log) to
   know whether the feature is actually saving anyone time.

5. **Training / skill coverage.** Blocked on [14-position-skills.md](14-position-skills.md) itself
   being built — there's no skill-tier concept in the schema yet, so "how many trained Bufet staff
   do we have" isn't answerable until that feature exists.

---

## Tier 3 — Needs a product decision, not (only) engineering

- **Labor cost / payroll stats.** `Shift`/`Rate` were *deliberately* removed. Reintroducing any
  cost-based stat means deciding to bring pay-rate data back into the schema — that's a scope
  decision for the app, not something to slip in as a side effect of a stats page.
- **Shift marketplace activity (doc 12).** Depends on that feature being built at all; nothing to
  report on until it exists.
- **Who sees `hardDayDebt`/fairness scores.** Today it's manager-facing and advisory only, inside
  the rozpis builder. Exposing it on a stats page is fine; exposing it to the employees it's
  scoring is a separate call someone should make on purpose.

---

## Suggested build order

1. Tier 1, items 1–3 (fairness leaderboard, fill-rate, absence patterns) — highest signal, zero
   schema change, reuses `FairnessService` and existing tables directly.
2. Tier 1, items 4–7 (self-signup ratio, lead time, registration funnel, dormant flag) — same
   page, more cards/tables, still no schema change.
3. Tier 1, items 8–9 (hours estimate, audit trail) — ship with the accuracy caveat on hours front
   and center.
4. Revisit Tier 2 once Tier 1 is live and a manager has actually said which of no-shows / late
   absences / turnover / AI acceptance they want next — each is a small, independent addition, not
   a batch.
