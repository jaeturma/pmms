# WP-01-01 — Repository and Framework Baseline Verification

## 1. Work Package Identity

| Field | Value |
|---|---|
| Work Package ID | WP-01-01 |
| Title | Repository and Framework Baseline Verification |
| Epic | EPIC-01 — Engineering Foundation and Repository Baseline |
| Phase | Phase 1 — Core Foundation Implementation |
| Status | Planned — Not Started |
| Complexity | Small |
| Priority | P1 |
| Implementation sequence | 1 (first work package in Phase 1) |
| Target release group | Foundation Release A |
| Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation |
| Primary bounded context | None (cross-cutting engineering baseline) |
| Secondary affected contexts | All |
| Product owner | To be identified |
| Technical owner | To be identified |
| Required reviewers | Engineering lead |

## 2. Purpose

Establishes a single, verified, documented record of the repository's actual current framework and tooling versions before any domain code is written. Every other Phase 1 work package's Section 7 (Current-State Inspection) assumes this baseline exists and is current; without it, every subsequent work package would have to re-derive the same facts independently. Prevents implementation sessions from assuming versions or packages that are not actually present.

## 3. Architecture Sources

- [../../../../.ai/current-phase.md](../../../../.ai/current-phase.md)
- [../../../10-review/architecture-completeness-assessment.md](../../../10-review/architecture-completeness-assessment.md)
- Approved technology direction (Laravel 13, React 19, Inertia, TypeScript, Tailwind 4, shadcn/ui, Flutter, MySQL, Redis, MinIO, Reverb, Horizon, PestPHP), as stated in the Phase 0.14 originating prompt.

## 4. Scope

- Verify installed PHP version and required extensions against `composer.json`'s `require.php` constraint.
- Verify installed Laravel Framework version via `php artisan --version`.
- Verify installed Node.js and package-manager versions against `package.json` engine expectations (if declared) and actual `node_modules` state.
- Verify Inertia, React, TypeScript, Tailwind, and Vite versions from `package.json`.
- Verify presence and contents of `resources/js/components/ui` (existing shadcn/ui-style primitives) as a starting inventory.
- Record the verified baseline in a new `.ai/engineering-baseline.md` (or equivalent) reference document.

## 5. Explicit Exclusions

- Does not install, upgrade, or downgrade any package.
- Does not modify `composer.json`, `composer.lock`, `package.json`, or any lock file.
- Does not verify backend quality tooling (Larastan, Pint) — that is WP-01-02.
- Does not verify frontend quality tooling (ESLint, Prettier, tsc) — that is WP-01-03.
- Does not verify the Flutter workspace — that is WP-01-05.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| None (entry point) | — |

## 7. Current-State Inspection

Inspect: `composer.json`, `composer.lock`, `package.json`, root `.gitignore`, `php artisan --version` output, `node --version` / `npm --version` output, `resources/js/components/ui/` contents, `git log --oneline` and `git status`. At the time this work package was authored, the repository was the unmodified Laravel 13 React starter kit (Laravel Framework 13.19.0, PHP `^8.3`, Inertia v3, React `^19.2.0`, Tailwind `^4.0.0`, Vite `^8.0.0`, a partial shadcn/ui-style component set under `resources/js/components/ui`, no `mobile/` directory, no `.github/` directory) — this must be re-verified, not assumed, at execution time, since the repository will have changed.

## 8. Proposed Implementation Direction

- **Architectural layer:** Documentation/tooling only — no application code.
- **Proposed location:** `.ai/engineering-baseline.md` (proposed name).
- **Core objects:** None (documentation artifact only).
- **Simplification decision:** Record only what is actually installed; do not speculate about future versions.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable (verification-only; no backend code written).

## 11. Web Frontend Requirements

Not Applicable (verification-only; no frontend code written).

## 12. Flutter Requirements

Not Applicable — Flutter workspace verification is WP-01-05.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Verification commands must not print secret values (`.env` contents) to any committed output or log.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

The resulting baseline document itself serves as the observability artifact for "what is actually installed" going forward.

## 20. Testing Requirements

No automated tests are produced by this work package; verification is manual command execution with recorded output (Completion Evidence, Section 27).

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

- Create `.ai/engineering-baseline.md` (proposed) recording verified versions.
- Update `.ai/current-phase.md` to note Phase 1 has begun with WP-01-01.

## 23. Tasks

| Task ID | Description | Expected Files/Areas | Preconditions | Verification | Notes |
|---|---|---|---|---|---|
| TASK-01 | Run `php artisan --version` and record output | None (read-only) | PHP/Composer installed | Output captured in evidence | — |
| TASK-02 | Compare `composer.json`/`composer.lock` PHP and package versions against installed versions | `composer.json`, `composer.lock` | None | Versions match or discrepancy noted | — |
| TASK-03 | Run `node --version` and `npm --version`, compare against `package.json` | `package.json` | Node installed | Output captured in evidence | — |
| TASK-04 | Inventory `resources/js/components/ui/` | `resources/js/components/ui/` | None | File list captured in evidence | — |
| TASK-05 | Write `.ai/engineering-baseline.md` with all verified facts | `.ai/engineering-baseline.md` | TASK-01..04 complete | File exists and is accurate | Proposed filename |

## 24. Acceptance Criteria

- **AC-01:** Given the repository checked out, when `php artisan --version` is run, then the output is captured verbatim in `.ai/engineering-baseline.md`.
- **AC-02:** Given `composer.json`'s `require.php` constraint, when the installed PHP version is checked, then the document states whether the installed version satisfies the constraint.
- **AC-03:** Given `package.json`, when installed Node/npm versions are checked, then the document states whether they are compatible with the declared dependencies.
- **AC-04:** Given `resources/js/components/ui/`, when its contents are inventoried, then the document lists every file present at verification time.
- **AC-05:** The resulting `.ai/engineering-baseline.md` contains no secret values (API keys, database credentials) from `.env`.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). No work-package-specific additions — this is the entry point of Phase 1.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Work-package-specific addition: `.ai/engineering-baseline.md` exists and every fact in it is independently reproducible by re-running the same commands.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md). Specifically: verbatim command output for TASK-01 through TASK-04, and the final content of `.ai/engineering-baseline.md`. No actual results are included in this Phase 0.14 document — this section defines what is required once executed.

## 28. Rollback and Recovery Considerations

Not Applicable — this work package produces only a new documentation file; rollback is deleting that file.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Detection | Escalation | Residual Concern |
|---|---|---|---|---|---|---|
| RISK-WP-01-01-01 | Recorded baseline goes stale as soon as any package is later updated | Low | Every later work package's Section 7 instructs re-verification, not blind trust in this document | Version drift noticed during a later work package's own inspection | Engineering lead | Low — self-correcting by design |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-01-01 — Repository and Framework Baseline Verification.

Read the complete work-package document first.

Inspect the current repository before making changes.

Implement only the approved scope.

Do not implement excluded or deferred features.

Follow all linked architecture, security, privacy, testing, design, workflow, and operational rules.

Run the required tests and quality checks.

Update the required documentation and AI workspace files.

Do not commit unless explicitly instructed.

At completion, provide:
1. Repository findings
2. Files created
3. Files modified
4. Implementation summary
5. Database changes
6. Backend changes
7. Frontend changes
8. Flutter changes
9. Authorization and audit changes
10. Tests and quality checks
11. Risks and limitations
12. Git status

Additional restrictions specific to this work package:
- Do not install, upgrade, or downgrade any package or dependency.
- Do not print or record any secret value from .env.
- Confirm no other work package's scope was touched.
```
