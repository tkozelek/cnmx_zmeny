# 12 — Shift Marketplace

## Purpose

An employee who has been assigned to a plan slot but can no longer work it can post a **"looking for replacement"** listing. Other eligible employees are notified by email. Someone picks it up, the admin approves (or the system auto-approves if configured), and the plan assignment is updated.

This replaces ad-hoc WhatsApp/phone swaps with a tracked, auditable flow.

---

## Key Concepts

| Term | Meaning |
|---|---|
| **Listing** | An employee's open request: "I can't work slot X, who wants it?" |
| **Offer** | Another employee responds: "I'll take it" |
| **Swap** | Mutual exchange: "I'll take your slot if you take mine" (optional, phase 2) |
| **Handover** | Admin (or system) finalises the transfer and updates `plan_assignments` |

---

## DB Tables

### `marketplace_listings`
```sql
id                  bigint      PK
plan_assignment_id  bigint      FK plan_assignments.id CASCADE DELETE
posted_by           bigint      FK users.id CASCADE DELETE
status              enum        'open', 'pending_approval', 'filled', 'cancelled', 'expired'
note                string(500) nullable   -- "Mám záväzok, prosím pomôžte"
notify_all          boolean     DEFAULT true  -- whether to blast email to eligible employees
expires_at          timestamp   nullable    -- auto-expire the listing after this time
filled_by           bigint      nullable FK users.id SET NULL
filled_at           timestamp   nullable
admin_approved_by   bigint      nullable FK users.id SET NULL
admin_approved_at   timestamp   nullable
created_at          timestamp
updated_at          timestamp

INDEX (plan_assignment_id)
INDEX (status)
```

### `marketplace_offers`
```sql
id              bigint      PK
listing_id      bigint      FK marketplace_listings.id CASCADE DELETE
offered_by      bigint      FK users.id CASCADE DELETE
status          enum        'pending', 'accepted', 'rejected', 'withdrawn'
note            string(255) nullable
created_at      timestamp
updated_at      timestamp

UNIQUE (listing_id, offered_by)   -- one offer per employee per listing
INDEX  (listing_id, status)
```

---

## Business Rules

### Who Can Post a Listing
- Any employee assigned to a plan slot in an **unlocked** week.
- Cannot post a listing for a slot whose day has already passed.
- Cannot post a listing if an identical open listing already exists for the same assignment.

### Who Can Offer
- Any active employee with the `employee` (or higher) role.
- Cannot offer on their own listing.
- Cannot offer if they already have a plan assignment that overlaps the slot's start/end time on the same day.
- Cannot offer if they have `unavailability` for that date — system warns, but does NOT hard-block (employee might have withdrawn the unavailability verbally).
- Cannot offer if the week is locked.

### Approval Flow

Two modes, controlled by `tenant_settings.marketplace_auto_approve` (boolean, default `false`):

**Manual approval (default):**
```
Employee posts listing
    → Email sent to eligible employees (see Notification section)
    → Another employee clicks "Chcem to zobrať"
    → Offer created with status = 'pending'
    → Admin sees pending offer in Filament dashboard widget
    → Admin clicks Approve → plan_assignment updated, listing filled
    → Both employees notified by email
```

**Auto-approve:**
```
Employee posts listing
    → Email sent to eligible employees
    → Another employee clicks "Chcem to zobrať"
    → plan_assignment updated immediately
    → Listing status = 'filled'
    → Both employees + admin notified by email
```

### Multiple Offers
If multiple employees offer before admin approves, all offers remain pending. Admin picks one (the Filament action shows all offers and lets admin choose). Rejected offers get a notification: "Tvoja ponuka nebola prijatá."

### Expiry
If `expires_at` is set and no one has accepted by then:
- Listing status → `expired`
- Poster is notified: "Tvoje hľadanie náhrady vypršalo. Nikto sa neprihlásil."
- Admin is notified if the slot remains unfilled.

A scheduled command handles expiry:
```php
// Runs every hour
MarketplaceListing::where('status', 'open')
    ->where('expires_at', '<=', now())
    ->each(fn ($l) => $l->expire());
```

### Week Lock Interaction
- Locked week: no new listings, no new offers, no approval possible.
- If a listing is `open` when a week gets locked: auto-cancel the listing with notification to poster.
- In `LockWeekAction::before()`: check for open listings, cancel them, notify.

---

## Notification System

### `MarketplaceListingPostedNotification`

Sent to: **all active employees who are NOT already assigned to a slot on the same day at an overlapping time**.

"Hľadá sa náhrada" email:
```
Dobrý deň,

[Meno] hľadá náhradu na:
  Pozícia:  Uvádzač
  Deň:      Sobota 14. júna 2025
  Čas:      13:00 – 21:00

Poznámka: "Mám záväzok, prosím pomôžte"

Ak máte záujem, prihláste sa na: https://kino.app.com/vymena/{listing_id}

Ponuka platí do: [expires_at alebo "kým admin neschváli"]
```

Only send to employees who are eligible (no conflict, no unavailability for that date). Query:

```php
// MarketplaceService::getEligibleEmployees(PlanSlot $slot): Collection
User::role('employee')
    ->where('is_active', true)
    ->whereDoesntHave('planAssignments', fn ($q) =>
        $q->whereHas('planSlot', fn ($q) =>
            $q->where('day_id', $slot->day_id)
              ->where(fn ($q) =>
                $q->whereBetween('start_time', [$slot->start_time, $slot->end_time])
                  ->orWhereBetween('end_time', [$slot->start_time, $slot->end_time])
              )
        )
    )
    ->whereNot('id', $listingPoster->id)
    ->get();
```

Employees with unavailability on the date are excluded from the blast by default (configurable via `tenant_settings.marketplace_notify_unavailable`, default `false`).

### `MarketplaceOfferReceivedNotification`
Sent to: listing poster + admins (if manual approval).
"Niekto sa prihlásil na tvoj shift."

### `MarketplaceOfferApprovedNotification`
Sent to: the employee whose offer was accepted.
"Tvoja ponuka bola schválená. Si priradený na [slot details]."

### `MarketplaceOfferRejectedNotification`
Sent to: employees whose offers were not chosen.
"Tvoja ponuka nebola prijatá. Záskok bol obsadený iným zamestnancom."

### `MarketplaceListingExpiredNotification`
Sent to: listing poster + admins.
"Hľadanie náhrady vypršalo — nikto sa neprihlásil."

### `MarketplaceListingFilledAdminNotification`
Sent to: admins (in auto-approve mode only, since they didn't manually trigger it).
"Plán práce bol automaticky aktualizovaný: [old employee] → [new employee] na [slot details]."

---

## Livewire: Employee UI

### `MarketplacePage` (new route `/vymena`)

Shows two sections:

**"Moje hľadania náhrady"** — listings the current user has posted:
- Active listings with offer count badge
- Expired / filled / cancelled listings (collapsible history)
- [Zrušiť] button on open listings
- [Nové hľadanie] button → opens create form

**"Dostupné záskoky"** — open listings from other employees that the current user is eligible for:
- Card per listing: position, day, time, poster's note
- [Chcem to zobrať] button → creates offer

### `NewListingForm` (inline or modal on MarketplacePage)

Fields:
- **Slot select** — dropdown of own upcoming plan assignments that are in unlocked weeks and in the future. Formatted as "Uvádzač 13:00 – Sobota 14.6.2025".
- **Note** — optional free text
- **Expires at** — optional date+time picker (max: slot date - 1 day)
- **Notify eligible employees** — toggle (default on)

Validation:
- Slot must be in the future and in an unlocked week
- No existing open listing for this slot
- Employee must be assigned to the slot

### Deep-link for email notifications

`/vymena/{listing}` — renders `MarketplacePage` with the specific listing highlighted and "Chcem to zobrať" pre-focused. Requires auth; redirects to login with `intended` redirect if not logged in.

---

## Filament: Admin UI

### `MarketplaceWidget` on Dashboard

Shows pending items needing attention:
- Count of listings awaiting approval
- Listing cards: slot details, poster name, list of offers with employee names

Quick-approve action directly from dashboard widget.

### `MarketplaceResource`

**Navigation group:** Planovanie  
**Permissions:** `plan.assign`

Table columns:
- Slot (position + time + date)
- Posted by
- Status badge
- Offers count
- Expires at
- Created at

Row actions:
- **View offers** → expands sub-table of all `marketplace_offers` for the listing, each with [Schváliť] / [Zamietnuť]
- **Cancel listing** (with confirmation + notify poster)
- **Manually fill** → admin picks any user from a select (bypasses offer flow entirely)

Filters:
- Status (open / pending / filled / expired / cancelled)
- By week
- Show only listings with ≥1 offer

---

## Tenant Settings Additions

Add to `tenant_settings`:

```sql
marketplace_enabled            boolean   DEFAULT true
marketplace_auto_approve       boolean   DEFAULT false
marketplace_notify_unavailable boolean   DEFAULT false  -- include unavailable employees in notification blast
marketplace_max_listing_days   tinyint   DEFAULT 3      -- auto-set expires_at to N days from now if not set manually
```

In `TenantSettingsResource` Filament form, add a "Burza smien" section with these fields.

---

## Service Layer

```php
// app/Services/MarketplaceService.php

class MarketplaceService
{
    public function post(PlanAssignment $assignment, User $poster, array $data): MarketplaceListing
    // Validates eligibility, creates listing, fires notification job

    public function offer(MarketplaceListing $listing, User $offerer): MarketplaceOffer
    // Validates no conflict, creates offer, notifies poster + admins

    public function approve(MarketplaceOffer $offer, User $approver): void
    // DB transaction:
    //   1. Update plan_assignment.user_id = offer.offered_by
    //   2. Update offer.status = 'accepted'
    //   3. Reject all other pending offers for this listing
    //   4. Update listing.status = 'filled', filled_by, filled_at
    //   5. Fire notifications
    //   6. Activity log: "Smena prevedená: [old] → [new] ([slot details]) schválil [approver]"

    public function cancel(MarketplaceListing $listing, User $canceller): void
    // Sets status = 'cancelled', notifies offerers

    public function expire(MarketplaceListing $listing): void
    // Sets status = 'expired', notifies poster + admins

    public function getEligibleEmployees(PlanSlot $slot): Collection
    // Returns users who can cover the slot (no conflict, active, not the poster)
}
```

All state transitions go through the service — never update listing/offer status directly in a controller.

---

## Activity Logging

Log on:
- Listing created: `"Meno K. hľadá náhradu: Uvádzač 13:00 Sob 14.6."`
- Offer received: `"Peter S. ponúkol záskokv pre listing #X"`
- Offer approved: `"Smena prevedená: Jana N. → Peter S. (Uvádzač 13:00 Sob 14.6.)"`
- Listing cancelled: `"Hľadanie náhrady #X zrušené"`
- Listing auto-expired: `"Hľadanie náhrady #X vypršalo bez záskoku"`

---

## Phase in Implementation Order

Add as **Phase 4b** between Phase 4 (Plan System) and Phase 5 (Unavailability). It depends on `plan_assignments` existing and the Filament panel being ready.

Phase 4b checklist:
- [ ] `marketplace_listings` + `marketplace_offers` migrations
- [ ] `MarketplaceListing`, `MarketplaceOffer` models + relationships
- [ ] `MarketplaceService` with all state-transition methods
- [ ] `MarketplacePolicy`: `post` (own assignment, unlocked, future), `offer` (no conflict), `approve` (admin/manager)
- [ ] 4 notification classes
- [ ] `MarketplaceListingPostedJob` (queued — sends blast email to eligible employees)
- [ ] `ExpireMarketplaceListingsCommand` (scheduled hourly)
- [ ] `LockWeekAction` — cancel open listings for locked week
- [ ] `MarketplacePage` Livewire component + `/vymena` route
- [ ] Deep-link `/vymena/{listing}` route
- [ ] `MarketplaceResource` in Filament
- [ ] `MarketplaceWidget` on Filament dashboard
- [ ] Tenant settings additions + Filament settings form section
- [ ] Feature tests: post → offer → approve flow, conflict rejection, lock cancellation, expiry
