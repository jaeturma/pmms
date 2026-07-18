# PMMS Authorization and Privileged-Access Assurance

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/phase-0.3-access-and-assignment-architecture.md](../01-architecture/phase-0.3-access-and-assignment-architecture.md) · [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md) · [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) · [../01-architecture/runtime-security-architecture.md](../01-architecture/runtime-security-architecture.md)

This document adds assurance, privileged-access governance, and testable controls around the Phase 0.3 authorization model. **No Gate, Policy class, middleware, or role/permission package configuration is created here.**

---

## 1. Authorization Assurance — Preserving the Phase 0.3 Formula

```text
Permission
+ Scope
+ Assignment
+ Resource State
+ Data Classification
+ Separation of Duties
+ Device Trust
+ Time Validity
+ Explicit Restrictions
```

This formula is **unchanged** from [../01-architecture/phase-0.3-access-and-assignment-architecture.md](../01-architecture/phase-0.3-access-and-assignment-architecture.md). Phase 0.6 does not redefine it — it defines how the platform gains assurance that every enforcement point actually evaluates it correctly and completely.

| Assurance Control | Direction |
|---|---|
| Centralized authorization | Every check routes through one decision-model implementation (per [../01-architecture/runtime-security-architecture.md, Section 2](../01-architecture/runtime-security-architecture.md#2-authorization-enforcement-points)) — no controller, job, or service hand-rolls its own ad hoc check |
| Policy coverage | Every permission in [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md) has a corresponding, testable authorization check before implementation is considered complete for that permission |
| Query filtering | List/search results are filtered to what the requester is authorized to see — an unauthorized record is never fetchable by ID either, but query filtering additionally prevents it from ever appearing in a result set |
| Export authorization | Every export re-evaluates the requester's authorization against the exported data's classification, per [../02-data/import-export-and-data-exchange.md, Section 2](../02-data/import-export-and-data-exchange.md#2-export-architecture) |
| File authorization | Every MinIO-backed download re-checks authorization at request time — never a standing, pre-validated grant |
| Broadcast authorization | Every Reverb channel subscription is authorized server-side, per [../01-architecture/realtime-architecture.md, Section 3](../01-architecture/realtime-architecture.md#3-rules) |
| Queue execution authority | A queued job re-validates the initiating actor's authority is still current at execution time, never trusting a payload's embedded authorization state as still valid |
| Offline authorization snapshots | Bounded, expiring, and provisional per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) — never treated as equivalent to a live authorization check |
| Revocation | Takes effect on the next request for connected clients, with a disclosed, bounded lag for offline devices, per [../01-architecture/access-review-and-revocation.md](../01-architecture/access-review-and-revocation.md) |
| Explicit denial | An explicit deny or active security hold always overrides any grant, per [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md) |
| High-integrity action approval | Eligibility approval, result certification, protest resolution, medal tally certification, and accreditation issuance/revocation always require the specific approval-tier authority named in [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 19](../01-architecture/phase-0.3-access-and-assignment-architecture.md#19-approval-authority-levels) |
| Privilege escalation detection | Repeated authorization failures, unexpected scope combinations, or a role change immediately followed by sensitive-action attempts are candidate security-event triggers (Section 37 of the main document) |
| Authorization test coverage | Every permission, every SoD conflict, and every scope-isolation boundary has a corresponding test scenario, per [security-testing-and-assurance.md](security-testing-and-assurance.md) |

## 2. Separation of Duties (Cross-Reference, Preserved Unchanged)

The 11 conflict entries (SOD-01 through SOD-11, plus SOD-03b) in [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) are preserved without modification. Phase 0.6 adds:

- **Enforcement assurance**: every SoD conflict is either structurally prevented (the conflicting combination cannot be assigned/executed) or, where structural prevention is not yet feasible, audit-detectable — restated as an absolute requirement, not a preference.
- **SoD violation as a security event**: an attempted or detected SoD violation is itself a security-event category (Section 37 of the main document, and [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md)).
- **SoD test coverage**: every SoD conflict has a corresponding negative test case confirming the conflicting combination is rejected, per [security-testing-and-assurance.md](security-testing-and-assurance.md).

## 3. Privileged Access Categories

| Category | Examples |
|---|---|
| Platform administration | ROLE-01 (Platform Super Administrator) |
| Security administration | ROLE-03 (Security Administrator) |
| Database administration | Infrastructure-level DBA access (future DevOps phase) |
| Infrastructure administration | Redis, MinIO, Reverb, queue-worker administration |
| Production deployment | CI/CD execution authority, deployment triggers |
| Log access | Application/infrastructure log viewing, distinct from audit-event access |
| Audit export | ROLE-05 (Audit Viewer)'s export capability specifically |
| Backup access | Ability to read or restore from backup storage |
| Key-management access | Access to encryption keys or the key-management system, once one exists |
| Support impersonation | Per SOD-11 and Section 4 below |
| Break-glass access | Currently unresolved necessity — [../01-architecture/access-open-decisions.md, AD-10](../01-architecture/access-open-decisions.md#ad-10--break-glassemergency-access-necessity-and-policy-owner) |
| Data-repair access | Direct production data correction outside the normal application workflow — governed by [access-review-production-and-support-controls.md](access-review-production-and-support-controls.md) |

## 4. Privileged-Access Requirements

Every privileged category above requires:

1. **Named accounts** — never a shared or generic administrator credential.
2. **MFA** — mandatory for every privileged category, not optional (extending Section 1's general MFA-readiness with a specific requirement for this category).
3. **Least privilege** — the narrowest privileged category that accomplishes the task, never a broader grant for convenience.
4. **Time limitation** — privileged access is granted for a bounded duration where the task allows it, not permanently by default.
5. **Approval** — granted through the assignment model's approval mechanism, per [../01-architecture/assignment-model.md](../01-architecture/assignment-model.md), never self-granted.
6. **Reason capture** — every privileged grant records why it was needed.
7. **Ticket or incident reference** — where the access relates to a specific support case or incident, that reference is captured.
8. **Session logging where feasible** — privileged sessions are a candidate for enhanced session-level logging beyond ordinary audit events.
9. **Post-use review** — privileged access, especially time-bounded or emergency grants, is reviewed after use, not only before.
10. **No shared administrator credentials** — restated as an absolute rule from item 1.
11. **Separation from ordinary business approval roles** — per SOD-07, Platform/Security Administrators do not concurrently hold a business-approval role (Eligibility Approver, Result Certifier, Tally Certifier, Accreditation Officer).

## 5. Support Impersonation Governance

Support impersonation, if ever implemented, is governed by all of the following, none of which is optional:

- Disabled by default; requires explicit organizational approval before any implementation proceeds.
- Requires a logged reason and (where applicable) a ticket reference for every session.
- Is time-limited — no indefinite impersonation session.
- **Can never perform an approval/certification/publication action while active** (SOD-11) — a hard technical block, not a policy reminder.
- Every impersonation session is fully audited (Section "Impersonation Auditing" in [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md)).
- Necessity itself remains an open question — [../01-architecture/access-open-decisions.md, AD-09](../01-architecture/access-open-decisions.md#ad-09--support-impersonation-necessity-and-approval-authority).

## 6. Break-Glass Access Governance

Break-glass (emergency) access, if ever implemented, requires:

- Explicit, documented governance approval before implementation — never assumed as a default capability.
- Strict time limitation and automatic expiry.
- Mandatory post-use review by a role distinct from the one who invoked it.
- Full audit logging (Section "Break-Glass Auditing" in [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md)).
- Necessity itself remains genuinely open with **no recommended direction** — [../01-architecture/access-open-decisions.md, AD-10](../01-architecture/access-open-decisions.md#ad-10--break-glassemergency-access-necessity-and-policy-owner).

Per working rule 29, PMMS does not permit support impersonation or break-glass access without this governance in place — neither is a default capability to build toward.

## 7. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably whether break-glass access is implemented at all (mirrors AD-10), the specific privileged-MFA enforcement mechanism, and privileged-session logging scope.
