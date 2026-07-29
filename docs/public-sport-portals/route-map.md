# Public Sport Portals — Route Map

All routes below live in `routes/web.php`'s existing guest
`throttle:60,1` group, alongside every other public-portal route.

| Method | URI | Controller action | Name | Notes |
|---|---|---|---|---|
| GET | `/{sportSlug}` | `PortalController::sportPortal` | `public.sport-portal` | Full Inertia page. `sportSlug` constrained to the 12 values in `App\Enums\SportPortalSlug::values()`. |
| GET | `/{sportSlug}/poll` | `PortalController::sportPortalPoll` | `public.sport-portal.poll` | JSON only (`{ liveNow, otherLiveCount }`), polled every 7s by `SportPortalLiveNowCard`. Same `sportSlug` constraint. |

## The 12 sport slugs

| Slug | Real `Sport.name` | Live board (`ScoreboardType`) |
|---|---|---|
| `basketball` | Basketball | Basketball (dedicated) |
| `volleyball` | Volleyball | Generic |
| `baseball` | Baseball | Softball/Baseball (dedicated) |
| `softball` | Softball | Softball/Baseball (dedicated) |
| `football` | Football | Generic |
| `sepak-takraw` | Sepak Takraw | Generic |
| `badminton` | Badminton | Generic |
| `table-tennis` | Table Tennis | Generic |
| `chess` | Chess | Generic |
| `boxing` | Boxing | Boxing (dedicated) |
| `athletics` | Athletics | n/a — individual-event data (`EventSchedule`/`EventResult`), no live match board |
| `swimming` | Swimming | n/a — individual-event data, same as Athletics |

Every slug is a real `Sport` catalog row today (`SportsCatalogSeeder`/
`DdopaaReferenceSeeder`), confirmed before this phase was planned — no new
`Sport` rows were created.

## Why no `whereNumber`/model binding

Unlike every meet-scoped public route (`/meets/{meet}/...`, bound by numeric
ID), `{sportSlug}` is a fixed enum value with `->whereIn(...)`, not a model
binding — there is no per-request "which record" question, only "which of
these 12 known sports." An unknown slug never reaches the controller; the
route itself 404s.

## Not registered

`/{sportSlug}` is deliberately **not** added to `PublicMeetNav` (the
Schedule/Results/Tally "core trilogy") or `PublicBottomNav`'s item count —
both are meet-scoped by design and this phase's routes are not. Per
`DESIGN-NOTES.md`, discoverability is via links from the existing `/sports`
and `/gallery` pages' cards, not header-nav growth; this was flagged as a
default, not a fully-closed decision, and can be revisited with the owner.
