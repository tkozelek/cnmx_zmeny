# Cutover: legacy app (`master`) → new app (`feature/bootstrap`)

Goal: switch Cinemax Žilina from the legacy app to the new multi-tenant app without losing data and with
staff noticing nothing more than one maintenance window and a re-login.

**Strategy.** The new app runs on its **own database**. The legacy database is only ever *read*
(`app:import-legacy-data` does SELECTs only, through a SELECT-only MySQL user), so it stays an untouched
fallback the whole time. Nothing is "swapped": the import copies the data across. The import is repeatable
for rehearsals, then runs one last time during a write-freeze before the docroot switches over.
Rollback = point the docroot back at the legacy app.

```
 days before            cutover window (announce 60 min, ~30-45 real)                  after
 ───────────────        ───────────────────────────────────────────────────────        ──────────────
 deploy new app   →     fresh new DB → legacy: artisan down → dumps → final import  →  monitor, archive
 rehearse import        → count table → switch docroot → smoke → super admin, cron      legacy DB, clean up
```

Background and the data mapping: `_planning/27-legacy-data-migration.md`.

---

## 1. What moves, what doesn't

| Legacy (`d40700_sys`) | New | |
|---|---|---|
| `users` (name, lastname, email, bcrypt password, `created_at`, `last_login_at`) | `users` + `team_user` + Spatie role | imported, e-mail trimmed + lowercased, **marked verified** |
| `users.id_role` 3 admin / 2 brigádnik | `HeadManager` / `Employee` | imported |
| `users.id_role` 1 neoverený | membership with `approved_at = null` ("Čaká na schválenie") | imported |
| `users.id_role` 4 zablokovaný | `is_active = false` | imported |
| `user_days` + `days.date` (+ `popis`) | `assignments` (date + `note`) | diff-merged, duplicates collapsed |
| `user_holidays` | `absences` | replaced per run; ended-before-start → cancelled, ended-while-running → active + shortened |
| `weeks.locked` | `week_locks` (`week_start` = legacy `date_from`, a Thursday) | imported |
| `shifts`, `rates`, `file_storage` (+ files), `bugs` | – | **scrapped** (decided) – files go into the legacy archive (D4) |
| `sessions`, `password_reset_tokens`, `jobs`, `failed_jobs`, `cache` | – | not needed – everyone logs in again |

Passwords: legacy hashes are bcrypt (cost 10), the new app uses bcrypt too, so **everyone keeps
their password**. `LoginController` rehashes to the current cost on first login.

E-mail verification: in legacy, the manager's approval was the check (there was no e-mail
verification). The import marks every imported address as verified – nobody gets a verification
mail and everybody can log in straight away.

The import (`app:import-legacy-data --team=<id>`):
- `--team` is required; `LEGACY_DB_USERNAME`/`LEGACY_DB_PASSWORD` are required (no fallback to `DB_*`),
- `--dry-run` runs everything, prints the count table and rolls back,
- every run ends with a **legacy-vs-new count table** and rolls back by itself on any mismatch,
- aborts on two legacy users whose e-mails collide after trim/lowercase,
- sends no mail and writes no audit lines for imported sign-ups,
- creates the fixed test accounts only with `--with-test-users` (never in production),
- records when it finished (a marker on the `local` disk – **`storage/` must be shared between releases**)
  and **refuses a later run** once the team has sign-ups, absences, locks, updates, new members or
  approvals written in the new app since then (`--discard-new-data` overrides – only for rehearsals),
- is covered by `tests/Feature/ImportLegacyDataTest.php`.

---

## 2. Decisions

| # | Question | Recommendation (from doc 27) | Decided |
|---|---|---|---|
| D2 | Point of no return | The first sign-up or absence written in the new app, or 2 h after go-live, whichever comes first | |
| D3 | Hosting switch | Docroot/symlink switch in the same domain, not DNS | |
| D4 | How long to keep the legacy dump + uploaded files | 30 days after go-live, encrypted, off the web host, then delete | |
| D8 | Super admin account | A separate e-mail that is **not** in legacy, created after the final import (§4) | |
| D9 | Team settings | Confirm with a manager: week starts Thursday, 5 weeks ahead, absence deadline 2 days (**new rule** – legacy had none), stale absence deletion 30 days | |
| D12 | Login throttle on day one | Everyone logs in at once; `POST /prihlasenie` allows 10/min per IP (5/min per e-mail). Cinema Wi-Fi shares one IP. Check `ip_hash` differs per network (§3 step 2). If staff get "too many attempts", `php8.4 artisan cache:clear` resets the limiter | |
| – | `PRIVACY_MAIL_PROVIDER` | Whoever runs the mail server sending `@cinemaxzmeny.eu` | |

---

## 3. Days before – prepare and rehearse

1. **Legacy DB access.** On the legacy MySQL server create a **read-only** user for the import:
   ```sql
   CREATE USER 'cnmx_import'@'<new-app-host>' IDENTIFIED BY '<strong-password>';
   GRANT SELECT ON d40700_sys.* TO 'cnmx_import'@'<new-app-host>';
   ```
2. **New database** (empty, utf8mb4) and DB user for the new app. **Don't promote the beta DB.** It was
   filled by `db:seed` (demo HeadManager `admin@kino.test` / `password` in 5 cinemas, and an older
   seeder's personal HeadManager whose password is in public git history). `DatabaseSeeder` refuses to
   run with `APP_ENV=production`.
   Server: **PHP 8.4** (the locked `phpoffice/phpspreadsheet` refuses 8.5) with intl, zip, gd,
   pdo_mysql and mbstring. CLI PHP defaults to 8.1 on this host: run every artisan/composer command
   with `php8.4`, and set the site's web PHP to 8.4 in the panel.
3. **Deploy the new release** to its own folder (never over the legacy folder, never with the legacy DB
   in `DB_*` – `migrate` would run against it). `storage/` is shared between releases (the import
   marker lives there):
   ```bash
   php8.4 $(which composer) install --no-dev --optimize-autoloader
   npm ci && npm run build            # or upload a pre-built public/build (fonts and icons are bundled - no CDN)
   cp .env.production .env            # see env list below
   php8.4 artisan key:generate        # NEW key - legacy sessions/cookies are not reused
   php8.4 artisan migrate --force     # creates schema + roles/permissions; NO --seed
   php8.4 artisan permissions:sync    # idempotent; also clears the Spatie permission cache
   php8.4 artisan storage:link
   ```
   `.env` essentials:
   - `APP_NAME="Cinemax Zmeny"` – mails use it as the sender name and footer
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<production-domain>` – in production the app
     forces https and this root for every link, and sends HSTS
   - `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`; `SESSION_DOMAIN` unset unless needed
   - `TRUSTED_PROXIES` – probably **not** needed: Apache on the host already sees real client IPs.
     Verify after deploy: log in from two networks (phone data + wifi) and check the two `Prihlásenie.`
     lines in `storage/logs/security-*.log` have different `ip_hash`. Only if they are identical, set the
     balancer's IPs here (never `*`).
   - `DB_*` → the **new** DB
   - `LEGACY_DB_*` → the read-only user from step 1; `LEGACY_DB_USERNAME`/`LEGACY_DB_PASSWORD` set
     **explicitly** (the import refuses to run without them)
   - `BCRYPT_ROUNDS` unset or ≥ 10 – below 10 the legacy password hashes are rejected and the import aborts
   - `MAIL_*` → a real mailer (not `log`); `MAIL_FROM_*` set
   - `QUEUE_CONNECTION=database`; **no** `TEST_USER_*`
   - `LOG_LEVEL=info` – the `Loggable` audit lines are info/warning
   - `PULSE_ADMIN_EMAILS=<your e-mail>` – the only people who can open `/pulse`
   - `PULSE_CACHE_INTERACTIONS_ENABLED=false` (the default) and `PULSE_USER_REQUESTS_SAMPLE_RATE=0.25`
   - `PRIVACY_OPERATOR_EMAIL=<your contact e-mail>` – **required**, the privacy page shows it;
     `PRIVACY_HOSTING`, `PRIVACY_MAIL_PROVIDER` and `PRIVACY_MAIL_IN_EU=true` once the provider is
     confirmed to keep mail in the EU
4. **Create the cinema** (the seeder is demo-only):
   ```bash
   php8.4 artisan tinker --execute '$t = App\Models\Team::create(["name" => "Žilina Max", "slug" => "zilina-max"]); App\Models\TeamSetting::create(["team_id" => $t->id]); echo $t->id;'
   ```
   Note the printed id – it's `--team=<id>` below. The settings get the defaults; adjust them per D9 in
   Nastavenia after the import.
5. **Queue worker + scheduler – both required.**
   - All mail is **queued** (verification, approval, password reset, rozpis). Without a worker nothing is
     sent. The hosting runs a managed queue worker for the live site (`logs/laravel_worker_*.log`) – after
     the switch it must run the **new** release with `php8.4` and `--tries=3`; check that in the panel.
     During rehearsals keep it pointed at the legacy app (or stopped for the new one), so no real staff
     get mail from a rehearsal.
   - **Scheduler – add only at go-live** (§4), in the hosting panel's cron section (there is no crontab
     today): `* * * * * cd /var/www12/p40695/<site>/current && php8.4 artisan schedule:run >> /dev/null 2>&1`.
     It runs the retention cleanup (`app:prune-personal-data`, daily 03:30), `auth:clear-resets`,
     `queue:prune-failed`, session gc and the Pulse trim. Keeping it off until go-live means the nightly
     prune never runs over rehearsal data.
6. **Pre-flight checks on the legacy DB** (read-only; run them against the live legacy DB). Fix every
   P2/P3/P5/P7 hit **in legacy** (legacy admin UI or a reviewed `UPDATE`) before the final rehearsal:
   ```sql
   -- P1 role distribution (1 = pending, 4 = blocked - both are mapped, just know the numbers)
   SELECT id_role, COUNT(*) FROM users GROUP BY id_role;
   -- P2 duplicate e-mails after trim/lowercase (the import aborts on these)
   SELECT LOWER(TRIM(email)) e, COUNT(*) c FROM users GROUP BY e HAVING c > 1;
   -- P3 malformed e-mails (these people can't reset passwords)
   SELECT id, CONCAT('[', email, ']') FROM users
    WHERE email NOT REGEXP '^[^@[:space:]]+@[^@[:space:]]+\\.[^@[:space:]]+$';
   -- P5 every locked legacy week starts on a Thursday (DAYOFWEEK: 1 = Sunday, 5 = Thursday);
   --    a non-Thursday lock would never match a computed week, so that week would show unlocked
   SELECT id, date_from FROM weeks WHERE locked = 1 AND DAYOFWEEK(date_from) <> 5;
   -- P6 baseline counts (the import's count table compares the same numbers)
   SELECT (SELECT COUNT(*) FROM users) users,
          (SELECT COUNT(DISTINCT ud.id_user, d.date) FROM user_days ud JOIN days d ON d.id = ud.id_day) signups,
          (SELECT COUNT(*) FROM user_holidays) holidays,
          (SELECT COUNT(*) FROM weeks WHERE locked = 1) locked_weeks;
   -- P7 names longer than 255 (the new columns are 255 again - this should return nothing)
   SELECT id FROM users WHERE CHAR_LENGTH(name) > 255 OR CHAR_LENGTH(lastname) > 255;
   -- P8 duplicate sign-ups that get collapsed into one (know the number)
   SELECT id_user, id_day, COUNT(*) c FROM user_days GROUP BY id_user, id_day HAVING c > 1;
   -- P9 sign-ups on a day the same person has an active absence - kept as-is, but know them
   SELECT ud.id_user, d.date FROM user_days ud
     JOIN days d ON d.id = ud.id_day
     JOIN user_holidays h ON h.id_user = ud.id_user AND h.date_canceled IS NULL
      AND d.date BETWEEN DATE(h.date_from) AND DATE(h.date_to);
   -- P10 what the first retention run will hit (cross-check with step 8)
   SELECT id_role, COUNT(*) FROM users
    WHERE created_at < NOW() - INTERVAL 6 MONTH AND id_role = 1
   UNION ALL
   SELECT 'quiet 12m', COUNT(*) FROM users u
    WHERE u.id_role IN (2, 4) AND u.created_at < NOW() - INTERVAL 12 MONTH
      AND (u.last_login_at IS NULL OR u.last_login_at < NOW() - INTERVAL 12 MONTH)
      AND NOT EXISTS (SELECT 1 FROM user_days ud JOIN days d ON d.id = ud.id_day
                       WHERE ud.id_user = u.id AND d.date >= CURDATE() - INTERVAL 12 MONTH);
   ```
7. **Rehearse the import** into the new DB (repeat until a run is clean with nothing to fix; add
   `--discard-new-data` if you tested sign-ups in between):
   ```bash
   php8.4 artisan app:import-legacy-data --team=<id> --dry-run   # full run + count table, then rolled back
   php8.4 artisan app:import-legacy-data --team=<id> --force
   php8.4 artisan cache:clear                                     # after EVERY import (cached memberships)
   ```
   The count table must show no mismatch (the run rolls back by itself otherwise). Then do the spot
   checks in §5. Time the run – it decides how long the window really is.
8. **Preview the retention cleanup** on the imported data – its first real run deletes legacy accounts
   with no login and no sign-up in 12 months, and pending applicants older than 6 months (managers are
   never pruned). Compare with P10 and warn a manager if the list has people who still expect access:
   ```bash
   php8.4 artisan app:prune-personal-data --dry-run
   ```
9. **Rehearse the dumps.** `mysqldump --single-transaction --no-tablespaces d40700_sys` with the
   SELECT-only user (MySQL ≥ 8.0.21 fails on the missing PROCESS privilege without `--no-tablespaces`).
   Set up the daily backup of the new DB (dump or hosting snapshot, kept 30 days) and **restore one
   once** into a scratch DB.
10. **Rehearsal smoke test** on the new app, with real data. It holds real personal data, so put it
    behind HTTP basic auth or an IP allow-list. Run §6 as a real manager and a real brigádnik (ask them,
    and line the same two people up for the cutover day).
11. **Announce it to staff** 2–3 days ahead (template in §8) – including the deadline to copy worked
    hours and the new absence deadline (D9).

---

## 4. Cutover window

Announce **60 minutes**; with a rehearsed import it takes 30–45. Pick a quiet hour, not just before a
week gets locked. Times are rough.

| Time | Step |
|---|---|
| T-10 | **Clean target** in the new release: `php8.4 artisan migrate:fresh --force && php8.4 artisan permissions:sync`, create the cinema again (§3 step 4), note the new `--team` id. The final import must not sit on rehearsal leftovers. |
| T-0 | **Freeze legacy**: in the legacy app folder `php artisan down --retry=60`. From now on nobody can write. |
| T+1 | **Dump the legacy DB**: `mysqldump --single-transaction --no-tablespaces d40700_sys > legacy-<date>.sql`. Pack the legacy uploaded files with it (D4). |
| T+3 | **Final import**: `php8.4 artisan app:import-legacy-data --team=<id> --force`, then `php8.4 artisan cache:clear`. The count table must show no mismatch – otherwise it rolled back: `php artisan up` on legacy and stop here. |
| T+8 | **Spot checks** (§5). |
| T+12 | **Team settings** per D9 in Nastavenia (as an imported head-manager). |
| T+14 | **Optimise**: `php8.4 artisan config:cache && php8.4 artisan route:cache && php8.4 artisan view:cache && php8.4 artisan event:cache`, then `php8.4 artisan queue:restart`. Point the queue worker at the new release (§3 step 5). |
| T+16 | **Switch the docroot** to the new release (`<new>/public`), clear OPcache / reload PHP in the panel. |
| T+18 | **Smoke test on the production URL** (§6) with the real brigádnik and manager. |
| T+30 | **Super admin** (D8): create the operator account (below), grant it, log into `/platform` and **enrol TOTP straight away** – until then, whoever logs in there first with the password sets up the 2FA. |
| T+35 | **Scheduler**: add the cron line (§3 step 5). Lock or take down the beta/staging URL. |
| T+40 | **Done.** Legacy **stays down** – never bring it back up writable next to the new app (split brain). |

Super admin account – a separate e-mail, **not** a legacy user and not registered through Žilina (a
later re-import would treat it as a new member). Use interactive `php8.4 artisan tinker` so the password
doesn't land in shell history:
```php
App\Models\User::create(['name' => '…', 'lastname' => '…', 'email' => '<platform e-mail>', 'password' => '<strong password>', 'email_verified_at' => now(), 'is_active' => true]);
```
```bash
php8.4 artisan app:super-admin <platform e-mail>
```

## 5. Spot checks (new DB)

The import's count table already compares users, roles, states, sign-ups, absences and locks with
legacy. By hand, for 3 people (one admin, one active brigádnik, one with a cancelled holiday), compare
between legacy and new: the current week's sign-ups, their absences, and a login with their existing
password. Also:
```sql
SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL;   -- = 0
```

## 6. Smoke test (~10 min)

- [ ] Log in as a real brigádnik and as a real manager with their **existing** passwords.
- [ ] `/tyzdne` opens the right week; a locked week is shown locked; the sign-ups match legacy.
- [ ] The brigádnik can sign up for a future, open day (then withdraw it again); a past or locked day refuses it.
- [ ] `/dovolenka` shows the imported absences; `/admin/pouzivatelia` lists everyone with correct roles/states.
- [ ] The Excel export downloads.
- [ ] A password reset to your own address arrives – the reset mail is queued, so this proves mail **and** the queue worker.
- [ ] `/ochrana-osobnych-udajov` shows your contact e-mail.
- [ ] A 404 shows the new error page; there's no stack trace anywhere (`APP_DEBUG=false`).
- [ ] `curl -sI https://<domain>/` shows `X-Frame-Options: DENY`, `Referrer-Policy` and HSTS.
- [ ] `storage/logs` has no errors and `failed_jobs` is empty.

Web server (Apache on HostCreators – `.htaccess` in `public/`, or ask the hosting):
- http → https redirect (the app adds HSTS once it's served over https).
- gzip/brotli for HTML, CSS, JS, SVG, JSON – the week page is ~240 KB raw, ~16 KB compressed.
- Long cache for `/build/` (`Cache-Control: public, max-age=31536000, immutable`) and ~30 days for
  `/images/` – via `mod_headers` in `public/.htaccess`; the hashed build assets never change.
- `expose_php = Off` in php.ini, OPcache on.
- **Backups of the new DB** from go-live (§3 step 9). After the point of no return it is the only copy
  of live data.

## 7. Rollback

Possible until the point of no return (D2):
1. Put the new app down: `php8.4 artisan down`.
2. Before switching, list what will be lost from the new DB – everything written there since the import:
   sign-ups and withdrawals, absences, approvals and role changes, new registrations, password and
   e-mail changes, locks and rozpis changes.
3. Switch the docroot back to the legacy release; point the queue worker back.
4. Run `php artisan up` in the legacy folder.

The legacy DB was never written to, so it's exactly as it was at T-0.

## 8. Staff announcement (template)

> **Nová verzia aplikácie na zmeny – {dátum} o {čas}**
> Približne hodinu bude aplikácia nedostupná. Potom sa prihlásiš rovnakým e-mailom a heslom ako
> doteraz (budeš odhlásený/á, stačí sa znova prihlásiť). Tvoje zápisy aj absencie zostávajú.
> Novinka: absenciu treba nahlásiť aspoň {N} dni vopred.
> Evidencia odpracovaných hodín a nahrané súbory v novej verzii nebudú – ak si chceš svoje hodiny
> ponechať, odpíš si ich do {dátum}.

## 9. After the cutover

- First days: watch `storage/logs`, `failed_jobs` and Pulse; check that new registrations get their verification mail.
- After the first night: check the retention run in `storage/logs` matches the step 8 preview.
- Merge `feature/bootstrap` into `master` (and make it the default branch), so `master` is what production runs.
- Remove `LEGACY_DB_*` from `.env` and drop the `cnmx_import` MySQL user once you're confident (no more imports).
- Keep the legacy dump + files for the agreed period (D4), then delete them.
- Later: retire `ImportLegacyData`, its test and the `legacy` DB connection in one commit.
