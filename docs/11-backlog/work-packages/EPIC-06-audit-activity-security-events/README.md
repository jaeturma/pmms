# EPIC-06 — Audit, Activity History, and Security Event Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release C
**Status:** Planned — Not Started

## Purpose

Create durable and distinguishable audit, activity-history, and security-event capabilities — three related but non-conflated event models, per ADR-0006's most consistently reinforced data rule.

## Architecture Sources

[../../../../03-security/](../../../../03-security/), ADR-0006.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-06-01](WP-06-01-audit-event-model-foundation.md) | Audit Event Model Foundation | Medium | P1 |
| [WP-06-02](WP-06-02-activity-history-model-foundation.md) | Activity History Model Foundation | Medium | P1 |
| [WP-06-03](WP-06-03-security-event-model-foundation.md) | Security Event Model Foundation | Medium | P1 |
| [WP-06-04](WP-06-04-actor-effective-user-device-context-and-correlation-metadata.md) | Actor, Effective User, Device, Context, and Correlation Metadata | Medium | P1 |
| [WP-06-05](WP-06-05-sensitive-view-and-export-audit-conventions.md) | Sensitive View and Export Audit Conventions | Medium | P1 |
| [WP-06-06](WP-06-06-audit-recording-service.md) | Audit Recording Service | Large | P1 |
| [WP-06-07](WP-06-07-audit-query-and-review-interface-foundation.md) | Audit Query and Review Interface Foundation | Medium | P2 |
| [WP-06-08](WP-06-08-audit-retention-and-access-rules.md) | Audit Retention and Access Rules | Small | P1 |
| [WP-06-09](WP-06-09-audit-integrity-and-coverage-tests.md) | Audit Integrity and Coverage Tests | Medium | P2 |

## Dependencies

WP-02-05 (Hard).

## Completion Outcome

Three distinct, non-conflated event models (audit/activity-history/security-event) with full actor/context/correlation metadata, a recording service, a review-interface foundation, retention/access rules, and integrity tests.

## Deferred Items

Final numeric retention values (PD-04, blocked on PSG-03) — audit is built append-only and retention-ready without them.

## Risks

RISK-EPIC06-01 — conflating audit and activity history into one table would violate the architecture's most consistently reinforced data rule.
