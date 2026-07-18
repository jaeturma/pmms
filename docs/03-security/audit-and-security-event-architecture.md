# PMMS Audit and Security-Event Architecture

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../02-data/audit-and-security-data-architecture.md](../02-data/audit-and-security-data-architecture.md) · [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md) · [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md)

This document extends Phase 0.5's audit-data architecture with the specific event catalog, integrity assurance, and monitoring boundaries Phase 0.6 requires. **No audit-event model, migration, or logging middleware is created here.**

---

## 1. Audit-Event Conceptual Fields

Extending [../02-data/audit-and-security-data-architecture.md, Section 1](../02-data/audit-and-security-data-architecture.md#1-audit-data-architecture) — every audit event conceptually carries:

Actor · actor type (human/device/service/AI) · effective user · impersonating user (where applicable) · action · target · context · scope · organization · meet · previous-state reference · new-state reference · reason · source · device · network context where appropriate · correlation ID · time · result (success/failure/denied) · security classification · related approval · related ticket · AI involvement flag · export or file reference.

**No physical field, column type, or table is defined here** — this is a conceptual shape, per [../02-data/audit-and-security-data-architecture.md](../02-data/audit-and-security-data-architecture.md), which this document does not duplicate or override.

## 2. Audit-Event Categories

| Category | Examples |
|---|---|
| Authentication | Login success/failure, logout, password reset, MFA enrollment/challenge |
| Authorization | Access granted, access denied, explicit deny triggered |
| Account administration | Account creation, deactivation, role assignment/removal |
| Role and permission changes | Any modification to a role's permission set (platform-level, not per-user) |
| Assignment changes | Assignment creation, activation, revocation, expiry |
| Privileged access | Any use of a privileged category from [authorization-and-privileged-access-assurance.md, Section 3](authorization-and-privileged-access-assurance.md#3-privileged-access-categories) |
| Impersonation | Session start, actions taken during, session end (Section 5 below) |
| Break-glass access | Invocation, actions taken, review (Section 6 below) |
| Eligibility decisions | Submission, review, approval, rejection, reopening |
| Score entry and corrections | Every submission and every correction, per [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) |
| Result certification | Certification, supersession |
| Result publication | Publication, unpublication, correction |
| Protest decisions | Filing, resolution |
| Medal tally certification | Encoding, certification |
| Accreditation issuance and revocation | Every credential lifecycle event |
| Sensitive medical access | Every access to a medical record beyond the minimal ACL-derived clearance-status flag (Section 4 below) |
| Finance approvals | Every expense/budget approval action |
| Data exports | Every export, per classification |
| File downloads | Every Restricted/Highly Restricted-tier download |
| Bulk imports | Every import batch |
| Data corrections | Every high-integrity correction, per [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture) |
| Device registration and revocation | Every device lifecycle event |
| Service-account actions | Attributed per [infrastructure-runtime-and-network-security.md, Section 4](infrastructure-runtime-and-network-security.md#4-service-account-security) |
| AI-assisted actions | Every AI-generated suggestion that was accepted/acted upon (Section 7 below) |
| Configuration changes | Platform-level configuration modifications |
| Security incidents | Declaration, containment actions, closure |
| Backup and restore | Every backup job and every restore operation |
| Production repair | Every direct production data correction outside normal application flow |

## 3. Audit Integrity

| Principle | Direction |
|---|---|
| Append-only intent | Audit events are never updated or deleted through ordinary application operation, restated absolutely from [../02-data/audit-and-security-data-architecture.md, Section 1](../02-data/audit-and-security-data-architecture.md#1-audit-data-architecture) |
| Restricted write access | Only the audit-recording mechanism itself writes audit events — no direct application code path bypasses it |
| No ordinary deletion | No role, including Platform Super Administrator, has an ordinary "delete audit entry" capability |
| Correction through supplemental events | An incorrect audit event is corrected by a new event referencing it, never by editing the original |
| Time synchronization | Audit-event timestamps depend on synchronized server clocks; clock drift is a monitored signal (Section, [security-metrics-monitoring-and-reporting.md](security-metrics-monitoring-and-reporting.md)) |
| Sequence or chaining evaluation | Hash-chaining consecutive audit events (each event's hash incorporating the prior event's hash) is a candidate tamper-evidence mechanism — evaluated, not committed |
| Hash or signature evaluation | Cryptographic signing of audit events is a candidate enhancement — evaluated, not committed, per [cryptography-key-and-secret-management.md, Section 1](cryptography-key-and-secret-management.md#1-cryptographic-architecture) |
| Backup | Audit events are backed up with the same "Highest" priority as the high-integrity data they attest to, per [../02-data/backup-restore-and-data-recovery.md, Section 1](../02-data/backup-restore-and-data-recovery.md#1-backup-coverage-by-data-category) |
| Export control | Audit exports are themselves a restricted, audited action (Section 2, "Data exports") |
| Separate storage evaluation | Storing audit events in a separate database/instance from operational data is a candidate defense-in-depth control — evaluated, not committed |
| Monitoring for gaps | A missing expected audit event (e.g., a certification action with no corresponding audit entry) is itself a detectable anomaly, a candidate security-event trigger |
| Privileged-access logging | Restated from [authorization-and-privileged-access-assurance.md, Section 4](authorization-and-privileged-access-assurance.md#4-privileged-access-requirements) |
| Clock-drift monitoring | Restated above |
| Tamper detection | The combination of append-only discipline, restricted write access, and (if adopted) hash-chaining constitutes the tamper-detection posture — **immutability is not claimed as an implemented guarantee** until the specific mechanism is built and verified, per working rule ("Do not claim immutability unless supported by implementation") |
| Retention validation | Audit retention follows [../02-data/retention-archival-and-disposal.md, Section 1](../02-data/retention-archival-and-disposal.md#1-retention-categories) — a placeholder pending DepEd/legal input, not invented here |

## 4. Sensitive-Data Access Auditing

Every access to Restricted or Highly Restricted-tier data beyond the minimal exposure already approved for a role's normal function is an audit-relevant event — notably:

- Every view of a medical record beyond the minimal ACL-derived clearance-status flag that crosses into Eligibility (per [../01-architecture/context-map.md, "Anti-Corruption Layers"](../01-architecture/context-map.md#anti-corruption-layers--explicit-justification)).
- Every view of eligibility evidence documents.
- Every view of guardian contact detail outside the owning delegation's normal registration workflow.
- Every financial-record view outside the ordinary encode/approve workflow.
- Every audit-event export or bulk audit query.

## 5. Impersonation Auditing

If support impersonation is ever implemented (per [authorization-and-privileged-access-assurance.md, Section 5](authorization-and-privileged-access-assurance.md#5-support-impersonation-governance)), every session records: the support actor, the impersonated user, start/end time, reason, ticket reference, and every action taken during the session — with the impersonated user's identity clearly distinguished from the acting support operator's identity in every resulting audit event (never blended into a single ambiguous "actor").

## 6. Break-Glass Auditing

If break-glass access is ever implemented (per [authorization-and-privileged-access-assurance.md, Section 6](authorization-and-privileged-access-assurance.md#6-break-glass-access-governance)), every invocation records: the invoking actor, the declared emergency justification, start/end time, every action taken, and the mandatory post-use review outcome — with an elevated audit-classification distinguishing break-glass events from ordinary privileged access.

## 7. AI-Assistance Auditing

Every AI-assisted action that is accepted/acted upon records: the requesting user (whose authority the AI action is bound to, per [../01-architecture/device-and-service-identity-model.md, Section 7](../01-architecture/device-and-service-identity-model.md#7-ai-service-identity-cross-reference)), the AI service identity, the model/prompt version where available, the specific suggestion made, whether it was accepted/modified/rejected, and the ordinary Command/Application-layer audit trail for whatever action resulted — an AI-assisted action is never logged as if a human acted alone, and never logged as if the AI acted with independent authority. Full AI governance detail: [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md).

## 8. Security Events

| Category | Examples |
|---|---|
| Failed login | Individually and in aggregate (pattern detection) |
| Brute-force pattern | Repeated failures against one account or from one source in a short window |
| Credential stuffing | Repeated failures across many accounts from a common source pattern |
| Account lock | Triggered lockout events |
| MFA failure | Repeated 2FA/passkey challenge failures |
| Suspicious session | Anomalous device/location/behavior signals (future-phase capability) |
| Privilege change | Any role/assignment grant, especially privileged-category grants |
| Explicit denial | An authorization decision resulting in explicit deny |
| Repeated authorization failure | A pattern of denied attempts against the same resource/actor |
| Data-exfiltration pattern | Unusual volume/pattern of exports or downloads |
| Unusual export | An export outside a user's normal pattern (volume, classification, timing) |
| Unusual download | Same, for individual file downloads |
| Device compromise | A device flagged rooted/jailbroken, tampered, or behaving anomalously |
| Token replay | A detected reuse of a previously-used, single-use token |
| Webhook signature failure | An inbound webhook failing signature verification |
| Malware detection | A scan result flagging an uploaded file as infected |
| Queue abuse | Anomalous queue-job volume or a job repeatedly failing/retrying |
| Reverb abuse | Anomalous channel-subscription or broadcast-triggering volume |
| Secret exposure | A detected secret in a log, commit, or error message |
| Database anomaly | Unusual query volume/pattern from an unexpected source |
| Redis anomaly | Unexpected command usage or connection pattern |
| MinIO anomaly | Unusual access-pattern or unauthorized-access-attempt signal |
| AI-policy violation | A detected attempt to use an AI feature outside its approved boundary (Section 58 of the main document) |
| Break-glass invocation | Every use, per Section 6 |
| Production repair | Every direct production correction, per [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture) |
| Backup failure | A failed or incomplete backup job |

## 9. Logging and Monitoring Boundaries

| Log Type | Distinguishing Purpose |
|---|---|
| Application logs | General operational diagnostics — not an audit substitute |
| Security logs | Security-event-specific technical detail supporting Section 8 |
| Audit events | The formal, business-meaning accountability record — Sections 1–7 |
| Infrastructure logs | Server/network/runtime-component operational logs |
| Device logs | Locally-captured device operational logs, synced per [../02-data/offline-sync-and-conflict-data-model.md](../02-data/offline-sync-and-conflict-data-model.md) |
| Access logs | Web-server/API-gateway-level request logs |
| Integration logs | External-service call logs (currently minimal, given no approved integrations) |
| Queue logs | Job execution/failure logs |
| AI logs | AI-service request/response logs, subject to the minimization rules in [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md) |

### Rules

- **Logs are not audit substitutes** — an application log entry is not a replacement for a formal audit event; the two serve different purposes and neither is dropped in favor of the other.
- **Sensitive data must be redacted** — no log of any type includes a Restricted/Highly Restricted-tier field's raw value.
- **Passwords and tokens must never be logged** — absolute, no exception.
- **Medical and eligibility evidence must not be logged** — restated as absolute given their elevated sensitivity.
- **Correlation IDs should be used** — every log entry across application/security/infrastructure/queue logs for a single logical request carries the same correlation ID, enabling cross-log tracing without needing sensitive payload content in any individual log line.
- **Log access must be restricted** — log access is itself a privileged-access category (Section, [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md)).
- **Retention must be governed** — per [../02-data/retention-archival-and-disposal.md, Section 1](../02-data/retention-archival-and-disposal.md#1-retention-categories) ("logs" category), a placeholder, not invented here.
- **High-volume logs may be sampled** — routine application/access logs may be sampled under high load.
- **Critical security events must not be sampled away** — Section 8's security events are never dropped due to sampling, regardless of volume.

## 10. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably whether hash-chaining or cryptographic signing is adopted for audit-integrity, whether audit storage is physically separated from operational data, and the specific anomaly-detection mechanism for Section 8's pattern-based security events.
