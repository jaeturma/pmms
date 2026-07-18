# EPIC-10 — Backend API, Inertia, and Frontend Application Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release D
**Status:** Planned — Not Started

## Purpose

Create consistent server and frontend interaction conventions — response/error contracts, Inertia shared props with sensitive-prop minimization, a frontend capability contract that is never treated as authoritative authorization, and state-handling patterns.

## Architecture Sources

[../../../../01-architecture/api-and-client-boundaries.md](../../../../01-architecture/api-and-client-boundaries.md), ADR-0004.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-10-01](WP-10-01-api-response-and-error-contract-foundation.md) | API Response and Error Contract Foundation | Medium | P1 |
| [WP-10-02](WP-10-02-validation-error-contract-foundation.md) | Validation Error Contract Foundation | Small | P1 |
| [WP-10-03](WP-10-03-inertia-shared-props-and-context-foundation.md) | Inertia Shared Props and Context Foundation | Medium | P1 |
| [WP-10-04](WP-10-04-sensitive-prop-minimization-rules.md) | Sensitive Prop Minimization Rules | Small | P1 |
| [WP-10-05](WP-10-05-frontend-permission-and-capability-contract.md) | Frontend Permission and Capability Contract | Medium | P1 |
| [WP-10-06](WP-10-06-inertia-navigation-and-flash-message-foundation.md) | Inertia Navigation and Flash Message Foundation | Small | P1 |
| [WP-10-07](WP-10-07-pagination-filtering-and-sorting-contract.md) | Pagination, Filtering, and Sorting Contract | Medium | P1 |
| [WP-10-08](WP-10-08-form-submission-and-conflict-handling-foundation.md) | Form Submission and Conflict Handling Foundation | Medium | P1 |
| [WP-10-09](WP-10-09-loading-empty-error-and-permission-denied-state-foundation.md) | Loading, Empty, Error, and Permission-Denied State Foundation | Medium | P1 |
| [WP-10-10](WP-10-10-frontend-integration-tests.md) | Frontend Integration Tests | Medium | P2 |

## Dependencies

WP-02-07 (Hard), WP-04-06 (Hard), WP-05-07 (Hard).

## Completion Outcome

A response/error/validation contract, Inertia shared props with sensitive-prop minimization, a frontend capability contract (usability only, never authoritative), navigation/flash/pagination/form conventions, and state-handling (loading/empty/error/permission-denied) patterns.

## Deferred Items

None architectural — this epic is foundation-only by nature.

## Risks

RISK-EPIC10-01 — frontend capability flags being mistaken for authorization would violate the "never rely on frontend visibility" rule.
