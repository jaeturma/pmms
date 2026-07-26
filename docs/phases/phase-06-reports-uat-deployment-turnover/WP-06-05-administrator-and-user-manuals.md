# WP-06-05 — Administrator & User Manuals

## Purpose
Produce role-based manuals for the people who will actually operate PMMS Division
Edition day-to-day — written from the real, shipped UI and workflows, not
aspirational features.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Walk every role's actual routes/pages (`App\Enums\UserRole`: Admin, Organizer,
  Delegation Officer, Viewer) plus the public guest experience, and write one
  manual per audience under a new `docs/manuals/` directory:
  - `admin-manual.md` — user/role management, division settings, audit log,
    backup awareness (cross-reference WP-06-02), sports catalog, schools/
    districts registry.
  - `organizer-manual.md` — meet lifecycle, venues, scheduling, accreditation,
    entries/eligibility review, matches, results encoding/validation, protests/
    incidents, live scoring operation, medal tally, reports, announcements,
    publishing to the public portal.
  - `delegation-officer-manual.md` — registering a delegation, athlete/personnel
    registration (including home-school attribution under Province), entries,
    viewing own delegation's matches/results/roster.
  - `viewer-manual.md` — what a Viewer role can see (aggregates, no minor data)
    and what it cannot.
  - `public-portal-guide.md` — the guest-facing experience: schedule, venues,
    results, medal tally, announcements, live scoreboard (with the
    provisional-not-official distinction called out).
- Each manual: task-oriented (numbered steps for real flows: "register a
  delegation," "encode and validate a result," "start a live scoring session"),
  screenshots optional but not required (text + exact navigation labels is
  sufficient and easier to keep current), and a short "what changed recently"
  note only where genuinely relevant (e.g., Province vs. City delegation
  registration, live scoreboard being provisional).
- Cross-reference existing per-feature docs (`docs/*.md`) rather than duplicating
  their technical detail — manuals are task-oriented for end users, the
  `docs/*.md` files remain the technical reference.

## Out of Scope
Video tutorials or recorded training; translation into other languages; in-app
help/tooltips (a product feature, not documentation).

## Deliverables
- New `docs/manuals/` directory with five manuals
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first — every manual step verified against the actual
  running app, not written from memory of what a similar system usually has.
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
WP-06-06 — UAT Preparation Materials
