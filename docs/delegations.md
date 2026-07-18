# Delegation Registration

WP-02-05. School delegations per meet, and the first module with per-record
authorization: delegation officers manage only their own delegation.

## Data model

- `delegations` — `meet_id` + `school_id` (unique pair, both restrict on delete; the
  school registry refuses to delete a school with delegations), head-of-delegation
  contact fields (`head_name`, optional phone/email), `status`
  (`App\Enums\DelegationStatus`: `draft → submitted → approved`, not mass assignable).
- `delegation_user` — pivot assigning delegation-officer users to their delegation.

## Authorization (DelegationPolicy)

| Action | Admin/Organizer | Assigned officer | Others |
|---|---|---|---|
| See in list | all delegations | own only | viewers: all, read-only |
| Register (create) | ✔ (meet must be `registration_open`) | ✘ | ✘ |
| Edit head details | ✔ always | ✔ while draft + registration open | ✘ |
| Submit | ✔ | ✔ while registration open | ✘ |
| Approve / Return | ✔ (submitted only) | ✘ | ✘ |
| Assign officers | ✔ (role-checked: only `delegation_officer` users) | ✘ | ✘ |
| Delete | ✔ draft only | ✘ | ✘ |

Registration-window enforcement uses `Meet::isRegistrationOpen()` (the WP-02-04 hook).
Status preconditions (submit needs draft, approve/return need submitted) are transition
rules in the controller; role/ownership rules live in the policy.

## Audit

`delegation.created|updated|submitted|approved|returned|officers_updated|deleted`, each
with school (and meet where relevant) context.

## UI

`resources/js/pages/delegations/index.tsx` — action buttons are driven by per-row
`can_*` flags computed from the policy server-side, so the UI never shows an action the
backend would refuse. Create/edit dialogs, officer checklist dialog, submit/approve/
return/delete confirmations. Sidebar entry: Delegations.

## For later work packages

- WP-02-06/07 hang athletes and personnel off the delegation and reuse the same policy
  scoping (`Delegation::hasOfficer()`).
- WP-02-08 checks both the meet window and delegation approval before accepting entries.
