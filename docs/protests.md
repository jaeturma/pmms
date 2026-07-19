# Protests & Incident Monitoring

WP-03-07. Protests against results and matches with a manager decision flow, and a
simple meet-day incident log. No committee workflow, appeals, or protest fees.

## Protests

### Data model

`protests` — `delegation_id` (the filing delegation), exactly one of
`event_result_id` / `match_id` (both null-on-delete), `grounds` (≤1000),
`status` (`App\Enums\ProtestStatus`), `filed_by`, `decided_by`/`decided_at`,
`remarks`. The target must belong to the delegation's own meet (server-enforced).

### Flow

`filed → under_review → upheld | dismissed` — review and decision are
**manager-only** (`role:admin,organizer` routes); decisions **require remarks**
and record the decider; decided protests are terminal. Audited:
`protest.filed|under_review|upheld|dismissed`.

**An upheld protest does not change a result.** The protests page links upheld
result-protests to the WP-03-05 correction workflow — a "Correct result" dialog
pre-fills the correction reason from the protest (`Protest #N upheld: remarks`)
and PATCHes the existing `results.correct` endpoint, keeping one single
result-change path (reopen → re-encode → re-validate, fully audited).

### Filing & visibility

Filing: a delegation officer for **their own** delegation, or a manager for any
(`ProtestPolicy::create`). Lists: managers see all, officers only their own
delegation's protests, viewers none (`ProtestPolicy::viewAny` + query scoping).
Status filter on shared components.

## Incidents

### Data model

`incidents` — `meet_id`, optional `venue_id`, `description` (≤500), `severity`
(`minor|moderate|serious`), `medical_referral` flag, `status` (`open|resolved`),
`reported_by`, `resolved_at`. **No medical case data**: a medical incident
records only that a referral happened — the flag — never medical details; the
form says so.

### Lifecycle & authorization

Kept by managers — the whole module (list included) sits in the
`role:admin,organizer` route group. Log → update → resolve ⇄ reopen → delete,
all audited (`incident.reported|updated|resolved|reopened|deleted`). List
filters by status and meet. Sidebar entry appears for managers only.
