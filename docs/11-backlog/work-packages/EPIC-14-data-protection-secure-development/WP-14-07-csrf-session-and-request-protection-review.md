# WP-14-07 — CSRF, Session, and Request Protection Review

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-07 | Title | CSRF, Session, and Request Protection Review |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 140 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Formally reviews the request-forgery and session-protection posture after EPIC-03's session work: verifies CSRF coverage is complete (no exempted routes without documented justification), session fixation protections hold, and request-level protections (validated input boundaries, mass-assignment guards) match the secure-development rules — a review-and-close work package, not a build.

## 3. Architecture Sources

[../../../03-security/identity-authentication-and-session-security.md](../../../03-security/identity-authentication-and-session-security.md), [../../../03-security/secure-development-rules-source](../../../03-security/), `.ai/secure-development-rules.md`, ADR-0006; reviews the output of WP-03-03 (session security).

## 4. Scope

Review and verify: CSRF middleware active on all state-changing web routes, with any `$except` entries justified and documented (expected: none); session regeneration on login/privilege change (WP-03-03 behavior re-verified); session invalidation on logout and password change; mass-assignment protection posture (`$fillable` discipline plus `Model::preventSilentlyDiscardingAttributes()` or equivalent strictness verified); request-validation conventions (every state-changing controller action backed by a FormRequest or explicit validation) checked across foundation routes; findings fixed or ticketed with justification.

## 5. Explicit Exclusions

Does not redesign session architecture (WP-03-03 owns it — defects found here route back as defects); does not implement API-token auth review (no external API in Phase 1); does not review the Flutter client (WP-12-05's shell follows its own review in WP-15-07).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-03-03 | Hard (reviews its output) |

## 7. Current-State Inspection

Fortify + starter kit provide baseline CSRF/session behavior; EPIC-03 will have hardened sessions; no formal verification pass has been recorded.

## 8. Proposed Implementation Direction

A structured review checklist executed against the actual codebase, closing each item with a test or a documented finding; new regression tests where gaps are found.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Review execution, gap fixes (small, in-scope hardening only), regression tests.

## 11. Web Frontend Requirements

Verify Inertia requests carry CSRF tokens correctly through the standard mechanisms (XSRF cookie/header) — inspected, not assumed.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable — authorization review is WP-15-03's scope; this is request-integrity review.

## 14. Security Requirements

This work package is itself a security control; every checklist item ends verified, fixed, or documented-with-justification — no silent passes.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Regression tests: CSRF rejection on a state-changing route without token; session ID changes across login; old session unusable after logout/password change; mass-assignment strictness test; a route-sweep test asserting no unexempted state-changing route lacks CSRF protection.

## 21. Test Data Requirements

Synthetic users via existing factories.

## 22. Documentation Updates

Record the completed checklist and posture in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Execute CSRF/session/request review checklist | WP-03-03 complete | Checklist closed item-by-item |
| TASK-02 | Fix in-scope gaps; ticket out-of-scope findings | TASK-01 | Fixes tested; findings documented |
| TASK-03 | Add regression tests for the verified posture | TASK-01 | All regressions pass |

## 24. Acceptance Criteria

- **AC-01:** Given the CSRF exemption list, when reviewed, then it is empty or every entry carries a documented justification.
- **AC-02:** Given login, logout, and password change, when exercised, then session regeneration and invalidation behave as required, verified by tests.
- **AC-03:** Given the review checklist, when complete, then every item is verified, fixed, or documented — none skipped.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-03-03 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): completed checklist, regression test output.

## 28. Rollback and Recovery Considerations

Review + small hardening fixes; each fix individually revertible.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-07-01 | Review rubber-stamps EPIC-03 output instead of independently verifying | Medium | Checklist requires test evidence per item, not assertions; reviewer differs from WP-03-03 implementer where possible | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-07 — CSRF, Session, and Request Protection Review.

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
- Verify by test evidence, not by reading code alone.
- Session architecture defects route back to WP-03-03 as defects — do not redesign here.
- Every checklist item ends verified, fixed, or documented — no silent passes.
```
