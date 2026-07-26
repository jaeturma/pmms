# Public Portal

WP-04-01 foundation. A no-login, mobile-first window into published meet
information, per product scope §9: published schedules, validated results, medal
tally, and announcements — nothing else. **Extended in Phase 7 (WP-07-08)**
to also include live, provisional match scores — see "Live scoreboard"
below; this was a deliberate expansion beyond §9's original four
categories, owner-directed, not a silent scope creep.

## Publication model

Nothing about a meet is public until a manager **publishes** it:

- `meets.is_published` flag; publish/unpublish are manager-only
  (`role:admin,organizer`), audited (`meet.published` / `meet.unpublished`), and
  reversible — unpublishing takes effect immediately.
- **Draft meets cannot be published** (nothing official exists yet).
- Every public query goes through `Meet::published()`; later public routes must
  resolve meets via that scope so unpublished meets 404 everywhere public.

## Privacy baseline (binding for every portal WP)

May appear publicly: meet name/school year/dates/status, venue names and
addresses, schedule slots, **validated** results, athlete **name + school +
placement/mark**, medal standings, published announcements.

Never public: birthdates, LRN, grade levels, photos, contact details, guardian
information, eligibility material, protests, incidents, audit data, user
accounts, encoded (unvalidated) results, internal venue notes, or anything
belonging to an unpublished meet.

Public controllers (`PortalController`) build their own minimal prop arrays —
never reuse an internal page's props. Tests assert public-safe props with
`missing()` checks, not UI inspection.

## Routing & layout

- Public routes are guest routes (no `auth` middleware) under
  `throttle:60,1`; the portal home owns `/` (route name `home`, which auth
  layouts and redirects already reference; later portal routes are named
  `public.*`).
- `resources/js/layouts/public-layout.tsx` — lightweight header (PMMS identity,
  Sign in / Dashboard link), content column, footer; no app sidebar, no
  session chrome. Pages under `resources/js/pages/public/` get it automatically
  via the `app.tsx` layout switch (this replaced the starter `welcome` page).

## Pages

- `/` — portal home (`public/home`): published meets with school year, dates,
  venue, and status; each card links to the meet page; empty state when nothing
  is published.
- `/meets/{meet}` (`public.meet`, WP-04-02) — the meet page: schedule for a
  selected day grouped by venue (chip day-selector; defaults to today during
  the meet, else the first scheduled day) with time range, event label, and
  slot note; plus a venue guide (names + addresses only — internal venue notes
  never leave the server). Resolved via `Meet::published()`, so unpublished
  meets 404.
- `/meets/{meet}/results` (`public.results`, WP-04-03) — official results:
  **validated results only**, enforced structurally by the query's status
  filter, so a corrected (reopened) result disappears automatically. Per-event
  standings show rank (ties marked), athlete name, school, and mark — nothing
  else; "Official as of" shows the validation timestamp without validator
  identity. Sport filter; newest-validated first.
- `/meets/{meet}/tally` (`public.tally`, WP-04-04) — the public medal tally:
  district/municipality standings (the official verdict) followed by a
  school-level reference table, from `MedalTallyService` **unchanged**
  (derived at read time from validated results only, ties share medals), so
  the public tally can never disagree with the internal one and reacts to
  corrections automatically. Sport filter shared with the results page
  (`validatedSportOptions()`). `PublicMeetNav` links Schedule ↔ Results ↔
  Medal tally.
**Division initiative:** the public results page's "school" field and the
public medal tally's school-level rows are both sourced from the placed
athlete's own home school (`athlete.school`), never the delegation's — fully
re-keyed as of WP5 (`docs/medal-tally.md`). See `docs/delegations.md`.

- Announcements (WP-04-05) — published advisories via the shared
  `PublicAnnouncements` component: latest five on the portal home (general +
  per-meet, meet labeled), a meet's own on its meet page. Managed internally at
  `/announcements` (manager-only) — see `docs/announcements.md`.

## Live scoreboard (Phase 7, WP-07-08)

`/meets/{meet}/matches/{match}/scoreboard` (`public.scoreboard`) — a
read-only view of a match's live scoring session (`docs/live-scoring.md`),
resolved through the same `Meet::published()` scope as every other public
page **and** the match must belong to that meet — either 404s. No separate
opt-in: a manager's existing publish decision is the one decision point,
matching how the schedule/results/tally already work; there is no
per-feature toggle to also approve live scores specifically. This is
provisional data by definition (an in-progress, unvalidated score, unlike
the "validated results only" guarantee everywhere else on the portal), so
the page carries an explicit "Live score — provisional, not the official
result" badge to keep it visually distinct from `/meets/{meet}/results`.
No live session for a match is not an error — an ordinary empty state, same
as every other no-data path on the portal. Polling only (`public.scoreboard.
poll`, same 5-second baseline the internal page guarantees works
standalone) — no Reverb channel for guests this WP, since a private Echo
channel requires an authenticated user and building a public-channel
exception was out of scope for this pass.

Discovery: the public meet page (`/meets/{meet}`) gets a new "Live now"
section listing every match in that meet with a currently active
(non-ended) session, each linking into its scoreboard — without this, the
feature would only be reachable by guessing or sharing a direct URL.
`PortalController::liveMatches()` queries `ScoringSession` directly
(`status != ended`, scoped to the meet's matches) rather than through
`EventMatch`, since only one non-ended session can exist per match, so
this is naturally one row per live match already, not per session.

Privacy: `side_a_label`/`side_b_label` are the same free-text labels the
operator set internally (school names, from `suggestedLabels`, or manual
text) — no individual athlete data (name, birthdate, etc.) ever appears
anywhere in a scoring session, so this page needed no new privacy review
beyond confirming that. `resources/js/components/live-score-display.tsx`
is the one shared, purely presentational component both this page and the
internal operator console (`scoring/show.tsx`) render from — extracted in
this WP so the two views can't drift apart; each page still fetches and
shapes its own props independently (`PortalController` never reuses an
internal page's props, the binding rule above), only the rendering itself
is shared.

## Accessibility & mobile review (WP-04-06)

Swept all four portal pages (`public/home`, `public/meet`, `public/results`,
`public/tally`) plus `PublicLayout`, `PublicMeetNav`, and `PublicAnnouncements`
at phone/tablet/desktop widths.

**What was checked:** page-level landmarks and heading order; labels on every
interactive control; focus visibility; decorative-icon/alt-text handling;
touch target sizing; horizontal-scroll containment; empty/unavailable states
for every portal page; `<title>` per page and a portal-wide meta description.

**Gaps found and fixed:**
- **Unpublished/nonexistent meets 404'd through Laravel's raw, unstyled error
  page** (no PMMS branding, no way back into the portal) — the exception
  responder in `bootstrap/app.php` only special-cased 403; 404 fell through to
  the framework default. Extended it to render the shared `error` Inertia page
  for 404 too, matching the existing 403 pattern. This is the "meet
  unpublished" unavailable state the portal needed and was previously missing
  entirely.
- The shared `error` page always offered a "Back to dashboard" link — for a
  guest that lands on `dashboard()` and bounces to login, a dead end from the
  portal's no-login flow. `error.tsx` is now layout- and CTA-aware: guests
  (`auth.user` absent) get `PublicLayout` and a "Back to portal home" link
  (`home()`); authenticated users keep `AppLayout` and "Back to dashboard".
  Added a dedicated 404 title/message ("Page not found" / references the
  meet-publication possibility) alongside the existing 403 copy.
- No portal-wide meta description — added one on `PublicLayout` via `<Head>`
  (merges with each page's own `<Head title>`).
- The day-selector chip row on the meet page (`public/meet.tsx`) had no
  semantic wrapper, unlike `PublicMeetNav`'s `<nav aria-label="Meet
  sections">`. Wrapped it in `<nav aria-label="Select day">` and added
  `aria-current` to the active day chip and the active `PublicMeetNav` item.
- Decorative icons paired with visible text (`CalendarDays`/`MapPin` on the
  home and meet pages, `Megaphone` in `PublicAnnouncements`, and the icon
  inside the shared `EmptyState`) had no `aria-hidden`, so screen readers
  could announce redundant icon labels. Marked them `aria-hidden="true"`.

**Verified already sound (no change needed):** every table (schedule,
results, tally) scrolls inside its own `overflow-x-auto` container, never the
page; heading order is a clean h1 → h2 per page with no skipped levels;
`PublicMeetNav` and the sport `Select` filters already carry `aria-label`s;
focus-visible rings are inherited from the shared `Button`/`Card` styles;
every no-data path already has a purpose-built `EmptyState` (portal home: "No
meets published yet", meet page: "Schedule not yet available", results: "No
results yet", tally: "No medals yet"); `PublicAnnouncements` intentionally
renders nothing when empty (supplementary content, not a state to explain).

**Accepted deviations:** `size="sm"` buttons (used for the day chips and meet
section nav) are 32px tall, under the 44px touch-target guideline — this
matches the button-sizing convention used throughout the rest of the app and
was not redesigned in this WP. Color contrast and dark/light theming were not
re-audited here; they use the same design tokens already in place across
Phases 1–3.

Out of scope, per the WP: no new portal pages/features, no Lighthouse/CI
tooling, no PWA/offline behavior, and no accessibility audit of the internal
(authenticated) app beyond the shared `error`/`EmptyState` components touched
above (those fixes are backward-compatible improvements, not a redesign).
