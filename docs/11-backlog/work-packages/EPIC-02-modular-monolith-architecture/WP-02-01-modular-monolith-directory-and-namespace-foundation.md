# WP-02-01 — Modular Monolith Directory and Namespace Foundation

## 1. Work Package Identity

| Field | Value |
|---|---|
| Work Package ID | WP-02-01 |
| Title | Modular Monolith Directory and Namespace Foundation |
| Epic | EPIC-02 — Modular Monolith and Application Architecture Foundation |
| Phase | Phase 1 |
| Status | Planned — Not Started |
| Complexity | Medium |
| Priority | P1 |
| Implementation sequence | 9 |
| Target release group | Foundation Release A |
| Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation |
| Primary bounded context | Cross-cutting (application skeleton) |
| Secondary affected contexts | All |
| Product owner | To be identified |
| Technical owner | To be identified |
| Required reviewers | Lead architect |

## 2. Purpose

Establishes the directory and PHP namespace convention bounded contexts will live under (e.g., `app/Domain/<Context>/...`, proposed), replacing the starter kit's flat `app/Models`/`app/Http` layout for domain code while leaving Laravel's own conventional directories (`app/Providers`, `app/Console`) untouched. Every later domain work package (EPIC-03 onward) places its code inside this convention.

## 3. Architecture Sources

[../../../../01-architecture/phase-0.2-domain-architecture.md](../../../../01-architecture/phase-0.2-domain-architecture.md), ADR-0002.

## 4. Scope

Propose and document a namespace convention distinguishing Domain (entities, value objects, domain events), Application (commands, queries, handlers), and Infrastructure (Eloquent models, repositories) layers per bounded context; document how this coexists with Laravel's existing `app/Models`, `app/Http/Controllers` conventions from the starter kit.

## 5. Explicit Exclusions

Does not create any bounded-context subdirectory for a specific context (Identity, Organization, etc. — those are created by their owning epic's first work package); does not move or delete any existing starter-kit file.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |

## 7. Current-State Inspection

Existing layout: `app/Models/User.php`, `app/Http/Controllers/`, `app/Actions/Fortify/`, `app/Providers/` — flat, non-modular, consistent with an unmodified starter kit. `composer.json` autoload: `App\` → `app/`.

## 8. Proposed Implementation Direction

- **Layer:** Cross-cutting convention, not a bounded context itself.
- **Proposed namespace:** `App\Domain\<Context>\...`, `App\Application\<Context>\...`, `App\Infrastructure\<Context>\...` (all proposed).
- **Simplification:** Existing Fortify/`User` code stays where it is until WP-03-01 explicitly migrates it — this work package does not force an immediate mass move.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Convention only — no runtime behavior change; `composer.json` PSR-4 autoload map may need a proposed additional entry, documented but not applied here.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Not Applicable — convention document only, no code to test yet.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

New `docs/11-backlog/../01-architecture/` cross-reference is not required; record the convention in `.ai/architecture.md` as a Phase 1 addendum and in this work package's own document.

## 23. Tasks

| Task ID | Description | Expected Files/Areas | Preconditions | Verification | Notes |
|---|---|---|---|---|---|
| TASK-01 | Draft namespace convention (Domain/Application/Infrastructure per context) | Documentation only | WP-01-01 complete | Convention documented | Proposed names only |
| TASK-02 | Confirm compatibility with existing `composer.json` PSR-4 map | `composer.json` (read-only) | TASK-01 | No conflict found | — |
| TASK-03 | Record convention in `.ai/architecture.md` addendum | `.ai/architecture.md` | TASK-01..02 | Section added | — |

## 24. Acceptance Criteria

- **AC-01:** Given the proposed convention, when compared to existing `app/Models`/`app/Http` code, then no conflict or forced immediate migration is required.
- **AC-02:** Given `.ai/architecture.md`, when updated, then the namespace convention is documented and cross-referenced from this work package.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Convention documented and does not conflict with existing autoload configuration.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented convention, before/after `.ai/architecture.md` diff.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation-only; no code moved yet.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Detection | Escalation | Residual Concern |
|---|---|---|---|---|---|---|
| RISK-WP-02-01-01 | Convention proves unworkable once EPIC-04 implements the first real context | Medium | WP-02-06 fitness checks catch drift early | First bounded-context implementation | Lead architect | Open until EPIC-04 |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-02-01 — Modular Monolith Directory and Namespace Foundation.

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
- Document the convention only; do not move existing starter-kit files.
- Mark every proposed namespace as proposed, not final.
```
