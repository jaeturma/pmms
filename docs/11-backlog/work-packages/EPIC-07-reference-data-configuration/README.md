# EPIC-07 — Reference Data and Configuration Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release C
**Status:** Planned — Not Started

## Purpose

Implement governed reference-data and configuration capabilities used across bounded contexts — organization/meet/sport/venue/committee reference-type skeletons, versioning/activation, configuration classification, and feature-flag readiness — without implementing sport-specific rules.

## Architecture Sources

[../../../../02-data/](../../../../02-data/), ADR-0005.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-07-01](WP-07-01-reference-data-architecture-foundation.md) | Reference Data Architecture Foundation | Medium | P1 |
| [WP-07-02](WP-07-02-organization-reference-types.md) | Organization Reference Types | Small | P1 |
| [WP-07-03](WP-07-03-meet-reference-types.md) | Meet Reference Types | Small | P1 |
| [WP-07-04](WP-07-04-sport-and-event-reference-skeleton.md) | Sport and Event Reference Skeleton | Medium | P1 |
| [WP-07-05](WP-07-05-venue-reference-skeleton.md) | Venue Reference Skeleton | Small | P1 |
| [WP-07-06](WP-07-06-committee-reference-skeleton.md) | Committee Reference Skeleton | Small | P1 |
| [WP-07-07](WP-07-07-reference-data-versioning-and-activation.md) | Reference Data Versioning and Activation | Medium | P1 |
| [WP-07-08](WP-07-08-configuration-classification-and-validation.md) | Configuration Classification and Validation | Medium | P1 |
| [WP-07-09](WP-07-09-feature-flag-readiness.md) | Feature Flag Readiness | Small | P1 |
| [WP-07-10](WP-07-10-reference-data-administration-ui-foundation.md) | Reference Data Administration UI Foundation | Medium | P2 |
| [WP-07-11](WP-07-11-reference-data-tests.md) | Reference Data Tests | Medium | P2 |

## Dependencies

WP-02-05 (Hard), WP-04-01, WP-04-02 (Hard, for org/meet reference types).

## Completion Outcome

A versioned, activation-aware reference-data architecture with organization/meet/sport/venue/committee skeletons, configuration classification/validation, and feature-flag readiness.

## Deferred Items

Sport-specific rule content within the sport/event skeleton (skeleton only, no rules).

## Risks

RISK-EPIC07-01 — a skeleton table used prematurely to encode a real sport rule would violate working rule 26.
