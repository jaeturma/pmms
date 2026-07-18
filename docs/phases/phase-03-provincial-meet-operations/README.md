# Phase 3 — Provincial Meet Operations

**Status:** Planned 2026-07-18 — pending owner approval. Execution has not started.
Replaces the pre-Phase-2 draft: its WP-03-01..06 (meet setup, delegations, athlete/coach
registration, eligibility, entries) were absorbed and shipped by Phase 2.

## Goal

Run the meet itself on PMMS: venues and event scheduling, accreditation with printable
IDs, matches and heats, results encoding with human validation, medal tally computed
from validated results only, protests and incidents, official reports, and a meet-day
operations dashboard. After Phase 3 a Provincial Meet can be operated end-to-end from
registration through final medal standings. The public portal is Phase 4.

## Grounding

- `docs/00-product/product-scope.md` §3 (MVP): manual or semi-automated scheduling,
  score entry per event, human-in-the-loop result validation, medal tally from validated
  results, rosters/result sheets, basic accreditation (not QR at MVP); advanced bracket
  automation explicitly deferred.
- `DESIGN-NOTES.md` (this directory): results affect the tally only after validation;
  finalized results are never silently edited — corrections require a reason and an
  audit record; public publication is Phase 4.
- Phase 2 as-built baseline to reuse: meet lifecycle (`MeetStatus` — `Active` is the
  operations window), confirmed entries as the participant source, approved
  delegations + approved eligibility reviews as the accreditation gate,
  `SearchesAndPaginates` + SearchBar/PaginationControls, `ReportActions` + print CSS +
  audited CSV exports, `AuditLogger`, the authorization matrix pattern
  (`docs/authorization.md`), and the role model (managers decide, officers act on
  their own delegation, viewers see non-sensitive data only).

## Principles

- Simple, presentable, responsive, maintainable, secure — no enterprise complexity.
- Result integrity first: every result decision is human, attributable, and audited;
  the tally is always derivable from validated results alone.
- Reuse Phase 1–2 foundations before writing anything new.
- MySQL is the source of truth; migrations, foreign keys, indexes, normalized design.
- One work package at a time; nothing committed or pushed without owner instruction.

## Work Packages

| WP | Title |
|---|---|
| WP-03-01 | Venue Registry |
| WP-03-02 | Event Scheduling & Venue Assignment |
| WP-03-03 | Accreditation & ID Printing |
| WP-03-04 | Tournament & Match Management |
| WP-03-05 | Results Encoding & Validation |
| WP-03-06 | Medal Tally & Rankings |
| WP-03-07 | Protests & Incident Monitoring |
| WP-03-08 | Operations Reports & Printables |
| WP-03-09 | Meet Operations Dashboard |
| WP-03-10 | Operations Audit & Authorization Review |
| WP-03-11 | Phase 3 Compliance Review |

Sequence is strict: each WP assumes its predecessors. Eleven WPs, not thirteen —
registration already shipped in Phase 2, and this phase is sized to what remains.

## Visual Checkpoints

1. **After WP-03-03:** venues and the meet schedule are browsable, and accredited
   athletes/personnel have printable ID cards — visible at http://pmms.app.
2. **After WP-03-06:** results are encoded, validated, and flow into a live medal
   tally — the integrity core of the phase demonstrable end-to-end.
3. **After WP-03-09:** protests/incidents, official reports, and the operations
   dashboard complete the meet-day demo.

## Exclusions (Phase 4+ or deferred)

Public portal and any public publication, QR/RFID scanning, offline/mobile field apps,
automated bracket generation and seeding algorithms, committee operations beyond
protest handling, medical case management, finance workflows, notifications beyond
in-app flash messages, Flutter, AI, multi-tenancy, SSO.

## Completion

Phase 3 completes via WP-03-11 (full quality gate + compliance review), mirroring
WP-02-13. The review report goes to this directory; the WP log lives in
`.ai/current-phase.md`.
