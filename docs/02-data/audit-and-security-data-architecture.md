# PMMS Audit and Security Data Architecture

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [high-integrity-data-model.md](high-integrity-data-model.md) · [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md) · [information-classification-and-privacy.md](information-classification-and-privacy.md)

This document defines the persistence architecture for audit events, security events, encryption, and data masking/redaction. **No physical table or cryptographic algorithm is selected here.**

---

## 1. Audit Data Architecture

- **Append-only intent, absolute.** An `AuditEvent` row is never updated or deleted after creation — restated from [temporal-history-and-versioning-model.md, Section 3](temporal-history-and-versioning-model.md#3-immutable-history-and-append-only-records) as the one domain with zero exceptions.
- **Conceptual shape:** actor (who), action (what), target (on what record), before/after references (where applicable — a reference to the prior and new state, not necessarily the full state duplicated), reason (where applicable), source (which module/API/device), device (where applicable), timestamp (UTC, per [database-naming-and-design-standards.md, Section 4](database-naming-and-design-standards.md#4-timestamp-and-time-zone-standards)), security-event classification (where relevant).
- **Privileged access.** The application database credential used for ordinary request handling should, in a future physical implementation, have `INSERT`/`SELECT` only on the audit table — no `UPDATE`/`DELETE` grant at all, so that even a compromised application-layer bug cannot silently alter history.
- **Integrity verification.** A tamper-evidence mechanism (e.g., a hash chain where each row's hash incorporates the previous row's hash) is a **candidate control**, not a committed design — evaluated further in [data-open-decisions.md](data-open-decisions.md); its absence would not make the audit log incorrect, only less independently verifiable against sophisticated tampering.
- **Export.** Audit exports are a distinct, heavily audited operation in their own right (`audit-event.export`, per [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md)) — never a raw table dump accessible outside that controlled path.
- **Retention.** The longest retention category in the platform (per [retention-archival-and-disposal.md](retention-archival-and-disposal.md)), pending DepEd policy confirmation.

## 2. Security-Event Storage

Distinct from general audit events, though overlapping in mechanism: security-relevant events (failed authentication patterns, access-scan denials/anomalies, device-trust changes, impersonation-session activity per [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 32](../01-architecture/phase-0.3-access-and-assignment-architecture.md#32-impersonation-and-support-access)) are:

- Stored with the same append-only discipline as general audit events.
- Classified with an explicit severity/category (informational, suspicious, confirmed-incident) distinct from the general audit event's classification.
- Retained with the same or greater rigor than general audit events, given their direct relevance to incident investigation.
- Never merged into the general `AuditEvent` table as an undifferentiated row type if doing so would make security-specific querying (e.g., "show me every failed authentication in the last hour across all users") inefficient — a security-events table may be a distinct table sharing the same append-only/immutability discipline, evaluated at physical-schema time (Phase 0.6).

## 3. Encryption and Sensitive Data

### Candidate Controls (Not Selected — Evaluated Only)

| Control | Applies To |
|---|---|
| Encryption in transit | All traffic — already an established runtime requirement per [../01-architecture/runtime-security-architecture.md, Section 3](../01-architecture/runtime-security-architecture.md#3-runtime-security-controls) |
| Database-at-rest protection | The full MySQL data volume, particularly for Restricted/Highly Restricted tables |
| Field-level encryption | Selected Highly Restricted values (e.g., specific medical fields, authentication secrets) where at-rest volume encryption alone is judged insufficient |
| Object-storage encryption | MinIO server-side encryption for stored objects, especially Restricted/Highly Restricted document categories |
| Encrypted mobile local stores | Per [../01-architecture/flutter-architecture.md, Section 5](../01-architecture/flutter-architecture.md#5-rules) |
| Key rotation readiness | Encryption keys are never hard-coded or treated as permanent; rotation must be operationally possible |
| Separation of encryption keys from data | Keys never stored in the same table/database as the data they protect |
| Search limitations for encrypted fields | A field-level-encrypted column cannot be searched/filtered by plaintext value without either decrypting at query time (a performance cost) or a deterministic-encryption/hashing approach for the specific lookup need — evaluated per field, not assumed solved generically |
| Hashing where reversibility is unnecessary | Authentication secrets (already handled by the existing Fortify scaffolding's bcrypt hashing) — never store a value in a reversible form when only equality-checking is needed |
| Tokenization | Considered for values needing a stable reference without exposing the underlying value directly (e.g., a payment reference, if ever integrated) |
| Secure deletion limitations | Physically overwriting deleted data on typical cloud/managed storage is not fully guaranteed — this is disclosed as a known limitation, not solved by this document |
| Backup encryption | Per [backup-restore-and-data-recovery.md](backup-restore-and-data-recovery.md) |
| Export encryption | Restricted-file exports (per [import-export-and-data-exchange.md](import-export-and-data-exchange.md)) are encrypted where the export leaves the controlled application boundary |

**No cryptographic algorithm or package is chosen in this phase** — per working rule, this is an implementation-phase (Phase 0.6+) decision, informed by the classification-driven requirements in [information-classification-and-privacy.md](information-classification-and-privacy.md).

## 4. Data Masking and Redaction

| Context | Masking Approach |
|---|---|
| UI masking | A Restricted/Highly Restricted field displays partially (e.g., a partial ID number) unless the viewing role's authorization explicitly permits full display |
| Report masking | Generated reports apply the same classification-driven masking as the UI, not a separate, potentially inconsistent rule set |
| Log redaction | Restated from [../01-architecture/observability-and-error-handling.md, Section 2](../01-architecture/observability-and-error-handling.md#2-logging-architecture) — medical, eligibility, finance, and authentication-secret content is never logged, masked or otherwise; it is simply absent from log output |
| Export redaction | An export respects the requester's authorization at generation time — a Restricted field is either fully present (because the requester is authorized) or fully absent (because they are not), never partially masked in an export intended for authorized use, since partial masking in an export the requester is otherwise authorized to receive serves no protective purpose and only degrades usability |
| Support-access redaction | An impersonation/support session (where it exists at all, per [../01-architecture/access-open-decisions.md, AD-09](../01-architecture/access-open-decisions.md#ad-09--support-impersonation-necessity-and-approval-authority)) never surfaces Medical, Eligibility, Finance, or Security data even if the impersonated user themselves could see it, per [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 32](../01-architecture/phase-0.3-access-and-assignment-architecture.md#32-impersonation-and-support-access) |
| Test-environment masking | Per [test-seed-and-reference-data-strategy.md](test-seed-and-reference-data-strategy.md) — no real protected data in Development/Test/Staging |
| Public-projection filtering | Per [public-reporting-and-projection-data.md](public-reporting-and-projection-data.md) — filtering happens at projection-build time, never as an afterthought filter on public read |
| Partial identifier display | A public-facing display may show a partial accreditation number or similar for confirmation purposes without exposing the full value |
| Medical-summary filtering | Only the minimal ACL-derived status flag (per [high-integrity-data-model.md](high-integrity-data-model.md)) ever reaches a non-Medical context |
| Guardian-contact masking | Contact details are Confidential-classified (per [information-classification-and-privacy.md](information-classification-and-privacy.md)) and masked in any view not specifically authorized to see them |
| Audit-export restrictions | Per Section 1 above |

## 5. Open Questions

- Tamper-evidence mechanism for the audit log (Section 1) — candidate, not committed.
- Field-level encryption candidate list (which specific Highly Restricted fields warrant it beyond volume-level at-rest encryption) — deferred to Phase 0.6 with security-team input.
- Encryption key-management approach (application-managed vs. a dedicated key-management service) — deferred.

Tracked in [data-open-decisions.md](data-open-decisions.md).
