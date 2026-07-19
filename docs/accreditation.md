# Accreditation & ID Printing

WP-03-03. Meet accreditation for athletes and personnel with printable ID cards.
Basic accreditation per MVP scope — no QR/barcodes, no scanning.

## Data model

`accreditations` — `delegation_id` (FK, cascade), exactly one of `athlete_id` /
`personnel_id` (each unique, FK cascade), `number` (unique, derived
`ACR-{meet}-{id}` after insert), `accredited_by` (users FK, null on delete),
`accredited_at`. **Presence of the row means accredited; revoking deletes it.**
Both decisions are audited, and a re-accredited person receives a new number —
revoked numbers are never reused.

## Gate (server-enforced in `AccreditationController::store`)

- The member's delegation must be **approved**.
- Athletes additionally need an **approved eligibility review**.
- No double accreditation (also guarded by the unique columns).

Accredit and revoke are manager-only decisions (`role:admin,organizer` route group).

## Views & authorization

Scoped like roster data via `DelegationPolicy::viewRoster` (managers + assigned
officers; never viewers):

- `/delegations/{delegation}/accreditation` — per-delegation view showing each
  athlete (grade/division, eligibility state) and personnel member (role) with
  accreditation status, who is eligible-but-not-yet-accredited (`can_accredit`),
  and accredit/revoke actions for managers. Linked from the delegations page ("IDs").
- `/accreditations/{accreditation}/card` — single printable card, audited
  `accreditation.card_viewed`.
- `/delegations/{delegation}/accreditation/cards` — batch print, audited
  `accreditation.cards_viewed`.

## ID cards

One clean card layout (print CSS, no PDF library): meet header with school year,
photo (or initials placeholder — photos served by the existing athlete/personnel
photo endpoints, which enforce the same visibility), name, role or grade/division,
school, accreditation number, and accreditation date. Cards print via the shared
`@media print` chrome-hiding CSS with `break-inside-avoid`.

## Audit

`accreditation.granted|revoked` (decisions) and
`accreditation.card_viewed|cards_viewed` (sensitive views) via `AuditLogger`, with
person, type, school, meet, and number in context.
