# WP-14-05 — Rate Limiting Baseline

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-05 | Title | Rate Limiting Baseline |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 138 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Establishes named, documented rate limiters for the foundation's abuse-sensitive surfaces — authentication attempts, password reset, health endpoints, general API — with Redis-backed counters, so later modules attach to defined limiters instead of leaving new endpoints unlimited by default.

## 3. Architecture Sources

[../../../03-security/application-api-and-client-security.md](../../../03-security/application-api-and-client-security.md), [../../../09-enterprise/redis-cache-session-lock-and-rate-limit-scaling.md](../../../09-enterprise/redis-cache-session-lock-and-rate-limit-scaling.md), ADR-0006.

## 4. Scope

Named limiters (proposed: `auth`, `password-reset`, `health`, `api-general`) with documented provisional thresholds; Redis store for counters; standard 429 response conforming to the WP-10-01 error contract (including a retry-after signal); verification that Fortify's existing login throttling and these limiters compose rather than conflict; guidance requiring every new route group to name its limiter.

## 5. Explicit Exclusions

Does not implement per-tenant quotas or fair-use governance (Phase 0.12 enterprise scope, deferred); does not implement backpressure/load-shedding (ED-deferred); does not set final production thresholds (provisional, load-untested values documented as such); does not add CAPTCHA or device-fingerprinting.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |
| WP-09-01 | Soft (Redis verification — limiters fall back to cache store availability) |

## 7. Current-State Inspection

Fortify ships login throttling; no other limiter is configured; default store follows the cache configuration.

## 8. Proposed Implementation Direction

`RateLimiter::for()` definitions in a dedicated service provider (proposed); Redis store via cache configuration; keys combine user ID (authenticated) or IP (guest) per limiter's documented keying rule.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Named limiter definitions, keying rules, 429 contract conformance, Fortify-composition verification.

## 11. Web Frontend Requirements

Not Applicable in this work package — WP-10-09's error states already cover 429 presentation via the error contract.

## 12. Flutter Requirements

Not Applicable — mobile clients receive the same 429 contract.

## 13. Authorization and Access Control

Not Applicable — rate limiting is abuse control, orthogonal to authorization.

## 14. Security Requirements

Keying must not allow trivial bypass (e.g., authenticated limiter keyed only by IP behind shared NAT — school networks are the norm for PMMS, so authenticated keys use user ID); 429 responses reveal no limiter internals beyond retry-after.

## 15. Privacy and Data-Governance Requirements

Limiter keys (IP/user ID) in Redis are transient operational data — TTL-bounded, never persisted to MySQL.

## 16. Audit and Activity Events

Sustained limit-hitting on `auth`/`password-reset` is a security-event candidate per WP-06-03 conventions (recorded as readiness guidance; wiring is a one-line follow-on where WP-03-07 already covers it).

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable — mobile sync endpoints (future) will name their own limiter per the guidance.

## 19. Observability Requirements

429 occurrences log through the structured channel with the limiter name and correlation ID.

## 20. Testing Requirements

Tests per limiter: under-limit passes, over-limit yields contract-conformant 429 with retry-after; keying test (two users behind one IP limited independently on authenticated limiters); Fortify-composition test (login throttling still works).

## 21. Test Data Requirements

Synthetic users via existing factories.

## 22. Documentation Updates

Record limiter names, provisional thresholds, keying rules, and the "every new route group names its limiter" rule in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Define named limiters with keying rules | WP-01-01 complete | Limiter tests pass |
| TASK-02 | Conform 429 responses to the error contract | TASK-01 | Contract test passes |
| TASK-03 | Verify Fortify composition | TASK-01 | Login throttling test passes |

## 24. Acceptance Criteria

- **AC-01:** Given each named limiter, when its threshold is exceeded, then a contract-conformant 429 with retry-after is returned.
- **AC-02:** Given two authenticated users behind one IP, when one exceeds an authenticated limiter, then the other is unaffected.
- **AC-03:** Given the existing Fortify login throttling, when this baseline is active, then login throttling behavior is preserved.
- **AC-04:** Given the guidance, when reviewed, then every foundation route group names its limiter or documents why it has none.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-01-01 complete; Redis available per WP-09-01 or fallback documented.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): limiter test output, 429 response capture, route-group limiter listing.

## 28. Rollback and Recovery Considerations

Limiter definitions are additive configuration; removal restores prior behavior. Redis counter keys expire naturally.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-05-01 | Provisional thresholds too low for legitimate meet-day burst traffic (mass check-ins) | Medium | Thresholds documented as provisional and load-untested; WP-15-08 performance baseline revisits | Engineering lead |
| RISK-WP-14-05-02 | Shared school NAT trips IP-keyed guest limiters for whole venues | Medium | Documented keying rules make guest-vs-authenticated distinction explicit; venue scenarios noted for threshold review | Security reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-14-05-01 | Final production thresholds per limiter | Non-blocking — provisional values documented |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-05 — Rate Limiting Baseline.

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
- Do not break or duplicate Fortify's existing login throttling — compose with it.
- Thresholds are provisional — document them as such.
- No per-tenant quotas, backpressure, or load-shedding — explicitly deferred enterprise scope.
```
