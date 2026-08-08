# Public Sports Directory & Mini-Portal — Inspection Report

Stage 1 only (per the brief's stop condition). No code has been written.
Every fact below was confirmed by reading the actual model/migration/
route/component files or querying the real dev database — nothing is
assumed.

## 1. Current sports

`Sport::count()` = **28** in this dev database, matching
`database/seeders/SportsCatalogSeeder.php`'s full catalog: Athletics,
Archery, Arnis, Badminton, Baseball, Basketball, Billiard, Bocce, Boxing,
Chess, Dancesports, Football, Futsal, Goal Ball, Gymnastics, Pencak Silat,
Swimming, Weightlifting, Sepak Takraw, Softball, Taekwondo, Table Tennis,
Tennis, Volleyball, Wrestling, Wushu, **Paragames - Athletics**,
**Paragames - Swimming**.

`sports` table schema (`database/migrations/2026_07_18_000006_create_sports_table.php`,
never altered since — confirmed zero `Schema::table('sports', ...)`
migrations exist anywhere): `id`, `name` (unique), `active`, timestamps.
**That's the complete column set.**

## 2. Existing Regular/Paragames classification

**No dedicated classification column or enum exists anywhere.** Paragames
is represented purely as a `Sport.name` string prefix (`'Paragames -
Athletics'`, `'Paragames - Swimming'`) — confirmed in
`SportsCatalogSeeder.php:50-51` and by the fact that this same convention
was already relied on (and documented) in the Municipal Teams feature's
`MedalTallyService` Paragames filter (`str_starts_with($name,
'Paragames')`). A new "Regular Sports / Paragames" tab on the Sports page
would reuse this exact same string-prefix check — no schema change
needed for the classification itself.

## 3. Current icon source

**No `Sport.icon_key`/icon field exists.** Two icon mechanisms currently
exist in the codebase, both purely presentational/decorative, neither
tied to a stored field:

- `resources/js/components/sports-medal-strip.tsx`'s `sportIcon(name):
  LucideIcon` — a small name-substring → Lucide-icon lookup (swim →
  `Waves`, athletics/track → `Footprints`, arnis/boxing/taekwondo →
  `Swords`, everything else → `Dumbbell`). Lives under the **legacy**
  `resources/js/components/` tree, not `apps/portal/`.
- `resources/js/apps/portal/components/sport-icon.tsx`'s `sportIcon(name):
  LucideIcon` — a **portal-local** near-duplicate I wrote for the
  Municipal Teams feature's Players & Coaches page (same substring
  matching, `Trophy` as the generic fallback instead of `Dumbbell`).
  Deliberately not shared with the legacy one, per this portal tree's own
  "no dependency outside `apps/portal`" convention.

Neither is "colorful sport pictograms" (running athlete, orange
basketball, etc.) — both are single-color Lucide line icons, a
monochrome outline style. The brief's "large colorful icon" requirement
is **new visual work**, not a reuse of an existing colorful icon set —
none exists in this codebase today.

## 4. Current descriptions

**No description field (short or long) exists on `Sport` or
`SportCategory`.** Confirmed via the full column lists in §1 and the
`SportCategory` schema (`sport_id`, `meet_sport_id`, `level`, `sex`,
`discipline`, `event_type`, `display_name`, `active` — no description
column). Every description shown anywhere in the public portal today is
either a generic templated sentence (e.g. `sport-portal.tsx`'s hero:
`"Live scores, schedules, and standings for {sport} at {meet}."`) or
absent entirely. There is nowhere in the schema to store a written
description today — this is new content that would need to be either
hardcoded per-sport in a frontend/backend lookup table, or added as a
real column (a real decision to make explicitly before Stage 6, not
assumed here).

## 5. Current sport photo capability

**No photo/image field exists on `Sport` either.** The only sport-
adjacent photo field in the whole schema is `athletes.sports_photo_upload_id`
(an individual athlete's own action photo, used by live scoreboards —
unrelated to a *sport's* own representative photo). `FileUpload` is this
app's general upload/attachment model (already used for district logos,
athlete photos, division logo/hero icon) and would be the natural
mechanism to attach a sport photo to, but no `sport_id`-scoped upload
column exists yet — would need a new nullable FK (e.g.
`sports.photo_upload_id`), the same shape as the already-shipped
`districts.logo_upload_id`/`divisions.hero_icon_upload_id` pattern.

## 6. Current venues

No direct `Sport → Venue` relationship exists. `Sport` has no `venues()`
method at all. The only real path is `Sport → Event → EventSchedule →
Venue` (`EventSchedule.venue_id`) or `Sport → Event → EventMatch →
schedule → Venue` — and this exact path is **already implemented** and
working in `PortalController::sportPortalData()`/
`individualEventSportPortalData()`, which both already build a per-sport
`venues` array (`id`, `name`, `address`, `directions_url` — a generated
Google Maps search link; **no stored lat/lng anywhere**, confirmed).
`SportCategory::venues()` exists too (a plain query helper, not an
Eloquent relationship) deriving venues from that category's own schedule
rows. A new mini-portal's Venue section can reuse this existing query
shape directly rather than inventing a new one.

## 7. Current schedules

`EventSchedule` is the only schedule-slot model (`meet_id`, `event_id`,
`sport_category_id` nullable, `venue_id`, `scheduled_date`, `starts_at`,
`ends_at`, `note`). No separate `Schedule` model, no `Sport →
EventSchedule` shortcut — queried explicitly by `sport_id` through
`Event`, exactly as `PortalController::sportPortalData()` already does
for the existing "Today's/Upcoming/Completed games" sections (bucketed in
PHP after fetch — there is no `EventSchedule::scopeToday()` or
equivalent). A compact "Schedule Summary" for the new mini-portal's top
section can reuse this same query, just capped tighter (the brief wants
one "next event" line, not the full list this existing section already
shows further down the page).

## 8–11. Tournament Managers, Assistant TMs, Tournament ICT, Tournament Secretaries

**A real model for this already exists and is schema-complete, but is not
wired into any public (or even authorization) code path yet.**

`App\Models\MeetSportAssignment` (table `meet_sport_assignments`,
migration `2026_08_02_090926_create_meet_sport_assignments_table.php`):
`meet_sport_id` (FK → the new `meet_sports` table, itself `meet_id` +
`sport_id`, unique per meet+sport), `sport_category_id` (nullable, for
category-scoped roles like "Category Tournament Manager"), `user_id`,
`role` (cast to `App\Enums\MeetSportAssignmentRole`), `is_lead`,
`start_date`/`end_date`, `status` (cast to
`App\Enums\MeetSportAssignmentStatus`: `Pending`/`Active`/`Declined`/
`Ended`).

`MeetSportAssignmentRole` — **every role the brief asks for already
exists as a real enum case, with a real `label()`**:
`TournamentManager`, `AssistantTournamentManager`,
`TrackTournamentManager`, `FieldTournamentManager`,
`BoysTournamentManager`, `GirlsTournamentManager`,
`CategoryTournamentManager`, `TournamentSecretary`, `TournamentICT`,
`TechnicalOfficial` (this last case is a duplicate concept, in a sense —
see §12).

**Multiple assignments of the same role are genuinely supported**: the
unique constraint is `['meet_sport_id', 'user_id', 'role']` — two
different users can both hold `TournamentManager` for the same sport;
what's prevented is one person holding the same role twice.

**Real demo data exists** (not just schema): `MeetSport::count()` = 8
(Athletics, Basketball, Volleyball, Boxing, Softball, Baseball, Swimming,
Gymnastics have a `MeetSport` row for the current meet).
`MeetSportAssignment::count()` = 4, **all four for Basketball only** — one
each of Tournament Manager, Assistant Tournament Manager, Tournament ICT,
Tournament Secretary, all `status = active`. Every other sport currently
has zero assignment rows — the mini-portal's personnel sections need a
real, honest empty state for 27 of 28 sports today, not just as a
theoretical edge case.

**Real gap**: this table is **not yet read by any existing controller** —
grepped `PortalController` and every other controller; nothing queries
`MeetSportAssignment` anywhere. It is also not yet used for
authorization (`ScoringSessionController`/`ResultController`/
`MatchRosterController::canManage()` all still check `UserRole::
TechnicalOfficial` + the legacy `sport_user` pivot, not this table). A new
public query for this data would be the **first** consumer of this table
anywhere in the app.

## 12. Technical Officials

**Two separate, only-partially-overlapping mechanisms exist:**

1. **`sport_user` pivot** (`Sport::technicalOfficials(): BelongsToMany<User>`,
   table `sport_user`: `sport_id`, `user_id`, timestamps, unique
   `['sport_id','user_id']`). Flat, sport-wide only — **no category, no
   venue, no duty-role column at all** (not Referee vs. Scorer vs.
   Timekeeper — that distinction does not exist anywhere in this schema).
   This is the pivot real authorization code (`MatchRosterController::
   canManage()`) actually checks today. Real data: 3 rows — Basketball,
   Boxing, and Softball each have one `Technical Official (Demo)` user
   assigned.
2. **`MeetSportAssignmentRole::TechnicalOfficial`** (§8-11) — a second,
   newer, unused-so-far representation of "this user is a TO for this
   sport," this time *with* an optional `sport_category_id` scope, but
   still no separate duty-role field. Zero rows use this case in the
   current data (all 4 existing `MeetSportAssignment` rows are Basketball
   TM/ATM/ICT/Secretary, not this case).

**The brief's example ("`[ REFEREE ]` Juan Santos, `[ SCORER ]` Maria
Garcia, `[ TIMEKEEPER ]` Pedro Reyes") cannot be built from real data
today** — there is no field anywhere recording that a given Technical
Official's specific duty is Referee vs. Scorer vs. Timekeeper vs. Judge
etc. Implementing that literally would require a new column (e.g. a
`duty`/`assignment_note` free-text or enum field) on whichever of the two
mechanisms above is chosen as the source of truth — a real, undecided
schema question, not something to fabricate placeholder values for.

**`App\Enums\UserRole::TechnicalOfficial`** is a third, unrelated concept
— the *account-level login role* (can this user log in and run live
scoring at all), not a per-sport assignment. Confirmed distinct from both
of the above; conflating them would be a real bug.

## 13. Current public sport routes

Two separate, non-overlapping route surfaces exist today:

- **`/meets/{meet}/sports`** (`public.sports`, `PortalController::sports()`)
  — a plain, meet-scoped directory listing every sport with an
  `EventResult`/entry in that meet, rendering `resources/js/apps/portal/
  pages/portal/sports.tsx`: sport name, event count, two text links
  (Results / Medal tally) per card. **No icon, no tabs, no search, no
  cards-as-visual-design, no link to a sport mini portal at all.** This is
  the page the brief wants revised — but note its current route is
  meet-scoped, not the clean `/sports` the brief wants.
- **`/{sportSlug}`** (`public.sport-portal`, Phase 12, meet-*agnostic*,
  resolves `Meet::published()->active()->first()` same as the Teams
  feature) — the rich, already-built mini portal, but **only for exactly
  12 sports**: Basketball, Volleyball, Baseball, Softball, Football,
  Sepak Takraw, Badminton, Table Tennis, Chess, Boxing, Athletics,
  Swimming (`App\Enums\SportPortalSlug`, `whereIn`-constrained so it can
  never intercept any other top-level route).

**Real, important gap: 16 of the 28 catalog sports have no working
`/{slug}` route at all** — Archery, Arnis, Billiard, Bocce, Dancesports,
Futsal, Goal Ball, Gymnastics, Pencak Silat, Weightlifting, Taekwondo,
Tennis, Wrestling, Wushu, and both Paragames sports. The brief's own
example route list includes `/arnis` and `/sepak-takraw` — Sepak Takraw
already works, **Arnis does not**. This needs an explicit decision before
Stage 4/5 (see "Missing data" and "Backend changes required" below) —
either extend `SportPortalSlug` to the full catalog, or have the new
Sports directory only link "View Sport" for the 12 that already resolve,
with the other 16 shown card-only (no working destination) or omitted.

## 14. Existing reusable components

Already built and directly reusable for the mini-portal's lower ("STAGE
10") sections — all under `resources/js/apps/portal/components/`:
`schedule-list.tsx` (`PortalScheduleList`, today's/upcoming/completed game
lists), `standings-table.tsx` (`PortalStandingsTable`, already accepts
`rows: [...] | null` with a real "not available" empty state),
`leading-scorers.tsx` (same `null`-safe pattern), `tournament-bracket.tsx`
(same), `venue-information.tsx` (`PortalVenueInformation`), `live-score-
card.tsx` + sport-specific scoreboard/sidebar pairs for
Basketball/Softball/Boxing, `section-header.tsx`, `empty-state.tsx`. All
of `sport-portal.tsx`'s "Live Now → Today's/Upcoming/Completed → Standings/
Leaders/Bracket → Venue" flow already exists and works for the 12 routed
sports; it just currently sits **below** a generic hero rather than below
the richer top section (Hero → Photo → Description → Categories → Venue
→ Schedule → Personnel) the brief wants.

**Nothing yet exists** for: a colorful large `SportIcon`, `SportCard`,
tab-based Sports directory (`SportTabs`/`SportSearch`), `SportHero` (icon
+ classification + category count + status), `SportPhoto`, `Sport
Description`, `SportCategories`, `TournamentManagement`/
`TournamentPersonnelCard`, `TechnicalOfficials`/`TechnicalOfficialCard`.
All genuinely new build.

## 15. Missing data

- Sport description (short + full) — no field, no content authored.
- Sport photo — no field, no images stored anywhere.
- Sport classification beyond the existing name-prefix Paragames
  convention — none needed if the name-prefix approach is accepted as
  sufficient (recommend keeping it, matching the Teams feature's own
  precedent decision).
- Colorful sport icon set — none exists; only monochrome Lucide
  approximations.
- 16 of 28 sports have no public route at all (§13).
- Technical Official per-duty role (Referee/Scorer/Timekeeper/etc.) — no
  field anywhere (§12).
- `MeetSportAssignment` data exists for only 1 of 8 `MeetSport` rows
  (Basketball) — every other sport's personnel section will be a real
  empty state on real current data, not a hypothetical.

## 16. Backend changes required

- A new controller action (or extend `PortalController`) for `GET
  /sports` — meet-agnostic like the Teams/sport-portal precedent,
  returning the lightweight `PublicSport[]` summary list (name,
  slug/route, classification, category count, live status) for the
  active meet only.
- A new/extended action for the sport mini-portal's **new** top-section
  data — categories (`Sport → SportCategory`, already queryable),
  venue/schedule (already-proven query shapes from §6-7), and — new —
  `MeetSport`/`MeetSportAssignment` resolution + grouping by role for
  Tournament Management, and `sport_user`/`MeetSportAssignmentRole::
  TechnicalOfficial` for Technical Officials, with the same public-safe
  column-scoping discipline every other public query in this app already
  follows (name + role + category + venue only — never phone/email/
  birthdate, mirroring `PersonnelRole`'s existing privacy treatment in
  the Teams feature).
- A decision + implementation for sport photo/description storage (new
  nullable columns, most likely `sports.description`/`sports.
  photo_upload_id`, mirroring the existing `districts.logo_upload_id`
  pattern) — **or** an explicit decision to keep these as a frontend-only
  hardcoded lookup keyed by sport name/slug instead of a real column, if
  that's preferred to avoid a migration. Both are legitimate; this is an
  owner decision, not made here.
- A decision on the 16-sport route-coverage gap (§13) — extending
  `SportPortalSlug`'s `whereIn`-constrained route to the full catalog is
  the more complete fix, but is itself a real, separate scope decision
  (every one of those 16 sports' mini portal would render with `liveNow:
  null` and empty schedule/venue sections today, same as any sport with
  no current games — not broken, just sparse).

## 17. Frontend changes required

New pages: `pages/portal/sports.tsx` (rewritten, not the current minimal
version — same file path as the existing meet-scoped one if `/sports`
replaces it, or a new file if both are kept), and either a rewritten
`sport-portal.tsx` or a new page for the richer mini-portal top section.
New components (portal's real flat `components/{name}.tsx` convention,
not the brief's literal `components/sports/` subfolder — same "follow
existing convention" pattern already established twice this session):
colorful `SportIcon`/`sport-icon.tsx` (replacing the existing monochrome
one), `sport-card.tsx`, `sport-tabs.tsx` (or reuse the existing
`PortalTabs`), the search UI (reuse the Teams Index's already-proven
client-side `useMemo` filter pattern, not a new component), `sport-
hero.tsx`, `sport-photo.tsx`, `sport-description.tsx`, `sport-
categories.tsx`, `tournament-management.tsx` +
`tournament-personnel-card.tsx`, `technical-officials.tsx` +
`technical-official-card.tsx`. New types in `apps/portal/types/index.ts`
following the existing `Portal{Thing}` naming (not the brief's literal
`PublicSport`/`PublicSportPortal` names — same established deviation
pattern).

## 18. Database changes required, if any

**None are strictly required to build *something* real** — `MeetSport`/
`MeetSportAssignment`/`sport_user`/`SportCategory`/`Venue`/`EventSchedule`
already exist and already have (sparse but real) data. Two are
**recommended but owner-decidable**: (a) `sports.description` +
`sports.photo_upload_id` nullable columns (or the frontend-hardcoded-
lookup alternative), (b) extending `SportPortalSlug` isn't a DB change
(it's a PHP enum) but functionally closes the 16-sport route gap. Neither
was assumed or started — both need an explicit go-ahead.

## 19. Privacy concerns

Same discipline as the already-shipped Teams feature must apply here:
`MeetSportAssignment`/`sport_user` both join to `User`, and `User` (not
inspected column-by-column in this report, but confirmed to have an
`email` at minimum from every other feature this session) must be
column-scoped to `name` only in any public query — **never** select the
full `User` model. No phone/address/birthdate/employee-ID/medical/
account fields exist on `MeetSportAssignment` itself, so the only real
risk is an incautious `->with('user')` exposing more than `id`/`name`.

## 20. Files likely to change

`routes/web.php` (new `/sports` + likely-extended sport-slug routes),
`app/Http/Controllers/PortalController.php` (new/extended actions) or a
new dedicated controller (matching the Teams feature's own precedent of
splitting out a new, self-contained public feature rather than growing
the already-large `PortalController` further — recommended),
`app/Enums/SportPortalSlug.php` (if the route-coverage gap is closed),
possibly a new migration for `sports.description`/`photo_upload_id`,
`resources/js/apps/portal/pages/portal/sports.tsx` (rewrite),
`resources/js/apps/portal/pages/portal/sport-portal.tsx` (rewrite/extend),
`resources/js/apps/portal/components/sport-icon.tsx` (replace with a
colorful version), ~10 new component files (§17),
`resources/js/apps/portal/types/index.ts` (new types),
`resources/js/apps/portal/layout/portal-header.tsx` ("Sports" nav item —
already present today via the existing `PortalNavDropdown label="Sports"`
sport-slug list; may need to become a single "Sports" link to `/sports`
instead, per the brief's recommended nav order), a new
`tests/Feature/PublicSportsDirectoryTest.php` and either an extended or
new sport-mini-portal test file, and `docs/features/public-sports-and-mini-portals.md`
(the eventual feature doc, once implemented).

## Open decisions before Stage 2 (recommend the owner weigh in)

1. **Route coverage**: extend `SportPortalSlug` to all 28 sports now, or
   ship the directory linking only the 12 that already work (with the
   other 16 shown without a "View Sport" action, or omitted from the
   grid entirely until routed)?
2. **Description/photo storage**: real new `sports` columns (a small,
   additive migration), or a frontend-only hardcoded content lookup keyed
   by sport name (no migration, but content lives in code rather than
   being editable via the registry)?
3. **`/sports` route**: replace the existing meet-scoped `/meets/{meet}/
   sports` entirely, or add the new meet-agnostic `/sports` alongside it
   and leave the old one as-is/deprecated?
4. **Technical Official duty roles** (Referee/Scorer/Timekeeper/etc.): out
   of scope for this pass (no field exists, real schema work), or a
   genuine new column to add now?
