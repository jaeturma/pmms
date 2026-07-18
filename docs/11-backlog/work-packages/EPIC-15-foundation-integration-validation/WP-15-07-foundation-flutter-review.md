# WP-15-07 — Foundation Flutter Review

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-15-07 | Title | Foundation Flutter Review |
| Epic | EPIC-15 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 150 |
| Target release group | Foundation Release F | Pilot relevance | Required |
| Architecture readiness classification | Verification Only | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead, Security reviewer |

## 2. Purpose

Verifies the Flutter foundation (EPIC-12) as an integrated mobile client against the real backend: architecture conventions held, secure storage genuinely secure, authentication shell correct against live auth endpoints, connectivity/offline skeletons behaving as specified, theme faithful to the design tokens, and logging redaction effective — the mobile counterpart of WP-15-01/03/04's web-side reviews, consolidated because the mobile surface is smaller.

## 3. Architecture Sources

[../../../01-architecture/flutter-architecture.md](../../../01-architecture/flutter-architecture.md), ADR-0009; reviews WP-12-01..12 output.

## 4. Scope

Convention sweep (feature folders, dependency directions per WP-12-02); secure-storage verification on at least one real device/emulator per platform (token not recoverable from backup/plaintext inspection within the platform's threat model, documented); authentication shell exercised against the real WP-03-02 endpoints (login, logout, session expiry handling, revoked-account behavior per WP-03-04); connectivity-state drill (airplane-mode transitions, behavior matches WP-12-08 spec); offline-store and sync-queue skeleton surfaces re-verified against their spike outcomes; theme spot check against WP-11-01 token values; WP-12-11 redaction re-verified with real API traffic; WP-12-12 suite re-run; review record for WP-15-12.

## 5. Explicit Exclusions

Does not fix mobile defects (EPIC-12 owns them); does not review operational mobile modules (none exist — skeleton only); does not perform app-store readiness or device-matrix testing (future); does not review mobile real-time (out of Phase 1 scope).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-12-01 through WP-12-12 at Implementation Complete | Hard |
| WP-03-02, WP-03-04 (real endpoints exercised) | Hard |

## 7. Current-State Inspection

Reviews the built app on real devices/emulators against the real backend — not simulator-only, not mocked APIs.

## 8. Proposed Implementation Direction

Structured review protocol per scope item; per-item verdict table; findings register in evidence format.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

A running backend instance for integration exercises — no backend changes.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Review execution across the mobile foundation.

## 13. Authorization and Access Control

Assignment-aware home skeleton verified to treat capability data as display-only; expired/revoked sessions verified to lock the client out.

## 14. Security Requirements

Secure-storage platform verification and redaction-with-real-traffic checks are the security core; findings here are high-severity by default.

## 15. Privacy and Data-Governance Requirements

Verify no personal data persists on-device beyond the documented secure-storage contents.

## 16. Audit and Activity Events

Server-side auth events from mobile logins spot-verified (WP-03-07 wiring covers mobile-originated flows).

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable — out of mobile Phase 1 scope.

## 18. Offline and Synchronization Requirements

Skeleton-surface verification only, matching what WP-12-09/WP-12-10 actually built.

## 19. Observability Requirements

Mobile log content sampled for redaction effectiveness.

## 20. Testing Requirements

WP-12-12 suite re-run; protocol records; per-platform secure-storage notes.

## 21. Test Data Requirements

Synthetic accounts against a disposable backend environment.

## 22. Documentation Updates

Review record to the sign-off evidence set.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Convention sweep and theme spot check | EPIC-12 complete | Records complete |
| TASK-02 | Secure-storage verification per platform | TASK-01 | Platform notes recorded |
| TASK-03 | Auth shell integration exercises (live endpoints) | TASK-01 | Login/expiry/revocation verified |
| TASK-04 | Connectivity drill and skeleton re-verification | TASK-01 | Records complete |
| TASK-05 | Redaction check with real traffic; re-run WP-12-12; produce record | TASK-02..04 | Record in evidence format |

## 24. Acceptance Criteria

- **AC-01:** Given a real device/emulator per platform, when secure storage is inspected within the documented threat model, then stored tokens are not recoverable in plaintext.
- **AC-02:** Given live backend endpoints, when login, expiry, and revocation flows are exercised, then the client behaves per specification, including denying access on revocation.
- **AC-03:** Given real API traffic, when mobile logs are sampled, then no token or sensitive field appears unredacted.
- **AC-04:** Given the WP-12-12 suite, when re-run, then it passes in the integrated state.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). EPIC-12 complete; disposable backend environment available.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): protocol records, platform notes, suite output, findings register.

## 28. Rollback and Recovery Considerations

Not Applicable — review only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-15-07-01 | Emulator-only verification misses device-specific secure-storage behavior | Medium | At least one physical device per platform where available; gaps documented explicitly in the record | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-15-07 — Foundation Flutter Review.

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
- Verification only — mobile defects route to EPIC-12 work packages.
- Exercise real endpoints on real devices/emulators — no mocked-API verification.
- Document platform coverage gaps honestly.
```
