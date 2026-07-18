# WP-10-03 — Inertia Shared Props and Context Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-03 | Title | Inertia Shared Props and Context Foundation |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 91 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Extends the starter kit's existing `HandleInertiaRequests` middleware (which already shares `auth.user` and appearance settings) with a consistent, typed shared-props contract every PMMS page can rely on, building on WP-04-06's context prop.

## 3. Architecture Sources

[../../../../01-architecture/api-and-client-boundaries.md](../../../../01-architecture/api-and-client-boundaries.md).

## 4. Scope

Formalize the shared-props TypeScript interface (`resources/js/types/`) covering `auth`, `context` (WP-04-06), `flash` (WP-10-06), and any other cross-cutting prop; ensure every prop is intentional, not accidental.

## 5. Explicit Exclusions

Does not implement the permission/capability contract (WP-10-05, a distinct, narrower concern).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-04-06, WP-05-07 | Hard |

## 7. Current-State Inspection

`app/Http/Middleware/HandleInertiaRequests.php`'s existing `share()` method (shares `auth.user`, appearance) as the starting point; `resources/js/types/index.ts`/`auth.ts` as existing type definitions to extend, not replace.

## 8. Proposed Implementation Direction

Extend the existing `HandleInertiaRequests::share()` and corresponding TypeScript types, rather than replacing them — preserving the starter kit's existing auth/appearance sharing.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Extension of the existing middleware.

## 11. Web Frontend Requirements

Extended TypeScript shared-props interface, used via `usePage<SharedProps>()`.

## 12. Flutter Requirements

Not Applicable — Flutter consumes the API contract (WP-10-01), not Inertia props.

## 13. Authorization and Access Control

Not Applicable directly — WP-10-05 handles capability-specific props.

## 14. Security Requirements

Every shared prop must be reviewed for necessity — no prop is added "just in case" (minimization discipline, extended fully by WP-10-04).

## 15. Privacy and Data-Governance Requirements

Not Applicable directly beyond WP-10-04's later formalization.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature test confirming the shared-props shape is consistent across multiple representative page loads.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the formalized shared-props contract in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Extend `HandleInertiaRequests::share()` and TypeScript types | WP-04-06, WP-05-07 complete | Feature test passes |

## 24. Acceptance Criteria

- **AC-01:** Given any authenticated page load, when inspected, then the shared props match the documented, typed contract exactly.
- **AC-02:** Given the existing starter-kit auth/appearance sharing, when this work package completes, then it remains functionally unchanged (extended, not replaced).

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-04-06, WP-05-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): example shared-props payload (redacted), test output.

## 28. Rollback and Recovery Considerations

Extension is additive; existing auth/appearance sharing preserved.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-03-01 | Extending the middleware accidentally breaks existing 2FA/appearance-tab functionality | Medium | AC-02 explicitly requires re-verifying existing behavior | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-03 — Inertia Shared Props and Context Foundation.

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
- Extend the existing HandleInertiaRequests middleware — do not replace it.
- Every shared prop must be reviewed for necessity, not added speculatively.
```
