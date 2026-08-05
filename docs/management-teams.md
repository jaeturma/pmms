# Management Teams & Membership

WP-REALIGN-09 (2026-08-02), part of the DdOPAA organizational realignment —
see `docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md`
§14-15 and `docs/architecture/pmms-approved-organizational-model.md` §6.

## Scope

`ManagementTeam` is deliberately the **administrative shell only** — who is on a
meet's Top Management/Meet Management/Results Committee/Division Screening and
Accreditation Committee (DSAC)/ICT/Supply/Food/Billeting/Transport/Medical/DRRM
team, and what its mandate
is. It does **not** own the specialized operational data those teams produce
(medical records, equipment inventory, meal plans, etc.) — those are separate,
later work packages (WP-REALIGN-10 through -12) that will reference a team by id,
the same split `docs/01-architecture/bounded-context-catalog.md`'s BC-05 catalog
entry itself recommended ("Committee Operations owns the administrative shell...
not the specialized operational data").

Two follow-on decisions already deferred here rather than resolved:
`EligibilityReviewPolicy::decide()` (docs/results.md's Results Committee note) and
result validation still read `Admin`/`Organizer` generically — wiring them to
check `ManagementTeam` membership (`team_type` = `DivisionSecretariat` /
`ResultsCommittee`) is a distinct, not-yet-built follow-up, not something this WP
does automatically just because the table now exists.

## Data model

- `management_teams` — `meet_id` (FK cascade), `team_type`
  (`App\Enums\ManagementTeamType`, 11 cases), `name`, optional `description`,
  `status` (`App\Enums\ManagementTeamStatus`: Forming → Active → Disbanded,
  default Forming). Unique per `(meet_id, team_type)` — at most one team of a
  given type per meet (the natural real-world shape; not a mandate requirement
  in so many words).
- `management_team_members` — `management_team_id` (FK cascade), `user_id` (FK
  cascade), optional `role_title` (free text, e.g. "Logistics Coordinator"),
  `is_head` (boolean), optional `responsibilities` (free text), `status`
  (`App\Enums\ManagementTeamMemberStatus`: Pending → Active → Ended, or Declined
  — mirrors `MeetSportAssignmentStatus`'s shape, same "acceptance" step the
  mandate's own responsibility lists describe). Unique per `(management_team_id,
  user_id)` — one membership row per person per team.
- **Deliberately not role-restricted**: unlike `MeetSportAssignmentController`
  (which only allows Admin/Organizer/Technical Official as candidates, matching
  the existing Technical Official assignment precedent), any account can be added
  as a team member — a committee (Billeting, Food, Transport, etc.) may
  reasonably be staffed by a Delegation Officer, Coach, or any other role. See
  `docs/architecture/pmms-role-and-scope-map.md`'s "Design note" — a person's
  base `UserRole` stays coarse; `ManagementTeamMember` rows are the actual scope,
  the same pattern `sport_user`/`MeetSportAssignment` already established for
  Technical Officials.

## Authorization

View (`management-teams.index`) is open to every authenticated role — "who's on
the ICT team" isn't sensitive, same view-open/mutate-restricted shape as
`docs/authorization.md`'s Tournament Assignments row. Create/update/delete a team,
and add/update-status/remove a member, are `role:admin,organizer`-only route
middleware — no per-record policy class, matching the Districts/Schools/Sports
catalog-CRUD precedent (there's no owner-scoping concept to enforce here, unlike
Athlete/Personnel/Entry).

## UI

`resources/js/pages/management-teams/index.tsx` (sidebar "Management Teams",
between "Tournament Assignments" and "Matches") — one card per team (grouped/
filterable by meet), each showing its members inline with an add-member dialog,
per-member status `Select`, and remove action; team-level edit (name/description/
status) and delete. Candidate members are a plain searchable-by-scroll `Select`
of every user (`userOptions`), not restricted like the Tournament Assignments
picker.

## Audit

`management_team.created|updated|deleted`,
`management_team_member.added|status_updated|removed`, all via `AuditLogger`, with
meet/team/user context.

## Deliberately out of scope (per WP)

Medical/DRRM/Supply/Food/Billeting/Transport's own operational data (WP-REALIGN-10
through -12) — this WP is membership only. Wiring `EligibilityReviewPolicy`/
`ResultController`'s manager-only checks to read `ManagementTeam` membership
instead of the generic `Admin`/`Organizer` role (the deferred piece of
WP-REALIGN-06/08) — not done automatically, a distinct future decision.
