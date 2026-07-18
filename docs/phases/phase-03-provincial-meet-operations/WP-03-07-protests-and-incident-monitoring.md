# WP-03-07 — Protests & Incident Monitoring

## Purpose
Complete this work package for the PMMS Division Edition. Keep implementation practical
for a Schools Division Office. Build only what is required for a maintainable
production-quality system.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Protests: filed by a delegation officer (their delegation) or a manager against an
  event result or match, with grounds text; flow `filed → under_review → upheld |
  dismissed`; decisions manager-only, remarks required, fully audited (`protest.*`).
- An upheld protest does not itself change a result — it links to the WP-03-05
  correction workflow (reason pre-filled from the protest), keeping one single
  result-change path.
- Incidents: simple meet-day log (venue, description, severity, open/resolved) kept
  by managers, audited; no medical case data — a medical incident records only that
  referral happened, never details.
- List views with status filters on shared components; officers see their own
  protests, managers all; viewers see neither.
- Tests: filing scope, decision flow + remarks requirement, protest→correction link,
  incident lifecycle, visibility.

## Out of Scope
Appeals beyond the single protest flow, committee assignment/workflow, medical case
management, finance (protest fees).

## Deliverables
- Updated source code
- Updated documentation (docs/protests.md)
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
WP-03-08 — Operations Reports & Printables
