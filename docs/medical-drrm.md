# Medical and DRRM

WP-REALIGN-12, part of the DdOPAA organizational realignment — see
`docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md`
§17/§24 and `docs/architecture/pmms-approved-organizational-model.md` §7.
Unlike every prior WP-REALIGN-0x domain, Medical carried a standing, formal
"requires policy validation" blocker (`OD-15`/`PSG-05` —
`docs/00-product/open-decisions.md`, `docs/10-review/policy-rulebook-and-source-validation-gap-register.md`)
that the approved model explicitly instructed resolving with the product
owner before this WP's schema work begins, not silently overriding. That
resolution happened via `AskUserQuestion` immediately before this doc was
written — see "Resolved decisions" below. This is also the one domain the
gap assessment itself flags **High risk** (privacy/policy-gated), not
Medium like every other WP-REALIGN-0x domain, and it is the largest single
WP in the series (10 new tables across two genuinely distinct subtrees).

## Resolved decisions

Four decisions were resolved with the owner via `AskUserQuestion` (this WP's
own OD-15/PSG-05 resolution, not a formal DepEd policy source — see the
"Honesty about what this resolves" note below):

1. **Medical data scope — minimal.** Known conditions/allergies + an
   emergency contact only. No encounter/treatment history, no per-incident
   case log. This is a real narrowing from the approved model's original
   two-table sketch (`MedicalClearance` + `MedicalIncident`) down to one
   table — there is no `MedicalIncident` case-management table in this WP.
2. **Medical access model — Medical Team only, plus a break-glass
   emergency override** with mandatory reason capture and post-use audit
   review.
3. **Minor consent — a parent/guardian consent flag captured at
   delegation registration.**
4. **DRRM scope — all three**: weather, medical mass-casualty, and
   security emergencies, matching the approved model's full entity list.

**Honesty about what this resolves**: `PSG-05` (a verified DepEd medical/
health-services policy document) was never located anywhere in this
project's history and remains formally unverified — this WP does not
retroactively manufacture that source. What the owner's decisions above
satisfy is the approved model's own narrower instruction: *"resolve that
with the product owner before WP-REALIGN-12 schema work begins."* If a
real DepEd medical policy surfaces later, these decisions may need
revisiting — flagged here rather than silently treated as permanent.

## Medical

**Deliberately minimal**, per decision #1 — one clearance record per
person, no case-management history.

### Data model

- **`medical_clearances`** — `meet_id` (FK, cascade), `athlete_id` (FK,
  cascade, nullable), `personnel_id` (FK, cascade, nullable) — exactly one
  of the two is set, the same mutual-exclusivity shape
  `ProtestController::store()` already enforces for
  `event_result_id`/`match_id` — `status`
  (`MedicalClearanceStatus`: Cleared/Pending/Restricted/Referred, the
  approved model's own "safe aggregate statuses"), `conditions` (nullable
  text — free-text known conditions/allergies, deliberately not a
  structured many-to-many catalog; decision #1's minimalism extends to
  the schema, not just the missing case-log table), `emergency_contact_name`/
  `emergency_contact_phone` (strings), `consent_confirmed` (boolean,
  default `false` — decision #3), `consent_confirmed_at` (nullable
  timestamp), `notes` (nullable text, Medical-Team-only detail).
- **`medical_access_logs`** — the break-glass audit trail decision #2
  requires. `medical_clearance_id` (FK, cascade), `accessed_by_user_id`
  (FK → `users.id`, cascade), `reason` (text, **required** — same
  reason-required discipline `InventoryAdjustmentController` already
  established for anything bypassing a normal-access path), `accessed_at`
  (timestamp, defaults to creation time), `reviewed_by_user_id` (FK →
  `users.id`, nullable — the post-use reviewer), `reviewed_at` (nullable
  timestamp), `review_notes` (nullable text). This is a genuinely new
  pattern for this app: every other domain's `AuditLogger` records
  *mutations*; this table records a *read* (an emergency view of
  sensitive data), because the mutation-only convention has nothing to
  log for "someone looked at this."

### Authorization — a real departure from every prior WP-REALIGN-0x policy

Every previous domain (Supply/Food/Billeting/Transport) grants Admin
*and* Organizer unconditional access. Medical does not — decision #2's
literal wording is "Medical Team only," not "Medical Team + managers."
Three tiers, not two:

- **Aggregate status** (`status` only — cleared/pending/restricted/
  referred): visible to Admin/Organizer/Medical-Team, same broad-summary
  visibility every other domain's index gives its non-manager viewers.
- **Raw detail** (`conditions`/`emergency_contact_*`/`notes`): visible
  only to **Active Medical Team members, or Admin** (not Organizer) —
  mirroring the `administer` gate's Admin-only tier this app already uses
  for its most sensitive existing data (audit logs, system settings,
  division settings), not the `manage-meet-data` (Admin+Organizer) gate
  every other WP-REALIGN-0x domain uses. This is the direct, deliberate
  consequence of "strongest privacy boundary" and the mandate's own "do
  not expose diagnosis... to unauthorized roles" language.
- **Break-glass override**: any authenticated staff role (Coach,
  Organizer, Technical Official, etc.) may invoke emergency access to
  view raw detail for **one specific clearance record** — not a standing
  unlock, not a bulk browse — with a required reason, immediately creating
  a `medical_access_logs` row. An Admin or Medical Team lead later marks
  it reviewed (`reviewed_by_user_id`/`reviewed_at`/`review_notes`),
  satisfying decision #2's "mandatory post-use review."

`App\Policies\MedicalPolicy` (plain class, same shape as `SupplyPolicy`/
`FoodPolicy`, built on `ChecksManagementTeamMembership`): `viewAny`
(status-only, Admin/Organizer/Medical-Team), `viewDetail` (Medical-Team-
or-Admin only), `manage` (create/update a clearance — Medical-Team-or-
Admin only, not Organizer), `requestEmergencyAccess` (any authenticated
staff role), `reviewAccess` (Admin or Medical-Team-lead — an `is_head`
`ManagementTeamMember` row).

### Controllers

`MedicalClearanceController` (index — returns status-only rows for
non-detail-tier viewers, full rows for detail-tier viewers, computed
server-side per request; store/update — Medical-Team-or-Admin only;
no `destroy()` — a clearance record is corrected via `update()`, never
deleted, matching the "sensitive records are append-corrected, not
erased" discipline results/protests already follow in this app).
`MedicalAccessController` (`store` — invoke break-glass access, records
the log row and returns the detail payload in the same response;
`review` — Admin/Medical-Team-lead marks a log entry reviewed).

### Deliberately out of scope (per WP)

Wiring `EligibilityReviewPolicy`/other domains to actually *read* the
aggregate `medical_clearances.status` — the approved model names this as
a future Anti-Corruption-Layer-style integration, not something this WP
does automatically just because the table now exists (the same
"introduces the table, doesn't wire every consumer" discipline
WP-REALIGN-09 already applied to `EligibilityReviewPolicy`). No case-
management history (decision #1). No structured conditions catalog. No
consent-workflow UI beyond the flag itself (e.g., no document-upload for
a signed consent form — that would reuse the existing `FileUploadService`
in a later refinement if needed, not this WP).

## DRRM

Genuinely net-new with zero prior art in either planning track — the
approved model didn't even sketch columns for it, only entity names.
Broadest scope per decision #4 (weather, medical mass-casualty, security).
Unlike Medical, DRRM data (plans, routes, contacts, equipment, incident
reports) is not personally sensitive — standard two-tier authorization
(`DrrmPolicy`, same `SupplyPolicy`/`FoodPolicy` shape: Admin/Organizer/
Active DRRM-Team member, no third tier).

New enum `DrrmCategory` (Weather/Medical/Security) tags plans, contacts,
checklists, and incidents by which of the three decision-#4 categories
they belong to.

### Data model (8 tables, per the migration plan's own list)

- **`drrm_plans`** — `meet_id` (FK, cascade), `category` (`DrrmCategory`),
  `title`, `description` (text).
- **`venue_emergency_plans`** — `venue_id` (FK, cascade — the existing,
  division-wide `Venue` catalog, reused unmodified; unlike
  `BilletingVenue`, an emergency plan genuinely describes the competition
  venue itself, not a disjoint lodging concept, so reuse is correct here
  where it wasn't for Billeting), `meet_id` (FK, cascade), `plan_detail`
  (text — assembly points, exits, procedure).
- **`evacuation_routes`** — `venue_id` (FK, cascade), `name`,
  `description` (text).
- **`emergency_contacts`** — `meet_id` (FK, cascade), `name`, `role`
  (nullable string, e.g. "Barangay Health Center," "Fire Department"),
  `phone`, `category` (`DrrmCategory`, nullable — which emergency type
  this contact serves; nullable since some contacts, e.g. a venue
  facilities manager, aren't category-specific).
- **`drrm_equipment`** — `meet_id` (FK, cascade), `name`, `quantity`
  (unsigned integer), `venue_id` (FK, nullable, `nullOnDelete` — storage
  location), `notes` (nullable text). **Deliberately a flat inventory
  list, not Supply's issue/return/transfer machinery** — nothing in
  decision #4 asked for tracking DRRM equipment custody the way Supply
  tracks issued sports equipment; duplicating that whole subsystem here
  would be unrequested scope.
- **`readiness_checklists`** — `meet_id` (FK, cascade), `category`
  (`DrrmCategory`), `item` (string — the checklist line), `is_complete`
  (boolean, default `false`), `completed_by_user_id` (FK → `users.id`,
  nullable), `completed_at` (nullable timestamp).
- **`emergency_incidents`** — `meet_id` (FK, cascade), `venue_id` (FK,
  nullable, `nullOnDelete`), `category` (`DrrmCategory`), `description`
  (text), `status` (`EmergencyIncidentStatus`: Reported/Responding/
  Resolved), `reported_by_user_id` (FK → `users.id`, cascade),
  `reported_at` (timestamp, defaults to creation time), `resolved_at`
  (nullable timestamp). **Deliberately a new table, not a repurposed
  `Incident`** — the approved model's own §7 explicitly rules this out:
  `Incident` (`app/Models/Incident.php`) is the existing, working,
  simpler protest-adjacent meet-day log (`medical_referral` is a single
  boolean flag on it today); DRRM incidents need classification/responder/
  escalation fields that would bloat `Incident`'s current purpose for the
  majority of its existing, non-emergency use.
- **`emergency_communication_logs`** — `emergency_incident_id` (FK,
  cascade), `message` (text), `sent_by_user_id` (FK → `users.id`,
  cascade), `sent_at` (timestamp, defaults to creation time) — an
  append-only record of communications sent during an incident response
  (e.g., "Evacuated Gym per Plan A," "Notified Barangay Health Center").

### UI — two pages, not one

Pre-event planning/readiness (`drrm_plans`/`venue_emergency_plans`/
`evacuation_routes`/`emergency_contacts`/`drrm_equipment`/
`readiness_checklists`) is a fundamentally different workflow from live
incident response (`emergency_incidents`/`emergency_communication_logs`)
— one is prepared in advance, the other happens in real time during an
emergency. Splitting into `resources/js/pages/drrm/plans.tsx` and
`resources/js/pages/drrm/incidents.tsx` keeps each page's cognitive load
manageable, the same reasoning WP-REALIGN-11 used to keep Food/Billeting/
Transport on three separate pages rather than one.

### Controllers

`DrrmPlanController` (index for the Plans & Readiness page — returns
plans, venue plans, routes, contacts, equipment, and checklists together,
the same "one index() bundles every related collection" shape
`MealScheduleController`/`VehicleController` already use; plan CRUD).
`VenueEmergencyPlanController`, `EvacuationRouteController`,
`EmergencyContactController`, `DrrmEquipmentController` (CRUD each).
`ReadinessChecklistController` (store/destroy + a toggle-complete
action). `EmergencyIncidentController` (index for the Incidents page,
CRUD, status transitions). `EmergencyCommunicationLogController`
(`store` only — append a message to an incident's log; no update/delete,
same append-only discipline as every other transactional log in this
app).

## Audit

Every mutation through `AuditLogger`:
`medical_clearance.created|updated`, `medical_access.requested|reviewed`,
`drrm_plan.created|updated|deleted`,
`venue_emergency_plan.created|updated|deleted`,
`evacuation_route.created|updated|deleted`,
`emergency_contact.created|updated|deleted`,
`drrm_equipment.created|updated|deleted`,
`readiness_checklist.created|completed|deleted`,
`emergency_incident.created|status_updated`,
`emergency_communication_log.created`.

## Testing

`tests/Feature/MedicalTest.php` — the model relationships, the mutual-
exclusivity constraint (athlete XOR personnel), the three-tier
authorization sweep (status-only vs. detail vs. break-glass, including a
non-Medical-Team Coach successfully invoking emergency access and an
Organizer being correctly denied *routine* detail access — the one
domain in this series where Organizer is NOT a manager tier), the
break-glass flow creating a real `medical_access_logs` row, and the
review action. `tests/Feature/DrrmTest.php` — CRUD across all 8 tables,
the incident → communication-log append flow, and a standard two-tier
authorization sweep matching `FoodTest.php`'s own convention.

## Deliberately out of scope (per WP)

No case-management history (decision #1). No PSG-05 formal policy source
(see "Honesty about what this resolves" above) — if DepEd issues an
actual medical/health-services policy later, revisit these decisions, don't
assume they're permanent. No wiring of Medical's aggregate status into
`EligibilityReviewPolicy` or any other consumer. No DRRM-equipment
issue/return tracking (Supply's machinery, not requested here). No
cross-meet DRRM plan reuse — plans are per-meet like every other
WP-REALIGN-0x catalog, consistent with the project's now-standard
catalog-scope convention.
