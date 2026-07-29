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

**Confirmed unchanged by Search (Phase 11, WP-11-06):** the cross-content
search at `/meets/{meet}/search` queries several tables in one request instead
of one, but returns nothing beyond what's listed above — schools (name only,
and only schools with real participation in this meet), sports/events (name +
real event count, only those actually contested in this meet), published
announcement titles, and validated result placements (the exact same
rank/athlete-name/school-name/mark quadruple already public on `/results`).
Every group is independently scoped through `Meet::published()` plus its own
meet-relevant filter (`participatingSchoolIds()`, `contestedSports()`,
`Announcement::published()`, validated-only `ResultPlacement`) — this is a new
entry point onto the same boundary, not a widening of it.

## Routing & layout

- Public routes are guest routes (no `auth` middleware) under
  `throttle:60,1`; the portal home owns `/` (route name `home`, which auth
  layouts and redirects already reference; later portal routes are named
  `public.*`).
- `resources/js/layouts/public-layout.tsx` — header (PMMS identity, site nav,
  Sign in / Dashboard link), content column, footer; no app sidebar, no
  session chrome. Pages under `resources/js/pages/public/` get it automatically
  via the `app.tsx` layout switch (this replaced the starter `welcome` page).

### Header nav & "Live now" (WP-08-07)

The reference (`docs/ui-ux/references/public-medal-tally.png` — the only
reference image that actually shows a public page; this WP's own doc
pointed at the wrong generic set, same recurring issue every WP in this
phase has hit) shows a persistent top nav (Home/Schedule/Results/Medal
Tally/…) and a "Live now" indicator. Several of the reference's nav items
(News & Announcements, Galleries, About) don't correspond to any real page
in this app and were **not** added — only real routes got nav entries.

Since Schedule/Results/Medal Tally are all meet-scoped routes (`/meets/
{meet}/...`), not standalone pages, the header needs *some* meet to link
them into. `HandleInertiaRequests` now shares a guest-only `publicNav` prop
(`{meetId, meetName, liveCount}` or `null`) — the same "shell-level chrome
shared once, not duplicated per controller action" pattern WP-08-03 already
established for the authenticated sidebar's `currentMeet`, mirrored here for
guests. It resolves the one active **published** meet (`Meet::published()
->active()`, the same meet the landing page features), falling back to the
most recently started published meet only when none is active — this
fallback keeps guest nav working even before an admin has set anything
active — and counts that meet's non-ended `ScoringSession`s for the "Live
now" badge — reusing the exact scope `PortalController::liveMatches()`
already uses, just as a `count()` instead of a full row fetch. Guarded to
`$user === null` so authenticated page loads never pay for the extra query,
mirroring `currentMeet`'s own guard in the opposite direction.

**Update (Phase 10, WP-10-02/WP-10-07):** the header nav (and the new
multi-column `PublicFooter`'s quick-links column, which reuses the same
nav-items array) now also lists Sports/News/Contact — real routes only,
same rule this section already established. Per Phase 10's own resolved
scope decision, these three go in the header nav and footer **only**,
never `PublicBottomNav` — its tuned 4-5-item one-thumb-reach mobile
design stays exactly as WP-08-09 built it.

**Update (Phase 11, WP-11-08):** the same shared `topNavItems` array
(read by both the header nav and `PublicFooter`'s quick-links column —
no second array introduced) now also lists Rankings, Gallery, About,
FAQs, and Search — the five pages WP-11-02 through WP-11-06 built.
Order: Home, Schedule, Results, Medal Tally, Rankings, Sports, Gallery,
News, About, FAQs, Contact, Search. Same rule as every prior new-page
WP: these five go in the header nav and footer **only**, never
`PublicBottomNav`, which is unchanged (still reads from its own
separate `bottomNavItems` array, never `topNavItems`). The header nav's
existing `overflow-x-auto` (needed even before this WP, since the nav
was already wider than some viewports) accommodates the larger item
count without any new wrapping/scrolling behavior.

When no meet is published at all, `publicNav` is `null` and the header
shows only "Home" — no nav links pointing nowhere. When there's a meet but
no live match, no "Live now" badge is shown (an inactive indicator sitting
there for nothing was rejected, same reasoning as WP-08-03's no-notification
-bell decision). The badge links into that meet's page
(`/meets/{meet}`), which already has the full "Live now" section
(WP-07-08) — the header intentionally does **not** duplicate the live-match
list in a dropdown the way the reference does, to avoid two places
rendering the same live-match data.

`resources/js/components/public-page-hero.tsx` — a reusable branded band
(gradient built from the existing `--sidebar`/`--primary` tokens, WP-08-02
— no new colors introduced) for a page's title/description/optional meta
line. Applied to `public/home.tsx` this WP as the shell's proof-of-use;
adopting it on the results/tally/scoreboard pages themselves is each of
those pages' own WP's scope (WP-08-08 for tally), not redone here.

### Mobile bottom navigation (WP-08-09)

`docs/ui-ux/references/mobile-ranking-medal-tally.png`'s mobile shell moves
site navigation from the header into a fixed bottom tab bar — this WP is the
one WP in this phase whose own reference-image list was actually correct
(every prior WP's pointed at the wrong generic set).

`resources/js/components/public-bottom-nav.tsx` — fixed to the viewport
bottom, `sm:hidden` (the header's horizontal nav, added in WP-08-07, is now
`hidden sm:flex` — the two are complementary, never both visible at once).
Reuses the same real destinations `publicNav` already resolves for the
header nav, plus a "Live" tab (Radio icon, same destination as the header's
"Live now" badge) shown only when `publicNav.liveCount > 0` — same "no
indicator for nothing to indicate" rule as everywhere else this phase.
Labels are shorter than the header's ("Ranking" vs. "Medal Tally") since
five tabs share one row; same destination either way, not a different
page. Active-tab detection compares the current pathname against each
item's exact route URL, same approach the header nav already used.
Padded with `env(safe-area-inset-bottom)` so the bar never sits under an
iOS home indicator; `<main>` gets matching extra bottom padding
(`pb-20 sm:pb-6`) so the last bit of page content is never hidden behind
the fixed bar. The footer credit line is hidden below `sm:` — the bottom
nav now serves that "bottom of the page" role, and a phone doesn't have
room for both.

### Mobile medal tally (WP-08-09)

`public/tally.tsx` picked up several mobile-specific adjustments, all
scoped to this page only (not the internal admin tally, whose own desktop
layout WP-08-05 already covers):

- The four summary `StatCard`s now go to a 4-across grid starting at
  `sm:` instead of `lg:` — more compact sooner, but still 2×2 on a narrow
  phone rather than cramming 4 columns into ~370px.
- The "Overall ranking" table collapses to the top 8 rows by default
  (`RANKING_PREVIEW_COUNT`) with a "View full ranking (N total)" button
  that expands it — a phone shouldn't get the whole standings list dumped
  on it at once. The backend still returns every row regardless
  (`districts` is never truncated server-side), so expanding needs no
  extra request; a new test proves 10 districts all arrive in props even
  though only 8 render by default.
- The table's "Points" column is hidden below `sm:` (`hidden sm:table-cell`)
  — it's already documented as reference-only/non-authoritative, so
  dropping it first when space is tight is a defensible priority call,
  not a data loss.
- New `resources/js/components/sports-medal-strip.tsx` — a compact,
  horizontally-scrollable icon-forward "highlights" strip (first 4 sports
  by medal count) sitting above the existing full `MedalsBySportCard`
  table, which stays as the complete breakdown — the strip is a shorter
  preview, not a second copy of the same data. A trailing "More sports"
  tile is a plain `#anchor` (not an Inertia `Link`, which would attempt a
  page visit) scrolling down to the full table. Per-sport icons are
  purely decorative (`Waves` for swimming, `Footprints` for athletics/
  track, `Swords` for combat sports, `Dumbbell` as the generic fallback
  for everything else including all ball sports) — no functional meaning,
  just a reasonable visual match; this project's icon set has no
  sport-specific icons to match more precisely.
- The public tally now also shows "As of {generatedAt}" in its info
  banner, the same `now()->toDayDateTimeString()` convention the internal
  admin tally already used — this page never had a generated-at
  timestamp before.

**Not built**, per the reference-vs-real-app conflicts this phase keeps
surfacing: no "Public View ▾" role-switcher dropdown (the existing Sign
in/Dashboard button already serves that purpose — no real "view mode"
concept exists to switch between); the "Overall ranking" table still
ranks municipalities, not individual schools, even though the reference's
mockup data pairs a school name with a municipality subtext in that
table — restructuring the *official* ranking to read school-by-school
would reopen the exact "school must never read as a competing standing"
conflict WP-08-05 already resolved for the desktop page.

## Pages

- `/` — portal home (`public/home`): the single meet the system admin has
  set active (`meets.is_active`, `Meet::scopeActive()`), not a list of
  meets. `PortalController::home()` resolves
  `Meet::query()->published()->active()->first()` — at most one row is
  ever active, enforced by `MeetController::activate()`/`deactivate()`
  (auto-exclusive: activating a meet deactivates whichever other meet was
  active, in one transaction; `meets/index.tsx` has the "Set active" /
  "Deactivate" controls, manager-only, mirroring the existing
  Publish/Unpublish pattern). When no meet is active, the home page shows
  an empty state instead of a card grid. Below the meet's hero and quick
  links (Schedule/Results/Medal tally), a responsive grid of the meet's
  competing municipalities — derived from that meet's `Delegation` rows
  (`district` or `school.district`, deduplicated), each rendered with a
  deterministic colored-initials placeholder logo
  (`components/municipality-badge.tsx` — no logo upload exists yet).
  `HandleInertiaRequests`'s `publicNav` prop (the header/bottom-nav's
  Schedule/Results/Medal Tally links, "Live now" badge) now also prefers
  the active meet, falling back to the previous "most recently started
  published meet" only when none is active, so the site-wide nav can never
  point at a different meet than the one the landing page features.
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
  (`validatedSportOptions()`), plus an age-division filter (WP-08-08). Also
  now exposes `totals`/`topByPoints`/`bySport`/`recentMedals` — the same
  `MedalTallyService` aggregates the internal admin tally (WP-08-05) already
  computes, safe to expose publicly since none of it is more sensitive than
  the medal counts already shown. `PublicMeetNav` links Schedule ↔ Results ↔
  Medal tally. See `docs/medal-tally.md` for the shared-component detail
  (WP-08-08 extracted the admin tally's widgets into components both pages
  render from).
**Division initiative:** the public results page's "school" field and the
public medal tally's school-level rows are both sourced from the placed
athlete's own home school (`athlete.school`), never the delegation's — fully
re-keyed as of WP5 (`docs/medal-tally.md`). See `docs/delegations.md`.

- `/meets/{meet}/athletics` (`public.athletics`, WP-08-11) — a real-data
  "shell" page, deliberately **not** a live scoreboard. Its reference
  (`desktop-athletics-live-event.png`) shows a live race clock with a
  lane-by-lane track animation, live per-athlete positions/times/gaps,
  live field-event standings, and a "Meet Records" register — none of
  which exist as real data anywhere in this app: `App\Enums\ScoreboardType`
  has no Athletics case, and — the deeper structural reason — no scoring
  event anywhere attributes a time or mark to an individual athlete
  mid-event; Athletics results, like every individual event, are only
  ever recorded after the fact through Phase 3's normal encode→validate
  flow. Presented the owner three options before writing code: a real
  shell with no fake race data; new (if modest) backend infrastructure
  for real per-athlete athletics results; or deferring the WP entirely.
  **Owner chose: real shell only.** The page shows, for a selected day,
  every Athletics-sport `EventSchedule` slot with a real Upcoming/
  Ongoing/Completed status (the same time-window-vs-`now()` derivation
  `DashboardController::eventsOverview()` already established in
  WP-08-04) and, once an event's result is validated, its real top-3
  placements with real marks (`EventResult`/`ResultPlacement`, the exact
  same data `/meets/{meet}/results` shows, just filtered to Athletics and
  attached inline per event) — plus a medal-totals summary scoped to
  Athletics only (`MedalTallyService::standings($meetId, $athleticsSportId)`,
  summed). An explicit banner states live per-athlete race tracking isn't
  available yet. No live clock, shot clock, field-event live board, or
  meet-records register was built. Linked from `/meets/{meet}` via a new
  "Athletics schedule and results" button, shown only when the meet
  actually has Athletics events scheduled (`hasAthletics`, derived from
  the same schedule query the meet page already runs — no extra query).

- Announcements (WP-04-05) — published advisories via the shared
  `PublicAnnouncements` component: latest five on the portal home (general +
  per-meet, meet labeled), a meet's own on its meet page. Managed internally at
  `/announcements` (manager-only) — see `docs/announcements.md`.
- `/meets/{meet}/sports` (`public.sports`, WP-10-07) — a card grid of the
  sports actually contested in this meet (from the `Meet`↔`Event`
  `meet_events` pivot, grouped by `sport_id`), each card showing the
  sport's real event count and linking straight into `/results` and
  `/tally` pre-filtered by `sport_id` (both routes already accept that
  query param) — a real integration, not a static dead-end list. `Sport`
  has no description/image field, so nothing beyond name and count is
  shown. Resolved via `Meet::published()`, so unpublished meets 404.
- `/meets/{meet}/news` (`public.news`, WP-10-07) — the full, paginated
  list of this meet's published announcements (`Announcement::published()`,
  10 per page), reusing the shared `PublicAnnouncements` component the
  home page's 5-item preview already renders — this is that preview's
  "see all" destination. Resolved via `Meet::published()`, so unpublished
  meets 404.
- `/meets/{meet}/gallery` (`public.gallery`, WP-11-03) — a static,
  non-photographic gallery of sport-identity tiles (icon + name + real
  event count). PMMS has no photo/media model or upload pipeline
  anywhere, and fabricating stock "event photos" would misrepresent
  real DepEd content, so this reuses `sports()`'s exact same
  `contestedSports()` data (extracted as a shared private helper on
  this WP, so both pages read from one query) at a distinct,
  aspect-square gallery-tile presentation instead of `sports.tsx`'s
  horizontal list-card — same two destinations (`results`/`tally`
  pre-filtered by `sport_id`). Resolved via `Meet::published()`, so
  unpublished meets 404.
- `/meets/{meet}/rankings` (`public.rankings`, WP-11-02) — a standalone
  page showing the full district/municipality standings
  `MedalTallyService::standings()` already computes for the public
  medal tally, given its own route (Phase 10 had folded this into the
  tally page; the owner asked for a separate one in Phase 11). No new
  computation, no sport/age filter — see `docs/medal-tally.md` for
  detail. Linked from `/meets/{meet}/tally`; not yet in the header nav/
  footer (pending WP-11-08). Resolved via `Meet::published()`, so
  unpublished meets 404.
- `/meets/{meet}/about` (`public.about`, WP-11-04) — the Division
  running the meet (`Division::current()`'s `name`/`type`/`areaLabel`,
  read from the already-global shared Inertia `division` prop, same as
  `tally.tsx`) plus real participation counts: competing municipalities
  and sports contested (both reusing `competingMunicipalities()`/
  `contestedSports()` exactly), and a distinct-schools count derived
  from this meet's registered athletes' own `school_id` (Division
  initiative: the athlete's home school, not the delegation's). No
  office/history/mission copy invented anywhere. Resolved via
  `Meet::published()`, so unpublished meets 404.
- `/meets/{meet}/faqs` (`public.faqs`, WP-11-05) — common questions
  about how the portal works, rendered via the new shadcn `Accordion`
  primitive (`@radix-ui/react-accordion`, added this WP —
  `resources/js/components/ui/accordion.tsx`). Question text is
  written copy, but every factual answer traces to real data
  (`meetSummary()`, reused exactly) or already-documented behavior
  (this doc's own publication/validation/live-provisional rules,
  `tally.tsx`'s rank-order disclaimer) — nothing invented, and no new
  fact hardcoded that could go stale independently of the real
  behavior it describes. Resolved via `Meet::published()`, so
  unpublished meets 404.
- `/meets/{meet}/search` (`public.search`, WP-11-06) — a query box
  returning grouped, public-safe matches across this meet: schools
  (only those with real participation — `participatingSchoolIds()`,
  shared with `about()`), sports/events (`contestedSports()`, shared
  with `sports()`/`gallery()`), published announcement titles, and
  **validated** result placements matched by athlete name or school
  name (the same rank/athlete/school/mark fields already public on
  `/results` — no birthdate/LRN/grade-level/contact/guardian data).
  Plain `LIKE`/`whereHas` queries only, no search-index dependency. An
  empty query runs no query at all. Resolved via `Meet::published()`,
  so unpublished meets 404.
- `/meets/{meet}/contact` (`public.contact`, WP-10-07) — meet/venue info
  (name, dates, school year, venue) plus quick links to every other
  portal page for this meet. **No office-contact section** — PMMS stores
  no division-office address/phone/email anywhere, and this WP's
  resolved scope decision was to never invent one; the page reuses
  `meetSummary()` exactly, with zero new query beyond the standard
  `Meet::published()` lookup. Resolved via `Meet::published()`, so
  unpublished meets 404.

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
  **Update (Phase 11, WP-11-07):** visual-only pass, this functional
  behavior unchanged — the outer wrapper gained responsive padding
  (`p-6 sm:p-10`), the centered card widened slightly at `sm:`
  (`max-w-md sm:max-w-lg`), and a fade/rise entrance
  (`animate-card-in`, respects the existing global `prefers-reduced-
  motion` reset) — matching the spacing/motion discipline every other
  page in Phase 10/11 already has. `EmptyState` itself was already
  elevated in WP-10-09; not touched again here.
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
Phases 1–3. **Update (WP-08.5-09): color contrast was finally actually
measured**, not just assumed unchanged — see
`docs/ui-ux/accessibility-review.md`'s "Color contrast audit" section for
the real WCAG ratios and the two failures it found and fixed (a
warning-banner text color and a medal-gold badge, both a "brand-hue text on
a light tint" pattern that reads fine to the eye but measures under 4.5:1).
Confirmed still the accepted convention (WP-08-14) for every
public page added since this review — `public/scoreboard.tsx` (WP-07-08),
`public/athletics.tsx` (WP-08-11), and the day-picker chips they reuse — same
`size="sm"` pattern, not a new gap. Re-confirmed again in WP-08.5-05 (Premium
Mobile Sports Experience) specifically for the public filter `Select`
triggers (`public/tally.tsx`, `public/results.tsx`) — 36px (`data-[size=default]`),
already above this accepted 32px baseline, so no change was needed there
either.

Out of scope, per the WP: no new portal pages/features, no Lighthouse/CI
tooling, no PWA/offline behavior, and no accessibility audit of the internal
(authenticated) app beyond the shared `error`/`EmptyState` components touched
above (those fixes are backward-compatible improvements, not a redesign).
