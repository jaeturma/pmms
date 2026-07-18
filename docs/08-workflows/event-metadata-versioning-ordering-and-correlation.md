# PMMS Event Metadata, Versioning, Ordering, and Correlation

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [event-taxonomy-ownership-and-contracts.md](event-taxonomy-ownership-and-contracts.md) · [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md)

---

## 1. Event Metadata (Conceptual)

Candidate metadata fields for every event: Event ID · Event type · Event version · Occurred at · Recorded at · Owning context · Aggregate type · Aggregate ID · Public ID where appropriate · Actor ID · Actor type · Effective user · Organization ID · Meet ID · Scope · Correlation ID · Causation ID · Idempotency key · Classification · Source service · Trace reference.

**No physical field is defined yet** — restated per this phase's own working instruction; this is a conceptual metadata contract, not a database schema or event-class definition.

## 2. Event Schema Versioning

| Element | Direction |
|---|---|
| Version identifier | Every event contract carries an explicit version (e.g., `ResultCertified.v1`) |
| Additive changes | A new optional field is a non-breaking, additive change |
| Breaking changes | Removing a field, changing a field's meaning, or renaming a field is breaking and requires a new version |
| Consumer compatibility | Consumers declare which version(s) they support; a producer does not assume every consumer has upgraded simultaneously |
| Deprecation | A deprecated event version is announced with a migration window before removal |
| Migration | A breaking change requires an explicit migration plan for existing consumers and any replay tooling |
| Replay compatibility | A replayed historical event must still be interpretable by current consumers, or the replay tooling must translate it |
| Documentation | Every event version's contract is documented alongside [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md) |
| Contract testing | Producer/consumer contract tests verify compatibility before a version change ships, per [../04-quality/api-contract-and-integration-testing.md](../04-quality/api-contract-and-integration-testing.md) |

**Never change the meaning of an existing event silently** — restated absolutely as this section's governing rule, directly extending working rule 51 ("workflow changes must be versioned") to event contracts specifically.

## 3. Correlation and Causation

| ID Type | Purpose |
|---|---|
| Correlation ID | Groups every command, event, notification, and audit record belonging to one business process instance |
| Causation ID | Identifies what specific command or event triggered this event — enabling a causal chain reconstruction |
| Request ID | Transport-level diagnostics for a single HTTP/queue-job execution, distinct from the business-level correlation ID |
| Workflow-instance ID | Identifies a specific long-running process instance, per [business-process-and-state-machine-architecture.md, Section 3](business-process-and-state-machine-architecture.md#3-long-running-workflow-architecture) |
| Trace ID readiness | A placeholder for future distributed-tracing integration, per [../05-devops/observability-logging-metrics-tracing-and-alerting.md](../05-devops/observability-logging-metrics-tracing-and-alerting.md) |

## 4. Event Ordering

**Do not assume global ordering** — restated absolutely; PMMS defines ordering requirements per stream or aggregate where necessary, using:

Aggregate version · sequence number · occurred time · recorded time · consumer deduplication.

**Consumers must tolerate delayed or duplicate messages** — restated as this section's governing rule, extending [../01-architecture/laravel-architecture.md, Section 5](../01-architecture/laravel-architecture.md#5-domain-event-architecture)'s idempotent-consumer rule. A consumer that assumes strict global delivery order will produce incorrect results under normal queue/network conditions.

## 5. Event Persistence, Retention, and Replay

| Element | Direction |
|---|---|
| Event persistence | Domain events feeding a high-integrity workflow are persisted durably, evaluated via the outbox pattern in [outbox-inbox-idempotency-and-message-reliability.md](outbox-inbox-idempotency-and-message-reliability.md) |
| Event retention | Retention period is a placeholder pending DepEd/legal input, restated from [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) — no numeric value invented here |
| Event replay | Replay is a candidate operational capability (reprocessing a consumer against historical events after a bug fix) — evaluated, not committed to a specific mechanism in this phase |
| Replay authorization | Replay is a privileged, audited operation, never available to an ordinary application user |
| Replay safety | Replay must not re-trigger side effects that are not themselves idempotent (e.g., replaying `ResultCertified` must not re-send a notification already delivered) |

## 6. Event Privacy and Classification

Every event inherits the data-classification tier (Public/Internal/Confidential/Restricted/Highly Restricted) of the data it references, per [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md) — restated absolutely, an event is never classified more permissively than its underlying data.

### Sensitive-Event Restrictions

| Category | Restriction |
|---|---|
| Medical events | Event payloads never carry clinical detail — an identifier and status reference only, restated from [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md) |
| Eligibility events | Event payloads reference the case, never the underlying eligibility-evidence document content |
| Financial events | Event payloads reference the transaction, never full account/payment-instrument detail |
| Authentication events | Never emitted as a general-purpose domain event with credential content — authentication events are Security/Audit events only, per [../03-security/audit-and-security-event-architecture.md](../03-security/audit-and-security-event-architecture.md) |
| Minor-athlete events | Subject to the same enhanced protection as any other minor-athlete data reference, restated from [../03-security/minor-athlete-and-guardian-data-governance.md](../03-security/minor-athlete-and-guardian-data-governance.md) |

## 7. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-06 (event retention numeric values, blocked on the same unresolved retention decisions as every prior phase) and WD-07 (event-replay tooling and authorization model timing).
