# Public Sports Directory & Sport Mini Portals

A public, browsable catalog of every sport in the meet catalog (`/sports-
directory`) plus a richer per-sport "mini portal" (`/{sportSlug}`, one
permanent route per sport) that now leads with sport identity — hero,
photo, description, competition categories, venue/schedule summary,
Tournament Management personnel, and Technical Officials — before the
existing live-scoring/results sections. Scoped entirely to `resources/js/
apps/portal/`; the admin app was not redesigned.

Preceded by an inspection report (`docs/reports/public-sports-and-mini-
portals-review.md`) that surveyed what real data existed before any code
was written. Four open decisions from that report were resolved by the
owner: route coverage extends to the full 28-sport catalog (not just the
original 12), `Sport` gets real `short_description`/`description`/
`photo_upload_id` columns, the new directory is *added* alongside the
existing meet-scoped `/meets/{meet}/sports` (not a replacement), and
Technical Official duty labels get a real column (`sport_user.duty`).

## Routes

| Path | Name | Controller action |
|---|---|---|
| `GET /sports-directory` | `public.sports-directory` | `PortalSportsController::index` |
| `GET /{sportSlug}` | `public.sport-portal` | `PortalController::sportPortal` |
| `GET /{sportSlug}/poll` | `public.sport-portal.poll` | `PortalController::sportPortalPoll` |
| `GET /sports/{sport}/photo` | `sports.photo` | `SportController::photo` |

`{sportSlug}` is constrained via `whereIn(SportPortalSlug::values())` —
now the full 28-sport catalog (expanded from the original Phase 12 set of
12), so every sport `SportsCatalogSeeder` seeds has a working public route,
not just the ones with an existing rich live-scoreboard integration. Both
routes resolve internally to `Meet::query()->published()->active()->
first()` (meet-agnostic in the URL), the same pattern `PortalTeamsController`
already established.

### Why `/sports-directory` and not `/sports`

The public directory is deliberately **not** at `/sports`. That URI is
already claimed by the authenticated admin catalog route (`sports.index`,
`SportController::index`) — this app's admin pages live at bare top-level
paths (`/districts`, `/schools`, `/sports`, `/events`, ...) with no `/admin`
prefix, protected by middleware rather than a path segment. Laravel's route
collection overwrites on identical method+URI, so registering the public
route at `/sports` too made the *admin* route win silently: guests hitting
`/sports` were redirected to `/login` with no error, no 404, nothing to
explain why. Confirmed with a real request before choosing `/sports-
directory` instead of touching the existing admin route. `tests/Feature/
PublicSportsDirectoryTest.php` has a regression test for this specific
collision.

## Data model additions

- **`sports.short_description`** (string, 200) / **`sports.description`**
  (text) — locally authored copy per sport (`SportsCatalogSeeder`), never
  fabricated rule detail. Both nullable; render as honest empty states when
  unset.
- **`sports.photo_upload_id`** → `file_uploads` — mirrors `District::
  logo_upload_id`/`logoUrl()`/`DistrictController::logo()` exactly, down to
  the guest-accessible streaming route (`SportController::photo()`). No
  admin upload form sets this yet (out of this feature's scope, same as
  the column itself was for the Teams feature's logo work) — `Sport::
  photoUrl()` returns `null` until one exists, and `PortalSportPhoto`
  renders nothing at all rather than a placeholder image.
- **`sport_user.duty`** (string, 100, nullable) — a free-text duty label
  ("Referee", "Scorer", "Timekeeper") per Technical Official assignment.
  Added to `sport_user` rather than `meet_sport_assignments` (role=
  `TechnicalOfficial`) because `sport_user` is the table with real,
  live-authorization-backing data today (`ScoringSessionController`/
  `ResultController` already read it); `meet_sport_assignments`' own
  Technical Official rows are currently unused by anything. No admin form
  sets `duty` yet either — it renders as the generic "Technical Official"
  label until one does.

## Personnel: two real, distinct sources

- **Tournament Management** (Tournament Manager, Assistant TM, Track/
  Field/Boys/Girls/Category TM, Tournament Secretary, Tournament ICT) —
  `MeetSportAssignment` rows scoped to the current meet's `MeetSport`,
  excluding the `TechnicalOfficial` role case. Genuinely supports several
  people per role (`[meet_sport_id, user_id, role]` unique, not
  `[meet_sport_id, role]`). Ordered Manager-first via `PortalController::
  tournamentManagementRolePriority()`, not enum declaration order.
- **Technical Officials** — `Sport::technicalOfficials()` (`sport_user`),
  meet-unscoped. Chosen over `MeetSportAssignment`'s own `TechnicalOfficial`
  role because that table has real seeded rows today; the assignment-role
  equivalent does not yet.

Both are queried fresh per sport-portal request in `PortalController::
sportProfile()` (`sportProfileTournamentManagement()`/
`sportProfileTechnicalOfficials()`). **Public-safe fields only**: name,
role/duty label, category, lead flag — never phone, email, home address,
birth date, employee ID, medical status, or account information. The full
`User`/assignment models carry more than this; the portal payload is built
field-by-field, not by serializing the model.

## Competition categories

Real `SportCategory` rows — catalog-wide (`meet_sport_id` null) unioned
with this specific meet's own scoped categories, both filtered to
`active`. The same union `PortalSportsController::categoryCount()` counts
for the directory cards, `PortalController::sportProfileCategories()`
returns as full rows for the mini portal. Never derived from
`AgeDivision`/`GenderCategory` combinatorics.

## Sports directory (`/sports-directory`)

`PortalSportsController::index()` lists every catalog sport that has a
`SportPortalSlug` (all 28) as a card — icon, name, short description,
category count, a LIVE badge when a scoring session is currently running
for that sport in the active meet, and a "View Sport →" link to its mini
portal. Two tabs (Regular Sports / Paragames, classified by the
`Sport.name` "Paragames - " prefix, the only classification mechanism that
exists) plus client-side search — the catalog is 28 rows, already fully
loaded, so a second request just to filter it would be pure overhead (same
reasoning `teams.tsx` already uses for its own search).

Every sport appears regardless of whether it's part of the currently
active meet — this is a stable, browse-the-catalog page, not a "what's in
meet X" list (that's still `/meets/{meet}/sports`, unchanged). A sport
with no current-meet inclusion still shows its real catalog-wide category
count rather than 0.

## Sport mini portal (`/{sportSlug}`)

`sport-portal.tsx`'s existing live-scoring/results sections are unchanged
in behavior; a new upper section was added above them, in this order:

1. **Hero** — `PortalHero`'s new optional `icon` prop (a colorful
   `PortalSportIcon`), classification (Regular Sport/Paragames), category
   count, and a LIVE badge when applicable.
2. **Photo** (`PortalSportPhoto`) — renders nothing when `photo_url` is
   `null`.
3. **Description** (`PortalSportDescription`) — `Sport.description`,
   split on blank lines into paragraphs; an empty state when unset.
4. **Competition categories** (`PortalSportCategories`).
5. **Venue information** — reuses the page's existing `venues` prop/
   `PortalVenueInformation` component (moved up from its old position
   near the bottom of the page, not duplicated).
6. **Schedule summary** — a compact "next 3" teaser built client-side
   from the page's existing `todayGames`/`upcomingGames` arrays (no new
   backend query — the full Today's/Upcoming grids below already fetch
   this data).
7. **Tournament Management** (`PortalTournamentManagement`).
8. **Technical Officials** (`PortalTechnicalOfficials`).

Then the existing Live Now / Today's / Completed / Upcoming games /
Standings / Leading scorers / Tournament bracket sections, unchanged.

## Colorful sport icons

`sport-icon.tsx`'s `sportIconStyle()`/`PortalSportIcon` replace the old
monochrome `sportIcon()` helper (kept alongside it for the one existing
caller that keys off a free-text name rather than a slug — `team-players-
coaches.tsx`). `lucide-react` has no dedicated icon for most individual
sports (checked the full icon set before writing this — no basketball/
football/badminton/table-tennis/chess-piece icons exist), so several
sports intentionally share a base shape (ball/combat/racket-adjacent) and
are told apart by color instead of a fabricated bespoke icon. Colors are a
fixed identity per sport — a saturated 500/600-level foreground on a
low-opacity tint background — chosen to read on both Day and Night Mode
surfaces without a separate dark variant, the same "fixed color, not
inverted" approach the Day/Night theme already uses for medal colors and
the LIVE indicator.

## Tests

- `tests/Feature/PublicSportsDirectoryTest.php` — directory rendering, no-
  active-meet empty state, Paragames classification, category counts, live
  flagging, and the `/sports` vs `/sports-directory` collision regression.
- `tests/Feature/PublicSportPortalTest.php` — extended with profile-field
  coverage (description/photo/paragames), category union/sort, Tournament
  Management ordering and category scoping, Technical Official duty
  labels, and a full-catalog route smoke test (Archery, previously
  unrouted).

## Not built (deliberately out of scope)

- An admin upload form for `Sport.photo_upload_id` — the column, model
  method, and streaming route exist and are ready; nothing sets it yet.
- An admin form for `sport_user.duty` — same story.
- Any change to the existing admin sports catalog page or `SportRequest`
  validation.
