# PMMS Backup, Restore, and Data Recovery

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [../01-architecture/resilience-performance-and-scaling.md, Section 4](../01-architecture/resilience-performance-and-scaling.md#4-backup-and-recovery-considerations) · [retention-archival-and-disposal.md](retention-archival-and-disposal.md) · [high-integrity-data-model.md](high-integrity-data-model.md)

This document extends the Phase 0.4 backup/recovery principles with data-specific detail: what must be backed up, at what priority, and what disaster-recovery data requirements follow from the high-integrity data model. **No numerical RPO/RTO target is invented — restated, not weakened, from Phase 0.4.**

---

## 1. Backup Coverage by Data Category

| Data Category | Backup Priority | Rationale |
|---|---|---|
| Official Results, Medal Tally, Protest decisions | **Highest** | Loss would be an irreversible institutional-integrity failure — these are the platform's core trust output |
| Audit events | **Highest** | The accountability mechanism underlying every other high-integrity guarantee; loss undermines the platform's entire traceability claim |
| Eligibility decisions | **Highest** | Legal/institutional significance; a lost decision cannot be reliably reconstructed |
| Scoring (raw records) | High | Feeds Official Results; loss before certification could force re-competition, a severe operational failure |
| Accreditation credentials | High | Security-relevant; loss disrupts venue access control |
| Medical records | High (and Highly Restricted — backup access itself is classification-gated) | Health/legal significance |
| Financial records | High | Institutional accountability |
| Participant identity | High | Foundational to nearly every other workflow; loss would be extremely disruptive to reconstruct |
| Document metadata + MinIO objects | High | Evidence supporting the above; both the metadata (MySQL) and content (MinIO) must be backed up together, since one without the other is useless |
| Registration, entries, assignments, schedules | Medium | Operationally important, more feasible to partially reconstruct than the "Highest" tier if lost |
| Logistics records (billeting, food, transport) | Medium–Low | Operationally important during the meet, lower long-term institutional stakes |
| Notifications | Low | Ephemeral by nature; loss is an inconvenience, not an integrity failure |
| Cache/session (Redis) | **None** | Explicitly never backed up as authoritative — Redis holds nothing that isn't recoverable from MySQL or safely lost, per [logical-data-architecture.md, Section 2](logical-data-architecture.md#2-source-of-truth-model) |

## 2. Backup Requirements

- **MySQL** — the authoritative relational store, backed up with the highest priority and frequency, informed by the category table above (the "Highest" tier tables may warrant more frequent or continuous backup, e.g., point-in-time recovery via binary log shipping, versus a daily snapshot for lower tiers — a Phase 0.6+ implementation decision).
- **MinIO** — object content, backed up in lock-step with its MySQL metadata (Section 1); a backup strategy that backs up one without the other produces an internally inconsistent recovery point.
- **Application configuration** — non-secret configuration is backed up as part of standard deployment-artifact versioning (source control); secrets are handled separately (Section 4).
- **Encryption keys** — backed up separately from the data they protect (per [audit-and-security-data-architecture.md, Section 3](audit-and-security-data-architecture.md#3-encryption-and-sensitive-data)), with access to the key backup itself tightly restricted — a key backup stored alongside the data it protects defeats the purpose of separation.
- **Audit records** — backed up with the same rigor as the live table; an audit backup is itself subject to the same "never delete" discipline as the live audit log.
- **Generated reports** — backed up per their retention category (Section, [retention-archival-and-disposal.md](retention-archival-and-disposal.md)); many are regenerable from source data, lowering their backup priority relative to genuinely irreplaceable source records.
- **Critical Redis data only where needed** — restated: Redis holds nothing requiring backup under the current architecture; if a future use case ever introduces genuinely non-recoverable Redis state, that would be a deviation from [logical-data-architecture.md](logical-data-architecture.md) requiring its own explicit decision, not an assumed default.
- **Mobile offline recovery considerations** — a lost/corrupted device should not be a data-loss event for anything already synced (per [offline-sync-and-conflict-data-model.md](offline-sync-and-conflict-data-model.md)); the device's local store is not itself a backup target, since its authoritative copy is the server.
- **Device credentials** — revocation lists and device-trust state, backed up as part of the standard MySQL backup (these are ordinary MySQL rows, per [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md)).
- **Deployment artifacts** — versioned in source control (Git), not a separate backup concern.

## 3. Backup Operational Requirements

- **Backup verification** — a backup is periodically test-restored to confirm it is actually usable.
- **Restore drills** — scheduled, not merely theoretical; the "Highest" priority tier (Section 1) warrants the most frequent drilling given the stakes of getting recovery wrong.
- **Offsite backup** — physically/logically separated from the primary environment.
- **Access control** — backup access follows the same classification-driven least-privilege discipline as the live data (a MySQL backup containing medical/eligibility data is exactly as sensitive as the live table, per [information-classification-and-privacy.md](information-classification-and-privacy.md)).
- **Retention** — governed by the same policy dependency as [retention-archival-and-disposal.md](retention-archival-and-disposal.md), not invented independently here.

## 4. Recovery Point and Recovery Time Objectives

**RPO and RTO are explicitly placeholders in this document, not numerically defined** — restated from [../01-architecture/resilience-performance-and-scaling.md, Section 4](../01-architecture/resilience-performance-and-scaling.md#4-backup-and-recovery-considerations) (Phase 0.4). What Phase 0.5 adds is the **tiered priority** in Section 1, which should directly inform whatever RPO/RTO targets DepEd/legal/engineering eventually set — the "Highest" tier (Official Results, Audit, Eligibility) plausibly warrants a materially tighter RPO than the "Medium" tier (logistics records), even though neither numeric value is set here.

## 5. Disaster Recovery Data Requirements

- A Disaster Recovery environment (per [../01-architecture/environment-and-configuration-model.md, Section 1](../01-architecture/environment-and-configuration-model.md#1-environment-model)) requires a replicated copy of the "Highest" and "High" priority data tiers at minimum, kept within whatever RPO is eventually set.
- DR replication for MinIO objects follows the same lock-step principle as ordinary backup (Section 2) — metadata and content replicated together.
- A DR failover event is itself an auditable operational event, logged with the same rigor as any other consequential administrative action.
- Post-failover, a reconciliation process (mirroring [object-metadata-and-file-lifecycle.md, Section 4](object-metadata-and-file-lifecycle.md#4-reconciliation-process-conceptual)) confirms DR-environment data integrity before it is trusted as the new primary.

## 6. Open Questions

- Numerical RPO/RTO targets — require DepEd institutional-record requirements and legal/compliance input (mirrors [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements) in spirit, though distinct in scope).
- Backup frequency/mechanism per tier (continuous point-in-time recovery vs. periodic snapshot) — Phase 0.6+ implementation decision.
- Whether a DR environment is provisioned before or only after the first pilot meet.

Tracked in [data-open-decisions.md](data-open-decisions.md).
