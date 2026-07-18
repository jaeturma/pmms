# WP-02-11 — Audit & Authorization Integration Review

## Purpose
Complete this work package for the PMMS Division Edition. Verify — and close gaps in —
the authorization and audit coverage of every Phase 2 module before the phase demo.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Authorization matrix: for each Phase 2 route/action × role, verify the enforced
  behavior matches the documented intent (docs/authorization.md); fix gaps.
- Audit coverage sweep: every state change and every sensitive read (athlete detail,
  eligibility document) produces an AuditLog entry with actor and context; fix gaps.
- Simple audit viewer for admins (filterable list over audit_logs on shared components)
  if not already present.
- Negative tests: each role attempting each forbidden action receives 403 and the
  permission-denied UI state.
- Document the final matrix.

## Out of Scope
New business features, security-event modeling, retention policies, log shipping.

## Deliverables
- Authorization matrix document
- Gap fixes and tests
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- Tests and quality checks completed.
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
WP-02-12 — Rosters & Printable Lists
