# WP-03-06 — Medal Tally & Rankings

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
- Medal tally computed **only from validated results** (rank 1/2/3 → gold/silver/
  bronze), derived at read time — no stored tally table to drift out of sync; a
  validated correction changes the tally automatically.
- Per-school and per-district standings with the conventional ordering (gold, then
  silver, then bronze, then school name); per-sport breakdown filter.
- Tally page readable by every authenticated role (aggregates, non-sensitive), built
  on shared components; ties in ranks produce shared medals per the tie flag from
  WP-03-05.
- Tests: tally math from validated-only results, correction ripple, ordering and
  ties, visibility.

## Out of Scope
Overall championship points formulas beyond medal counts, public portal (Phase 4),
printable tally report (WP-03-08).

## Deliverables
- Updated source code
- Updated documentation (docs/medal-tally.md)
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
WP-03-07 — Protests & Incident Monitoring
