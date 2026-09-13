# UI/UX Review — Signed-in Shell, Weeks, Absences, Users Table, Rozpis Builder

Date: 2026-09-13
Scope: `resources/views/layouts/*`, `resources/views/calendar/index.blade.php`, `resources/views/holiday/index.blade.php`, `resources/views/admin/index.blade.php` + `App\Livewire\UsersDataTable`, `resources/views/rozpis/*`, `resources/views/livewire/{rozpis-day,day-card}.blade.php`, `resources/views/components/rozpis/*`.
Method: manual read-through of every Blade view in scope, cross-checked against `ui-ux-pro-max` skill guidance (accessibility/touch-target/typography domains). No dev server was run — this is a static review, not a browser pass.

Overall the dark theme is consistent and the interaction copy (Slovak, contextual titles/tooltips) is genuinely good. The issues below are concrete and fixable; none require a redesign.

---

## 1. Signed-in shell (`layouts/layout.blade.php` + `partials/_navigation`, `_desktop`, `_mobile`)

- **Profile dropdown has no keyboard escape / no `aria-expanded`.** `_desktop.blade.php:38-53` toggles `openProfile` on click and hover, but there's no `@keydown.escape` handler (the mobile drawer has one on the outer wrapper, this doesn't) and the trigger `<button>` never sets `aria-expanded`/`aria-haspopup`. A screen reader user gets no signal the button opens a menu.
- **Hover-open menu on desktop is a touch-and-keyboard trap.** `@mouseenter`/`@mouseleave` (`_desktop.blade.php:40-41`) plus a separate `@click` toggle means touch/keyboard users open it by click but it never auto-closes except by `@click.outside` — inconsistent with the hover-driven mouse behavior. Pick one interaction model (click-to-toggle everywhere) rather than mixing hover and click.
- **New-user badge count (`_desktop.blade.php:9-13`, `_mobile.blade.php:41-45`) has no `aria-label`.** A red "3" bubble reads as just "3" to a screen reader with no context that it means pending approvals.
- Otherwise solid: logo + hamburger pattern, `x-teleport` mobile drawer with dedicated close button and `@keydown.escape.window`, active nav-link states via `<x-nav-link>` — no complaints there.

## 2. Weeks (`calendar/index.blade.php`, `components/date.blade.php`)

- **Locked-week banner and unlocked state rely on color alone.** `calendar/index.blade.php:17-24` distinguishes "Zamknúť/Odomknúť týždeň" only via a blue vs. neutral background and a lock icon that also just changes color/shape — there's no text state indicator elsewhere on the page reinforcing "this week is locked" beyond the button itself and the small badge in `x-date` (`components/date.blade.php:123-127`). Acceptable since the icon changes too, but worth noting the badge is the only other place it's echoed.
- **Sub-12px text again** in the locked badge (`text-xs` is fine there, but `x-date`'s lock badge icon is `text-[0.65rem]`, `components/date.blade.php:125`) — minor, same pattern as §5 below.
- **Flatpickr week-picker (`components/date.blade.php:21-133`) has no visible fallback if `window.flatpickr` isn't loaded** (`init()` just returns silently, line 53) — the trigger button still renders and looks clickable but does nothing. If Vite/CDN fails to load Flatpickr, the only way to change weeks becomes the two arrow buttons; there's no error state or console warning surfaced to the user.
- **Good:** responsive day-card grid (`1 → 2 → 4 → 7` columns), live signup summary via Livewire, absences-in-this-week table only renders `@if($absences->isNotEmpty())` — no empty-table clutter.
- **`day-card.blade.php:83-91`** renders a position badge inside `class="hidden"` — dead markup that's built server-side and never shown. Not a UX bug, but it's shipped HTML doing nothing; delete it or actually surface it.

## 3. Absences (`holiday/index.blade.php`)

- **Modal opens via Alpine (`x-show="openModal"`) but the trigger button (`holiday/index.blade.php:14`) has no `aria-haspopup="dialog"`/`aria-controls`,** and the panel itself isn't a native `<dialog>` (unlike the rozpis guide/history panels, which correctly use `<dialog>` — see `components/rozpis/dialog.blade.php`). Two different modal implementations exist in the same app; the absence modal is the one missing focus-trap/Escape support that `<dialog>` gives for free elsewhere.
- **Date range picker input is `readonly` with no visible instructions for keyboard-only users** (`holiday/index.blade.php:72`) — clicking opens Flatpickr, but a user tabbing to it with a keyboard has no indication Enter/Space opens a picker (no `aria-label`, relies purely on placeholder text with a `cursor-pointer` mouse affordance).
- **Reason field allows arbitrary free text with only `required`** (line 84-92) — fine for now, no character limit shown to the user even though the model presumably has a column limit; if that's a `varchar(255)`, a user can type past it with no feedback until submit fails. Not urgent, but a `maxlength` + counter would be a one-line safety net.
- **Good:** two clearly separated tables gated by permission (`@can('viewAny', ...)` for the manager-only roster vs. everyone's own list), error alert box reads well, buttons are full-height with adequate padding.

## 4. Users table (`admin/index.blade.php` + `App\Livewire\UsersDataTable`)

- **Approve/deny/edit actions render as raw HTML strings with `wire:click` inline** (`UsersDataTable.php:167-181`). Functionally fine, but the accept/deny buttons (`h-9 w-9`, 36px) meet touch-target size while the row action `<i>` icons have no `aria-label` on the button — only a `title` attribute, which isn't reliably announced by screen readers the way `aria-label` is. Same for the edit link.
- **Status badges ("Zablokovaný", "Čaká na schválenie", role name) are color+text together — good,** this one avoids color-only meaning.
- **"Posledná aktivita" column shows `$row->updated_at`,** which is *any* field update, not last login/activity — mildly misleading label for what the column actually measures (cosmetic edits to a user's profile would bump this too). Consider renaming to "Naposledy upravený" if it isn't truly last-activity, or wire it to actual last-login if that's tracked.
- **"Pridať používateľa" modal is Flowbite's `data-modal-target`/`data-modal-toggle` pattern** (`admin/index.blade.php:5-6`) — a third distinct modal mechanism in the app alongside the Alpine `x-teleport` one (absences) and native `<dialog>` (rozpis). Three different modal systems is a maintenance/consistency cost worth flagging even if each one individually works.
- **Good:** search/filter/sort via the Rappasoft table is standard and themed consistently (`tailwind` theme, `setEmptyMessage` in Slovak), per-page options sensible (10/25/50/100).

## 5. Rozpis creation (`rozpis/index.blade.php`, `livewire/rozpis-day.blade.php`, `components/rozpis/*`)

This is the most complex screen and where most of the issues concentrate.

- **Desktop-only by explicit design** — the page comment says so outright (`rozpis/index.blade.php:2-3`, "Desktop-first and deliberately full-bleed"). The core interaction (dragging people onto position slots via SortableJS, `livewire/rozpis-day.blade.php:127-130` / `268-270`) has no touch-friendly fallback path *for reordering slots* — placing a person does have a `<select>` fallback (lines 172-194), which is good, but **reordering positions within a day is drag-only** (`data-drag-handle`, line 50-54, tied to `x-sortable-order="reorderSlots"` with no button alternative). Per WCAG 2.2 SC 2.5.7 (Dragging Movements), an author-controlled drag operation needs a single-pointer alternative (e.g. move-up/move-down buttons). If managers ever build a rozpis from a phone/tablet, reordering is currently impossible without a mouse-style drag.
- **Icon-only action buttons are under the 24px CSS-px minimum target size.** The remove-slot (`×`, line 115-121), copy-to-other-days (line 86-90), unplace (line 138-144), and accept-suggestion (line 155-159) buttons are all `h-5 w-5` (20px). WCAG 2.2 AA's Target Size (Minimum) wants ≥24×24 CSS px (or spacing/exception). These sit inside `group-hover:opacity-100` reveal-on-hover too, which compounds the problem on touch devices where there is no "hover" to reveal them at all — `sm:opacity-0 sm:group-hover:opacity-100` (line 141) only applies at `sm:` and up, so on narrow/touch viewports they stay visible, which is the right call, but they're still small targets sitting right next to each other with minimal gap.
- **Pervasive sub-12px text**: 40+ occurrences of `text-[0.55rem]` / `text-[0.6rem]` / `text-[0.65rem]` across `rozpis-day.blade.php`, `position-list.blade.php`, `_guide.blade.php`, `published.blade.php`, `day-card.blade.php` (9-10.4px actual size). This is the densest screen in the app, so the instinct to shrink text is understandable, but several of these are load-bearing labels (group headings, "Zásluhy"/fairness score column header, the "hard to staff"/"desirable day" badges) rather than pure decoration. At minimum the numeric fairness scores and group headers should move to `text-xs` (12px) — they're read carefully by managers making staffing calls, not glanced at.
- **The workload/fairness table (`rozpis/index.blade.php:144-174`) explains a fairness score via a `title` tooltip only** (line 151-152) — hover-only, invisible on touch, and the whole "Zásluhy" concept (weighted credit for unpopular/weekend shifts) is genuinely non-obvious. A hidden native tooltip is the wrong vehicle for a concept this load-bearing; consider a visible legend or an info icon that opens the existing `<dialog>`-based guide panel instead. Same issue recurs on the published view (`rozpis/published.blade.php:176-177`, `"Kto môže byť vylosovaný"` tooltip explaining fairness ordering).
- **Copy-to-other-days popover** (`livewire/rozpis-day.blade.php:85-113`) opens on click, no `@keydown.escape` to close it (only `@click.outside`), and checkboxes inside have no visible "select all" — minor friction if a manager wants to copy to every remaining day.
- **`<select>` fallback for placing people includes the fairness score in the option text** (`(1.0)` etc., lines 187-193) — keyboard/touch users get the same information the drag-and-drop path shows as a colored badge, in a plainer but still complete form. Flagging as a **positive**, not a defect: this is the correct accessible pattern (don't rely on color alone).
- **Good:** the page tells the manager unambiguously whether the plan is published or a draft (`rozpis/index.blade.php:26-37`), publish/unpublish has a confirm() guard with a clear consequence stated in the copy, "copy from last week" and per-slot "copy to other days" are well-labeled, and the AI-suggestion UI (dashed violet cards, "not saved yet" tooltip) makes a real effort to distinguish unsaved suggestions from committed assignments.
- **Published (read-only) view mirrors the builder's heading component well** (`day-heading.blade.php` shared between both) — good consistency, and the read-only view correctly strips every interactive control.

---

## Cross-cutting patterns worth fixing once, not per-page

1. **Three different modal implementations** (native `<dialog>` in rozpis, Alpine `x-teleport` in absences, Flowbite `data-modal-*` in admin/users) — pick one and migrate the other two. Lowest priority functionally, highest for long-term maintenance.
2. **Color-token inconsistency**: `components/empty-state.blade.php` uses `slate-*`, everything else in the reviewed pages uses `neutral-*`. Same dark gray family, different Tailwind scale — will drift visually if either palette's grays are ever retuned.
3. **`title`-attribute tooltips as the sole explanation for non-obvious UI** (fairness scores, hard-to-staff/desirable-day badges) — invisible on touch, no keyboard equivalent. Worth a small reusable "info popover" component if this pattern keeps recurring.
4. **Icon-only buttons using `title` instead of `aria-label`** recur across the users table and rozpis builder — `title` is not a reliable accessibility API; screen reader support is inconsistent. Add `aria-label` alongside `title` on icon-only buttons.

## Suggested priority order

1. Rozpis drag-only reordering → add a keyboard/touch alternative (WCAG 2.2 AA blocker if this app needs to be accessible).
2. Icon button target sizes (rozpis builder, users table row actions) → bump to at least 24px, ideally closer to 32-36px given they sit in hover-reveal groups.
3. Sub-12px load-bearing text in rozpis (fairness score, group headers) → 12px minimum.
4. `aria-label`s on icon-only buttons and the profile dropdown's `aria-expanded`.
5. Modal/tooltip consistency cleanup — lower urgency, batch with other refactor work.
