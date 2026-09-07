# DAVRAA Report

The Tournament ICT's tool for preparing the official-style **List of
Recommended Qualifiers to DAVRAA**. Sidebar: **DAVRAA Report** (shown to Admin,
central ICT, sport-scoped Tournament ICT, and the meet's Event Secretariat).

The defining rule: **grouping is a reporting decision, made by hand.** It is
never derived from Match / Result / Medal / Schedule / confirmed Entry. Those
only *suggest* athletes. The same Sport may produce one group or many.

## Data model

Everything references existing records by id — no person record is ever
duplicated.

- `davraa_report_groups` — `meet_id`, `sport_id`, `name` (user-defined),
  `division` (`GenderCategory` — boys/girls/mixed), `level`
  (`App\Enums\DavraaReportLevel` — elementary/secondary/sned), `status`
  (`App\Enums\DavraaReportStatus` — draft/final/archived), `notes`, `created_by`.
- `davraa_report_group_event` — pivot of the selected Sports Events.
- `davraa_report_members` — one per printed row: `designation`
  (`App\Enums\DavraaReportDesignation` — athlete/coach/assistant_coach/chaperone),
  `athlete_id` **or** `coach_user_id` (a manually-typed chaperone may have
  neither), `sort_order`, and a **field snapshot**: `last_name`, `given_names`,
  `middle_initial`, `lrn`, `school_name`, `district_name`.

### Snapshot / stability

`DavraaReportBuilder` rebuilds each member's snapshot from canonical
`Athlete` / `User`(+`Personnel`) data **every save**. The printed / exported
report renders from those snapshot columns — never a live query — so a finalized
report stays a stable document even after roster or coach assignments change.
Missing values (LRN, school, district) are left blank and flagged
`incomplete`; nothing is invented.

## Grouping — fully flexible

`DAVRAA Report Group` form fields: Report Group Name · Sport · Sports Event(s)
(multi-select) · Division · Level · Coach(es) / Assistant / Chaperone · Athletes
(multi-select) · Notes.

- **One coach, several events** — pick several events and one coach in a single
  group.
- **Different coaches per group** — make several groups for the same Sport, each
  with its own coach and roster (`Athletics – Sprints`, `Athletics – Jumps`).
- **Basketball** — separate groups by Division/Level (`Basketball 5x5 Secondary
  Boys`, `… Girls`, `… Elementary Boys`); Basketball 3x3 is its own Sport, so it
  gets its own groups.
- **Team events** — the ICT picks the actual team athletes from the athlete
  multi-select; the Result roster does **not** need to be complete.
- **Individual events** — athletes from several events can be combined into one
  group.

## Dropdowns

- **Coaches** — `GET davraa-reports/options?sport_id=` returns coach accounts
  (role `coach`) with an approved `CoachAssignmentRequest` in the meet; those
  associated with the selected Sport are marked. ICT may pick **any** of them —
  a coach is not required to be assigned to every selected event.
- **Athletes** — filtered by the canonical `sport_roster_members` for the
  current meet + selected Sport (optionally narrowed by Division/Level and, for
  display, by selected events). A name/LRN `search` also matches any athlete in
  the meet, so an ICT can add a qualifier even when Entry data is incomplete.
  Off-roster picks are allowed and marked.

## Ordering

Rows are reorderable in the form (↑/↓). Default sequence: ATHLETES → COACH →
ASST. COACH → CHAPERONE, but the ICT sets the final order (`sort_order`,
persisted).

## Actions

`DavraaReportController`: create · edit · **duplicate** (clones events +
members, status → draft) · manage athletes/coaches (via the form) · reorder ·
**preview/print** (`davraa-reports/print` Inertia page, `@media print` isolates
the sheet) · **export** (CSV — `LIST OF RECOMMENDED QUALIFIERS TO DAVRAA`
header, the 8 columns, and Prepared By / Recommended By / Tournament Secretary /
Tournament Manager footer) · **archive/restore** via `status` (never a hard
delete).

## Permissions (`DavraaReportAccess`, server-side)

Mirrors `DataIntegrityAccess`:

- **Admin** / central ICT (ICT management team) — every sport, meet-wide.
- **Tournament ICT** — create/manage only within their assigned Sport(s);
  manually selects coaches and athletes.
- **Event Secretariat** — view / print / export for any sport; no editing.
- **Coach** / others — no access.

Audit: `davraa_report.created|updated|duplicated|status_changed|printed|exported`.
