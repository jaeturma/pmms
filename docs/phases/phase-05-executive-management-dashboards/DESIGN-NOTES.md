# Phase 5 Design Notes

Correction to the superseded draft: there are **four roles, not seven** —
`App\Enums\UserRole`: Admin, Organizer, Delegation Officer, Viewer. Phase 5 is
for Admin and Organizer only (`Gate::manage-meet-data`, same gate Phase 3's
operational queues already use). "Medal tally is delegation-based" is wrong —
standings are keyed by each athlete's own `school_id` and roll up to
district/municipality (`docs/medal-tally.md`); a Phase 5 performance-history
view must aggregate the same way, per meet, then across meets.

Important rules:

- MySQL is the source of truth; Phase 5 pages read the same tables operations
  write — no reporting database, no ETL.
- Nothing in Phase 5 is meet-day real-time. Phase 3's `operations` block
  already owns "what's happening right now for the Active meet" — Phase 5
  owns "how are things trending across meets/school years." Don't duplicate
  Phase 3's widgets; link to them instead where relevant.
- A **delegation** is the registering unit (a school under City, a
  municipality under Province); an **individual** (athlete, personnel, entry,
  result, medal) is attributed to their own **school**, always — the
  Division initiative's core distinction (`docs/division.md`). Every Phase 5
  query must be explicit about which one it's counting.
- `MedalTallyService::standings($meetId, $sportId)` already derives one
  meet's standings correctly (validated-only, ties shared, area-label-aware).
  The performance-history WP calls it once per meet and aggregates the
  results — it does not reimplement tally logic.
- Registration/validation "progress" reuses existing status enums
  (`Delegation` draft/submitted/approved, `ResultStatus`
  encoded/validated, `EligibilityReview::Status` pending/approved/returned,
  `ProtestStatus`, `IncidentStatus`) — counted per meet, not recomputed.
- No new mutable tables. If a query becomes too slow to compute live, the
  fix is indexing or a cached read model tied to existing data — not a new
  system of record. Cross that bridge only if it's actually slow.
- Public portal stays untouched — Phase 5 is entirely inside the
  authenticated app; no new public routes, no new public props.
- Reuse the shared component library (`ui/table`, `PageHeader`, `EmptyState`,
  `StatCard`) before introducing anything new, same as every prior phase.
