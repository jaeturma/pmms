# Management Dashboard (Phase 5)

WP-05-01/02/03/04/05/06/07. Cross-meet/historical oversight for Admin and Organizer, layered on
top of — never duplicating — Phase 3's single-Active-meet operations block
(`docs/dashboard.md`). Phase 3 answers "what does today look like for the
meet that's running right now"; this answers "how is the program doing over
time."

## Access

`/management` (route `management.index`), gated `can:manage-meet-data` — the
same gate Phase 3's operational queues already use (Admin + Organizer only).
Delegation Officer and Viewer get a 403; nothing about their access changes.

## Foundation (this WP)

`App\Http\Controllers\ManagementDashboardController::index()` renders
`management/index` with:

- `meets` — meets in scope (all, or narrowed to one school year), each with
  id/name/school_year/dates/status label.
- `filters.school_year` — the active filter, `null` for "all."
- `schoolYearOptions` — distinct school years across every meet, newest first.
- `generatedAt`.

`meetsInScope(?string $schoolYear)` (private) is the one query every later
Phase 5 WP's aggregate starts from — it is not duplicated per widget.

## Participation & Registration Trends (WP-05-02)

`participation(Collection $meets)` (private) adds a `participation` prop:

- `rows` — per meet: `delegations` (draft/submitted/approved/total counts,
  the registering unit — a school under City, a municipality under
  Province) and separately `athletes`/`personnel`/`entries` (individuals,
  counted via their own delegation but never merged into the delegation
  counts).
- `totals` — the same four figures summed across every meet in scope.

Delegation counts come from `Delegation::where('meet_id', ...)` grouped by
`status` (`App\Enums\DelegationStatus`). Individual/entry counts come from
`Athlete`/`Personnel`/`Entry::whereHas('delegation', ...)` — deliberately not
scoped by `school_id`, since this WP counts *how many*, not *which school*;
per-school breakdowns are WP-05-04's job (performance history).

The page renders this as two separate tables — "Delegations by status" and
"Participation" (athletes/personnel/entries) — plus four summary
`StatCard`s, keeping the delegation-vs-individual distinction visually
explicit rather than one merged table.

## Operations Progress & Risk (WP-05-03)

`operationsProgress(Collection $meets)` (private) adds an `operations` prop —
one row per meet, all read from existing status enums, nothing recomputed:

- `results` — encoded vs. validated counts (`App\Enums\ResultStatus`).
- `eligibility` — pending/approved/returned counts (`App\Enums\
  EligibilityStatus`).
- `protests` — filed/under_review/upheld/dismissed counts (`App\Enums\
  ProtestStatus`), reached via `whereHas('delegation', ...)` since `Protest`
  has no direct `meet_id`.
- `incidents` — open/resolved counts (`App\Enums\IncidentStatus`).
- `is_stalled` — one plain, explicit risk flag: the meet is `Active`
  (`App\Enums\MeetStatus`) **and** has at least one `encoded_at` result still
  `encoded` (not yet validated) older than `STALLED_RESULT_HOURS` (24, a
  class constant — not predictive, not configurable via UI at MVP). No other
  risk scoring exists or is planned; if that threshold needs to be tunable
  later, that's its own decision, not assumed here.

The page's "Operations progress & risk" table links Encoded → `/results`
and Incidents open → `/incidents`, both pre-filtered by `meet_id` (both
controllers already support it); Eligibility pending → `/eligibility` and
Protests → `/protests` link to their plain index pages, since neither
controller supports a `meet_id` filter and adding one is out of this WP's
scope. A destructive "Stalled" badge renders when `is_stalled` is true.

## Delegation & School Performance History (WP-05-04)

`performanceHistory(Collection $meets, MedalTallyService $tally)` (private)
adds a `performance` prop. It calls `MedalTallyService::standings($meetId)`
once per meet in scope and sums gold/silver/bronze/total across meets —
tally derivation itself is never reimplemented:

- `districts` — the **official aggregate**, one row per district/
  municipality, summed across every meet's district standings.
- `schools` — the reference aggregate, keyed by `"{school}|{district}"`
  (not school name alone, since school names are only unique *per district*)
  to avoid cross-district collisions.

Both are re-sorted with `orderedStandings()` (private) — the same
gold-then-silver-then-bronze-then-name convention as
`MedalTallyService::ordered()` (docs/medal-tally.md), applied here to the
across-meets totals rather than reimplemented from `ResultPlacement` rows.

The page renders district history first (default `Heading` weight, "the
official verdict") with school history below it demoted to `small` variant +
"Reference only," mirroring exactly how the live per-meet tally
(`docs/medal-tally.md`) and the "Post-Division refinement" ordering already
work — this WP is the cross-meet extension of that same convention, not a
new one. Each row links to its registry entry (`/districts?search=...` /
`/schools?search=...`).

## Venue Utilization (WP-05-05)

`venueUtilization(Collection $meets)` (private) adds a `venues` prop — one
row per venue, derived entirely from `EventSchedule`, no new tables:

- `slots` — scheduled slot count.
- `hours` — total scheduled hours (`abs(diffInMinutes(ends_at, starts_at))`
  summed, then `/60` rounded to 1 decimal).
- `meets` / `events` — distinct count of each, across the slots in scope.

Only venues with at least one slot in the meets in scope are returned — an
unused venue has nothing to report, so it's omitted rather than shown as a
zero row. Each row links to its registry entry (`/venues?search=...`).

## Management Reports & Export (WP-05-06)

Two new routes, both `can:manage-meet-data`, both accepting the same
`school_year` filter as `/management`:

- `/reports/management` (`reports.management`) — printable page
  (`reports/management`), the same `@media print` chrome-hiding pattern as
  every existing report, no PDF library. Shares its data with the
  interactive dashboard via a new private `widgetData(Collection $meets,
  MedalTallyService $tally)`, extracted from `index()` so the dashboard,
  the printable report, and the CSV export can never disagree.
- `/reports/management/download` (`reports.management.download`) —
  streamed CSV, audited `report.management_exported`
  (docs/audit-trail.md), via a new private `csv()` helper mirroring
  `ReportController`'s (duplicated, not extracted to a shared trait — a
  12-line private method isn't worth a new abstraction across two
  controllers). Unlike the tally report's CSV (one shared header for
  district+school rows, since they're the same shape), this CSV is six
  independent blocks — one per widget, each with its own header row and a
  blank-line separator — since participation/operations/performance/venues
  have unrelated shapes.

The interactive `/management` page links "Printable report" from its
`PageHeader`, carrying the current `school_year` filter through, same UX as
the tally/roster reports.

**Pitfall hit and fixed during this WP:** `php artisan wayfinder:generate`
without `--with-form` regenerates every route helper file *without* the
`.form()` variant some pages need (`resources/js/routes/*`), breaking
unrelated pre-existing pages (auth forms, two-factor, settings) that were
generated by the Vite plugin's `wayfinder({ formVariants: true })`
(`vite.config.ts`) — `tsc` caught it immediately. Fixed by re-running with
`--with-form`. Prefer `npm run build`/`npm run dev` (which regenerate
correctly via the Vite plugin) over the bare artisan command going forward.

## Accessibility & Mobile Review (WP-05-07)

Swept `management/index.tsx` and `reports/management.tsx` against the same
checklist as WP-04-06 (table horizontal-scroll containment, filter
aria-labels, decorative icons aria-hidden, heading order, empty states).
Real gaps found and fixed:

- **`StatCard`'s icon** (shared component — also used by the main
  `/dashboard`) had no `aria-hidden`; the label text already conveys the
  meaning. Fixed at the component, benefiting every consumer, not just
  Phase 5.
- **`ReportActions`'s Download/Print icons** (shared — every report page)
  same fix, same reasoning.
- **Local decorative icons**: the "Printable report" link's `Printer` icon
  and the "Stalled" badge's `TriangleAlert` icon, both paired with visible
  text.
- **Bare-number links**: several table cells linked only a count (e.g. "3")
  into another module — accessible via sighted context but ambiguous to a
  screen-reader user tabbing through links in isolation. Added
  `aria-label`s (e.g. "3 encoded results for Provincial Meet 2026") to the
  four such links in the operations table.
- **`reports/management.tsx` had no empty state** — with zero meets in
  scope it would have rendered seven empty tables instead of the
  `EmptyState` the interactive dashboard and every other report already
  show. Fixed to match.

Verified already sound (no changes needed): heading order (`h1` via
`PageHeader`, `h2` for every section — no skipped levels), every table
wrapped in `overflow-x-auto`, the school-year `Select` already carries
`aria-label="Filter by school year"`, `StatCard` grid collapses to one
column on phones (`sm:grid-cols-2 lg:grid-cols-4`, same convention as every
other stat grid in the app).

No behavioral/backend changes — pure presentation, so no new Pest tests
(consistent with how the Post-Division tally-reorder change was handled
earlier: existing prop-based assertions don't change when only markup
changes).

## Delegation vs. school (read before adding a widget here)

A **delegation** is the registering unit — a school under City, a
municipality under Province (`docs/division.md`). An **individual** (athlete,
personnel, entry, result, medal) is attributed to their own **school**,
always — never the delegation. Every aggregate added in later Phase 5 WPs
must be explicit about which one it's counting; conflating them was the
concrete error found in this phase's original (discarded) draft plan.

## Tests

`tests/Feature/ManagementDashboardTest.php` — guest redirect, Delegation
Officer/Viewer forbidden (403), Admin/Organizer allowed, school-year filter
narrows the `meets` and `participation.rows` props correctly, delegation
counts by status and individual/entry counts are correct per meet and in
aggregate, a delegation/athlete/entry belonging to a *different* meet never
leaks into another meet's row, results/eligibility/protest/incident counts
are correct per meet, and the stalled flag is true only for an Active meet
with an old encoded result (false for a non-Active meet with the same old
result, and false for an Active meet whose encoded result is recent);
performance history aggregates the same school's medals correctly across
two different meets (gold=2, total=2 in both the district and school rows),
and district/school ordering follows the gold→silver→bronze→name
convention; venue utilization sums slots/hours/meets/events correctly
across meets in scope (2 slots totaling 4.5 hours across 2 meets), respects
the school-year filter, and lists no venues when nothing is scheduled; the
printable report renders the same widgets as the dashboard for the same
school-year filter, and the CSV download is audited and carries all six
section headers plus real content.
