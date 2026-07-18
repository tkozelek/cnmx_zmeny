# 19 — Design System (UI/UX)

Visual direction: **clean modern SaaS dashboard** — whitespace-driven, card-based, restrained color, clear hierarchy (think Linear/Notion, not a dense ERP grid). No existing brand constraints — palette below is a proposal, not a lock-in.

This doc is the shared reference for both halves of the rewrite: Filament (`_planning/05-filament.md`) skins itself with it via `->colors()` / a custom panel theme; Livewire (`_planning/06-livewire.md`) consumes it through the shared Blade components already inventoried there (`components/ui/*`). Don't duplicate tokens in either doc — point back here.

---

## Why the current app reads as dated

Concrete, fixable causes (not vibes) — worth naming so the rewrite doesn't repeat them:
- No consistent spacing/type scale — Blade views were styled per-page, not from shared components.
- Flat data tables with no row hierarchy, no empty states, no loading states.
- Status is implicit (locked week, unavailable day) instead of an explicit badge/color.
- Flowbite/Tailwind used mostly for one-off form controls, not for layout rhythm.

The fixes below are about systemizing what's already in the stack (Tailwind + Filament + Flowbite/Alpine), not adding new UI frameworks.

---

## Color

One primary, one neutral ramp, four semantic colors. Everything else in the UI is these five.

| Role | Token | Tailwind base | Use |
|---|---|---|---|
| Primary | `primary` | `indigo` (500/600 for actions, 50/100 for tints) | Buttons, links, active nav, focus rings |
| Neutral | `slate` | full ramp | Text, borders, backgrounds, cards |
| Success | `emerald` | 500/600 | Slot filled, week unlocked-and-complete, saved |
| Warning | `amber` | 500/600 | Deadline approaching, unfilled slot, pending approval |
| Danger | `rose` | 500/600 | Locked-with-conflict, delete actions, validation errors |
| Info | `sky` | 500/600 | Neutral notices, "read the plan" hints |

Rules:
- Page background `slate-50` (`slate-950` dark), cards `white`/`slate-900` on top — one visible elevation step, not stacked shadows.
- Color communicates *state*, never decoration. If a badge color doesn't map to one of the five roles above, it's wrong.
- Filament: set `->colors(['primary' => Color::Indigo])` in both panel providers (already stubbed in `05-filament.md`) — don't hand-pick a second primary for the employee portal, one brand color across both surfaces.
- **Dark is the default, not an afterthought.** Design every screen against `slate-950`/`slate-900` first, then check it in light. Filament: `->darkMode()->defaultThemeMode(ThemeMode::Dark)`. Blade: put `class="dark"` on `<html>` by default (still respect a user override via `prefers-color-scheme`/a stored toggle — don't force it, just default to it). Every component in doc 06's `components/ui/*` needs a `dark:` variant before it's considered done, not bolted on later.

## Responsive strategy

The two surfaces have different primary devices — design them in opposite directions.

- **Employee portal (Livewire) — mobile-first.** Workers check their schedule/mark absence from a phone. Write base (unprefixed) Tailwind classes for a single-column phone layout, then use `sm:`/`md:` to add columns/grid for desktop. `CalendarWeek` is the case that matters most: base styles = one day at a time with swipe/prev-next nav (already specced in doc 06), `md:` breakpoint = full 7-day grid. Touch targets stay ≥44px at every breakpoint, not just mobile.
- **Admin panel (Filament) — desktop-first.** Plan-building, drag-and-drop assignment (`WeekPlanPage`), and reports are data-dense tasks nobody does one-handed on a phone. Design against a ~1280px canvas first; Filament's own responsive table/nav collapse handles the phone fallback automatically — don't spend effort hand-tuning admin layouts for mobile beyond that default.

## Typography

- **Inter** (variable), loaded via `bunny-fonts`/self-hosted — matches Filament's own default, so admin and employee portal already share it for free without extra config.
- Scale: `text-sm` body (14px), `text-base` for primary reading content, `text-lg`/`text-xl`/`text-2xl` for card titles / page titles / dashboard headline numbers. Stop there — three heading sizes is enough for an app this shallow.
- Weight: `font-medium` for emphasis, `font-semibold` only for page titles and stat numbers. Avoid `font-bold` everywhere — it's the fastest way to make a UI look loud instead of clean.

## Spacing & layout

- 4px base unit (Tailwind default). Card padding `p-4`/`p-6`, section gaps `gap-6`/`gap-8`, page gutters `px-4 sm:px-6 lg:px-8`.
- Cards (`components/ui/card.blade.php` per doc 06) are the one layout primitive: every distinct piece of content (a stat, a table, a form section) sits in a card with `rounded-lg border border-slate-200 bg-white shadow-sm`. No content floats directly on the page background except the top nav and page title.
- Max content width `max-w-7xl mx-auto` on every page — stops tables/forms from stretching edge-to-edge on wide monitors.

## Core patterns specific to this app

| Pattern | Where | Spec |
|---|---|---|
| Status badge | Week status, plan slot fill, unavailability conflict | Pill (`rounded-full px-2.5 py-0.5 text-xs font-medium`), background = semantic-50, text = semantic-700, e.g. Open=emerald, Locked=slate, Conflict=rose |
| Stat card | Dashboard widgets (`WeekOverviewWidget`, `PositionCoverageWidget`) | Big number (`text-2xl font-semibold`) + label (`text-sm text-slate-500`) + optional trend/delta, in a card |
| Week grid cell | `WeekPlanPage`, `CalendarWeek` | Empty slot = dashed border + muted "+ add" affordance; filled slot = solid card with name + position color dot; locked = same but no hover/click affordance and a subtle diagonal-stripe or lock icon overlay |
| Empty state | No plan yet, no unavailability entries, no shifts this month | Centered icon + one-line message + (if actionable) a single primary button. Never just a blank card or table with a header row and nothing under it |
| Toast/flash | Any create/update/delete | `masmerise/livewire-toaster` (already recommended in doc 18) — success emerald, error rose, 4s auto-dismiss |

## Form controls

Use Tailwind's own `@tailwindcss/forms` plugin (official first-party plugin, not a new dependency class) for input/select/checkbox resets instead of hand-styling each one — it's already the path of least resistance on top of Tailwind and keeps every form in both panels visually identical without a component library.

## What this doc deliberately doesn't cover

- Filament resource layout (table columns, form fields, navigation groups) — already specified per-resource in `05-filament.md`.
- Livewire component responsibilities and data flow — already specified in `06-livewire.md`.
- This doc is tokens + patterns only; apply them when building each resource/component, don't re-derive a palette per page.
