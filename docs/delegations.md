# Delegation Registration

WP-02-05. Delegations per meet, and the first module with per-record
authorization: delegation officers manage only their own delegation.

## Registering unit: School or Municipality

A delegation registers under **exactly one** of `school_id` / `district_id` —
whichever matches the current `Division` type (see `docs/division.md`): a
City division registers by School; a Province division (this deployment's
default) registers by District, presented as "Municipality." A municipal
delegation pools multiple schools' athletes/personnel under one registration;
`Delegation::registrantName()` / `registrantType()` return whichever is set
and are the only correct source for the delegation's own identity (never
build it from `school_id` directly). Individual athlete/personnel home-school
attribution is a separate, later concern (see "For later work packages").

Which field is required is enforced by `Division::current()->type` in
`DelegationStoreRequest`, not a bare "exactly one of" — under Province,
`school_id` is `prohibited` outright (not just optional), so a Province
deployment can never accidentally create a school-rooted delegation.

## Data model

- `delegations` — `meet_id` + `school_id` (nullable) + `district_id`
  (nullable, restrict on delete) — separate `unique(meet_id, school_id)` and
  `unique(meet_id, district_id)` indexes (MySQL excludes NULLs from
  multi-column uniqueness per row, so each enforces correctly regardless of
  which registering unit is in use); head-of-delegation contact fields
  (`head_name`, optional phone/email), `status`
  (`App\Enums\DelegationStatus`: `draft → submitted → approved`, not mass assignable).
- `delegation_user` — pivot assigning delegation-officer users to their delegation.

## Authorization (DelegationPolicy)

| Action | Admin/Organizer | Assigned officer | Others |
|---|---|---|---|
| See in list | all delegations | own only | viewers: all, read-only |
| Register (create) | ✔ (meet must be `registration_open`) | ✘ | ✘ |
| Edit head details | ✔ always | ✔ while draft/submitted + registration open | ✘ |
| Submit | ✔ | ✔ while registration open | ✘ |
| Approve / Return | ✔ (submitted only) | ✘ | ✘ |
| Assign officers | ✔ (role-checked: only `delegation_officer` users) | ✘ | ✘ |
| Delete | ✔ draft only | ✘ | ✘ |

Registration-window enforcement uses `Meet::isRegistrationOpen()` (the WP-02-04 hook).
Status preconditions (submit needs draft, approve/return need submitted) are transition
rules in the controller; role/ownership rules live in the policy.

## Audit

`delegation.created|updated|submitted|approved|returned|officers_updated|deleted`, each
with `registrant` (the delegation's own school-or-municipality name, via
`registrantName()`) and meet (where relevant) context — never a raw
`school`/`district` key, so the audit trail reads correctly for either
division type.

## UI

`resources/js/pages/delegations/index.tsx` — action buttons are driven by per-row
`can_*` flags computed from the policy server-side, so the UI never shows an action the
backend would refuse. The registration dialog shows a School picker under a City
division or a Municipality picker under Province (never both), sourced from
`schoolOptions`/`districtOptions` — whichever the current division type doesn't use is
returned empty. Officer checklist dialog, submit/approve/return/delete confirmations.
Sidebar entry: Delegations (label unaffected; only the registry page/nav for
districts/municipalities relabels).

## Officer roster scope (accepted consequence, reviewed WP7)

`AthletePolicy`/`PersonnelPolicy` scope an officer to a record via
`$record->delegation->hasOfficer($user)` — the **delegation**, not the
individual's own `school_id`. Under a municipal (Province) delegation this
means an assigned officer sees and manages the delegation's **entire pooled
roster**, across every school it registers under — a materially larger
scope than a City deployment's one-officer-one-school norm, where delegation
and school are always 1:1.

This is accepted and intended, not an oversight: assigning an officer to a
delegation (`DelegationPolicy::assignOfficers`, managers only) is already a
deliberate trust decision, and the delegation — not the school — is this
app's authorization boundary everywhere else (protests, entries, matches,
accreditation). Narrowing officer visibility to only their own school within
a shared municipal delegation would need a new, finer-grained scoping
mechanism nowhere else in the app has; it is not required by the current
deployment and is not built. Proven explicitly in `AthleteTest` ("an officer
assigned to a municipal delegation sees the whole pooled roster").

## Individual attribution is fully re-keyed (as of WP5)

Athletes and personnel carry their **own** `school_id` (WP3, see
`docs/athletes.md` "Home school"), and every module that displays an
individual's school — their own list/profile, entries, match participants,
result placements, report rows, ID cards, the medal tally, the public
portal — reads it from `athlete.school`/`personnel.school`, never from the
delegation. The delegation's `registrantName()` remains correct and
unchanged for describing the **delegation itself** (rosters, ID-card batch
headers, protest labels, audit `registrant` context) — see "Registering
unit" above for that distinction. `MedalTallyService` (`docs/medal-tally.md`)
was the last module re-keyed (WP5) — a municipal delegation's medals now
correctly split across its own schools and still roll up into one
municipality row. This closes out the Division initiative's data-model work
— see the plan at `C:\Users\DEPED\.claude\plans\wondrous-spinning-cosmos.md`
for the remaining WP6 (sample seeder) and WP7 (compliance review).

## For later work packages

- WP-02-06/07 hang athletes and personnel off the delegation and reuse the same policy
  scoping (`Delegation::hasOfficer()`).
- WP-02-08 checks both the meet window and delegation approval before accepting entries.
