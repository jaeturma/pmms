# WP-06-04 — Performance & Load Verification

## Purpose
Verify the app performs acceptably at this deployment's actual scale — a single
Division's meet (Davao de Oro, 11 municipalities) on one local server — not a
generic multi-tenant load target. Focused query/page-performance review, not a
synthetic load-testing framework this project has no infrastructure to run
continuously.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Seed a realistic full-scale dataset (extend/reuse `SampleProvinceDemoSeeder`'s
  shape — 11 municipalities, multiple schools each, meaningful athlete/entry/
  match/result volume for a real provincial meet) to test against, distinct
  from its current small demonstration size.
- Profile the pages that aggregate across a whole meet — most likely to regress
  at scale: medal tally (`MedalTallyService::standings()`), dashboards
  (executive/operations), the six reports (WP-06-01), the schools/delegations/
  entries index pages with search+pagination.
- Check for N+1 queries on those pages (Laravel's query log or `barryvdh/laravel-
  debugbar` if already available; if not already a dependency, prefer manual
  query-log inspection over adding a new dependency for a one-time check).
- Confirm `SearchesAndPaginates`-based index pages stay responsive at full scale
  (pagination is already used everywhere per project convention — verify it's
  actually preventing full-table loads, not just present).
- Fix any real N+1 or missing-index issues found, scoped to the specific query,
  not a general refactor.
- Document findings (numbers: page load times or query counts before/after any
  fix) in `docs/performance.md` (new) or as a section in an existing relevant
  doc if a dedicated file is overkill — decide based on how much there is to
  say.

## Out of Scope
Load/concurrency testing tools (k6, JMeter, etc.) — not warranted at this
deployment's single-server, single-meet-at-a-time scale; caching infrastructure
beyond what already exists; horizontal scaling design.

## Deliverables
- Any query/index fixes found
- New/updated performance documentation
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
1. Repository findings (with concrete before/after numbers where fixes were made)
2. Files created
3. Files modified
4. Test results
5. Quality results
6. Remaining issues
7. Recommended next work package

Next:
WP-06-05 — Administrator & User Manuals
