# WP-09-01 — Bug & Support Workflow

## Purpose
Give the Division a real, documented way to report and track a bug or a
support request after go-live — using GitHub Issues (the repo already
lives at `github.com/jaeturma/pmms`), not a new in-app feature or a
manually-maintained log file.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- New `.github/ISSUE_TEMPLATE/` directory with two issue forms:
  - **Bug report** — something that used to work, or should work per
    `docs/manuals/`/the per-feature `docs/*.md` files, doesn't. Fields:
    what happened, what was expected, steps to reproduce, which role was
    signed in, screenshots/error text if available.
  - **Support request** — something someone needs done that the app
    doesn't self-serve today (e.g. a role promotion — a real, documented
    limitation per `docs/manuals/admin-manual.md` §2). Fields: what's
    needed, why, urgency.
  - A short `config.yml` if useful (e.g. pointing "I have a question" at
    the manuals instead of a blank issue) — keep it minimal.
- New `docs/support-workflow.md`: how an issue moves from filed to closed
  — a small fixed label set (`bug`, `support`, `needs-triage`, and a
  severity label if useful), who triages, when something gets fixed vs.
  logged as future work, when/how it closes. Plain markdown, no new
  tooling or automation.
- Cross-reference from `docs/turnover.md`'s escalation table (WP-06-08) —
  update it to point at the new workflow doc rather than leaving the
  `_[fill in]_` placeholders unconnected to how issues actually get filed.

## Out of Scope
GitHub Actions or any CI/workflow automation; an issue-routing bot; a
formal SLA (a business decision, not a documentation task this WP can
originate — matches WP-06-08's precedent for the escalation contacts
table); automated notifications when an issue is filed.

## Deliverables
- `.github/ISSUE_TEMPLATE/` (bug report + support request templates)
- `docs/support-workflow.md`
- Updated `docs/turnover.md`
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- Tests and quality checks completed (no code expected to change; gate
  must still pass green).
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
WP-09-02 — Monitoring & Health-Check Routine
