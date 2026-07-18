# PMMS Production Support, Access, and Data Repair Operations

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../03-security/access-review-production-and-support-controls.md](../03-security/access-review-production-and-support-controls.md) · [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture)

This document operationalizes Phase 0.6's production/support-access controls into a DevOps support model, and defines data-repair and queue-replay procedures. **No production system is connected to and no data repair is performed by this document.**

---

## 1. Production Access Controls (Preserved from Phase 0.6)

Named accounts · MFA · least privilege · approval · expiry · ticket or incident reference · no shared accounts · no routine direct database editing · logging · post-use review · offboarding · periodic review — restated unchanged from [../03-security/access-review-production-and-support-controls.md, Section 2](../03-security/access-review-production-and-support-controls.md#2-production-access). This document does not redefine these controls; it operationalizes them into the DevOps support workflow below.

## 2. Operational Audit

Every production access, configuration change, deployment, and data repair is audit-relevant, per [../03-security/audit-and-security-event-architecture.md, Section 2](../03-security/audit-and-security-event-architecture.md#2-audit-event-categories) ("production repair" category, already named in Phase 0.6). DevOps operations do not introduce a separate, lesser-audited path — the same audit discipline governing business-transaction actions governs infrastructure/operational actions.

## 3. Support Model

### Tier 0 — Self-Service

Documentation and status information — a status page (once one exists), FAQs, and this documentation package itself for anyone with access to it.

### Tier 1 — Basic User Support

Account guidance and issue intake. No access to Restricted/Highly Restricted data beyond what's needed to confirm a user's identity for account-recovery purposes.

### Tier 2 — Application and Workflow Support

Deeper functional support — helping a committee understand a workflow, diagnosing a "why doesn't this look right" report. Read-only access to relevant data by default, per [../03-security/access-review-production-and-support-controls.md, Section 3](../03-security/access-review-production-and-support-controls.md#3-support-access).

### Tier 3 — Engineering, Infrastructure, Data, or Security Support

Escalation target for issues Tier 1/2 cannot resolve — requires the privileged-access governance in Section 1, never a default broad grant.

### Vendor Escalation

External services where applicable — currently inapplicable, since no vendor is approved (per [../03-security/vendor-and-third-party-risk.md, Section 3](../03-security/vendor-and-third-party-risk.md#3-currently-approved-vendors)).

Each tier defines: ownership (a role, not a name) · escalation path (to the next tier or a specialist) · hours (a candidate future operational decision, distinct during ordinary operation vs. meet-day per [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md)) · evidence (every support interaction touching data is logged) · protected-data restrictions (restated absolutely — no tier accesses medical/eligibility-evidence/guardian/finance detail outside the elevated governance in [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md)).

## 4. Data Repair Operations

A production data correction that cannot be achieved through the ordinary application workflow follows a formal, controlled process — never a casual SQL edit:

Formal issue (a documented, specific problem statement) · business and technical approval (both required, never technical-only) · scope (exactly what will change) · evidence (why the repair is needed and correct) · backup (confirmed recent, before the repair executes) · repair plan (the specific steps) · dry run where possible (validated against a non-production copy first) · controlled tool or migration (a reviewed script/migration, never an interactive ad hoc query) · **no casual SQL edits** — restated absolutely, this is the single most important rule in this document · audit (the repair itself produces an audit-relevant "production repair" event) · reconciliation (confirming the repair achieved its intended effect without unintended side effects) · verification · report · regression test (a repair addressing a defect adds the corresponding test, per [../../.ai/quality-rules.md](../../.ai/quality-rules.md)'s "production incidents must improve the test suite") · post-repair review.

This restates and operationalizes [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture)'s absolute prohibition on direct production database edits outside a documented emergency-repair procedure — this section *is* that documented procedure's operational specification.

## 5. Queue Replay and Reconciliation

| Step | Direction |
|---|---|
| Failed-job inspection | Understanding why a job failed before deciding how to respond |
| Idempotency verification | Confirming the job is genuinely safe to replay (per [../02-data/offline-sync-and-conflict-data-model.md, Section 4](../02-data/offline-sync-and-conflict-data-model.md#4-idempotency-data)) before replaying it |
| Source-state review | Confirming the data the job would act on is still in the expected state |
| Replay approval | For any job touching a Critical-tier domain, approval mirrors the data-repair governance in Section 4 |
| Duplicate prevention | A replay must not duplicate an effect the job already partially achieved before failing |
| Partial-completion handling | A job that partially succeeded before failing is handled with awareness of exactly what already happened, not replayed blindly from the start |
| Downstream projection reconciliation | A replayed job's downstream effects (projections, notifications) are reconciled, not assumed automatically correct |
| Notification duplication control | A replayed notification-sending job doesn't re-notify a recipient who already received the original |
| Audit | Every replay is audit-relevant |
| Evidence | The replay's justification and outcome are documented |

## 6. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably support-tier staffing/hours (a Phase 0.9+ operational-planning decision) and whether a dedicated data-repair tool/interface is built versus relying on reviewed one-off migrations.
