# EPIC-04 — Organization, Meet Context, and Tenant-Ready Ownership Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release B
**Status:** Planned — Not Started

## Purpose

Establish trusted organization and meet context while preserving tenant-ready conventions without prematurely activating full commercial multi-tenancy, per DD-21 and ADR-0012.

## Architecture Sources

[../../../../01-architecture/domain-open-decisions.md](../../../../01-architecture/domain-open-decisions.md) (DD-21), [../../../../09-enterprise/tenant-data-ownership-and-isolation-architecture.md](../../../../09-enterprise/tenant-data-ownership-and-isolation-architecture.md), ADR-0012.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-04-01](WP-04-01-organization-hierarchy-foundation.md) | Organization Hierarchy Foundation | Medium | P1 |
| [WP-04-02](WP-04-02-meet-record-and-lifecycle-foundation.md) | Meet Record and Lifecycle Foundation | Medium | P1 |
| [WP-04-03](WP-04-03-user-organization-membership-foundation.md) | User Organization Membership Foundation | Medium | P1 |
| [WP-04-04](WP-04-04-user-meet-access-foundation.md) | User Meet Access Foundation | Medium | P1 |
| [WP-04-05](WP-04-05-trusted-context-resolution.md) | Trusted Context Resolution | Medium | P1 |
| [WP-04-06](WP-04-06-context-propagation-for-http-and-inertia.md) | Context Propagation for HTTP and Inertia | Medium | P1 |
| [WP-04-07](WP-04-07-context-propagation-for-jobs-events-and-audit.md) | Context Propagation for Jobs, Events, and Audit | Medium | P1 |
| [WP-04-08](WP-04-08-tenant-ready-ownership-and-isolation-conventions.md) | Tenant-Ready Ownership and Isolation Conventions | Medium | P1 |
| [WP-04-09](WP-04-09-cross-context-isolation-tests.md) | Cross-Context Isolation Tests | Medium | P2 |

## Dependencies

WP-02-05 (Hard), WP-03-01 (Hard, for membership).

## Completion Outcome

Organization/meet records, membership, meet access, trusted context resolution, and propagation into HTTP/Inertia/jobs/events/audit, with tenant-ready ownership conventions and isolation tests.

## Deferred Items

Billing tenants, subscription tenants, database-per-tenant, custom domains, white-labeling — all explicitly excluded.

## Risks

RISK-EPIC04-01 — context-loss-fails-open bug would be a critical tenant-isolation regression (RISK-GENERAL-10); mitigated by WP-04-09.
