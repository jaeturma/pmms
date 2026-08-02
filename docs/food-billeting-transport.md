# Food, Billeting, and Transport

WP-REALIGN-11, part of the DdOPAA organizational realignment — see
`docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md`
§17/§24 and `docs/architecture/pmms-approved-organizational-model.md` §7.
Unlike WP-REALIGN-10 (Supply/Equipment, which had zero prior planning
artifact anywhere), these three domains have real prior art in Track B's
bounded-context catalog — `BC-22` Billeting, `BC-23` Food Services, `BC-24`
Transportation (`docs/01-architecture/bounded-context-catalog.md`) — but
that catalog's scope (occupancy logs, meal entitlement/consumption/wastage
tracking, fuel/utilization reporting) is consistently larger than what the
approved model actually asks for here. The approved model's own entity list
is deliberately smaller: `MealAnnouncement`/`MealSchedule` (no
entitlement/accounting layer), `BilletingVenue`/`BilletingAssignment`, and
`Vehicle`/`TransportTrip`/`TransportRequest` — this doc follows the approved
model's narrower scope, not the fuller BC-22/23/24 vision, matching every
other WP-REALIGN-0x WP's "simplify from Track B, don't rebuild it" pattern.

Four structural decisions were resolved with the owner via `AskUserQuestion`
before writing this doc (all four decided the recommended option):

1. **Billeting detail — single status-tracked row, not a check-in/out event
   log.** One `BilletingAssignment` per (meet, delegation) with a status
   (Assigned → CheckedIn → CheckedOut), not a separate events table. BC-22's
   fuller occupancy-log/facility-issue scope is deliberately not built here.
2. **Transport request→trip — linked, not independent.** `TransportTrip`
   carries a nullable FK to the `TransportRequest` it fulfills. BC-24 names
   `TransportTrip` as the sole authoritative aggregate; requests feed into
   trips rather than standing alone.
3. **Public exposure — none in this WP.** Everything here stays behind
   auth. The approved model's "public-facing pages must show only approved
   general info" note becomes a future WP once there's a concrete
   public-portal ask, same as WP-REALIGN-09/10 staying internal-only.
4. **Meal structure — a real `MealType` enum, date, and optional venue,**
   not a free-text announcement. Still far short of BC-23's full
   entitlement/headcount/wastage tracking.

## Shared authorization refactor

All three domains (plus WP-REALIGN-10's already-shipped `SupplyPolicy`)
repeat the identical "Admin/Organizer, or an Active `ManagementTeamMember`
of the matching `team_type`" check. This WP extracts that into
`App\Policies\Concerns\ChecksManagementTeamMembership` (a trait providing
`hasActiveMembership(User $user, ManagementTeamType $type, ?Meet $meet =
null): bool`) and retrofits `SupplyPolicy` to use it — a small,
behavior-preserving change to already-shipped code, the same "extract on
second use" discipline this codebase applies elsewhere (`TopByPointsCard`,
`PublicLiveMatches`), now triggered by a *third* and *fourth* use rather
than a second.

## Data model

Three new enums beyond the domain models themselves: `MealType`
(Breakfast/Lunch/Dinner/Snack), `BilletingAssignmentStatus`
(Assigned/CheckedIn/CheckedOut), `TransportRequestStatus`
(Pending/Fulfilled/Cancelled), `TransportTripStatus`
(Dispatched/Boarding/EnRoute/Arrived/Delayed/Cancelled — BC-24's own
dispatch/boarding/arrival/delay vocabulary).

### Food

- **`meal_announcements`** — `meet_id` (FK, cascade), `title`, `message`
  (text), `posted_by_user_id` (FK → `users.id`, cascade). **Deliberately
  not the existing `Announcement` model** — `Announcement` is a
  publish-gated, Admin/Organizer-only, public-portal-facing advisory
  (`is_published`/`published_at`, per its own docblock); `MealAnnouncement`
  is an internal notice a Food Team member posts for other meet staff
  (e.g. "Lunch will be served 30 minutes late today"), manageable by the
  Food Team specifically, not Admin/Organizer alone, and per decision #3
  never public. Reusing `Announcement` would force a foreign authorization
  model and an unwanted publish workflow onto it.
- **`meal_schedules`** — `meet_id` (FK, cascade), `meal_type` (`MealType`),
  `date` (date), `starts_at`/`ends_at` (nullable time strings, `H:i:s` —
  same SQLite/MySQL comparison-parity normalization `EventSchedule`
  already uses), `venue_id` (FK → `venues.id`, nullable, `nullOnDelete` —
  the existing division-wide `Venue` catalog, reused unmodified, same as
  WP-REALIGN-10's `equipment_items.venue_id`), `notes` (nullable text).
  Unique per `(meet_id, meal_type, date)` — one schedule entry per meal per
  day.

### Billeting

- **`billeting_venues`** — `meet_id` (FK, cascade), `name`, `address`
  (nullable text), `capacity` (nullable unsigned integer), `contact_name`/
  `contact_phone` (nullable strings), `venue_id` (FK → `venues.id`,
  nullable, `nullOnDelete` — purely informational, set only if a billeting
  site happens to coincide with an existing competition `Venue`; the
  billeting row itself is the source of truth for lodging-specific fields,
  not `Venue`, since `Venue`'s only current relation/purpose is
  `EventSchedule` competition scheduling — overloading it with lodging
  capacity/contact fields would be the same "don't force a disjoint
  purpose onto an existing model" mistake the gap assessment flags
  elsewhere), `notes` (nullable text). Unique per `(meet_id, name)`.
- **`billeting_assignments`** — `billeting_venue_id` (FK, cascade),
  `delegation_id` (FK, cascade), `meet_id` (FK, cascade — redundant with
  `delegation.meet_id` but kept explicit per the migration plan's own
  wording, "FK'd to `management_teams` + `meets` + `delegations`", all
  three), `room_detail` (nullable string), `contact_name` (nullable
  string — an on-site contact for that delegation's block, distinct from
  the delegation's own head contact), `status`
  (`BilletingAssignmentStatus`, default Assigned), `assigned_at`
  (timestamp, defaults to creation time). Unique per `(meet_id,
  delegation_id)` — a delegation is billeted at one place at a time.

### Transport

- **`vehicles`** — `meet_id` (FK, cascade), `plate_number`, `type`
  (nullable string, e.g. "Bus"/"Van"), `capacity` (nullable unsigned
  integer), `driver_name`/`driver_phone` (nullable strings), `notes`
  (nullable text). Unique per `(meet_id, plate_number)`. Per-meet roster,
  not a division-wide fleet catalog — same catalog-scope reasoning
  WP-REALIGN-10 already established for equipment ("a Supply Team defines
  its own catalog from scratch, scoped to that meet_id"), now carried
  forward as this project's standing convention for every
  `ManagementTeam`-owned domain rather than re-litigated per WP.
- **`transport_requests`** — `meet_id` (FK, cascade), `delegation_id` (FK,
  cascade), `pickup_location`/`dropoff_location` (strings),
  `requested_at` (timestamp — when the ride is needed, distinct from
  `created_at`), `passenger_count` (nullable unsigned integer), `notes`
  (nullable text), `status` (`TransportRequestStatus`, default Pending),
  `requested_by_user_id` (FK → `users.id`, cascade).
- **`transport_trips`** — `meet_id` (FK, cascade), `vehicle_id` (FK,
  cascade), `delegation_id` (FK, nullable, `nullOnDelete` — a trip need
  not be tied to one delegation, e.g. an officials' shuttle),
  `transport_request_id` (FK, nullable, `nullOnDelete` — the request this
  trip fulfills, per decision #2; kept nullable both ways so a trip's
  history survives if the request row is ever removed), `pickup_location`/
  `dropoff_location` (strings), `status` (`TransportTripStatus`, default
  Dispatched), `scheduled_at` (timestamp), `departed_at`/`arrived_at`
  (nullable timestamps), `notes` (nullable text). Creating a trip against
  a `transport_request_id` also flips that request's `status` to
  Fulfilled — the same "creating the fulfilling record updates the
  fulfilled record's status" shape `EquipmentReturnController` already
  uses for `EquipmentIssue::status`.

## Authorization

Three new policy classes, all built on the shared
`ChecksManagementTeamMembership` trait:

- **`FoodPolicy`** — `viewAny`/`manage`: Admin/Organizer or an Active
  Food Team member for the meet. No further tier — meal schedules/
  announcements are meet-wide operational info, not delegation-specific,
  so this is the same two-tier shape `SupplyPolicy` already has.
- **`BilletingPolicy`** / **`TransportPolicy`** — same Admin/Organizer/
  Team-member `manage` tier, **plus a third read-only tier**: a
  `DelegationOfficer` may view rows belonging to their own delegation
  (`billeting_assignments`/`transport_requests`/`transport_trips` where
  `delegation_id` is one of their delegation's ids) — required directly by
  the approved model's own wording, "contact/room detail restricted to
  Billeting Team + the assigned delegation's own officer" and "driver/
  passenger PII restricted same as Billeting." This is **row-level
  scoping, not field-level redaction**: an officer's own delegation's row
  arrives with every field intact (they're entitled to all of it), other
  delegations' rows are simply never included in their query — the exact
  pattern `ProtestController`/`ProtestPolicy` already use for delegation
  officers scoped to their own delegation's protests.
- Every `index()` controller mirrors `EquipmentCategoryController`'s
  `accessibleMeetIds()` shape for the Admin/Organizer/Team-member tiers;
  Billeting/Transport add a second row-scoping branch for the
  DelegationOfficer tier, with `canManage` false for that tier (read-only,
  same "viewer sees data, manage actions hidden" shape
  `ManagementTeamController` already uses for its own open-view roles).

## UI

Three separate pages, not one combined page — the three domains have
meaningfully different data shapes and (for Billeting/Transport) a third
authorization tier Food doesn't need, so folding them into one page would
blur three distinct access models together:

- `resources/js/pages/food/index.tsx` — schedule list grouped by date/meal
  type, announcement board section.
- `resources/js/pages/billeting/index.tsx` — one card per venue (mirroring
  `EquipmentCategoryController`'s category-card shape), each listing its
  assignments inline with a status `Select`; a Delegation Officer sees
  only their own assignment, read-only.
- `resources/js/pages/transport/index.tsx` — one card per vehicle, each
  listing its trips; a separate request queue section (Pending requests
  awaiting a trip); a Delegation Officer sees only their own requests/
  trips, read-only, plus a "request transport" action (the one manage
  action a DelegationOfficer *does* get — filing a request for their own
  delegation, the same shape `ProtestController::create()` already grants
  officers for their own delegation).

All three sit in the sidebar's `managerNavItems` tier (like Equipment),
with the same disclosed gap Equipment already has: a Delegation Officer or
Team member who isn't Admin/Organizer won't see a sidebar link, only
direct-URL access — unresolved pending the same future shared-visibility-
prop work flagged in `docs/equipment-management.md`.

## Controllers

Seven controllers, one "primary" entity per domain (index lives there,
mirroring `EquipmentCategoryController`/`EquipmentItemController`'s split):

- `MealScheduleController` (index, store, update, destroy) — primary for
  the Food page.
- `MealAnnouncementController` (store, update, destroy).
- `BilletingVenueController` (index, store, update, destroy) — primary
  for the Billeting page.
- `BilletingAssignmentController` (store, updateStatus, destroy).
- `VehicleController` (index, store, update, destroy) — primary for the
  Transport page (the roster Transport Team maintains, same role
  `EquipmentCategory` plays for Supply).
- `TransportRequestController` (store, updateStatus, destroy — the
  DelegationOfficer-accessible `store()`).
- `TransportTripController` (store — optionally fulfilling a
  `transport_request_id` — updateStatus, destroy).

## Audit

`meal_announcement.created|updated|deleted`,
`meal_schedule.created|updated|deleted`,
`billeting_venue.created|updated|deleted`,
`billeting_assignment.created|status_updated|deleted`,
`vehicle.created|updated|deleted`,
`transport_request.created|status_updated|deleted`,
`transport_trip.created|status_updated|deleted` — every mutation through
`AuditLogger`, same convention as every prior WP-REALIGN-0x domain.

## Testing

Three new Pest files (`tests/Feature/FoodTest.php`,
`tests/Feature/BilletingTest.php`, `tests/Feature/TransportTest.php`),
following `EquipmentTest.php`'s structure: model relationships, CRUD per
controller, the request→trip fulfillment flow (creating a trip against a
request flips the request to Fulfilled), the DelegationOfficer read-only/
own-delegation-only tier for Billeting/Transport (a real gap `EquipmentTest`
didn't need to cover, since Supply has no equivalent third tier), and an
authorization sweep extending `AuthorizationMatrixTest.php`'s per-role
convention.

## Deliberately out of scope (per WP)

No procurement/accounting layer for Food (mandate-excluded, same as
Supply). No billeting check-in/out event log or facility-issue tracking
(decision #1). No public-portal exposure (decision #3) — a future WP once
there's a concrete ask. No division-wide vehicle fleet spanning multiple
meets (per-meet roster only, matching Supply's own catalog-scope decision).
Medical/DRRM (WP-REALIGN-12) remain separate, later work — this WP does
not touch their tables or domains, and WP-REALIGN-12 additionally has its
own standing policy-validation blocker unrelated to this WP.
