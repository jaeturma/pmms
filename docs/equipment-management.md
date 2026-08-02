# Supply and Equipment Management

WP-REALIGN-10, part of the DdOPAA organizational realignment — see
`docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md`
§16/§24 and `docs/architecture/pmms-approved-organizational-model.md` §7. This is
the one operational domain with **no prior planning artifact anywhere** in the
codebase or either documentation track before this WP — no `Equipment`/
`Inventory`/`Supply` model, controller, or bounded-context entry existed. The
approved model names the target entities (`EquipmentItem`, `EquipmentCategory`,
`EquipmentIssue`, `EquipmentReturn`, `EquipmentTransfer`, `InventoryAdjustment`)
and one hard constraint — "no procurement/accounting layer (mandate explicitly
excludes it)" — but leaves every column-level and workflow decision open. This
doc makes those decisions.

Four structural decisions were resolved with the owner via `AskUserQuestion`
before writing this doc (all four decided the recommended option):

1. **Item granularity — quantity-based.** `equipment_items` rows are stock
   lines ("Basketballs — qty 20"), not one row per physical unit. No
   serial/asset-tag tracking.
2. **Catalog scope — per-meet only.** `equipment_categories`/`equipment_items`
   are scoped to a single `meet_id`, defined fresh each meet by that meet's
   Supply Team — mirrors `ManagementTeam` itself being meet-scoped. No
   division-wide durable-goods catalog in this WP.
3. **Transfer scope — between venues within a meet.** `equipment_transfers`
   moves a stock line from one `Venue` to another; it does not model
   inter-committee custody handoff.
4. **Issue/return model — mixed, category-flagged.** `equipment_categories`
   carries `is_consumable`. Issues against a non-consumable ("returnable")
   category expect one or more matching `equipment_returns` rows (partial
   returns supported); issues against a consumable category are terminal —
   the quantity is used up, no return is ever recorded against them.

## Data model

All six tables carry `id` + timestamps; every FK follows this app's existing
`constrained()->cascadeOnDelete()` convention unless noted otherwise.

- **`equipment_categories`** — `meet_id` (FK, cascade), `name`, `description`
  (nullable), `is_consumable` (boolean, default `false`). Unique per
  `(meet_id, name)` — same one-of-a-kind-per-meet shape `management_teams`
  uses for `(meet_id, team_type)`.
- **`equipment_items`** — `equipment_category_id` (FK, cascade), `venue_id`
  (FK → `venues.id`, nullable, `nullOnDelete` — an item may sit in general
  storage with no venue assigned yet; `Venue` is a division-wide catalog like
  `Sport`, reused unmodified), `quantity` (unsigned integer — the stock
  line's total count), `condition` (nullable, `App\Enums\EquipmentCondition`:
  Good/Fair/Damaged/Lost), `notes` (nullable text).
  - **Available quantity is derived at read time, not stored**: `quantity -
    SUM(active issues' outstanding quantity)`, the same "no stored
    duplicate of a computable value" discipline `MedalTallyService` already
    established for medal counts. An issue's *outstanding* quantity is its
    own `quantity` minus the sum of `equipment_returns.quantity` rows
    against it. This one formula covers both consumable and returnable
    categories without a separate code path — consumables simply never
    accumulate returns, so their issued quantity stays permanently
    deducted.
- **`equipment_issues`** — `equipment_item_id` (FK, cascade), `venue_id` (FK
  → `venues.id`, cascade — where the equipment is being used; required,
  distinct from the item's *storage* venue, which a transfer moves
  separately), `quantity` (unsigned integer), `custodian_name` (nullable
  string — free text naming who physically took custody, same
  `role_title`-style free-text precedent `ManagementTeamMember` already
  uses), `issued_by_user_id` (FK → `users.id`, cascade — the Supply Team
  member who processed it), `purpose` (nullable text), `status`
  (`App\Enums\EquipmentIssueStatus`: Issued → PartiallyReturned → Returned;
  consumable-category issues stay `Issued` forever, since no return is ever
  possible against them), `issued_at` (timestamp, defaults to creation time).
- **`equipment_returns`** — `equipment_issue_id` (FK, cascade — **DB-level
  and app-level guarded against consumable-category issues**, see
  Validation below), `quantity` (unsigned integer — supports partial
  returns across multiple return events), `condition_on_return` (nullable,
  `EquipmentCondition`), `received_by_user_id` (FK → `users.id`, cascade),
  `notes` (nullable text), `returned_at` (timestamp, defaults to creation
  time).
- **`equipment_transfers`** — `equipment_item_id` (FK, cascade),
  `from_venue_id` (FK → `venues.id`, nullable, `nullOnDelete` — the item may
  be moving out of unassigned general storage), `to_venue_id` (FK →
  `venues.id`, cascade), `quantity` (unsigned integer), `transferred_by_user_id`
  (FK → `users.id`, cascade), `reason` (nullable text), `transferred_at`
  (timestamp, defaults to creation time). **A transfer also updates the
  item's own `venue_id`** to `to_venue_id` — the transfer row is the audit
  trail, the item's `venue_id` is the current-state pointer, same
  two-layer shape `AuditLogger` already gives every other status change in
  this app (a log row plus the model's own current field).
- **`inventory_adjustments`** — `equipment_item_id` (FK, cascade), `type`
  (`App\Enums\InventoryAdjustmentType`: Damage/Loss/Recount/Found),
  `quantity_delta` (signed integer — negative for Damage/Loss, positive for
  Found, either sign for Recount), `reason` (**required** text — mirrors
  `ResultController::correct()`'s reason-required-on-correction precedent;
  an inventory number changing outside the normal issue/return/transfer
  flow always needs a stated cause), `adjusted_by_user_id` (FK →
  `users.id`, cascade), `adjusted_at` (timestamp, defaults to creation
  time). **Applying an adjustment updates `equipment_items.quantity`
  directly** (unlike issues/returns, which only affect the *derived*
  available count) — an adjustment is a correction to the stock line
  itself.

Three new enums, `app/Enums/EquipmentCondition.php`,
`app/Enums/EquipmentIssueStatus.php`, `app/Enums/InventoryAdjustmentType.php` —
each with a `label()` method, matching every existing enum in this codebase
(`ManagementTeamStatus`, `ResultStatus`, etc.).

## Validation rules (app-level, not just DB constraints)

- An `equipment_issues.quantity` may never exceed its item's currently
  *available* quantity (the derived value above) at issue time.
- An `equipment_returns` row may only be created against an issue whose
  category `is_consumable = false`; attempting to return against a
  consumable-category issue is a validation error, not a silent no-op —
  same "surface it as a clean validation error" discipline
  `ManagementTeamController::store()` uses for its own uniqueness check.
- The sum of `equipment_returns.quantity` against one issue may never exceed
  that issue's own `quantity`.
- `equipment_transfers.quantity` may never exceed the item's quantity
  currently *at* `from_venue_id` conceptually — since this WP tracks one
  `venue_id` per item (not a per-venue ledger), a transfer's quantity is
  capped at the item's own `quantity` and, in the common case, a transfer of
  a partial quantity **splits the stock line** (the existing item's
  quantity is reduced by the transferred amount, and a new item row is
  created at `to_venue_id` with that amount) rather than trying to track
  "this item is split across two venues" on one row. A full-quantity
  transfer just moves the existing row's `venue_id`, no split needed.

## Authorization

Unlike `ManagementTeamController` (a flat `role:admin,organizer`-only route
group with no per-record policy, because there's no owner-scoping concept to
enforce), Supply/Equipment data has a real scoping concept — a meet's Supply
Team — so this WP introduces a real policy class, following
`docs/architecture/pmms-role-and-scope-map.md`'s own recommended pattern for
every WP-REALIGN-09-through-13 domain:

- **`App\Policies\SupplyPolicy`** — `viewAny`/`view`: Admin/Organizer
  (unconditional, via `Gate::allows('manage-meet-data')`) **or** a
  `ManagementTeamMember` row for that meet's Supply team
  (`$user->managementTeamMemberships()->whereHas('managementTeam', fn ($q) =>
  $q->where('meet_id', $meet->id)->where('team_type',
  ManagementTeamType::Supply))->exists()`) — the exact shape
  `ScoringSessionController::canManage()` already uses for Technical
  Officials. `create`/`update`/`delete` on categories/items/issues/
  returns/transfers/adjustments: same check, no further per-record
  restriction (a Supply Team member manages the whole team's inventory, not
  a subset of it — matching the fact `ManagementTeamMember` itself has no
  finer-grained scope).
- **This is a deliberate narrowing from WP-REALIGN-09's own precedent**:
  Management Teams' *index* is open to every authenticated role ("who's on
  the ICT team isn't sensitive"); equipment inventory is internal
  operational data with no public/general-viewer relevance, so `viewAny`
  here is Admin/Organizer/Supply-Team-member-only, not open to Coach/Viewer/
  every role. Flagged here explicitly since it's a genuine deviation from
  the immediately-preceding WP's authorization shape, not an oversight.

## UI

`resources/js/pages/equipment/index.tsx` — sidebar entry "Equipment"
(`icon: Boxes`), placed in the sidebar's admin/organizer tier
(`managerNavItems`, alongside "Management"), not `mainNavItems` where
Management Teams sits. This is a deliberate deviation from that adjacency:
`SupplyPolicy` also grants access to a meet's Supply Team members who aren't
Admin/Organizer, but the sidebar only reads the coarse `UserRole`, not
per-committee `ManagementTeamMember` rows — those members can still reach
`/equipment` directly (the backend authorizes them correctly), they just
don't get a sidebar link. Closing that gap needs a dedicated shared Inertia
prop computing `SupplyPolicy::viewAny()` per request, which is real scope
beyond this WP's UI section — noted here as a known follow-up, not silently
dropped. Meet-scoped (a meet
selector, same `All meets`-style filter `management-teams/index.tsx` uses,
defaulting to the current meet). Layout: one card per category, each listing
its items (with venue, quantity, computed available count, condition badge),
inline issue/return/transfer/adjustment actions per item via dialogs
(matching `ManagementTeamController`'s add-member-dialog pattern) rather than
separate pages per action — six tables, one page, consistent with how
Management Teams keeps team + member management on one page rather than
splitting members into their own route.

## Audit

Every mutation goes through `AuditLogger`, matching every other domain:
`equipment_category.created|updated|deleted`,
`equipment_item.created|updated|deleted`, `equipment_issue.created`,
`equipment_return.created`, `equipment_transfer.created`,
`inventory_adjustment.created` — the four transactional logs
(issue/return/transfer/adjustment) are deliberately create-only, no
update/delete audit actions, since they're an append-only historical
record (same "corrections are a new event, not a silent edit" discipline
`ResultController::correct()` established).

## Testing

New `tests/Feature/EquipmentTest.php`, following `ManagementTeamTest.php`'s
own structure — CRUD on categories/items, the full issue → return lifecycle,
the issue → no-return-allowed path for consumables, a transfer that splits a
partial-quantity stock line, an inventory adjustment requiring a reason,
availability-quantity math across concurrent issues/returns, and a
`SupplyPolicy`-focused authorization sweep (Admin/Organizer/Supply-Team-member
allowed, every other role denied) extending `AuthorizationMatrixTest.php`'s
existing per-role sweep convention.

## Deliberately out of scope (per WP)

No procurement/accounting/purchasing layer (explicitly excluded by the
mandate). No division-wide durable-goods catalog spanning multiple meets
(catalog-scope decision above). No serialized per-unit/asset-tag tracking
(item-granularity decision above). No inter-committee custody handoff via
`equipment_transfers` (transfer-scope decision above) — if Supply needs to
hand equipment to Medical or ICT, that's tracked as an issue to a venue those
teams are using, not a team-to-team transfer record. Food/Billeting/Transport
(WP-REALIGN-11) and Medical/DRRM (WP-REALIGN-12) remain separate, later work
packages — this WP does not touch any of their tables or domains, and
WP-REALIGN-12 additionally has a standing policy-validation blocker
(`docs/11-backlog/phase-1-deferred-scope.md:17`) unrelated to this WP.
