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
- livewire/livewire (LIVEWIRE) - v3
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

## Repository state: two things live here at once

1. **`app/`, `routes/`, `resources/`, `database/`** — the existing, live Laravel app ("CNMX Zmeny"), a shift-scheduling system for cinema staff. Legacy code slated for a full rewrite, not yet replaced.
2. **`_planning/`** — the complete design spec for the ground-up multi-tenant rewrite (written on `planning/multi-tenant-rewrite`, merged into this branch so the notes sit next to the code).

**Current branch: `feature/bootstrap` = Phase 0** of `_planning/11-implementation-order.md`. Rewrite code for *this* phase belongs here. Work for any *later* phase gets its own branch off `master` — confirm the phase before committing rewrite code.

When a task touches the current app, read `_planning/01-current-state.md` first — it lists what's intentionally kept vs. scrapped, which explains why parts of the current code look primitive (integer role IDs, no policies) even though better packages are installed.

## Where the notes and plans live

Read these before touching multitenancy, roles, or schema — they hold context not derivable from the code:

- **`_planning/README.md`** — index + key decisions. Then the numbered doc for your area (`02-database`, `03-multitenancy`, `04-spatie-permissions`, `05-filament`, …, `19-design-system`).
- **`_planning/claude-memory/`** — an older committed memory snapshot from the planning sessions (`project_cinema_decisions`, `project_cinema_scheduler`, `user_profile`, `feedback_style`).
- **Claude's live memory dir** (`~/.claude/projects/C--Users-Tom---Kozelek-PhpstormProjects-cnmx-zmeny/memory/`, indexed by `MEMORY.md`) — loaded automatically each session. Currently holds the as-built multi-tenant schema decisions (`project_multitenancy_design.md`), the unresolved legacy-role conflict (`project_multitenancy_legacy_roles.md`), and commit-style feedback (`feedback_git_commits.md`).

**Doc drift warning:** `_planning/11-implementation-order.md` still lists `stancl/tenancy` in Phase 0/1, but that decision was reversed — `_planning/README.md` and `03-multitenancy.md` are authoritative: **single database, Spatie Permission teams**. Trust the README over doc 11 on tenancy.

## Skills in `.claude/skills/`

Generated by `laravel/boost`, not hand-written: `laravel-best-practices` (with `rules/*.md`), `livewire-development`, `pulse-development`, `tailwindcss-development`. Activate the relevant one when working in that domain; don't hand-edit — Boost regenerates them.

## Commands

No test suite or linter/static-analysis config exists yet (no `tests/`, `pint.json`, `phpstan.neon`; `phpunit.xml` is gitignored) — `php artisan test` will find nothing to run. Pint and PHPStan are installed and work on defaults.

```
composer install              # PHP deps
npm install                   # JS deps
npm run dev                   # Vite dev server
npm run build                 # Vite production build
php artisan serve             # local server
php artisan migrate           # run migrations
docker compose up -d          # local MySQL/Redis (docker-compose.yml)
```

Ad-hoc app commands (`app/Console/Commands/`): `ClearAllCaches`, `ClearWeeksAfterYear`.

## Testing philosophy

When adding tests, write only feature tests for **core user-facing flows**, not exhaustive coverage. One happy-path (+ one meaningful failure case where it matters, e.g. a locked week) per flow is enough. Concretely:

- A user signing up for / being removed from a day (`DayUserController::toggleUser`/`destroy`)
- Marking, ending, and deleting an absence/holiday (`HolidayController`)
- Admin accepting (role change from unverified), editing, and deleting a user (`AdminUserController`, `AdminUserEditController`)
- Locking a week and confirming it becomes read-only (`CalendarController::lock`)

Do **not** test what a reader can already verify from the code: getters/casts, route registration, validation-rule presence, framework/package behavior, or trivial CRUD with no business rule. If a test doesn't assert something someone could get wrong, skip it.

## Current app architecture (`app/`)

Laravel 13, but wired with the pre-Laravel-11 structure: `app/Http/Kernel.php` (not `bootstrap/app.php`) defines middleware groups/aliases, and `app/Exceptions/Handler.php` handles exceptions.

**Auth & roles** — roles are a plain integer FK (`users.id_role` → legacy `roles` table), defined in `config/constants.php` (`neovereny`=1 unverified, `brigadnik`=2 worker, `admin`=3, `zablokovany`=4 blocked), checked via custom `User::hasRole(int)`/`User::isAdmin()`. Two middleware aliases gate routes:
- `allowed` (`EnsureUserIsAllowed`) — lets `admin`/`brigadnik` through, logs out and redirects blocked/unverified users with a Slovak flash message.
- `role:{n}` (`EnsureUserHasRole`) — hard role-ID gate, e.g. `role:3` for admin-only routes.

Spatie Permission is now configured (`config/permission.php`: `teams => true`, and its roles table renamed to `permission_roles` to avoid colliding with the legacy `roles` table) but **not yet wired into `User`** — see the legacy-role conflict memory before adding `HasRoles`.

**Tenancy** — single-database, one team = one tenant: `teams` + `team_user` pivot, `users.current_team_id`, explicit `team_id` on `weeks`/`shifts`/`rates`/`file_storage`/`user_holidays` (migrations `2026_07_18_1000*`). `days`/`user_days` reach their team through `weeks`; `bugs` is deliberately global. The dead `stancl/tenancy` scaffolding (`config/tenancy.php`, `app/Models/Tenant.php`, `routes/tenant.php`, `TenancyServiceProvider`, tenants/domains migrations) has been deleted.

**Week/day scheduling model** — the core domain. `WeekService` generates `Week` + `Day` rows (a `Week` has 7 `Day`s); the work week starts next Thursday (`WeekService::generateWeek()`), not Monday. `Week.locked` freezes a week from further edits. `Day` ↔ `User` is a many-to-many pivot (`user_days`, only a free-text `popis` — no position or time slot; `_planning/01-current-state.md` calls this too flat and slates it for a proper `work_plan_assignments` model). `CalendarController` + `DayUserController` drive the employee week view; `AdminController`/`AdminUserController`/`AdminUserEditController` drive admin management, all behind `role:3`.

**Other domain pieces**: `Holiday`/`HolidayService` (unavailability marking — planned rename to `UnavailabilityService` with deadline enforcement), `Shift`/`Rate` (hours + pay-rate tracking), `File`/`FileService`/`FileUploadController` (per-week uploads, reused via Excel export), `Bug`/`BugReportController` (in-app bug reporting — planned for removal).

**Frontend**: server-rendered Blade under `resources/views/` (one directory per feature area — `admin`, `calendar`, `holiday`, `hours`, `roles`, `users`, …) plus one Livewire component (`app/Livewire/UserTable.php`) for the admin user table. Tailwind + Flowbite, Vite for assets (a `webpack.mix.js`/`laravel-mix` leftover still sits in the repo — Vite is the active pipeline per `package.json`).

**Locale**: Slovak-first (`config/app.php` locale `sk`, fallback `en`). Validation messages, flash messages, and route URIs (`/prihlasenie`, `/registracia`, `/dovolenka`) are Slovak — keep new user-facing strings consistent.

## The planned rewrite (`_planning/`)

Target architecture (not yet implemented):

- **Single database**, multi-tenant via **Spatie Permission teams** (one team = one cinema), scoped through Filament's native `->tenant()` — no separate tenant databases.
- **Filament v3** for all admin/management work; **Livewire v3** stays for the employee-facing calendar/unavailability/hours UI.
- Full DB redesign: no `mediumInt` PKs, no integer role magic numbers, positions become a first-class team-scoped model, and the flat `user_days` pivot is replaced by a template-driven work-plan/assignment system.
- Phase order in `_planning/11-implementation-order.md` (Phase 0 bootstrap → Phase 11 data migration), each phase its own branch, merged to `development`, with its own definition of done (tests, PHPStan level 6, Pint, PR review).
