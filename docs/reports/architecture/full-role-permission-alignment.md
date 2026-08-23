# Full role and permission alignment report

## 1. Actual production roles

Production identity consists of the base `UserRole` plus active assignment records. Management units include Top Management, Incident Command, Sports Lines Up and Placement, Secretariat, Grievance, program, decoration, venue, food, usherettes, peace/security, billeting, finance, logistics, medical, learners rights, quality assurance, water/light/sanitation, Information, clean/green, Event Secretariat, support staff, DSAC, announcers, and kitchen personnel.

## 2. Current permissions

Capabilities are enforced through Laravel gates and domain policies. Administrators retain system and meet administration; Organizers have meet-wide read access. Central ICT may provision accounts, maintain school master data, review coach registrations, maintain assignment membership, and support schedules. Information manages announcements. Operational teams use their own policies. Competition personnel use active meet-sport assignments.

The Organizer base role is now a meet-wide read-only role. It may view setup, registration, competition, and operations, but it receives no mutation permission merely from `users.role = organizer`; write authority must come from an explicit active functional assignment.

## 3. Current scope

`CompetitionAccessService` centralizes meet, sport, category/event, delegation, and ownership filters. Coaches are delegation plus event scoped. Tournament personnel and officials are sport/category scoped across delegations. DSAC sees current-meet eligibility data. Management monitoring is current-meet read-only.

## 4. Target model

Keep one account per person. Store broad capability in `users.role`; store each contextual duty in `meet_sport_assignments`, `management_team_members`, `athlete_oversight_assignments`, or coach assignment records. Optional `sport_category_id` narrows a sport appointment. Multiple active records express multiple legitimate duties.

## 5. Over-permission corrected

Information was previously treated as Central ICT by account, school, and coach-review helpers. This was removed. Management reports now have a distinct read-only gate. Tournament and operational mutations continue to require their domain authority. Central ICT access to assignment routes is controller-checked and does not grant competition decision powers.

## 6. Missing permissions corrected

Information can now manage announcements. Top Management and other meet-management units can open read-only monitoring. Central ICT can reach account provisions and maintain existing assignment memberships. Tournament Manager accounts are valid sport-assignment candidates.

## 7. Duplicate-role analysis

Creating a new enum role for every committee or sport duty would duplicate identity and make multi-role users unreliable. PMMS therefore retains coarse account roles and presents all semantic roles derived from assignments in User Management.

## 8. Missing-assignment behavior

No assignment means no scoped mutation authority. Inactive, declined, ended, wrong-meet, wrong-sport, wrong-category, wrong-delegation, and wrong-owner records are rejected by policies/controllers and omitted by scoped queries. Category assignment is validated against the selected sport.

## 9. Navigation alignment

Sidebar links follow the same capability flags: Information receives Announcements; authorized management receives Monitoring; restricted modules are hidden. User Management links directly to sport assignments and management teams and displays a user’s complete role and scope context.

## 10. Policy alignment

Eligibility, medical, supply, food, billeting, DRRM, grievance, schedule, entry, scoring, result, and account workflows remain separate. Route visibility never substitutes for policy/controller authorization. Sensitive decisions remain auditable.

## 11. Query-scope alignment

List, detail, report, download, scoring, and result queries apply the same scope source. The current meet is mandatory where applicable. Nullable relationships are handled without assuming that historical or partially imported records still have every parent.

## 12. Verification strategy

Feature coverage must assert both positive and negative paths: Information versus Central ICT, Top Management read-only behavior, DSAC eligibility-only authority, medical-only clearance, coach ownership/delegation/event scope, tournament sport/category scope, entry eligibility prerequisites, and denial for inactive or missing assignments. Formatting, frontend type checking/build, focused feature tests, and the full test suite are the release checks.
