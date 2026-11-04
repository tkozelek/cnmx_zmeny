# Cutover: legacy app (`master`) → new app (`feature/bootstrap`)

Goal: switch Cinemax Žilina from the legacy app to the new multi-tenant app without losing data and with
staff noticing nothing more than one short maintenance window and a re-login.

**Strategy.** The new app runs on its **own database**. The legacy database is only ever *read*
(`app:import-legacy-data` does SELECTs only), so it stays an untouched fallback the whole time.
The import is repeatable for rehearsals, then run one last time during a short write-freeze before the
domain switches over. Rollback = point the domain back at the legacy app.

```
 days before            cutover window (~15 min)                         after
 ───────────────        ─────────────────────────────────────────        ──────────────
 deploy new app   →     legacy: artisan down → backup → final import  →  monitor, archive
 rehearse import        → verify counts → switch domain → smoke test       legacy DB, clean up
```

---

## 1. What moves, what doesn't

| Legacy (`d40700_sys`) | New | |
|---|---|---|
| `users` (name, lastname, email, bcrypt password, `created_at`, `last_login_at`) | `users` + `team_user` + Spatie role | imported, **e-mail marked verified** |
| `users.id_role` 3 admin / 2 brigádnik | `HeadManager` / `Employee` | imported |
| `users.id_role` 1 neoverený | membership with `approved_at = null` ("Čaká na schválenie") | imported |
| `users.id_role` 4 zablokovaný | `is_active = false` | imported |
| `user_days` + `days.date` (+ `popis`) | `assignments` (date + `note`, max 255 chars) | diff-merged |
| `user_holidays` (`date_canceled` → cancelled, original `created_at`) | `absences` (`reason` max 500 chars) | replaced per run |
| `weeks.locked` | `week_locks` (`week_start` = legacy `date_from`, a Thursday) | imported |
| `shifts`, `rates`, `file_storage`, `bugs` | – | **scrapped** (decided) |
| `sessions`, `password_reset_tokens`, `jobs`, `failed_jobs`, `cache` | – | not needed – everyone logs in again |

Passwords: legacy hashes are bcrypt (cost 10), the new app uses bcrypt too, so **everyone keeps
their password**. `LoginController` rehashes to the current cost on first login.

E-mail verification: in legacy, the manager's approval was the check (there was no e-mail
verification). The import marks every imported address as verified – nobody gets a verification
mail and everybody can log in straight away. Pre-flight P2/P3 make sure the addresses are usable.

The import (`app:import-legacy-data`):
- creates the fixed test accounts only with `--with-test-users` (never in production),
- records when it finished and **refuses a later run** once the team has sign-ups, absences or locks
  written in the new app since then (`--discard-new-data` overrides – only for rehearsals),
- is covered by `tests/Feature/ImportLegacyDataTest.php`.

---

## 2. Decisions still open

- **D2 – rollback deadline.** Once staff write to the new app, going back loses those writes. Agree a
  point of no return (e.g. 2 hours after go-live, or the first new sign-up).
- **D3 – the hosting switch.** Same domain with a new docroot (old and new release folders side by
  side) or a DNS change. A docroot/symlink switch is instant; DNS is not – avoid DNS for the switch.
- **D4 – how long to keep the legacy DB/dump** after go-live (GDPR storage limitation), then delete it.

---

## 3. Days before – prepare and rehearse

1. **Legacy DB access.** On the legacy MySQL server create a **read-only** user for the import:
   ```sql
   CREATE USER 'cnmx_import'@'<new-app-host>' IDENTIFIED BY '<strong-password>';
   GRANT SELECT ON d40700_sys.* TO 'cnmx_import'@'<new-app-host>';
   ```
2. **New database** (empty, utf8mb4) and DB user for the new app. **Don't promote the beta DB.** It was
   almost certainly filled by `db:seed` (demo HeadManager `admin@kino.test` / `password` in 5 cinemas, and an
   older seeder's personal HeadManager whose password is in public git history), and it has had
   destructive drop migrations pending. `DatabaseSeeder` now refuses to run with `APP_ENV=production`.
   Server: **PHP 8.4** (the locked `phpoffice/phpspreadsheet` refuses 8.5) with intl, zip, gd,
   pdo_mysql and mbstring.
3. **Deploy the new release** to its own folder (never over the legacy folder):
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build            # or upload a pre-built public/build (fonts and icons are bundled - no CDN)
   cp .env.production .env            # see env list below
   php artisan key:generate           # NEW key - legacy sessions/cookies are not reused
   php artisan migrate --force        # creates schema + roles/permissions; NO --seed
   php artisan permissions:sync       # idempotent; also clears the Spatie permission cache
   php artisan storage:link
   ```
   `.env` essentials:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<production-domain>`
   - `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`; `SESSION_DOMAIN` unset unless needed
   - `TRUSTED_PROXIES` – probably **not** needed: Apache on the host already logs ~50 distinct real client
     IPs a day, so the balancer passes the visitor's IP through. Verify after deploy: log in from two
     networks (phone data + wifi) and check the two `Prihlásenie.` lines in `storage/logs/security-*.log`
     have different `ip_hash`. Only if they are identical, set the balancer's IPs here (never `*`).
   - `DB_*` → the new DB; `LEGACY_DB_*` → the read-only user from step 1
   - `MAIL_*` → a real mailer (not `log`); `MAIL_FROM_*` set
   - `QUEUE_CONNECTION=database`; **no** `TEST_USER_*`
   - `LOG_LEVEL=info` – the `Loggable` audit lines are info/warning
   - `PULSE_ADMIN_EMAILS=<your e-mail>` – the only people who can open `/pulse`
   - `PRIVACY_OPERATOR_EMAIL=<your contact e-mail>` – **required**, the privacy page shows it;
     `PRIVACY_HOSTING`, `PRIVACY_MAIL_PROVIDER` (whoever runs the mail server sending `@cinemaxzmeny.eu`)
     and `PRIVACY_MAIL_IN_EU=true` once the provider is confirmed to keep mail in the EU
   - `BCRYPT_ROUNDS` unset or ≥ 10 – below 10 the legacy password hashes are rejected and the import aborts
   - `PULSE_CACHE_INTERACTIONS_ENABLED=false` (the default now) and `PULSE_USER_REQUESTS_SAMPLE_RATE=0.25`
   - `APP_URL=https://…` – in production the app forces https and this root for every link, and sends HSTS
4. **Create the cinema** (the seeder is demo-only):
   ```bash
   php artisan tinker --execute '$t = App\Models\Team::create(["name" => "Žilina Max", "slug" => "zilina-max"]); App\Models\TeamSetting::create(["team_id" => $t->id]); echo $t->id;'
   ```
   Note the printed id – it's `--team=<id>` below.
5. **Queue worker + scheduler – both required.**
   - E-mail verification and "account approved" are **queued**. Without a worker, new registrations
     never get their link. Supervisor/systemd: `php artisan queue:work --tries=3 --max-time=3600`.
     Shared hosting without daemons: `* * * * * php /path/artisan queue:work --stop-when-empty --max-time=55`.
   - The hosting already runs a managed queue worker for the live site (`logs/laravel_worker_*.log`) – after
     the switch it must run the **new** release with `php8.4`; check that in the panel. The worker is what
     sends mail, so keep it pointed at the legacy app (or stopped for the new one) during rehearsals.
   - **Scheduler – add after go-live, in the hosting panel's cron section:**
     `* * * * * cd /var/www12/p40695/<site>/current && php8.4 artisan schedule:run >> /dev/null 2>&1`.
     It sends no mail itself; it runs the retention cleanup (`app:prune-personal-data`, daily 03:30, with
     its first run reviewed via `--dry-run` - see step 8), `auth:clear-resets` (both brokers),
     `queue:prune-failed`, session gc and the Pulse trim. The privacy page promises this cleanup.
6. **Pre-flight checks on the legacy DB** (read-only; run them against the live legacy DB):
   ```sql
   -- P1 role distribution (1 = pending, 4 = blocked - both are mapped, just know the numbers)
   SELECT id_role, COUNT(*) FROM users GROUP BY id_role;
   -- P2 duplicate e-mails after trim/lowercase (the import matches on e-mail; duplicates would merge two people)
   SELECT LOWER(TRIM(email)) e, COUNT(*) c FROM users GROUP BY e HAVING c > 1;
   -- P3 malformed e-mails or stray whitespace (fix in legacy first; these people can't reset passwords)
   SELECT id, CONCAT('[', email, ']') FROM users
    WHERE email <> TRIM(email) OR email NOT REGEXP '^[^@[:space:]]+@[^@[:space:]]+\\.[^@[:space:]]+$';
   -- P4 sign-up notes longer than 255 / holiday reasons longer than 500 (they get truncated)
   SELECT COUNT(*) FROM user_days WHERE CHAR_LENGTH(popis) > 255;
   SELECT COUNT(*) FROM user_holidays WHERE CHAR_LENGTH(popis) > 500;
   -- P5 every legacy week starts on a Thursday (DAYOFWEEK: 1 = Sunday, 5 = Thursday) - the lock import relies on it
   SELECT id, date_from FROM weeks WHERE DAYOFWEEK(date_from) <> 5;
   -- P6 baseline counts to compare after the import
   SELECT (SELECT COUNT(*) FROM users) users,
          (SELECT COUNT(DISTINCT ud.id_user, d.date) FROM user_days ud JOIN days d ON d.id = ud.id_day) signups,
          (SELECT COUNT(*) FROM user_holidays) holidays,
          (SELECT COUNT(*) FROM weeks WHERE locked = 1) locked_weeks;
   ```
7. **Rehearse the import** into the new DB (repeat until clean; add `--discard-new-data` if you tested
   sign-ups in between):
   ```bash
   php artisan app:import-legacy-data --team=<id> --force
   ```
   Then run the **post-import checks** (section 5) and compare them with P6.
8. **Preview the retention cleanup** on the imported data – its first real run deletes legacy accounts
   with no login and no sign-up in 12 months (managers are never pruned):
   ```bash
   php artisan app:prune-personal-data --dry-run
   ```
9. **Rehearsal smoke test** on the new app, with real data. If it runs on a public host (e.g. the beta
   domain), put it behind HTTP basic auth or an IP allow-list while it holds real personal data.
   Log in as a real manager (ask one) and check: the current week, past locked weeks are locked,
   absences, the users list, the Excel export, a password reset to your own address (mail + queue).
10. **Announce it to staff** 2–3 days ahead (template in section 8).

---

## 4. Cutover window (~15 min, pick a quiet hour – not just before a week gets locked)

| Time | Step |
|---|---|
| T-0 | **Freeze legacy**: in the legacy app folder `php artisan down --retry=60`. From now on nobody can write. |
| T+1 | **Back up both DBs**: `mysqldump --single-transaction --no-tablespaces d40700_sys > legacy-<date>.sql` (a SELECT-only user needs `--no-tablespaces` on MySQL ≥ 8.0.21); also dump the new DB (the rehearsal state). |
| T+3 | **Final import**: `php artisan app:import-legacy-data --team=<id> --force` |
| T+4 | **Verify**: post-import checks (section 5) must equal the P6 baseline taken *now* during the freeze – re-run P6. |
| T+6 | **Optimise** the new release: `php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache`, then `php artisan queue:restart`. |
| T+7 | **Switch the domain** to the new release (docroot/symlink → `<new>/public`, reload nginx/PHP-FPM, clear OPcache). |
| T+8 | **Smoke test on the production URL** (section 6). |
| T+15 | **Done**. Legacy **stays down** – never bring it back up writable next to the new app (split brain). |

## 5. Post-import checks (new DB)

```sql
SELECT COUNT(*) FROM users;                                   -- = legacy users
SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL;   -- = 0
SELECT COUNT(*) FROM team_user WHERE team_id = <id> AND approved_at IS NOT NULL;  -- = users minus id_role 1
SELECT COUNT(*) FROM users WHERE is_active = 0;               -- = legacy id_role 4 count
SELECT r.name, COUNT(*) FROM model_has_roles m JOIN roles r ON r.id = m.role_id
 WHERE m.team_id = <id> GROUP BY r.name;                      -- head-manager = legacy id_role 3
SELECT COUNT(*) FROM assignments WHERE team_id = <id>;        -- = P6 signups
SELECT COUNT(*) FROM absences    WHERE team_id = <id>;        -- = P6 holidays
SELECT COUNT(*) FROM week_locks  WHERE team_id = <id>;        -- = P6 locked_weeks
```
Spot check: pick 3 people, and compare their sign-ups for the current week and their absences between
legacy and new.

## 6. Smoke test (production URL, ~5 min)

- [ ] Log in as a real employee and as a real manager with their **existing** passwords.
- [ ] `/tyzdne` opens the right week; a locked week is shown locked; the sign-ups match legacy.
- [ ] An employee can sign up for a future, open day (then withdraw it again).
- [ ] `/dovolenka` shows the imported absences; `/admin/pouzivatelia` lists everyone with correct roles/states.
- [ ] The Excel export downloads.
- [ ] A password reset to your own address arrives (proves mail + the queue worker).
- [ ] `/ochrana-osobnych-udajov` shows your contact e-mail.
- [ ] A 404 shows the new error page; there's no stack trace anywhere (`APP_DEBUG=false`).
- [ ] `curl -sI https://<domain>/` shows `X-Frame-Options: DENY` and `Referrer-Policy`.
- [ ] `storage/logs` and `failed_jobs` are empty.

Web server (Apache on HostCreators – `.htaccess` in `public/`, or ask the hosting):
- http → https redirect (the app adds HSTS once it's served over https).
- gzip/brotli for HTML, CSS, JS, SVG, JSON – the week page is ~240 KB raw, ~16 KB compressed.
- Long cache for `/build/` (`Cache-Control: public, max-age=31536000, immutable`) and ~30 days for
  `/images/` – via `mod_headers` in `public/.htaccess`; the hashed build assets never change.
- CLI PHP defaults to 8.1 on this host: run every artisan/composer command with `php8.4`, and set the
  site's web PHP to 8.4 in the panel.
- `expose_php = Off` in php.ini, OPcache on.
- **Backups of the new DB** from go-live: daily dump or hosting snapshot, kept 30 days, one restore test
  during a rehearsal. After the point of no return it is the only copy of live data.

## 7. Rollback

Possible until the point of no return (D2):
1. Put the new app down: `php artisan down`.
2. Switch the domain back to the legacy docroot.
3. Run `php artisan up` in the legacy folder.

The legacy DB was never written to, so it's exactly as it was at T-0. Anything written in the new app
in between is lost; list those writes from the new DB before switching back, if any.

## 8. Staff announcement (template)

> **Nová verzia aplikácie na zmeny – {dátum} o {čas}**
> Na približne 15 minút bude aplikácia nedostupná. Potom sa prihlásiš rovnakým e-mailom a heslom ako
> doteraz (budeš odhlásený/á, stačí sa znova prihlásiť). Tvoje zápisy aj absencie zostávajú.
> Evidencia odpracovaných hodín a nahrané súbory v novej verzii nebudú – ak si chceš svoje hodiny
> ponechať, odpíš si ich do {dátum}.

## 9. After the cutover

- First days: watch `storage/logs`, `failed_jobs` and Pulse; check that new registrations get their verification mail.
- Remove `LEGACY_DB_*` from `.env` and drop the `cnmx_import` MySQL user once you're confident (no more imports).
- Keep the legacy dump/DB read-only for the agreed period (D4), then delete it.
- Later: retire `ImportLegacyData`.
