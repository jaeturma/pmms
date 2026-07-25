# WP-07-02 — Generic Live Scoreboard UI

## Purpose
Build the operator console and the live-updating display on top of
WP-07-01's endpoints — internal only, no sport-specific UI.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Operator console (Admin/Organizer only, linked from the existing
  `/matches` page for a `Scheduled` match): start a session (confirm/edit
  the suggested side labels from the match's entries), +/- score controls
  per side, period/status text field, pause/resume, end. Every action
  calls WP-07-01's endpoints; no client-side score state is authoritative.
- Live display page (same audience as `Matches — list`: Admin/Organizer
  all matches, Delegation Officer own delegation's matches only, Viewer
  forbidden): read-only running score, subscribes to the Reverb channel
  when available, otherwise polls the WP-07-01 read endpoint on an
  interval — the same page works either way, no separate "offline" page.
- Full-screen display mode (large-type, minimal chrome) for a
  laptop/tablet/TV/projector showing the live display page, toggled from
  the page itself.
- Empty/loading/error states: no active session for this match, session
  ended, connection lost (polling keeps retrying, no user action required).
- Tests: operator actions forbidden for Delegation Officer/Viewer,
  Delegation Officer can view their own delegation's match but not another
  delegation's, Viewer forbidden from viewing entirely, live display page
  reflects a score change made through the operator console (via the
  polling contract, not a browser-only assertion).

## Out of Scope
Sport-specific layouts; public/portal page; result auto-creation.

## Deliverables
- Updated source code
- Updated documentation
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
WP-07-03 — Live Scoring Accessibility, Testing & Acceptance
