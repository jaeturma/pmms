# EPIC-05 — Role, Permission, Scope, and Assignment Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release B
**Status:** Planned — Not Started

## Purpose

Implement the core authorization model required by all later PMMS modules: role catalog, permission catalog, scope model, assignment model, and an Authorization Decision Service applying the full authority formula (role + permission + scope + assignment), never a role-only shortcut.

## Architecture Sources

[../../../../01-architecture/role-catalog.md](../../../../01-architecture/role-catalog.md), [../../../../01-architecture/permission-catalog.md](../../../../01-architecture/permission-catalog.md), [../../../../01-architecture/scope-model.md](../../../../01-architecture/scope-model.md), [../../../../01-architecture/assignment-model.md](../../../../01-architecture/assignment-model.md), ADR-0003.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-05-01](WP-05-01-role-catalog-foundation.md) | Role Catalog Foundation | Medium | P1 |
| [WP-05-02](WP-05-02-permission-catalog-foundation.md) | Permission Catalog Foundation | Medium | P1 |
| [WP-05-03](WP-05-03-role-permission-assignment-foundation.md) | Role-Permission Assignment Foundation | Medium | P1 |
| [WP-05-04](WP-05-04-scope-model-foundation.md) | Scope Model Foundation | Medium | P1 |
| [WP-05-05](WP-05-05-assignment-model-foundation.md) | Assignment Model Foundation | Large | P1 |
| [WP-05-06](WP-05-06-time-valid-assignment-rules.md) | Time-Valid Assignment Rules | Medium | P1 |
| [WP-05-07](WP-05-07-authorization-decision-service.md) | Authorization Decision Service | Large | P1 |
| [WP-05-08](WP-05-08-laravel-policies-and-application-authorization-conventions.md) | Laravel Policies and Application Authorization Conventions | Medium | P1 |
| [WP-05-09](WP-05-09-explicit-denial-and-security-hold-foundation.md) | Explicit Denial and Security Hold Foundation | Medium | P1 |
| [WP-05-10](WP-05-10-separation-of-duties-foundation.md) | Separation-of-Duties Foundation | Medium | P1 |
| [WP-05-11](WP-05-11-permission-and-assignment-administration-ui-foundation.md) | Permission and Assignment Administration UI Foundation | Large | P2 |
| [WP-05-12](WP-05-12-authorization-test-matrix.md) | Authorization Test Matrix | Large | P2 |

## Dependencies

WP-04-01, WP-04-02, WP-04-03 (Hard).

## Completion Outcome

A full, time-valid, scope-aware, non-inheriting authorization model with an Authorization Decision Service, Laravel Policy conventions, explicit-denial/security-hold handling, and separation-of-duties enforcement, backed by an administration UI and a dedicated test matrix.

## Deferred Items

SOD-01/03/04/09 enforcement for the six policy-blocked modules — the mechanism is built in Phase 1; the module-specific rule content remains blocked on OD-07/09/12/15.

## Risks

RISK-EPIC05-01 — the role/permission-count documentation inconsistency (TD-07/TD-08 from Phase 0.13) could propagate into an incorrect seed if not corrected before WP-05-01.
