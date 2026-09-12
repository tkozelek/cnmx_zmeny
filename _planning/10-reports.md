# 10 — Reports & Exports

## Report Types

| Report | Who can access | Format | Trigger |
|---|---|---|---|
| Weekly Work Plan | admin, manager | PDF + Excel | Manual (per week) |
| Hours Summary | admin, manager | Excel | Manual (per month/week) |
| Payroll Calculation | admin only | Excel | Manual (per month) |
| Position Coverage | admin, manager | PDF (visual) | Manual (per week) |
| Unavailability Summary | admin, manager | Excel | Manual (per week/month) |
| User Activity | admin only | PDF/Excel | Manual (date range) |

---

## Weekly Work Plan Export

**Purpose:** Printable schedule showing who works when in which position.

**Format:** Excel (.xlsx) + PDF

### Excel Layout

Sheet 1: "Plán práce"

```
Kino Lumière — Pracovný plán: 12.6.2025 – 18.6.2025

Pozícia     | Čas   | Štv 12.6 | Pia 13.6 | Sob 14.6 | Ned 15.6 | Pon 16.6 | Uto 17.6 | Str 18.6
------------|-------|----------|----------|----------|----------|----------|----------|----------
Uvádzač     | 13:00 | Martin K.| Jana N.  | Eva P.   | ...      | ...      | ...      | ...
Uvádzač     | 15:00 | Peter S. | Tomáš D. | ...      | ...      | ...      | ...      | ...
Bufet       | 13:00 | Anna M.  | ...      | ...      | ...      | ...      | ...      | ...
Pokladňa    | 13:00 | ...      | ...      | ...      | ...      | ...      | ...      | ...
Vedúci      | —     | Mgr. Nov.| Mgr. Nov.| ...      | ...      | ...      | ...      | ...

Generované: 10.6.2025 14:32 | Uzamknuté: áno
```

Sheet 2: "Nedostupnosť" — list of all unavailability for the week:
```
Zamestnanec  | Dátum    | Dôvod
-------------|----------|-------------
Jana N.      | 14.6.    | Lekár
Peter S.     | 15.6.    | —
```

### PDF Layout

Landscape A4. One table per sheet. Filament's `PdfExport` or a dedicated `DomPDF`/`Browsershot` renderer.
- Header: Cinema name, week dates, lock status
- Table: same as Excel Sheet 1 but formatted for print
- Footer: "Generované [datetime] | [user who generated]"

**Implementation:**

```php
// app/Exports/WeeklyPlanExport.php (Maatwebsite Excel)

class WeeklyPlanExport implements WithMultipleSheets
{
    public function __construct(private Week $week) {}

    public function sheets(): array
    {
        return [
            new PlanSheet($this->week),
            new UnavailabilitySheet($this->week),
        ];
    }
}
```

Triggered from Filament `ExportPlanAction` on `WeekResource`:
```php
return Excel::download(new WeeklyPlanExport($week), "plan-{$week->date_from}.xlsx");
```

---

## Hours Summary Export

**Purpose:** List of all shifts worked by all employees in a date range, with totals.

**Format:** Excel

```
Kino Lumière — Odpracované hodiny: Jún 2025

Zamestnanec | Dátum    | Pozícia    | Od    | Do    | Prestávka | Hodín | Sadzba | Odmena
------------|----------|------------|-------|-------|-----------|-------|--------|--------
Martin K.   | 12.6.    | Uvádzač    | 13:00 | 21:00 | 30 min    | 7.5h  | 5.50 € | 41.25 €
Jana N.     | 13.6.    | Uvádzač    | 13:00 | 21:00 | 30 min    | 7.5h  | 5.50 € | 41.25 €
...
------------|----------|------------|-------|-------|-----------|-------|--------|--------
Martin K.   | SPOLU    |            |       |       |           | 32h   |        | 176.00 €
Jana N.     | SPOLU    |            |       |       |           | 30h   |        | 165.00 €
------------|----------|------------|-------|-------|-----------|-------|--------|--------
CELKOM      |          |            |       |       |           | 248h  |        | 1364.00 €
```

Weekend days apply the `rates.saturday` / `rates.sunday` rate automatically.

**Implementation:**

```php
// app/Exports/HoursSummaryExport.php

class HoursSummaryExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        private Carbon $from,
        private Carbon $to,
        private ?int $userId = null  // null = all users
    ) {}

    public function query(): Builder
    {
        return Shift::with(['user', 'position', 'user.rates'])
            ->whereBetween('date', [$this->from, $this->to])
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->orderBy('user_id')
            ->orderBy('date');
    }
}
```

---

## Payroll Calculation Export

**Purpose:** Per-employee monthly summary ready for accounting. Admin-only.

**Format:** Excel (one sheet per employee, or one sheet with all)

Each row = one employee. Columns:
- Name + lastname
- Total weekday hours
- Total Saturday hours  
- Total Sunday hours
- Total break deduction
- Weekday rate × weekday hours
- Saturday rate × Saturday hours
- Sunday rate × Sunday hours
- Break deduction amount
- **Total gross pay**

**Implementation:** Same `Maatwebsite\Excel` approach, but with aggregation queries.

---

## Position Coverage Report

**Purpose:** At-a-glance view of how well each day in a week is staffed.

**Format:** PDF (visual — colored cells)

```
Kino Lumière — Obsadenosť pozícií: 12.6.–18.6.2025

Pozícia / Čas | Štv | Pia | Sob | Ned | Pon | Uto | Str
--------------|-----|-----|-----|-----|-----|-----|----
Uvádzač 13:00 |  ✓  |  ✓  |  ✓  |  ⚠  |  ✓  |  ✓  |  ✓  ← ⚠ = not fully staffed
Bufet 13:00   |  ✓  |  ✗  |  ✓  |  ✓  |  ✓  |  ✓  |  ✓  ← ✗ = no one assigned
Vedúci        |  ✓  |  ✓  |  ✓  |  ✓  |  ✓  |  ✓  |  ✓

Legend: ✓ fully staffed  ⚠ partially staffed  ✗ not assigned
```

**Implementation:** Generate as a Filament `InfollistSection` view for on-screen display, and use `barryvdh/laravel-dompdf` or `spatie/browsershot` for PDF.

---

## Unavailability Summary

**Purpose:** Overview of which employees are unavailable across a date range. Useful for planning.

**Format:** Excel

```
Zamestnanec   | Dátum    | Deň    | Dôvod     | Prihlásené   | Admin override
--------------|----------|--------|-----------|--------------|---------------
Jana N.       | 14.6.    | Sobota | Lekár     | 11.6. 09:15  | Nie
Peter S.      | 15.6.    | Nedeľa | —         | 10.6. 22:48  | Nie
Eva P.        | 13.6.    | Piatok | Dovolenka | 08.6. 14:00  | Áno (admin: Mgr. Novák)
```

---

## User Activity Log Report

**Purpose:** Audit trail for admins. Who changed what and when.

**Format:** PDF / Excel (date range filter)

Queries `activity_log` table (Spatie Activity Log).

Columns: Date, User, Action (created/updated/deleted), Subject (model + ID), Changes (old → new values).

---

## Reports Page (Filament)

A dedicated Filament `ReportsPage` under the "Prehľady" navigation group.

UI sections:
1. **Plán práce** — week selector + [Stiahnuť Excel] [Stiahnuť PDF]
2. **Odpracované hodiny** — date range picker + user filter (optional) + [Stiahnuť Excel]
3. **Mzdy** — month picker + [Stiahnuť Excel] *(admin only)*
4. **Obsadenosť pozícií** — week selector + [Zobraziť] [Stiahnuť PDF]
5. **Nedostupnosť** — date range picker + [Stiahnuť Excel]
6. **Aktivita** — date range picker + user filter + [Stiahnuť Excel] *(admin only)*

All downloads are triggered as `return response()->download()` — no queued export needed for typical cinema-scale data (< 100 employees × 31 days = small).

If exports grow large in future: queue them with `Laravel Excel`'s `ShouldQueue` interface and notify admin by email when ready.

---

## Scheduled Reports (Optional Future Feature)

Monthly payroll summary auto-emailed to cinema admin on the 1st of each month:

```php
// routes/console.php
Schedule::command('reports:monthly-hours')
    ->monthlyOn(1, '08:00')
    ->runInBackground();
```

```php
// app/Console/Commands/SendMonthlyHoursReport.php
Tenant::all()->each(function (Tenant $tenant) {
    tenancy()->initialize($tenant);

    $admins = User::role('admin')->get();
    $export = new HoursSummaryExport(now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth());
    $file   = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

    foreach ($admins as $admin) {
        $admin->notify(new MonthlyHoursReportNotification($file, now()->subMonth()));
    }

    tenancy()->end();
});
```
