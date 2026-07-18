# PMMS Retention, Disposal, and Legal-Hold Governance

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) · [../02-data/data-open-decisions.md, PD-04](../02-data/data-open-decisions.md#pd-04--retention-periods-8-categories) · [data-governance-operating-model.md](data-governance-operating-model.md)

This document adds governance process around Phase 0.5's retention categories and defines legal/operational hold and test-data governance. **No retention period, disposal schedule, or legal-hold policy is finalized here — every period remains a placeholder, per working rule 31.**

---

## Retention Governance

Preserving the 15 retention categories from [../02-data/retention-archival-and-disposal.md, Section 1](../02-data/retention-archival-and-disposal.md#1-retention-categories) unchanged, this document adds the governance process around them:

| Governance Element | Direction |
|---|---|
| Retention owner | The bounded-context data owner (per [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md)) is accountable for their category's retention correctness |
| Policy source | Every category's eventual retention period cites a specific policy source, tracked in [policy-source-registry.md](policy-source-registry.md) — no period is set without one |
| Start event | The event that begins a retention clock (e.g., meet closure, record creation, last access) is explicitly identified per category |
| Retention period placeholder | Restated absolutely — every numeric value in [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) remains `Placeholder` pending DepEd records-management and legal input |
| Review | Retention assignments are periodically reviewed for continued appropriateness, not set once and forgotten |
| Hold | A legal/operational hold suspends the retention clock entirely, per the section below |
| Archive | Archival (per [../02-data/retention-archival-and-disposal.md, Section 2](../02-data/retention-archival-and-disposal.md#2-archiving)) precedes disposal for categories with archival value |
| Disposal | Disposal occurs only after retention period expiry, hold-clearance, and archival-completion (where applicable) — never as a default "delete when convenient" action |
| Deletion evidence | A disposal action itself produces an audit-relevant record — evidence that disposal occurred, when, and under what authority, without retaining the disposed data itself |
| Backup expiry | Backup copies of disposed data are themselves subject to their own retention/disposal cycle, not indefinitely retained after the source record's disposal |
| Exception handling | A retention exception (e.g., data needed longer than the category's default for a specific documented reason) is itself approved and audited, not a silent deviation |
| Periodic review | The full retention-category table is periodically reviewed as policy sources become available, converting placeholders to validated values over time |

## Legal and Operational Holds

| Element | Direction |
|---|---|
| Hold reason | Every hold records why it was placed (e.g., pending investigation, pending dispute, pending formal legal request) |
| Scope | A hold specifies exactly which records/categories it covers — never an unbounded "hold everything" default |
| Authority | A hold is placed only by a designated authority (Data owner + Security owner, or a specifically escalated authority for a genuine legal hold) |
| Start | The hold's effective start time is recorded |
| End | The hold's release requires an explicit action by the same or a higher authority — it does not expire silently |
| Affected data | The specific records/categories under hold are identifiable at any time |
| Deletion suspension | Retention-driven disposal is suspended for held data, absolutely, regardless of the category's normal retention period having otherwise elapsed |
| Archive behavior | Held data may still be archived (moved to a lower-activity store) but is never disposed while the hold is active |
| Access | Held data's access controls remain governed by its normal classification — a hold changes deletion behavior, not access rules |
| Review | Active holds are periodically reviewed for continued necessity |
| Release | Releasing a hold is itself an audited, authorized action |
| Audit | Every hold placement, modification, and release is audit-relevant |
| Backup implications | A hold on a record extends to its backup copies as well — a backup is not a loophole around an active hold |

**Per working rule, `legal hold` is used here only as a generic architecture concept** — PMMS's architecture supports the capability; whether and when a specific hold qualifies as a formal legal requirement is a determination for Data Privacy and Legal Stakeholders, not this documentation.

## Test and Lower-Environment Data Governance

Extends [../02-data/test-seed-and-reference-data-strategy.md, Section 3](../02-data/test-seed-and-reference-data-strategy.md#3-test-data-strategy) with governance-specific controls:

| Control | Direction |
|---|---|
| Synthetic data by default | Restated absolutely — every Local/Development/Staging dataset is synthetic unless a specific, approved exception exists |
| No casual production copies | A "just copy prod down for testing" practice is explicitly prohibited |
| Formal masking process | Any approved production-derived lower-environment use requires the masking process in [../02-data/audit-and-security-data-architecture.md, Section 4](../02-data/audit-and-security-data-architecture.md#4-data-masking-and-redaction), with named approval, per [../02-data/test-seed-and-reference-data-strategy.md, Section 3](../02-data/test-seed-and-reference-data-strategy.md#3-test-data-strategy) |
| Restricted backup restoration | Restoring a production backup into a lower environment is treated as a production-data-copy event, subject to the same formal masking/approval requirement |
| Lower-environment access | Access to lower environments follows the same least-privilege discipline as production, scaled to the lower environment's actual risk |
| Secret separation | Lower-environment secrets are distinct from production secrets, per [cryptography-key-and-secret-management.md, Section 3](cryptography-key-and-secret-management.md#3-secret-management) |
| External integration disabling | Lower environments do not call real external services (payment, notification-delivery) — restated from [../01-architecture/environment-and-configuration-model.md](../01-architecture/environment-and-configuration-model.md) |
| Email and SMS suppression | Lower-environment notification delivery is suppressed or redirected to a safe test destination, never sent to a real recipient |
| Public exposure prevention | Lower environments are not publicly indexed/discoverable |
| Retention | Lower-environment data has its own (typically shorter) retention, independent of production retention categories |
| Disposal | Lower-environment data is disposed of routinely as part of environment refresh cycles |
| Test-user management | Test accounts are clearly distinguishable from real accounts and are not usable to access production data |
| Audit for production-data use | Any approved production-data-derived use in a lower environment is itself an audited, time-bounded exception, not a standing practice |

## Open Questions

See [security-open-decisions.md](security-open-decisions.md) and [../02-data/data-open-decisions.md, PD-04](../02-data/data-open-decisions.md#pd-04--retention-periods-8-categories) — retention periods remain the single largest blocking dependency across both the data and security/governance packages.
