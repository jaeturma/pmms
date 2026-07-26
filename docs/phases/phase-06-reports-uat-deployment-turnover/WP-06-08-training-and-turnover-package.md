# WP-06-08 — Training & Turnover Package

## Purpose
Produce the handover material whoever maintains PMMS after this development
effort ends will actually need — building on WP-06-05's manuals and WP-06-07's
deployment documentation, not duplicating them.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- New `docs/turnover.md`: a single handover reference covering what a future
  maintainer (Division IT staff or a future developer) needs that isn't already
  in `docs/deployment.md` (WP-06-07) or the per-role manuals (WP-06-05):
  - System overview and architecture map (one-paragraph summary + pointer to
    `docs/01-architecture/` and the per-feature `docs/*.md` files as the
    detailed reference — not a re-explanation).
  - Where things live: codebase location, database name/connection, backup
    location (cross-reference WP-06-02), how to view logs.
  - Known limitations and deferred/open items, collected from across the
    project rather than left scattered: City division type's "district
    competes" option (`docs/division.md`), no `.xlsx` export (by decision),
    Reverb's production on/off status (WP-06-07's decision), any findings left
    open from WP-06-03's security review or WP-06-04's performance pass.
  - Escalation/support expectations — who to contact for what kind of issue
    (this is a template the owner fills in with real names/contacts, not
    something to fabricate).
  - A basic maintenance checklist: routine tasks (verify backups are running,
    check `composer audit`/`npm audit` periodically, review the audit log).
- A short training outline (agenda + topics, not a full slide deck) that a
  trainer could use to walk Division staff through the system, referencing the
  manuals (WP-06-05) as the material to actually present from.

## Out of Scope
Recorded video training; an actual training session (real-world activity, not
something executed in a coding session); a formal SLA/support contract (a
business decision, not a documentation task this WP can originate).

## Deliverables
- New `docs/turnover.md`
- New training outline document
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- Tests and quality checks completed (no code expected to change; gate must
  still pass green).
- Documentation updated.
- No secrets exposed.
- No commit or push performed.

## Completion Report
Include:
1. Repository findings
2. Files created
3. Files modified
4. Test results
5. Quality results
6. Remaining issues
7. Recommended next work package

Next:
WP-06-09 — Phase 6 Compliance Review & Acceptance
