# PMMS Event Taxonomy, Ownership, and Contracts

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/laravel-architecture.md, Section 5](../01-architecture/laravel-architecture.md#5-domain-event-architecture) · [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md)

---

## 1. Event Taxonomy (Six Types)

Restated from [../01-architecture/laravel-architecture.md, "Event Type Distinctions"](../01-architecture/laravel-architecture.md#5-domain-event-architecture), given its full Phase 0.11 architecture:

| Event Type | Purpose | Example |
|---|---|---|
| Domain event | A completed business fact within a bounded context | `ResultCertified` |
| Application event | A technical signal within the Application layer coordinating a use case | Not business-meaningful outside the use case itself |
| Integration event | A domain event translated for consumption by another bounded context or external system, via an explicit contract | `ResultCertified` translated into Medal Tally's consumer payload |
| Broadcast event (real-time) | A transient, non-durable message pushed to a Reverb channel | A live scoreboard tick — see [realtime-broadcast-and-reverb-message-architecture.md](realtime-broadcast-and-reverb-message-architecture.md) |
| Audit event | An immutable record of actor/action/target/reason/timestamp | Every Critical/Elevated-audit-level command execution |
| Notification event | A trigger for a message to a human recipient | `EligibilityRequirementsSubmitted` triggering a notice to the reviewer |

**These categories must not be used interchangeably** — restated absolutely. A single domain event may simultaneously trigger an integration event, a broadcast event, an audit event, and a notification event, but the domain event itself is none of these; it is the source fact all four derive from, per [../01-architecture/laravel-architecture.md, Section 5](../01-architecture/laravel-architecture.md#5-domain-event-architecture).

### Security Events (Seventh, Cross-Referenced Category)

A security event (per [../03-security/audit-and-security-event-architecture.md, Section 8](../03-security/audit-and-security-event-architecture.md#8-security-events)) is a specialized audit event carrying elevated review/alerting requirements — not a separate taxonomy branch, but a classification applied to specific audit events (authentication anomalies, authorization denials, privilege changes, break-glass use).

## 2. Domain Event Naming and Ownership

Domain events express completed business facts, named in past tense (`ResultCertified`, not `CertifyResult`) — the full catalog is [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md), organized by owning bounded context. **Domain events originate from their owning bounded context only** — no context emits an event on another context's behalf, restated absolutely.

Phase 0.11 does not add new domain events beyond what [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md) already catalogs — it defines the metadata, versioning, delivery, and reliability architecture those events use once implemented (Sections 3–8 of this document and [event-metadata-versioning-ordering-and-correlation.md](event-metadata-versioning-ordering-and-correlation.md)).

## 3. Event Payload Rules

**Event payloads must not include unnecessary sensitive data** — restated absolutely per working rule 47, extending [../01-architecture/laravel-architecture.md, Section 5](../01-architecture/laravel-architecture.md#5-domain-event-architecture)'s existing rule ("a payload carries identifiers and minimal necessary context — e.g., `ScoreRecorded` carries a score-record ID, not the full participant biographical record"). Queue payloads specifically use identifiers and safe references (working rule 48) — never a large document, medical record, financial detail, or authentication credential.

## 4. Event Ownership Table (Illustrative, Cross-Referencing the Existing Catalog)

| Event (from [domain-events-catalog.md](../01-architecture/domain-events-catalog.md)) | Owning Context | Consistency | Notification-Worthy |
|---|---|---|---|
| `EligibilityApproved` | BC-09 Eligibility and Clearance | Strong | Yes |
| `CompetitionEntryConfirmed` | BC-11 Competition Entries | Strong | Yes |
| `ScoreRecorded` | BC-15 Scoring | Strong | No |
| `ResultCertified` | BC-16 Official Results | Strong | Yes |
| `ProtestFiled` | BC-17 Protest and Appeals | Strong | Yes |
| `MedalTallyRecalculated` | BC-18 Medal Tally and Team Standings | Strong | Yes |
| `AccreditationRevoked` | BC-19 Accreditation | Strong (offline-priority) | Yes |
| `AnnouncementPublished` | BC-28 Media and Communications | Eventual | Yes |

Full 30+ event list with per-event consistency, audit, notification, public, offline, and idempotency-concern columns: [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md).

## 5. Event Consumers

Event consumers never mutate the source aggregate directly — a consumer reacting to `ResultCertified` calls its own context's Application-layer use case (e.g., Medal Tally's `RecalculateMedalTally` command), never reaches back into Official Results' tables, restated from [../01-architecture/laravel-architecture.md, Section 5](../01-architecture/laravel-architecture.md#5-domain-event-architecture). Consumers must be idempotent (Section 5, [outbox-inbox-idempotency-and-message-reliability.md](outbox-inbox-idempotency-and-message-reliability.md)).

## 6. Event Naming Conventions

Past-tense, PascalCase, business-meaningful (`ResultCertified`, not `UpdateStatus` or `Event1`) — restated from the existing catalog's convention. No numeric event-ID scheme is introduced (consistent with the existing catalog, which identifies events purely by name); Phase 0.11 instead versions each named event's *contract* (Section 5, [event-metadata-versioning-ordering-and-correlation.md](event-metadata-versioning-ordering-and-correlation.md)).

## 7. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-05 (whether a numeric event-ID scheme is ever introduced alongside the existing name-based catalog, for tooling/traceability purposes).
