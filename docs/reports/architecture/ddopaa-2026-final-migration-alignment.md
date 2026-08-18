# DdOPAA 2026 final migration alignment

## Decision

The supplied SQL is a reference/data source and must not be executed against PMMS. PMMS already owns most of the domain structure. The Laravel importer will translate the source records into the tables below inside a transaction and by stable natural keys.

## Source-to-PMMS table map

| SQL target | PMMS equivalent | Classification | Alignment decision |
|---|---|---|---|
| `pmms_meets` | `meets` | REUSE | Resolve/create DdOPAA Meet 2026 by name and school year; retain PMMS date/status fields. |
| `pmms_municipalities` | `districts` | REUSE | `districts` is the established Province-division municipality master. No municipality table is created. |
| `pmms_school_districts` | `school_districts` | REUSE | Resolve under the canonical municipality, including the documented Nabunturan typo alias. Congressional districts are not inferred. |
| `pmms_people` | `people` | CREATE | Existing `personnel` is a meet/delegation roster row (and may repeat across meets), not a global identity. A small canonical identity table is required to deduplicate workbook people before an account exists. |
| `pmms_sports` | `sports` | REUSE | Resolve source codes/names against the existing catalog. Preserve Basketball 3x3 and separate Weightlifting/Kickboxing records; do not infer combined-header scope. |
| `pmms_meet_sports` | `meet_sports` | REUSE | Upsert only confirmed source sport mappings for the 2026 meet. |
| `pmms_twg_units` | `management_teams` | EXTEND | Reuse the generic Meet -> Team -> Members model, adding a stable source unit code and allowing several TWG units in the broader meet-management category. No per-committee tables. |
| `pmms_twg_memberships` | `management_team_members` | EXTEND | Add a pre-account `person_id` and source sequence; keep optional `user_id` for activation. |
| `pmms_dsc_assignments` | `district_sports_coordinator_assignments` | CREATE | No equivalent assignment exists. Model Meet + Municipality (`district_id`) + SchoolDistrict + Person, with multiple DSCs permitted per district. |
| `pmms_sport_personnel_assignments` | `meet_sport_assignments` | EXTEND | Add pre-account `person_id`, original designation, source geography/sequence, and provisioning flag. Keep the existing scoped role/status workflow. |
| `pmms_user_provisioning` | `account_provisions` | CREATE | The app has self-registration/password reset but no invitation/pending-user entity. Store unique suggested usernames and activation state; never store a password or invented email. |
| `pmms_sport_participation_rules` | existing sport/category/event eligibility fields and checkers | DATA_BACKFILL_ONLY | Do not introduce a competing rules engine in this migration. Retain the reference rows for a separately reviewed catalog backfill. |
| `pmms_coach_registration_settings` | Fortify registration + coach roster/assignment workflow | EXTEND | Registration exists, but explicit coach scope requests are missing; add assignment requests rather than a singleton settings table. |

## Existing authority alignment

- Municipality remains the official Province delegation through `delegations.district_id`; `school_id` remains each athlete/personnel member's origin.
- DSAC and Medical policies are already separated. Coaches are not granted either approval capability.
- Results use submitted/confirmed/finalized states and the medal tally reads finalized results only.
- `meet_sport_assignments` is the scope source for tournament personnel. Combined source functions are represented as atomic assignments to one person where the current enum requires it.
- Confidential medical details remain outside DSC, coach, and team-manager access. DSC receives readiness/status scope only.

## Import safeguards

1. Back up the target database and record the current migration batch before deployment.
2. Run `php artisan migrate --pretend`, then `php artisan migrate`; never use `migrate:fresh` or `db:wipe`.
3. Run the DdOPAA seeders. They use stable keys, transactions, and update-or-create semantics.
4. Review unresolved aliases/sports in the generated import report before activating provisions.
5. Re-running the import must not create duplicate people, assignments, memberships, or provisions.

## Preserved source discrepancies

- The final TM/TO sheet has 28 headers and no Basketball 3x3 header although Players includes it. Basketball 3x3 is preserved but receives no inferred personnel assignments.
- `WEIGHTLIFTING / KICKBOXING` is a combined personnel header. Existing separate sport records are preserved; the importer reports this mapping as unresolved instead of silently choosing one.
- Municipality-only district labels retain their source text and receive a null School District unless an exact canonical alias resolves.
