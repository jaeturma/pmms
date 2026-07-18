# WP-03-02 — Authentication Flow Baseline

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-03-02 | Title | Authentication Flow Baseline |
| Epic | EPIC-03 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 18 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | EPIC-12 (Flutter auth shell) | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Confirms and documents the login/logout flow contract (request/response shape) that both the React frontend (already implemented via Fortify's default pages) and the future Flutter client (WP-12-05) will rely on, formalizing what is currently implicit starter-kit behavior.

## 3. Architecture Sources

[../../../../01-architecture/identity-model.md](../../../../01-architecture/identity-model.md), ADR-0003.

## 4. Scope

Document the login/logout request/response contract; confirm CSRF and session-cookie behavior for the web flow; document what an API-token-based flow for Flutter would require (Sanctum or equivalent, evaluated but not installed).

## 5. Explicit Exclusions

Does not install Laravel Sanctum or any API-token package; does not implement the Flutter authentication shell (WP-12-05); does not change existing Fortify login controllers.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-03-01 | Hard |

## 7. Current-State Inspection

`resources/js/pages/auth/`, `routes/web.php` (Fortify-registered routes), `app/Providers/FortifyServiceProvider.php`.

## 8. Proposed Implementation Direction

Documentation of existing behavior plus a proposed API-authentication approach for Flutter (Sanctum personal-access tokens or SPA-style cookie auth, evaluated in this work package, actual package installation deferred to WP-12-05 if approved then).

## 9. Database Changes

Database Changes: None (Sanctum's token table, if adopted, is created by WP-12-05, not here).

## 10. Backend Requirements

Documentation of existing Fortify controller flow; no new backend code.

## 11. Web Frontend Requirements

Not Applicable — existing pages already implement this flow; documented, not changed.

## 12. Flutter Requirements

Documents the proposed token/session contract WP-12-05 will implement against.

## 13. Authorization and Access Control

Not Applicable — authorization model built in EPIC-05.

## 14. Security Requirements

Confirm session-cookie flags (`SESSION_ENCRYPT`, `SameSite`) are documented; confirm CSRF protection remains intact for the web flow.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not implemented here — WP-03-07's scope.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable — Flutter offline behavior is EPIC-12's later scope.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

No new tests — existing `AuthenticationTest.php` already covers this flow; this work package documents rather than re-tests.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record login/logout contract and Sanctum evaluation in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document existing login/logout request/response contract | WP-03-01 complete | Contract documented |
| TASK-02 | Evaluate Sanctum vs. alternatives for future Flutter auth, document recommendation | TASK-01 | Recommendation documented, no package installed |
| TASK-03 | Record in `.ai/architecture.md` | TASK-01..02 | Section added |

## 24. Acceptance Criteria

- **AC-01:** Given the existing login flow, when documented, then the request/response contract matches actual Fortify behavior verified in WP-01-04.
- **AC-02:** Given the Sanctum evaluation, when documented, then a specific recommendation is stated without installing the package.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-03-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Contract and recommendation documented.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented contract and recommendation.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-03-02-01 | Sanctum recommendation proves wrong once Flutter's actual needs are clearer in EPIC-12 | Low | Recommendation explicitly re-evaluated at WP-12-05 | Security reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-03-02-01 | Sanctum token auth vs. alternative for Flutter | Non-blocking for Phase 1 web scope; blocks WP-12-05 |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-03-02 — Authentication Flow Baseline.

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
- Do not install Sanctum or any API-token package.
- Do not modify existing Fortify controllers.
```
