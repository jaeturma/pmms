# PMMS Security, Privacy, and Audit Testing Strategy

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/testing-architecture.md](../01-architecture/testing-architecture.md) · [secure-development-lifecycle.md](secure-development-lifecycle.md) · [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md)

This document defines security, privacy, and audit testing strategy, extending [../01-architecture/testing-architecture.md](../01-architecture/testing-architecture.md)'s 8 test layers with security-specific scenarios. **No test code, PestPHP suite, or CI pipeline is created here.**

---

## 1. Security Testing Strategy

| Test Area | Scope |
|---|---|
| Unit tests | Individual authorization-rule and validation-logic units |
| Authorization tests | Every permission in [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md) has a positive (granted) and negative (denied) test case |
| Scope-isolation tests | A user scoped to Meet A cannot access Meet B's data, per [../01-architecture/scope-model.md](../01-architecture/scope-model.md) |
| Separation-of-duties tests | Every SoD conflict (SOD-01 through SOD-11) has a negative test confirming the conflicting combination is rejected |
| Authentication tests | Login, MFA, password reset, and lockout behavior |
| Session tests | Session expiry, invalidation on revocation/password-change, CSRF protection |
| API security tests | Object-level and function-level authorization (BOLA/BFLA), rate limiting, input validation |
| File-upload tests | Malicious file rejection, oversized-file rejection, MIME-mismatch rejection |
| Malware-flow tests | Quarantine-to-release lifecycle behaves correctly for clean, infected, and scan-failure outcomes |
| Queue tests | A queued job re-validates authorization at execution time, per [../01-architecture/event-and-queue-architecture.md, Section 2](../01-architecture/event-and-queue-architecture.md#2-job-rules) |
| Reverb tests | Channel-authorization callbacks correctly grant/deny subscription |
| Redis-failure tests | The application degrades gracefully when Redis is unavailable, per [infrastructure-runtime-and-network-security.md, Section 1](infrastructure-runtime-and-network-security.md#1-redis-security) |
| MinIO authorization tests | A download is denied without proper authorization, regardless of a guessed/known object key |
| Mobile security tests | Token storage, device binding, offline-window expiry |
| Offline abuse tests | Prohibited offline-final actions (Section, [mobile-device-and-offline-security.md, Section 3](mobile-device-and-offline-security.md#3-offline-security)) are rejected even when attempted |
| Device credential tests | Revoked devices are denied on next check-in |
| Export tests | Export authorization and classification-respecting redaction |
| Privacy-filter tests | Per Section 2 below |
| Audit-integrity tests | Per Section 3 below |
| Incident-response exercises | Tabletop exercises validating the lifecycle in [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) |
| Backup and restore tests | Per [../02-data/backup-restore-and-data-recovery.md, Section 3](../02-data/backup-restore-and-data-recovery.md#3-backup-operational-requirements) |
| Penetration testing readiness | See dedicated subsection below |

### Penetration Testing Readiness

A formal, independent penetration test or red-team exercise is **not performed as part of this documentation phase** — this is architecture-level planning, not a security assessment. Penetration testing is anticipated as a pre-launch and periodic post-launch activity once a testable implementation exists; scope, cadence, and vendor selection are open decisions.

## 2. Privacy Testing

| Test Area | Scope |
|---|---|
| Public projection review | Every public projection is verified to contain only Public-tier fields, per [../02-data/public-reporting-and-projection-data.md, Section 1](../02-data/public-reporting-and-projection-data.md#1-public-projections) |
| Data minimization tests | A workflow's data collection is verified against its documented necessity (Section, [privacy-by-design-architecture.md, Section 2](privacy-by-design-architecture.md#2-privacy-by-design-controls)) |
| Field visibility tests | Every classification tier's field-level exposure is tested against every role category |
| Guardian relationship tests | Unverified relationship claims are rejected, per [minor-athlete-and-guardian-data-governance.md, Section 2](minor-athlete-and-guardian-data-governance.md#2-guardian-data) |
| Minor profile tests | A minor's public profile excludes exact birthdate/full contact info, per [minor-athlete-and-guardian-data-governance.md, Section 1](minor-athlete-and-guardian-data-governance.md#1-minor-athlete-privacy) |
| Medical access tests | Only the minimal clearance-status flag crosses into Eligibility; full medical detail never does |
| Eligibility evidence tests | Evidence documents are never exposed beyond the review chain |
| Export-redaction tests | Sensitive fields are correctly excluded/masked in export output |
| Log-redaction tests | No sensitive field value appears in any application/security log |
| Lower-environment tests | Lower environments contain no real production personal data by default |
| Retention tests | Records past their (eventually finalized) retention period are correctly flagged for disposal |
| Disposal tests | Disposal correctly removes data while preserving the required deletion-evidence record |
| AI data-minimization tests | An AI-feature request includes only the minimum necessary data, per [ai-security-privacy-and-governance.md, Section 2](ai-security-privacy-and-governance.md#2-ai-privacy-and-governance) |
| Cross-tenant leakage tests | If multi-organization support is ever adopted, data never crosses organizational boundaries inappropriately |

## 3. Audit Testing

| Test Area | Scope |
|---|---|
| Event completeness | Every consequential action (Section 2, [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md)) produces a corresponding audit event — no silent gaps |
| Actor accuracy | The recorded actor matches the actual authenticated identity that performed the action |
| Scope accuracy | The recorded scope (organization/meet/committee) matches the action's actual context |
| Before and after references | A correction's audit event correctly references both the prior and new state |
| Reason capture | Actions requiring a reason (corrections, privileged access, exports) fail or flag if the reason is missing |
| Impersonation chain | Both the support actor and the impersonated user are correctly, distinctly recorded, per [audit-and-security-event-architecture.md, Section 5](audit-and-security-event-architecture.md#5-impersonation-auditing) |
| Break-glass record | Every invocation is fully recorded per [audit-and-security-event-architecture.md, Section 6](audit-and-security-event-architecture.md#6-break-glass-auditing) |
| Export record | Every export is recorded with actor, scope, and content classification |
| Sensitive-view record | Every Restricted/Highly Restricted-tier access beyond minimal exposure is recorded, per [audit-and-security-event-architecture.md, Section 4](audit-and-security-event-architecture.md#4-sensitive-data-access-auditing) |
| Device-action record | Device-attributed actions correctly distinguish device identity from operator identity |
| AI involvement | Every AI-assisted action correctly flags AI involvement per [audit-and-security-event-architecture.md, Section 7](audit-and-security-event-architecture.md#7-ai-assistance-auditing) |
| Correlation | Related events (e.g., a request's full application/security/audit log trail) share a correlation ID |
| Sequence gaps | A missing expected event in a known sequence (e.g., certification without a preceding validation event) is detectable |
| Tamper detection | If hash-chaining or signing is adopted (per [cryptography-key-and-secret-management.md](cryptography-key-and-secret-management.md)), tamper-detection tests confirm it functions as designed |
| Backup and restore | Audit-event backups restore correctly and completely |
| Restricted access | Audit-event access itself is restricted and that restriction is tested |

## 4. Relationship to Phase 0.4 Testing Architecture

This document extends, rather than replaces, [../01-architecture/testing-architecture.md](../01-architecture/testing-architecture.md)'s 8 test layers (Unit, Application, Feature, Integration, Contract, Frontend, Flutter, Non-Functional) — every security/privacy/audit test scenario above belongs within one of those existing layers, using PestPHP for the backend per the confirmed technology direction. No new test framework is introduced.

## 5. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably penetration-testing vendor/timing, and whether a dedicated security-test suite is maintained separately from or integrated within the existing Phase 0.4 test-layer structure.
