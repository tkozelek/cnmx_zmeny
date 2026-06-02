# 16 — Analytics Dashboard

## Purpose

Give admins and managers insight into how the cinema is staffed — who works the most, which positions are hardest to fill, absence patterns, and marketplace activity. All data comes from existing tables, no external analytics service needed.

---

## Where It Lives

A dedicated Filament page: **"Analytika"** under the "Prehľady" navigation group.  
Permission: `reports.analytics` (add to the permissions list in doc 04, assign to admin + manager).

The page is split into four sections:

1. **Hodiny & Záťaž** (Hours & Workload)
2. **Obsadenosť pozícií** (Position Coverage)
3. **Neprítomnosť** (Absence Patterns)
4. **Burza smien** (Marketplace Activity)

Each section has a date range filter at the top (default: last 30 days). Charts are rendered with Filament's built-in chart widgets (powered by Chart.js — already bundled with Filament).

---

## Section 1: Hodiny & Záťaž

### Chart: Hours per employee (bar chart, stacked by day type)

X-axis: employee name (sorted by total hours desc)  
Y-axis: hours  
Stacks: Weekday (blue) / Saturday (orange) / Sunday (red)

Data query:
```php
Shift::whereBetween('date', [$from, $to])
    ->selectRaw('user_id, SUM(TIMESTAMPDIFF(MINUTE, start, end) / 60 - break_minutes / 60) as total_hours')
    ->selectRaw('SUM(CASE WHEN DAYOFWEEK(date) = 7 THEN ... END) as saturday_hours')
    ->selectRaw('SUM(CASE WHEN DAYOFWEEK(date) = 1 THEN ... END) as sunday_hours')
    ->groupBy('user_id')
    ->with('user')
    ->get();
```

### Chart: Hours trend over time (line chart)

X-axis: week date ranges  
Y-axis: total hours worked across all employees  
Shows if staffing levels are increasing, decreasing, or consistent.

### Stat cards (top of section)

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│  Celkové hodiny  │  │  Priem. hodín /  │  │  Najviac hodín   │
│     248 h        │  │    zamestnanec   │  │  Martin K. 42h   │
│  za posledných   │  │      18.4 h      │  │                  │
│    30 dní        │  │                  │  │                  │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

### Table: Per-employee breakdown

Sortable, filterable table below the chart:

| Zamestnanec | Pracovné dni | Víkendy | Celkom hodín | Celkom odmena |
|---|---|---|---|---|
| Martin K. | 12 | 4 | 42h | 231€ |
| Jana N. | 10 | 6 | 38h | 209€ |

"Celkom odmena" shown only to admins (hidden from manager role via Filament's `visible()` conditional).

---

## Section 2: Obsadenosť pozícií

### Chart: Fill rate per position (horizontal bar chart)

Shows for each position: what % of planned slots were actually filled (had a user assigned when the week was locked).

```
Vedúci      ████████████████████ 100%
Pokladňa    ████████████████░░░░  82%
Uvádzač     ███████████████░░░░░  78%
Bufet       █████████████░░░░░░░  68%
```

Data:
```php
// For each position, count plan_slots in locked weeks and how many had assignments
PlanSlot::whereHas('day.week', fn ($q) => $q->where('locked', true))
    ->whereBetween('day.date', [$from, $to])  // join days
    ->withCount(['assignments as filled' => fn ($q) => $q->whereNotNull('user_id')])
    ->withCount('assignments as total')
    ->with('position')
    ->get()
    ->groupBy('position_id');
```

### Chart: Daily fill rate heatmap

Calendar-style heatmap (7 columns = days of week, rows = weeks in range).  
Colour intensity = % of slots filled that day.  
Dark green = fully staffed, red = critically understaffed.

> Implementation note: Filament doesn't have a built-in heatmap widget. Options: (a) render a custom Blade view inside a Filament widget, (b) use a Chart.js matrix plugin, (c) use a simple HTML table with inline background-color styles. Option (c) is easiest and most maintainable.

### Hardest-to-fill positions

A simple ranked list: "Tieto pozície sú najčastejšie neobsadené keď sa týždeň uzamkne:"

1. Bufet — 32% plánovaných zmien bez obsadenia
2. Pokladňa — 18%
3. Uvádzač — 22%

Useful for deciding where to hire or train more staff.

---

## Section 3: Neprítomnosť

### Chart: Absence submissions over time (line chart)

X-axis: weeks  
Y-axis: number of absence records submitted  
Separate lines for single-day, multi-day, recurring-affected days

Shows seasonal patterns (holiday seasons, exam periods).

### Chart: Absence frequency per employee (bar chart)

Which employees submit absence most often. Not meant as punitive — useful for scheduling planning ("Jana is frequently unavailable on Fridays").

### Stat cards

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│  Tento mesiac    │  │  Opakujúce sa    │  │  Prepadnuté      │
│  14 neprítomností│  │  5 aktívnych     │  │  termíny         │
│                  │  │  vzorcov         │  │  3 tento mesiac  │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

"Prepadnuté termíny" = absence requests that were submitted but past the deadline (admin had to override). High number here suggests the deadline is too tight or employees aren't being reminded.

### Conflict rate

"V X % prípadov bola nahlásená neprítomnosť pre deň, kde zamestnanec už bol zaradený do plánu."

This helps admin evaluate whether to adjust the workflow (plan first, then let employees report absence = conflict-prone; vs. collect absences first, then plan = better).

---

## Section 4: Burza smien

Only shown if `tenant_settings.marketplace_enabled = true`.

### Stat cards

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│  Celkom požiadaviek│ │  Úspešne vymenených│ │  Vypršalo bez    │
│  za 30 dní: 12   │  │       9 (75%)    │  │  náhrady: 3      │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

### Chart: Marketplace activity per position (bar chart)

Which positions get the most replacement requests — signals where employees are most reluctant to work or have the most conflicts.

### Chart: Time-to-fill (scatter or box plot)

For filled listings: how many hours between posting and an offer being accepted.  
Useful for tuning the `expires_at` default (if most fill within 4h, a 3-day expiry is unnecessarily long).

### Table: Recent listing history (last 20)

| Pozícia | Dátum zmeny | Zverejnil | Záskok | Čas do záskoky | Stav |
|---|---|---|---|---|---|
| Uvádzač 13:00 | Sob 14.6. | Jana N. | Peter S. | 3h 22min | Obsadené |
| Bufet 15:00 | Ned 15.6. | Tomáš D. | — | — | Vypršalo |

---

## Implementation Details

### Filament Chart Widgets

Each chart is a separate Filament `ChartWidget` subclass:

```php
// app/Filament/Widgets/HoursPerEmployeeChart.php
class HoursPerEmployeeChart extends ChartWidget
{
    protected static ?string $heading = 'Hodiny podľa zamestnanca';
    protected string $dataChecksum = '';  // invalidate cache on filter change

    protected function getData(): array
    {
        // Return Chart.js dataset format
        return [
            'datasets' => [...],
            'labels'   => [...],
        ];
    }

    protected function getType(): string { return 'bar'; }
}
```

### Date Range Filter

A shared filter component on the Analytics page header. Uses Livewire to re-render all widgets when the range changes. Options:
- Ostatných 7 dní
- Ostatných 30 dní (default)
- Tento mesiac
- Minulý mesiac
- Vlastné obdobie (custom date range picker)

### Caching

Analytics queries can be expensive on larger datasets. Cache each chart's data for 15 minutes:

```php
protected function getData(): array
{
    return Cache::remember(
        "analytics:hours:{$this->dateFrom}:{$this->dateTo}",
        now()->addMinutes(15),
        fn () => $this->buildData()
    );
}
```

Cache key includes the tenant ID automatically (Stancl prefixes the cache).

---

## Exports from Analytics

Each chart/table has a small "Stiahnuť" button that exports the underlying data as Excel.  
These reuse the `Maatwebsite\Excel` export classes from `10-reports.md`.

---

## Implementation Checklist

- [ ] `reports.analytics` permission — add to permissions list (doc 04), assign to admin + manager
- [ ] `AnalyticsPage` Filament page + navigation entry
- [ ] Date range filter component (shared Livewire state across widgets on the page)
- [ ] `HoursPerEmployeeChart` widget
- [ ] `HoursTrendChart` widget
- [ ] `HoursBreakdownTable` widget (with conditional pay column for admin only)
- [ ] `PositionFillRateChart` widget
- [ ] `DailyFillHeatmap` widget (custom Blade-based)
- [ ] `HardestToFillList` computed stat
- [ ] `AbsenceTrendChart` widget
- [ ] `AbsenceFrequencyChart` widget
- [ ] `AbsenceStatCards` widget
- [ ] `MarketplaceStatCards` widget (conditional on setting)
- [ ] `MarketplaceByPositionChart` widget (conditional)
- [ ] `MarketplaceListingHistoryTable` widget (conditional)
- [ ] 15-minute cache on all chart data queries
- [ ] Export buttons on charts/tables
- [ ] Feature tests: data accuracy for each query (use factories to seed predictable data)
