# PMMS Data Governance Operating Model

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md) · [security-architecture.md, Section 3](security-architecture.md#3-security-governance-model) · [../02-data/high-integrity-data-model.md, "Data Quality Controls"](../02-data/high-integrity-data-model.md#data-quality-controls)

This document defines the data-governance roles and processes that operate PMMS's data over time — connecting Phase 0.5's data-ownership structure to ongoing governance activity. **No governance-tooling implementation is created here.**

---

## 1. Data Governance Roles

| Role | Responsibility | Distinguished From |
|---|---|---|
| Data owner | Accountable decision-maker for a specific bounded context's authoritative data, per [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md) | Data steward (operational, not accountable-decision-maker) |
| Data steward | Day-to-day data-quality and classification correctness within an owned domain | Data owner (steward executes, owner decides) |
| Data custodian | Operates the technical systems (database, storage) holding the data, without business decision authority over it | Data owner/steward (custodian is technical execution only) |
| System owner | Accountable for the platform's overall operation | Data owner (system owner is platform-wide; data owner is per-domain) |
| Privacy owner | Accountable for privacy-by-design compliance readiness across all domains | Data owner (privacy owner is cross-cutting; data owner is per-domain) |
| Security owner | Accountable for security control effectiveness across all domains | Data owner (security owner is cross-cutting) |
| Records owner | Accountable for retention/disposal correctness, working with each data owner | Data owner (records owner is cross-cutting, coordinating retention across domains) |
| Domain owner | Business-process accountability for a bounded context (may coincide with data owner) | — |
| Reporting owner | Accountable for BC-33 Reporting and Analytics' derived-data correctness and non-authoritative status | Data owner of source contexts (reporting owner never has correction authority over source data) |
| Public-information owner | Accountable for BC-29's projection correctness and privacy-filter effectiveness | Data owner of source contexts |
| AI-data owner | Accountable for the data-minimization and governance boundary around every AI-assisted feature's data access | Data owner of source contexts (AI-data owner ensures the AI boundary itself is respected, not the source data) |

No names are assigned — every role above is "to be identified," per [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md), consistent with every prior phase's governance-role treatment.

## 2. Governance Processes

| Process | Owner | Description |
|---|---|---|
| Data definition | Data owner + Data steward | Defining what a data concept means, its classification, and its owning context — the process behind [../02-data/conceptual-schema-catalog.md](../02-data/conceptual-schema-catalog.md) |
| Quality review | Data steward | Applying the data-quality dimensions from [../02-data/high-integrity-data-model.md, "Data Quality Controls"](../02-data/high-integrity-data-model.md#data-quality-controls) on an ongoing basis |
| Access approval | Data owner (for sensitive tiers, with Privacy owner concurrence) | Approving new access grants per [../01-architecture/assignment-model.md](../01-architecture/assignment-model.md) |
| Classification | Data owner + Privacy owner | Assigning/reviewing the classification tier per [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md) |
| Retention | Records owner + Data owner | Applying [retention-disposal-and-legal-hold-governance.md](retention-disposal-and-legal-hold-governance.md) |
| Sharing | Data owner + Privacy owner (+ DepEd Leadership for external sharing) | Approving any data-sharing instance per [data-sharing-export-and-public-disclosure-controls.md](data-sharing-export-and-public-disclosure-controls.md) |
| Correction | Data owner | Executing the correction architecture in [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture) |
| Archival | Records owner + Data owner | Applying [../02-data/retention-archival-and-disposal.md, Section 2](../02-data/retention-archival-and-disposal.md#2-archiving) |
| Disposal | Records owner + Data owner, with Audit owner visibility | Executing disposal only after retention/hold conditions are satisfied |
| Policy update | Security owner + Privacy owner | Incorporating a newly-verified policy source (per [policy-source-registry.md](policy-source-registry.md)) into the relevant control documents |
| Incident response | Incident commander | Per [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) |
| Audit evidence | Audit owner | Maintaining and producing evidence for [compliance-control-framework.md, Section 2](compliance-control-framework.md#2-control-catalog) control entries |
| Issue escalation | Any governance role, to Security/Privacy/System owner | A governance concern (data-quality failure, classification dispute, retention ambiguity) is escalated rather than silently resolved by whoever notices it |

## 3. Data Quality Governance

Connects Phase 0.5's quality dimensions to ongoing governance:

| Dimension | Governance Process |
|---|---|
| Completeness | Data steward monitors for missing required fields; exceptions are queued for correction, not silently accepted |
| Accuracy | Data steward reviews flagged discrepancies (e.g., a corrected record's pattern) against source evidence |
| Consistency | Data steward monitors cross-context reference integrity (per [../02-data/transaction-concurrency-and-locking.md, Section 4](../02-data/transaction-concurrency-and-locking.md#4-referential-integrity)) for orphaned or contradictory references |
| Timeliness | Data steward monitors whether time-sensitive records (e.g., eligibility decisions before entry deadlines) are processed within operationally necessary windows |
| Uniqueness | Data steward monitors duplicate-detection outcomes from [../02-data/identity-resolution-and-duplicate-management.md](../02-data/identity-resolution-and-duplicate-management.md) |
| Validity | Data steward monitors validation-rule rejection patterns for systemic issues (e.g., a form field consistently causing rejections, suggesting a UX problem) |
| Traceability | Data owner confirms every high-integrity record maintains its full version/audit chain, per [../02-data/temporal-history-and-versioning-model.md](../02-data/temporal-history-and-versioning-model.md) |
| Reproducibility | Data owner periodically confirms a historical report still reproduces its original figures, validating the "historical data remains reproducible" principle from [../02-data/persistence-rules.md](../../.ai/persistence-rules.md) |

### Governance Mechanics

- **Data-quality rules** are defined per bounded context by its data steward, building on the validation needs already flagged in [../02-data/conceptual-schema-catalog.md](../02-data/conceptual-schema-catalog.md).
- **Monitoring** is a future-phase operational capability (dashboards, alerts) — architecturally anticipated, not built here.
- **Exception queues** hold records failing a quality rule for steward review, rather than silently accepting bad data or silently rejecting a legitimate edge case.
- **Correction** follows [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture) for high-integrity data, or an ordinary edit path for non-high-integrity data.
- **Reconciliation** (e.g., MinIO object/metadata reconciliation per [../02-data/object-metadata-and-file-lifecycle.md, Section 4](../02-data/object-metadata-and-file-lifecycle.md#4-reconciliation-process-conceptual)) is a periodic governance activity, not a one-time check.
- **Reporting** of data-quality trends is a candidate future capability for [security-metrics-monitoring-and-reporting.md](security-metrics-monitoring-and-reporting.md).
- **Escalation** follows Section 2's issue-escalation process.
- **Trend analysis** (whether a quality dimension is improving/degrading over time) is a candidate future capability, not built here.

## 4. Relationship to Prior Phases

This model governs, but does not alter, the persistence ownership established in [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md) and the bounded-context ownership established in [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md). Governance roles are additive accountability, not a redefinition of who owns what data.

## 5. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably whether a dedicated data-governance committee is warranted beyond the existing DepEd committee structure, and the specific cadence for quality-review and reconciliation activities.
