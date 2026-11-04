# 27 — Legacy data migration: `master` → `feature/bootstrap`

> Date: 2026-11-04 · Status: plan, importer exists but needs hardening before the real cutover.
> Supersedes Phase 11 of `11-implementation-order.md` (that one still talks about `plan_assignments`,
> `unavailabilities` and migrating `shifts`/`rates`, none of which is true anymore).

Goal: move Cinemax Žilina's production data from the legacy app (`master`, DB `d40700_sys`) into the
new multi-tenant schema. Every user, sign-up, absence and week lock comes over, every imported e-mail
is marked verified, everyone keeps their password, and staff see nothing more than one short
maintenance window and a re-login.

## 0. What already exists

| Piece | Where | State |
|---|---|---|
| Importer | `app/Console/Commands/ImportLegacyData.php` (`app:import-legacy-data`) | Works, read-only against the `legacy` connection, transactional, re-runnable for rehearsals. The re-run guard only catches *newly created* sign-ups/absences/locks, see G9. |
| Test | `tests/Feature/ImportLegacyDataTest.php` | 2 tests, green (2026-11-04) |
| Cutover runbook | `docs/deploy-cutover-legacy-to-new.md` | Deploy, pre-flight SQL, cutover window, smoke test, rollback, staff announcement |

This doc is the plan around those: the full data mapping, what the audit found missing, the decisions
still open and the order of work. The runbook stays the step-by-step for the day itself; §6 lists what
it needs updating with.

Repo state on 2026-11-04: `feature/bootstrap` and `master` are both level with origin. `master` has 5
commits not on this branch (formula escaping in the export, removal of the shifts API, test setup,
README, removing a seeded personal account). None changes the legacy data's shape, and the source is
the production database rather than `master`'s code, so the migration doesn't need them merged.

---

## 1. Principles

1. **The new app gets its own empty database.** The beta DB is never promoted (it holds demo seed
   data and a HeadManager whose password is in git history).
2. **Legacy is read-only.** The importer only SELECTs, through a MySQL user that has only `SELECT`.
   The legacy DB stays an exact fallback until the point of no return.
3. **One command, rehearsed until boring.** The same `app:import-legacy-data` runs in every rehearsal and
   at cutover. There are no hand-written SQL fix-ups on the day. Legacy data problems get fixed in
   legacy, or the importer handles them deterministically.
4. **Reconcile, don't eyeball.** Every run ends with a count-by-count comparison against legacy, and
   a mismatch fails the run (§4 G2).
5. **Freeze → import → verify → switch.** The legacy app goes into `artisan down` before the final
   import, so nothing is written between the snapshot and the switch.

---

## 2. Full data mapping

### `users` → `users` + `team_user` + Spatie role (team-scoped)

| Legacy column | New | Notes |
|---|---|---|
| `id` (mediumint) | new bigint `id` | Not preserved. Importer keeps a legacy→new id map for the child tables. |
| `name`, `lastname` (varchar 255) | `name`, `lastname` (varchar **100**) | Values over 100 chars fail in MySQL strict mode and abort the whole import. See G1. |
| `email` | `email` (globally unique) | Matched by e-mail. Must be trimmed and lowercased, with collisions rejected. See G5. |
| `password` (bcrypt, cost 10) | `password` | Hash copied as-is, so **everyone keeps their password**. The `hashed` cast accepts an existing bcrypt hash only if `BCRYPT_ROUNDS` ≥ 10. Below 10 it throws "Could not verify…" and the whole import aborts. The default is 10, so login rehashes only if `BCRYPT_ROUNDS` is set above 10. |
| `remember_token` | – | Dropped. A new `APP_KEY` logs everyone out anyway. |
| `created_at` | `created_at` | Preserved, because the retention rules key off it. |
| `updated_at` | `updated_at` | Import time. |
| `last_login_at` | `last_login_at` | Preserved, because retention keys off it. |
| – | `email_verified_at` | **Import time for every user**, including pending applicants. In legacy, the manager's approval was the verification. |
| – | `current_team_id` | The Žilina team. |
| `id_role` | see below | |
| – | `is_super_admin`, `compact_week`, `pending_email*`, MFA columns | Defaults (`false` / `null`). |

| Legacy `id_role` | `team_user.approved_at` | `users.is_active` | Spatie role (team = Žilina) |
|---|---|---|---|
| 1 neoverený | `null` (pending) | true | `employee` |
| 2 brigádnik | legacy `created_at` | true | `employee` |
| 3 administrátor | legacy `created_at` | true | `head-manager` (D5) |
| 4 zablokovaný | legacy `created_at` | **false** | `employee` |

### `weeks` + `days` + `user_days` → `assignments`

| Legacy | New | Notes |
|---|---|---|
| `user_days.id_user` | `assignments.user_id` | Through the id map. |
| `days.date` (via `user_days.id_day`) | `assignments.date` | Resolved in PHP, because legacy may be on another server and can't be joined. |
| `user_days.popis` (varchar 50) | `assignments.note` (255) | Trimmed. It always fits. |
| – | `position_id`, `position_slot_id`, `start_time`, `end_time` | `null`. Legacy has no positions. These are the "unplaced sign-ups" in the rozpis builder. |
| – | `created_by` | `null` (= self sign-up; legacy didn't record who added the row). |
| – | `team_id` | Žilina. |
| duplicate `(id_user, id_day)` rows | one row | Legacy has no unique constraint. The new `UNIQUE(team_id, user_id, date)` requires collapsing them, and the last `popis` wins. |

Diff-merged on re-runs: only `note` is refreshed, any positions placed in the new app survive, and a
sign-up missing from legacy is deleted.

### `weeks.locked` → `week_locks`

| Legacy | New |
|---|---|
| `weeks.date_from` where `locked = 1` | `week_start` (must be a Thursday = `team_settings.week_start_day` 3; see G4 / P5) |
| – | `is_locked = true`, `locked_by = null`, `rozpis_published_at = null`, `created_at` = import time |
| unlocked weeks, `date_to`, `next_week_id`, `prev_week_id`, `days` rows | – (weeks are computed now) |

### `user_holidays` → `absences`

| Legacy | New | Notes |
|---|---|---|
| `date_from`, `date_to` (datetime, inclusive dates at 00:00) | `date_from`, `date_to` (date) | Date part. Both apps run `Europe/Prague`, so there's no timezone shift. |
| `popis` (varchar 255) | `reason` (500) | Always fits. |
| `date_canceled` null | `status = active` | |
| `date_canceled` before `date_from` (ended before it started) | `status = cancelled` | |
| `date_canceled` on/after `date_from` (ended while running) | `status = active`, `date_to = max(date_from, min(date_to, DATE(date_canceled) − 1 day))` | **G11.** This matches `AbsenceService::end()`. Legacy stamped `date_canceled` even on a running absence. Importing those as `Cancelled` would erase days that were actually taken, from past weeks, the available pool and the overlap checks. |
| `created_at`, `updated_at` | preserved | `AbsencePolicy` uses them: a fresh absence can be deleted outright, and a cancelled one's "inactive since" is `updated_at`. |
| – | `day_of_week = null`, `team_id` = Žilina | |

Replaced wholesale on every run.

### Not migrated

| Legacy | Why |
|---|---|
| `shifts`, `rates` (evidencia hodín) | Feature scrapped, tables dropped (`2026_09_12_130000`). Decided 2026-10-15 and stated on the privacy page. See D1. |
| `file_storage` + files on disk | Per-week uploads scrapped (`2026_10_06_212947_drop_media_table`). The files go into the legacy archive and are deleted with it (D4). |
| `bugs` | Bug reporting dropped. |
| `roles` (legacy integer table) | Replaced by the mapping above. |
| `sessions`, `password_reset_tokens`, `cache`, `jobs`, `failed_jobs`, `pulse_*`, `tenants`, `domains` | Runtime or dead data. In-flight password-reset links die, and everyone logs in again. |

---

## 3. "Everything" vs. what was decided

The request is to move *everything*. Users, sign-ups, absences and locks all move. Worked hours
(`shifts`/`rates`) and uploaded files do **not**, because those features no longer exist in the new app
and their tables are dropped. Migrating them would mean:

- re-adding the `shifts`/`rates` (and `media`) tables, models and some UI to read them, and
- changing the privacy page, which currently says this data is not kept.

**Recommendation: keep the 2026-10-15 decision.** The staff announcement tells people to copy their
hours before the cutover, and the legacy dump keeps them for the D4 retention period in case of a dispute.
If you want them kept, that is a separate piece of work (an "archive" read-only view), not part of this
import. See D1.

---

## 4. Audit of the existing importer: gaps to close before cutover

Verified OK:
- `User::$fillable` covers every field the importer writes.
- The `hashed` cast keeps legacy bcrypt hashes (with `BCRYPT_ROUNDS` ≥ 10).
- `activity()->withoutLogging()` keeps thousands of rows out of the activity log.
- The whole run is one transaction, and timezones match.
- The import sends no e-mail. Imported users are verified, imported weeks are unpublished, and no `Registered` event fires.
- No N+1 queries on the day-one hot paths: the week view, users list, absences list and export.

The existing test runs on SQLite, so MySQL strict mode and collation behaviour are only exercised in the
rehearsals (§8 step 3).

| # | Gap | Impact | Fix | Verify |
|---|---|---|---|---|
| **G1** | `name`/`lastname` are 255 in legacy but 100 in the new schema | One long name throws `Data too long` and the cutover import fails mid-window | Validate before writing: abort with the list of offending legacy ids so they get fixed in legacy (pre-flight P7). Don't truncate people's names silently. | Test: an overlong name → command fails and DB unchanged |
| **G2** | No `--dry-run`, and reconciliation is manual SQL (runbook §5) | Copy-paste errors in the window, no safe preview on the prod host | Add `--dry-run` (run everything, print the report, roll back). End every run with a reconciliation table (§7: legacy count vs new count per row); any mismatch throws inside the transaction, so nothing commits. | Test: dry-run leaves DB empty; a forced mismatch fails |
| **G3** | Import is per-row Eloquent: about 4 queries per sign-up (`firstOrNew`, plus `LogsRozpisActivity` lazy-loading `team` *before* `withoutLogging()` short-circuits, plus `NotifiesRozpisChange` running `Team::find` + a Pennant check), and 8–10 per user (a second `save()` for `created_at`, pivot sync, `syncRoles`) | At 10–30k sign-ups that's 40–120k queries in one transaction, which can mean minutes inside the window if the DB is remote. Saves (not deletes, since those rows lack `team_id`) can also queue "rozpis changed" mail on a re-run over a published week. | On a fresh target, bulk-insert assignments in chunks with `DB::table()->insert`, as `importAbsences` already does. Keep the diff-merge only for rehearsal re-runs, wrapped in `Assignment::withoutEvents()`. Set `created_at` through `forceFill` in the first write. | Test asserts `Queue::assertNothingPushed()`; time the rehearsal |
| **G4** | `week_start` is trusted from legacy `date_from` | A non-Thursday legacy week becomes a lock that never matches a computed week, so that week appears unlocked | Align through `WeekService` with the team's `week_start_day`. If the aligned date ≠ `date_from`, abort and list the weeks (pre-flight P5). | Test: misaligned locked week → fails with its id |
| G5 | Users are matched on the raw e-mail | Low: legacy `users.email` is UNIQUE under `utf8mb4_unicode_ci`, so case and trailing-space duplicates can't exist. Login is case-insensitive too. | Trim and lowercase for tidiness only. If you do, also normalise `$keepEmails` in `clearSeededData`, or a stray space makes that user look deleted on every re-run (new id). Confirm the production collation with `SHOW CREATE TABLE users`. | – |
| G6 | Import writes `team_user` directly, so the `user:{id}:approved-teams` cache (10 min) isn't invalidated | Only matters right after a rehearsal re-run. Spatie's cache holds role and permission *definitions*, not assignments, so it isn't affected. | `php artisan cache:clear` after every import (runbook) | – |
| **G7** | Operator account collision | If your super-admin e-mail is also a legacy user, the import resets its password to the legacy hash and makes it a HeadManager. Super admins are hidden from people lists and sign-ups, so your legacy sign-ups would vanish from view. | Decision D8: separate e-mail for the super admin. The importer refuses to touch a user with `is_super_admin = true`. | Test: super-admin e-mail in legacy → skipped or fails |
| G8 | `--team` defaults to `1` | Low risk, but the wrong team on prod would be bad | Make `--team` required | – |
| **G9** | The re-run guard checks only `created_at` on assignments, absences and locks, and the marker file lives in the release's `storage/` | A rollback-and-reimport silently overwrites updates (an ended absence, a placed position) and **deletes users who registered in the new app** (`clearSeededData`). A redeploy without shared `storage/` drops the marker, which turns the guard off. | Also check `updated_at`, and new `users`/`team_user` rows since the marker. Keep `storage/` shared between releases. | Test: an approval or registration after the import → re-run fails |
| **G10** | `LEGACY_DB_USERNAME`/`PASSWORD` fall back to `DB_*` (`config/database.php:76-77`) | If they're unset, the import connects to legacy with the new app's write-capable credentials, which breaks principle 2 | No fallback. The command fails if `LEGACY_DB_USERNAME` is unset. | – |
| **G11** | `date_canceled` mapped wholesale to `Cancelled` | Erases days that were actually taken (see §2 absences) | Map per §2: cancelled only if ended before it started, otherwise active and shortened | Test: one absence ended before it started, one ended while running |

Tests: add only the cases in the Verify column. The existing happy-path test stays as is, and per the
testing philosophy there are no tests for plain column copies.

---

## 5. Pre-flight checks on legacy (read-only)

P1–P6 are already in the runbook §3.6 (role distribution, duplicate e-mails, malformed e-mails, overlong
notes, non-Thursday weeks, baseline counts). P4 (overlong notes) can be dropped: legacy `popis` columns are
50 and 255, so they always fit. Add:

```sql
-- P7 names that don't fit the new varchar(100) (G1) - fix in legacy before the cutover
SELECT id, CHAR_LENGTH(name), CHAR_LENGTH(lastname) FROM users
 WHERE CHAR_LENGTH(name) > 100 OR CHAR_LENGTH(lastname) > 100;

-- P8 duplicate sign-ups that get collapsed (know the number; reconciliation counts DISTINCT)
SELECT id_user, id_day, COUNT(*) c FROM user_days GROUP BY id_user, id_day HAVING c > 1;

-- P9 sign-ups on a day the same person has an active absence - kept as-is, but know them
SELECT ud.id_user, d.date FROM user_days ud
  JOIN days d ON d.id = ud.id_day
  JOIN user_holidays h ON h.id_user = ud.id_user AND h.date_canceled IS NULL
   AND d.date BETWEEN DATE(h.date_from) AND DATE(h.date_to);

-- P10 what the first retention run will hit (cross-check with app:prune-personal-data --dry-run)
SELECT id_role, COUNT(*) FROM users
 WHERE created_at < NOW() - INTERVAL 6 MONTH AND id_role = 1
UNION ALL
SELECT 'quiet 12m', COUNT(*) FROM users u
 WHERE u.id_role IN (2, 4) AND u.created_at < NOW() - INTERVAL 12 MONTH
   AND (u.last_login_at IS NULL OR u.last_login_at < NOW() - INTERVAL 12 MONTH)
   AND NOT EXISTS (SELECT 1 FROM user_days ud JOIN days d ON d.id = ud.id_day
                    WHERE ud.id_user = u.id AND d.date >= CURDATE() - INTERVAL 12 MONTH);
```

Fix every P2/P3/P5/P7 hit **in legacy** (through the legacy admin UI or a reviewed `UPDATE`) before the
final rehearsal. The importer then fails fast if anything new appears (G1/G4/G5).

---

## 6. Decisions to make

| # | Question | Recommendation |
|---|---|---|
| **D1** | Migrate worked hours / uploaded files after all? | **No.** Keep the scrapped decision (§3) and give staff a deadline to copy their hours. |
| D2 | Point of no return (from the runbook) | The first sign-up or absence written in the new app, or 2 h after go-live, whichever comes first. |
| D3 | Hosting switch (from the runbook) | Docroot or symlink switch, not DNS. |
| D4 | How long to keep the legacy dump + files (GDPR storage limitation) | 30 days after go-live, encrypted and off the web host, then delete. |
| **D5** | All legacy admins → `head-manager`, or some → `manager`? | Import all as `head-manager` (same powers as before), then demote in the UI afterwards if wanted. |
| **D6** | Rozpis module (Pennant `TeamFeature::Rozpis`) on for Žilina at cutover? | **Off**, which is already the default (`Feature::define(..., fn () => false)`), so there's nothing to do. Turn it on in `/platform` once positions and groups are set up. |
| **D7** | Let retention pruning run from night one? | Yes, because the privacy page promises it. But review `app:prune-personal-data --dry-run` (and P10) in the last rehearsal, and warn a manager if the list contains people who still expect access. |
| **D8** | Super admin account | A separate e-mail that is **not** in legacy, created **after** the final import with no team membership. Don't register through Žilina, because a later re-run would delete the account (G9). `app:super-admin` needs an existing active, verified user, so add a small creation step (a `forceCreate` via tinker, or a `--create` option on the command). Then grant the flag and **enrol TOTP immediately**: until it's enrolled, whoever logs into `/platform` with the password first sets up the 2FA. |
| **D9** | Team settings | Confirm with a manager before cutover: `week_start_day` 3 (Thursday), `week_lookahead` 5 (legacy's hard-coded 5 weeks), `absence_deadline_days` 2 (**new rule**, legacy had no deadline, so staff will notice), `stale_absence_deletion_days` 30. |
| D10 | Pending applicants (`id_role` 1) | Import them (pending, e-mail verified). The 6-month rule removes old ones. |
| D11 | Cutover window length | Announce **60 min**, not 15. Two dumps, the import, reconciliation, the switch and the smoke test realistically take 30–45 min. Time it in the staging rehearsal. |
| D12 | Login throttle on day one | Everyone logs in again at once, and `POST /prihlasenie` allows 10 attempts per minute per IP. Staff on the cinema's shared Wi-Fi share an IP. If `TRUSTED_PROXIES` is wrong, the whole site shares the balancer's IP. Verify that `request()->ip()` shows real client IPs on the production host, and raise the per-IP limit for the first days. The per-e-mail limit of 5 still protects accounts. |

---

## 7. Reconciliation (what G2 checks, and what to compare by hand until it exists)

Each row compares a legacy count (during the freeze) with the new count for the Žilina team:

| Legacy | New |
|---|---|
| `COUNT(DISTINCT LOWER(TRIM(email)))` from `users` | `team.users()->count()` (minus test users) |
| same | users with `email_verified_at IS NOT NULL` (= all) |
| `id_role = 1` | memberships with `approved_at IS NULL` |
| `id_role = 4` | `is_active = false` |
| `id_role = 3` | `head-manager` holders in the team |
| `id_role IN (1,2,4)` | `employee` holders in the team |
| `COUNT(DISTINCT id_user, days.date)` | `assignments` |
| `user_holidays` total / `date_canceled IS NOT NULL` | `absences` total / `status = cancelled` |
| `weeks WHERE locked = 1` | `week_locks WHERE is_locked = 1` |

Spot checks (manual, every rehearsal): for 3 people (one admin, one active brigádnik, one with a cancelled
holiday), compare the current week's sign-ups, their absences and their login with the existing password
between legacy and new.

---

## 8. Order of work

1. **Harden the importer (G1–G11) + tests** on `feature/bootstrap`, where the importer lives. Fix the
   §10 pre-launch items alongside it.
   → verify: the new test cases fail before and pass after; `ImportLegacyDataTest` green; Pint clean.
2. **Update the runbook** `docs/deploy-cutover-legacy-to-new.md` (**done 2026-11-04**; the reset mail is
   queued since M4, so the password-reset check does prove the queue):
   - **Checks:** add P7–P10 and drop P4. Use `--dry-run` + the built-in reconciliation instead of §5's
     manual SQL, whose `users = legacy users` count also drifts once the super admin exists.
   - **Import:** run `cache:clear` after every import. Create the super admin per D8 after the final import.
     Set `BCRYPT_ROUNDS` ≥ 10 and `LEGACY_DB_USERNAME`/`PASSWORD` explicitly (G10).
   - **Clean target:** just before T-0, run `migrate:fresh --force` + create the team, so the final import
     doesn't sit on rehearsal leftovers or on what the nightly prune did to them. Alternatively, keep the
     scheduler off until go-live.
   - **Legacy dump:** use `mysqldump --single-transaction --no-tablespaces`. With a SELECT-only user,
     MySQL ≥ 8.0.21 otherwise fails on the missing PROCESS privilege. Test it in a rehearsal.
   - **Backups of the new DB:** a daily `mysqldump` or hosting snapshot from go-live, one restore test
     in a rehearsal, and "backup ran" in the §9 checks. After D2 it's the only copy of live data.
   - **Window:** set it per D11, line up the real employee and manager for the smoke test in advance,
     and lock the staging/beta URL during and after the switch.
   - **Mail check:** the "password reset proves the queue" check is wrong, because the reset mail is
     sent synchronously. Use a queued mail, e.g. resending verification for a test registration.
   - **Rollback:** list what's lost by rolling back (password changes and registrations in the new app,
     not just sign-ups).
   - **Legacy files:** archive the uploaded files with the dump (D4).
   → verify: a dry read-through, where every command in it exists (`php artisan list`).
3. **Local rehearsal with a production dump.** Restore a fresh `mysqldump` of `d40700_sys` into a local
   MySQL as the `legacy` connection, `migrate:fresh` an empty target, create the team, then
   `--dry-run` → real run → reconciliation → spot checks → `app:prune-personal-data --dry-run`.
   Browser smoke as a real admin and a real brigádnik (current week, a locked past week, absences, users
   list, export).
   → verify: a zero-mismatch reconciliation, and the smoke checklist (runbook §6) passes.
   Delete the local dump afterwards (it's personal data).
4. **Fix legacy data** found by P2/P3/P5/P7 and repeat step 3 until a run is clean with nothing to fix.
5. **Staging rehearsal on the production host** against the live legacy DB through the read-only user
   (runbook §3), with the new app behind basic auth.
   → verify: the same reconciliation; queue worker and scheduler running; a password-reset mail arrives.
6. **Announce** to staff 2–3 days ahead (runbook §8), including the hours-copy deadline (D1) and
   the new absence deadline (D9).
7. **Cutover** (runbook §4): freeze legacy → dump both DBs → final import → reconciliation → cache
   clear + optimise → switch docroot → smoke → done.
8. **After:** watch logs, `failed_jobs` and Pulse for a few days. At D2, the legacy app stays down for
   good. At D4, delete the dump, the files, `LEGACY_DB_*` and the `cnmx_import` MySQL user. Later, delete
   `ImportLegacyData`, its test and the `legacy` DB connection in one commit, and mark Phase 11 done.

---

## 9. Risks

| Risk | Mitigation |
|---|---|
| Import fails mid-window on bad data | G1/G4/G5 fail fast, P-checks are fixed in legacy beforehand, and the window ends with a rehearsal that ran clean on the same data. On failure, the transaction leaves the new DB as it was, so `artisan up` on legacy is the rollback. |
| Writes land in legacy after the snapshot | `artisan down` on legacy *before* the dump and import, and legacy never comes back up writable (split brain). |
| Real staff get e-mails from a rehearsal | G3, `QUEUE_CONNECTION` worker off on rehearsal hosts unless testing mail, and no published rozpis during rehearsals. |
| First prune deletes people who still work there | D7: review the dry-run list before go-live. Managers are exempt. |
| Someone can't log in (case or whitespace in e-mail) | G5 + P3. The password reset works because every imported address is verified. |
| A stale access cache right after import | G6 + `cache:clear` in the runbook. |
| Real personal data on rehearsal hosts | Basic auth or an IP allow-list on staging, local dumps deleted after use, and the D4 deadline for the legacy archive. |

---

## 10. Pre-launch fixes outside the importer (review 2026-11-04)

These come from parallel reviews of security, access control and e-mail, each checked against the code.
No critical findings. Access control holds:
- unverified, pending and blocked users can't get in;
- an employee only reaches their own schedule, sign-ups, absences and profile;
- cinemas are isolated (a foreign id gives a 404);
- a manager can't escalate.

Fix before cutover:

| # | Area | Fix | Where |
|---|---|---|---|
| S1 | Dependencies | **Done.** livewire 4.4.8, symfony/mime 8.1.10; `composer audit` is clean | composer.lock |
| S2 | Access | **Accepted as is (2026-11-04).** A demoted manager can keep *reading* until reload. Every write action (`accept`, `deny`, `endAbsence`, `deleteAbsence`) already re-authorizes per call. | `app/Livewire/UsersDataTable.php:56`, `AbsencesDataTable.php:57` |
| S3 | Export | **Done.** Manager, position label and substitutes are escaped with `escapeFormula()`. (There is no separate Zoznam sheet in the export.) | `app/Exports/RozpisExport.php:195-199`, `RozpisService.php:173,278` |
| S4 | Pulse | **Done.** The token URLs are ignored, and the `viewPulse` allow-list requires an active, verified account. | `config/pulse.php`, `AppServiceProvider.php:47` |
| M1 | Mail | Rozpis-change mails skip people *removed* from the schedule: collect affected user ids at change time. | `RozpisNotificationService.php:78-84`, `NotifiesRozpisChange` |
| M2 | Mail | Rozpis mails go to deactivated and removed members: filter on `is_active` + an approved membership in that team. | `RozpisNotificationService.php:84` |
| M3 | Mail | Re-publishing re-mails everyone (return early if already published). The delayed change job must re-check that the week is still published. | `RozpisPublishController::store`, `SendRozpisChangeNotification::handle` |
| M4 | Mail | Make `ResetPasswordNotification` `ShouldQueue` (timing leak, SMTP failure = 500). Add `$tries`/`$backoff` to notifications and `--tries=3` to the cron worker. Sign the e-mail-change URL in `toMail()`. | `app/Notifications/*` |
| M5 | Mail | Add a per-target rate limit on e-mail change requests (spam relay). | `EmailController::update` |

After launch (low): hash `pending_email_token`; fail-closed `BelongsToTeam` scope; strip approval and role
on single-cinema deactivation; cinema name and a cinema-switching link in mails; Slovak copy fixes
(date format `5. 11. – 11. 11. 2026`, "zmena" ambiguity); a working plain-text mail version;
`color-scheme: light dark`; `auth:clear-resets add_user` + pruning invite tokens; debounce `uniqueFor`
+ margin; `URL::forceRootUrl(config('app.url'))`; consistent `APP_NAME`. Confirm the intent: managers
aren't bound by locks or past dates, and absences have no lock rule.

### Logging (done 2026-11-04)

- **Files:** `daily` (`laravel-*.log`) for app, exceptions and audit; `security` (`security-*.log`) for logins,
  failed logins (e-mail as a keyed hash), logouts, password resets, verification, registration, role
  attach/detach (Spatie events on), membership approve/deny, and refused 403 / throttled 429. Both are
  kept `LOG_DAILY_DAYS` days and pruned by `app:prune-personal-data`.
- **Every line** carries `request_id` (also sent as the `X-Request-Id` header), method, path, `ip_hash`,
  `user_id` and `team_id` (Laravel Context, which also follows queued jobs).
- **Audit:** `Loggable` now also logs creates, and `Assignment` uses it, so sign-ups and withdrawals are in
  the file log. Also logged: notifications sent or failed, scheduled tasks that fail or exit non-zero, the
  retention run and the legacy import.
- **Scrubbing:** `App\Logging\ScrubPersonalData` (a tap on every file channel) removes secrets, masks
  name/e-mail/reason/note keys, e-mail addresses, IPv4 addresses, reset/verify tokens and URL signatures,
  turns models into `Class#id`, and rebuilds QueryException messages without bound values.
  Covered by `tests/Feature/LogScrubbingTest.php`. The privacy page wording is updated to match.
- Note for G3: until the importer bulk-inserts, every imported sign-up writes one "vytvoril model" line.

### Pre-deployment fix pass (2026-11-04)

The eight-agent audit has been fixed apart from the decisions listed below.
- **Mail (M1–M5 above):** done. Changed-mails reach removed people; inactive/removed members are filtered out; publish is idempotent; the job re-checks publication and the unique lock got a margin; reset mail is queued; queued mails get `tries=3` + backoff; e-mail change has a per-target limit; the approval mail can't be re-sent.
- **Backend:**
  - Fairness weights are memoised per date (the rozpis builder took seconds per drag at real volume).
  - `activity_log.created_at` index plus a bounded history query.
  - Removing/deactivating a user withdraws their sign-ups from today on.
  - The admin invite is atomic.
  - A rozpis slot race gives a toast, not a 500.
  - Prune survives one bad user and also clears invite tokens and sessions; `auth:clear-resets add_user` is scheduled; failed jobs are kept 7 days.
  - An array `email` no longer 500s the throttle.
  - `throttle:member-writes` on the tenant group, `throttle:invites` on user creation, an hourly Gemini cap.
  - https root URL + HSTS in production; Pulse cache recorder off by default; `/api/user` removed.
- **Frontend/a11y** (see the audit list):
  - No PHP logic in Blade (view composers).
  - One date format (`App\Support\SlovakDate`), nbsp after one-letter words, "absencia"/"brigádnik".
  - Hero visible without JS; table assets only where used; note sent with the sign-up; busy states; touch fixes; print styles; font preloads.
  - Rozpis headings and labels; error summary; tap targets.
- **Tooling:** Larastan level 6 (`phpstan.neon`, existing errors in `phpstan-baseline.neon`), `phpunit.xml.dist`, Pint clean, dead code removed, CLAUDE.md rewritten.
- **Server facts (SSH, read-only):**
  - Apache, CLI PHP 8.1 (use `php8.4`), and a hosting-managed queue worker.
  - **No crontab** - `schedule:run` must be added.
  - Apache sees real client IPs, so `TRUSTED_PROXIES` is likely unnecessary.
- **Still open (decisions):**
  - A paid Gemini key or keep RozpisAi off.
  - The `PRIVACY_MAIL_PROVIDER` value.
  - Fairness history after import (only placed sign-ups count).
  - Importer gaps G1–G11 (§4).

### Importer fixes (2026-11-04)

- **Done:**
  - G1: `users.name`/`lastname` widened back to 255.
  - G2: `--dry-run` plus a built-in count table; any mismatch rolls back.
  - G3 (partly): model events off for assignments, so no mails and no audit lines; the per-row speed is accepted.
  - G5: e-mails trimmed and lowercased, with an abort on collisions.
  - G8: `--team` is required.
  - G9: the guard also catches updates, new members and approvals.
  - G10: no `DB_*` fallback for the legacy credentials.
  - G11: an absence ended mid-way stays active and is shortened; only one ended before it started is cancelled.
- **Left as is (Tomáš):**
  - G4: locks.
  - G6: `cache:clear` in the runbook.
  - G7: the super-admin account is handled manually.
