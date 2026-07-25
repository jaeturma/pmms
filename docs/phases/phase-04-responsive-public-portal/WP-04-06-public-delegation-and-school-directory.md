# WP-04-06 — Public Delegation and School Directory

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 4 — Responsive Public Portal

## Objective
Implement **Public Delegation and School Directory** as a simple, presentable, mobile-responsive public feature for the Provincial Meet.

## Required Reading

```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
docs/phases/phase-04-responsive-public-portal/README.md
docs/phases/phase-04-responsive-public-portal/WP-04-06-public-delegation-and-school-directory.md
```

Also read relevant Phase 1–3 completion reports.

## Core Rules
- Inspect the repository before coding.
- Implement only this work package.
- Publish approved records only.
- Keep municipality as the official delegation for Provincial Meet.
- Keep school as the athlete’s origin institution.
- Use responsive React and Inertia pages.
- Apply server-side publication and privacy rules.
- Add loading, empty, error, and unavailable states.
- Use synthetic test data only.
- Run quality checks.
- Update documentation.
- Do not commit or push.
- Do not start the next work package.

## Public Privacy Rules
Do not expose birth dates, addresses, guardian details, medical records, eligibility documents, private contacts, account IDs, internal remarks, audit records, or unpublished incidents.

## Expected Deliverables
- Public routes
- Public-safe query or resource classes
- Responsive pages
- Publication filters
- Focused tests
- Completion report

## Explicit Exclusions
- No Flutter
- No AI
- No SaaS
- No Regional or National Meet operations
- No livestream, RFID, or scoreboard integration
- No public self-registration
- No internal dashboard features

## Backend Requirements
- Filter unpublished records.
- Prevent access to private records.
- Use pagination where necessary.
- Keep controllers small.
- Add privacy and publication tests.
- Audit publication-status changes where applicable.

## Frontend Requirements
- Mobile-responsive layout
- Accessible navigation
- Search and filters where applicable
- Clear status and availability messages
- No fake operational data

## Testing
Test public availability, published/unpublished filtering, privacy, search, pagination, empty states, and unauthorized private access.

Run all established backend and frontend quality checks.

## Documentation
Create or update:

```text
docs/reports/phase-04/WP-04-06-completion.md
docs/user-manual/public-portal.md
.ai/current-phase.md
.ai/project-context.md
```

## Acceptance Criteria
- Only approved public data is displayed.
- Draft and protected data are excluded.
- Medal tally is delegation-based.
- School details remain controlled.
- Pages work on desktop, tablet, and phone.
- Tests and quality checks are complete.
- Documentation is updated.
- No unrelated features are added.
- No commit or push is performed.

## Completion Report
Report repository findings, files created and modified, database changes, backend and frontend changes, publication/privacy controls, test results, remaining issues, documentation, Git status, and next work package.

Next:

```text
WP-04-07 — Public Athlete and Team Profiles
```

Do not begin it.
