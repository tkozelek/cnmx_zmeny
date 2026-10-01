# Cinemax shift planner

Internal web application built for the staff of a Cinemax cinema in Žilina. Part-time employees sign up
for working days in a weekly calendar, track their worked hours and holidays, and managers lock and
export the final weekly schedule. Built and run by me alongside my part-time job there (2024-2025).
The user interface is in Slovak.

## Features

- **Weekly calendar**: weeks are generated automatically. Employees sign up for individual days and can
  add a note. The calendar shows who signed up for each day.
- **Schedule management** (manager role): remove sign-ups, lock a week so it can no longer change,
  export the week to Excel.
- **Worked hours**: employees record their shifts (date, start, end, break) and set their own hourly
  rates for weekdays, Saturdays and Sundays. The app sums hours and estimated earnings per day type.
  Managers can view any employee's hours.
- **Holidays**: employees enter holiday periods, and can end or cancel them early.
- **User management**: self-registration, then manager approval. Roles: unverified, part-timer,
  manager, blocked. Middleware keeps unapproved and blocked users out.
- **Files**: managers upload documents that employees can download.
- **Other**: e-mail notifications (password reset, account approved), bug report form, help page,
  password change, login and registration rate limiting.

## Tech stack

PHP 8.4, Laravel 12, Blade, Livewire 3, Tailwind CSS 3, Flowbite, Chart.js, SortableJS, Vite,
MySQL, maatwebsite/excel (exports). Authentication is implemented without a starter kit.

## Data model

| Table | Purpose |
|-------|---------|
| `users`, `roles` | accounts and their role |
| `weeks`, `days` | generated calendar weeks (linked to previous and next week) and their days |
| `user_days` | an employee's sign-up for a day, with a note |
| `shifts` | worked shifts (date, start, end, break) |
| `rates` | an employee's hourly rates per day type |
| `user_holidays` | holiday periods |
| `file_storage` | uploaded documents |
| `bugs` | bug reports |

## Running locally

Requirements: PHP 8.4, Composer, Node.js, MySQL.

```bash
composer install
npm install
# create .env with the standard Laravel settings (APP_KEY, DB_*, MAIL_*)
php artisan key:generate
php artisan migrate     # also creates the roles and an initial admin account
npm run dev
php artisan serve
```

Maintenance commands: `php artisan app:clear-weeks-after-year` removes calendar weeks older than
a year, and `php artisan cache:allclear` clears all caches.
