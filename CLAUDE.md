<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- filament/filament (FILAMENT) - v5
- laravel/pennant (PENNANT) - v1
- spatie/laravel-permission (PERMISSION) - v6
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>

# CLAUDE.md

## Working guidelines

- **Think before coding.** State assumptions. If a request has more than one reading, name them instead of silently picking one; if something is unclear, ask before building. Say so when a simpler approach exists.
- **Simplicity first.** Minimum code that solves the request — no speculative features, single-use abstractions, unrequested configurability, or handling for impossible cases.
- **Surgical changes.** Every changed line should trace to the request. Don't reformat or refactor adjacent code. Remove orphans your own change created (imports, variables, functions); mention pre-existing dead code rather than deleting it.
- **Verify before calling it done.** Turn the task into a checkable goal (a test that fails then passes, a page that renders correctly) and loop until it holds. For multi-step work, state a short plan with a check per step. Tests still follow the Testing philosophy below — don't add tests for flows it excludes.

## Repository state

`feature/bootstrap` holds the **rewritten multi-tenant app** ("Cinemax Zmeny"): shift sign-ups for cinema staff, one team = one cinema, single database. It replaces the legacy app on `master` (integer roles, `weeks`/`days`/`user_days`), which is still what production runs until the cutover. `_planning/` is the design history; where it disagrees with the code, the code and the newest numbered docs (20+) win.

- **Cutover / data migration:** `_planning/27-legacy-data-migration.md` (plan, importer gaps, pre-launch list) and `docs/deploy-cutover-legacy-to-new.md` (runbook). Importer: `app:import-legacy-data`.
- **Operator & privacy:** run privately by Tomáš (GDPR controller, hobby project - no company). Keep legal copy minimal; the privacy page (`resources/views/privacy/index.blade.php`, `config/privacy.php`) must match what the code does (retention in `app:prune-personal-data`, log scrubbing).

## Where the notes live

- `_planning/README.md` - index. Authoritative for the built schema: `20-schema-rework-handoff.md`, `21-backend-rewrite-progress.md`, `23-rozpis-position-assignment.md`, `26-platform-admin-handoff.md`, `27-legacy-data-migration.md`.
- `_planning/claude-memory/` - an older memory snapshot. Live memory is loaded automatically from `~/.claude/projects/.../memory/MEMORY.md`.
- `CACHING.md` - every application cache key and where it is invalidated. Check it before adding one.

## Skills in `.claude/skills/`

Generated by `laravel/boost` (`laravel-best-practices`, `livewire-development`, `pulse-development`, `tailwindcss-development`, ...). Activate the relevant one; don't hand-edit - Boost regenerates them.

## Commands

```
composer install && npm install
npm run dev / npm run build      # Vite
php artisan serve                # local server (no Herd on this machine)
docker compose up -d             # local MySQL (cnmx_zmeny-mysql-1)
php artisan migrate              # never --seed against anything real; DatabaseSeeder is demo data
php artisan test --compact       # PHPUnit on SQLite :memory: (phpunit.xml.dist; local phpunit.xml is gitignored)
vendor/bin/pint --dirty          # formatting
vendor/bin/phpstan analyse       # Larastan level 6; existing errors live in phpstan-baseline.neon - new code must not add any
```

App commands: `app:import-legacy-data` (legacy -> new, see doc 27), `app:prune-personal-data` (retention, daily 03:30, `--dry-run`), `app:super-admin {email} [--revoke]`, `permissions:sync` (roles/permissions from `RolePermissionSeeder`), `cache:allclear`. The scheduler (`app/Console/Kernel.php`) and a queue worker are both required in production.

## Testing philosophy

Feature tests for **core user-facing flows** only - one happy path plus one meaningful failure case per flow:

- Signing up for / withdrawing from a day (`DayCard`, `AssignmentController`), locked and past weeks refused
- Creating, ending and deleting an absence (`AbsenceController`, absence data tables)
- Approving/denying a member, editing roles, removing/deactivating a user (`UsersDataTable`, `Admin\UserController`)
- Locking a week and it becoming read-only (`WeekLockController`)
- Rozpis: building, publishing, notifications (`RozpisDay`, `RozpisPublishController`)
- Tenant isolation, super-admin session, retention pruning, legacy import

Do **not** test what a reader can verify from the code: getters/casts, route registration, validation-rule presence, framework/package behaviour, trivial CRUD. SQLite tests can't catch MySQL strict-mode/collation behaviour - rehearse those against MySQL.

## App architecture (`app/`)

Laravel 13 with the pre-Laravel-11 structure: `app/Http/Kernel.php` defines middleware groups, `app/Exceptions/Handler.php` handles exceptions, providers are listed in `config/app.php`.

**Tenancy** - single DB, one `Team` = one cinema. Team-owned models use `BelongsToTeam` (global scope from Spatie's registrar team id). The `tenant` middleware group (`auth`, `AuthenticateSession`, `EnsureUserIsActive`, `SetCurrentTeam`, `SubstituteBindings`, `throttle:member-writes`) wraps everything behind login; `SetCurrentTeam` must run before bindings so another cinema's record is a 404. The same middleware is re-applied to Livewire requests (`AppServiceProvider::persistTenantMiddlewareThroughLivewire`). `User` is not team-scoped (one person, many cinemas via `team_user`).

**Roles** - Spatie Permission in teams mode, `App\Enums\Role`: `head-manager`, `manager`, `employee` (UI: "brigádnik"). Permissions come from `RolePermissionSeeder` (sync with `permissions:sync`); policies in `app/Policies` check `hasPermissionInTeam()`. States are not roles: blocked = `users.is_active = false`, pending = `team_user.approved_at IS NULL`, unverified = `email_verified_at IS NULL`. Super admin = `users.is_super_admin` and only counts with an active `/platform` session (TOTP) - see `SuperAdminSession`.

**Scheduling** - weeks are computed, not stored: `WeekService` derives them from `team_settings.week_start_day` (default Thursday). Sign-ups are `assignments(team_id, user_id, date, note, position_id?, position_slot_id?)`, one per user per day. A `week_locks` row with `is_locked` freezes a week; `rozpis_published_at` publishes the rozpis. Absences: `absences` (`date_from`/`date_to`, `status` active/cancelled, `reason` required). Removing or deactivating a user withdraws their sign-ups from today on (`User::withdrawFutureAssignments`).

**Rozpis** (per-cinema module, Pennant `App\Enums\TeamFeature::Rozpis`, `RozpisAi` for the Gemini suggestion; off by default) - positions/groups/slots, the `RozpisDay` Livewire builder, `RozpisService`, `FairnessService`, publish + debounced "changed" mails (`RozpisNotificationService`, `SendRozpisChangeNotification`), Excel export (`RozpisExport`).

**Platform** - Filament v5 panel at `/platform` (`app/Filament/Platform`, separate `platform` guard, mandatory TOTP) for the operator: cinemas, users, modules, audit (`PlatformAudit` -> activity_log).

**Logging** - `daily` (app, exceptions, `Loggable` audit lines) and `security` (logins, failed logins, role changes, approvals, 403/429) channels, both scrubbed of personal data by `App\Logging\ScrubPersonalData`; every line carries `request_id`, `user_id`, `team_id`, `ip_hash` (Laravel Context). Never log names, e-mails or absence reasons - log ids.

**Frontend** - Blade views per feature area in `resources/views/` (`calendar`, `holiday` = absences page, `rozpis`, `admin`, `profile`, `nastavenia`, ...), Livewire 4 class components in `app/Livewire`, `rappasoft/laravel-livewire-tables` for the users/absences tables, Tailwind v3 + Alpine, Vite, self-hosted fonts/icons (no CDN - the CSP and the privacy page depend on it). No PHP logic in Blade: compute in the controller/component/view composer.

**Locale** - Slovak UI (`sk`). Terms: "absencia" (not "dovolenka"; the URL `/dovolenka` stays), "brigádnik/brigádnici" for workers, "rozpis", "kino". Dates as `5. 11. 2026`.
