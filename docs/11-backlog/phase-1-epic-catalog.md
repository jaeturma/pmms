# PMMS Phase 1 Epic Catalog

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-1-work-package-catalog.md](phase-1-work-package-catalog.md)

For every epic: ID, name, purpose, architecture sources, included work packages, dependencies, completion outcome, deferred items, risks.

## EPIC-01 — Engineering Foundation and Repository Baseline

- **Purpose:** Establish a verified, reproducible, documented engineering workspace before domain implementation.
- **Architecture sources:** [../10-review/architecture-completeness-assessment.md](../10-review/architecture-completeness-assessment.md), [../04-quality/](../04-quality/), [../05-devops/](../05-devops/).
- **Work packages:** WP-01-01 through WP-01-08.
- **Dependencies:** None (entry point).
- **Completion outcome:** A documented, verified toolchain baseline (versions, quality tools, environment hygiene, Git governance) that every later epic assumes without re-verifying.
- **Deferred items:** CI workflow YAML creation (documented as convention in WP-01-07, created only during that WP's future execution); Docker/infrastructure files.
- **Risks:** RISK-EPIC01-01 (Flutter toolchain absence extends WP-01-05 beyond initial estimate).

## EPIC-02 — Modular Monolith and Application Architecture Foundation

- **Purpose:** Create the foundation for bounded-context-oriented implementation without premature microservices or a generic workflow engine.
- **Architecture sources:** [../01-architecture/](../01-architecture/) (bounded-context and domain architecture), ADR-0002, ADR-0004.
- **Work packages:** WP-02-01 through WP-02-08.
- **Dependencies:** EPIC-01 (WP-01-01).
- **Completion outcome:** A namespace/directory convention, shared-kernel contracts, command/query/event conventions, and architecture-fitness-test readiness that every domain-bearing epic (03–09) builds inside.
- **Deferred items:** Executable fitness-test tooling selection (deptrac/PHPStan custom rules) remains a WP-02-06 open decision, not a hard blocker.
- **Risks:** RISK-EPIC02-01 (namespace convention chosen too early could require rework once first real bounded context is implemented).

## EPIC-03 — Identity, Authentication, and Session Foundation

- **Purpose:** Implement the basic user-account, authentication, and session-security foundation.
- **Architecture sources:** [../01-architecture/identity-model.md](../01-architecture/identity-model.md), [../03-security/](../03-security/), ADR-0003, ADR-0006.
- **Work packages:** WP-03-01 through WP-03-08.
- **Dependencies:** EPIC-02 (WP-02-01, WP-02-05).
- **Completion outcome:** A normalized user-account model built on the existing Fortify baseline, with session security, account status/revocation, service/device identity skeleton, and authentication audit wired to EPIC-06.
- **Deferred items:** Enterprise SSO implementation (explicitly excluded per candidate-WP note); full device-credential rotation cadence (open decision, Phase 0.3).
- **Risks:** RISK-EPIC03-01 (normalizing the existing Fortify `User` model risks breaking passkey/2FA behavior if not carefully regression-tested).

## EPIC-04 — Organization, Meet Context, and Tenant-Ready Ownership Foundation

- **Purpose:** Establish trusted organization and meet context while preserving tenant-ready conventions without prematurely activating full commercial multi-tenancy.
- **Architecture sources:** [../01-architecture/domain-open-decisions.md](../01-architecture/domain-open-decisions.md) (DD-21), [../09-enterprise/tenant-data-ownership-and-isolation-architecture.md](../09-enterprise/tenant-data-ownership-and-isolation-architecture.md), ADR-0012.
- **Work packages:** WP-04-01 through WP-04-09.
- **Dependencies:** EPIC-02 (WP-02-05), EPIC-03 (WP-03-01, for membership).
- **Completion outcome:** Organization/meet records, membership, meet access, trusted context resolution, and propagation into HTTP/Inertia/jobs/events/audit, with tenant-ready ownership conventions and isolation tests.
- **Deferred items:** Billing tenants, subscription tenants, database-per-tenant, custom domains, white-labeling — all explicitly excluded.
- **Risks:** RISK-EPIC04-01 (context-loss-fails-open bug would be a critical tenant-isolation regression; mitigated by WP-04-09).

## EPIC-05 — Role, Permission, Scope, and Assignment Foundation

- **Purpose:** Implement the core authorization model required by all later PMMS modules.
- **Architecture sources:** [../01-architecture/role-catalog.md](../01-architecture/role-catalog.md), [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md), [../01-architecture/scope-model.md](../01-architecture/scope-model.md), [../01-architecture/assignment-model.md](../01-architecture/assignment-model.md), ADR-0003.
- **Work packages:** WP-05-01 through WP-05-12.
- **Dependencies:** EPIC-04 (WP-04-01, WP-04-02, WP-04-03).
- **Completion outcome:** A full, time-valid, scope-aware, non-inheriting authorization model with an Authorization Decision Service, Laravel Policy conventions, explicit-denial/security-hold handling, and separation-of-duties enforcement, backed by an administration UI and a dedicated test matrix.
- **Deferred items:** SOD-01/03/04/09 enforcement for the six policy-blocked modules (Eligibility, Protest, Medal Tally, Medical) — the *mechanism* is built in Phase 1; the *module-specific rule content* remains blocked on OD-07/09/12/15.
- **Risks:** RISK-EPIC05-01 (role/permission-count documentation inconsistency, TD-07/TD-08, could propagate into an incorrect seed if not corrected before WP-05-01).

## EPIC-06 — Audit, Activity History, and Security Event Foundation

- **Purpose:** Create durable and distinguishable audit, activity-history, and security-event capabilities.
- **Architecture sources:** [../03-security/](../03-security/) audit architecture, ADR-0006.
- **Work packages:** WP-06-01 through WP-06-09.
- **Dependencies:** EPIC-02 (WP-02-05).
- **Completion outcome:** Three distinct, non-conflated event models (audit/activity-history/security-event) with full actor/context/correlation metadata, a recording service, a review-interface foundation, retention/access rules, and integrity tests.
- **Deferred items:** Final numeric retention values (PD-04, blocked on PSG-03) — audit is built append-only and retention-ready without them.
- **Risks:** RISK-EPIC06-01 (conflating audit and activity history into one table would violate the architecture's most consistently reinforced distinction).

## EPIC-07 — Reference Data and Configuration Foundation

- **Purpose:** Implement governed reference-data and configuration capabilities used across bounded contexts.
- **Architecture sources:** [../02-data/](../02-data/) reference-data architecture, ADR-0005.
- **Work packages:** WP-07-01 through WP-07-11.
- **Dependencies:** EPIC-02 (WP-02-05), EPIC-04 (WP-04-01, WP-04-02, for org/meet reference types).
- **Completion outcome:** A versioned, activation-aware reference-data architecture with organization/meet/sport/venue/committee skeletons, configuration classification/validation, and feature-flag readiness.
- **Deferred items:** Sport-specific rule content within the sport/event skeleton (skeleton only, no rules).
- **Risks:** RISK-EPIC07-01 (a skeleton table used prematurely to encode a real sport rule would violate working rule 26).

## EPIC-08 — File, Document, and Object Storage Foundation

- **Purpose:** Establish safe file handling and MinIO-compatible object storage without implementing all domain document workflows.
- **Architecture sources:** [../02-data/](../02-data/) object-metadata rules, [../03-security/](../03-security/) file-safety rules, ADR-0005, ADR-0006.
- **Work packages:** WP-08-01 through WP-08-11.
- **Dependencies:** EPIC-01 (WP-01-06), EPIC-02 (WP-02-05), EPIC-05 (WP-05-07, for authorized download).
- **Completion outcome:** Storage-configuration abstraction (local/S3/MinIO-compatible), file-metadata model, temporary upload, validation/classification, tenant-ready object-key conventions, authorized/signed download, versioning, quarantine readiness, and reconciliation readiness.
- **Deferred items:** Selection and integration of a specific malware scanner (WP-08-08 is readiness only).
- **Risks:** RISK-EPIC08-01 (MinIO object-content vs. MySQL-metadata ownership rule being violated by treating object storage as authoritative for anything but content).

## EPIC-09 — Queue, Scheduler, Notification, and Real-Time Foundation

- **Purpose:** Establish controlled asynchronous and real-time infrastructure while preserving MySQL-backed authoritative state.
- **Architecture sources:** [../08-workflows/](../08-workflows/), ADR-0004, ADR-0011.
- **Work packages:** WP-09-01 through WP-09-12.
- **Dependencies:** EPIC-01 (WP-01-01), EPIC-06 (WP-06-01, for notification-triggering audit).
- **Completion outcome:** Redis/Queue/Horizon baseline, naming/routing/idempotency conventions, failed-job governance, scheduler, in-app/email notification foundation, Reverb/broadcast baseline with private-channel authorization, and reconnection/state-refresh behavior.
- **Deferred items:** SMS and push delivery — explicitly not approved for Phase 1 per repository evidence and Phase 0.13 findings (no provider selected, WD-XX open).
- **Risks:** RISK-EPIC09-01 (Redis being treated as authoritative for anything beyond transient/queue/cache state would violate ADR-0004/ADR-0012).

## EPIC-10 — Backend API, Inertia, and Frontend Application Foundation

- **Purpose:** Create consistent server and frontend interaction conventions.
- **Architecture sources:** [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md), ADR-0004.
- **Work packages:** WP-10-01 through WP-10-10.
- **Dependencies:** EPIC-02 (WP-02-07), EPIC-04 (WP-04-06), EPIC-05 (WP-05-07).
- **Completion outcome:** A response/error/validation contract, Inertia shared props with sensitive-prop minimization, a frontend capability contract (usability only, never authoritative), navigation/flash/pagination/form conventions, and state-handling (loading/empty/error/permission-denied) patterns.
- **Deferred items:** None architectural — this epic is foundation-only by nature.
- **Risks:** RISK-EPIC10-01 (frontend capability flags being mistaken for authorization would violate Section 13's "never rely on frontend visibility" rule).

## EPIC-11 — PMMS Arena Web Design System Foundation

- **Purpose:** Implement the minimum reusable React design-system foundation required for later modules.
- **Architecture sources:** [../06-design/](../06-design/), ADR-0009.
- **Work packages:** WP-11-01 through WP-11-13.
- **Dependencies:** EPIC-01 (WP-01-03), EPIC-10 (WP-10-02, WP-10-05, WP-10-07).
- **Completion outcome:** Design tokens, light/dark/semantic themes, typography/iconography, application shell/navigation, context switcher, layout, form/button/table/dialog/feedback/empty-state components, and an accessibility/keyboard baseline.
- **Deferred items:** Every future sport-specific component (brackets, heat sheets, scoreboards) — explicitly not attempted here; branding-palette final approval (DX-02) — a neutral placeholder palette is used pending approval.
- **Risks:** RISK-EPIC11-01 (WCAG target unresolved, DX-01, could require rework of WP-11-12 if the eventual target exceeds the provisional AA assumption).

## EPIC-12 — Flutter Application Foundation

- **Purpose:** Prepare the Flutter application for later field, device, offline, and QR workflows.
- **Architecture sources:** [../01-architecture/flutter-architecture.md](../01-architecture/flutter-architecture.md), [../06-design/](../06-design/) cross-platform sections, ADR-0009.
- **Work packages:** WP-12-01 through WP-12-12.
- **Dependencies:** EPIC-01 (WP-01-05), EPIC-03 (WP-03-02), EPIC-10 (WP-10-01), EPIC-11 (WP-11-01).
- **Completion outcome:** A Flutter project (currently absent from the repository) with architecture/feature-folder conventions, environment/API configuration, secure storage, an authentication shell, an assignment-aware home skeleton, a PMMS Arena theme, connectivity-state handling, offline-store and sync-queue architectural skeletons, and logging/error handling.
- **Deferred items:** Full mobile operational modules (scanning, accreditation, scoring field capture) — skeleton only.
- **Risks:** RISK-EPIC12-01 (`mobile/` does not exist in the repository — WP-12-01 must create the project from scratch, a materially larger effort than "verification" for the other EPIC-01/EPIC-12 baseline work packages).

## EPIC-13 — Observability, Health, and Operational Readiness Foundation

- **Purpose:** Create the minimum diagnostics and operational visibility required before feature modules.
- **Architecture sources:** [../05-devops/](../05-devops/), ADR-0008.
- **Work packages:** WP-13-01 through WP-13-10.
- **Dependencies:** EPIC-02 (WP-02-08).
- **Completion outcome:** Structured logging, correlation IDs, health/readiness/dependency-health endpoints (app, queue, scheduler, Reverb, storage), safe error reporting, and an operational diagnostics interface.
- **Deferred items:** Any claim of production monitoring or SLA/service-level compliance.
- **Risks:** RISK-EPIC13-01 (health endpoints exposing internal diagnostic detail publicly would itself be a security/privacy finding).

## EPIC-14 — Data Protection, Privacy, and Secure Development Foundation

- **Purpose:** Implement cross-cutting safeguards before sensitive PMMS modules are built.
- **Architecture sources:** [../03-security/](../03-security/), ADR-0006.
- **Work packages:** WP-14-01 through WP-14-10.
- **Dependencies:** EPIC-02 (WP-02-02).
- **Completion outcome:** Data-classification constants, sensitive-data masking, log redaction, secure-export readiness, rate limiting, security-header/cookie review, CSRF/session/request-protection review, secret hygiene checks, and support/impersonation restriction, closed out with regression tests.
- **Deferred items:** None — this epic is itself the cross-cutting safeguard layer other epics depend on.
- **Risks:** RISK-EPIC14-01 (a minor-athlete or medical-data logging shortcut introduced by a later epic, per ARR-10 in the Phase 0.13 risk register, would be caught here only if WP-14-10 is genuinely exhaustive).

## EPIC-15 — Foundation Integration, Validation, and Release Readiness

- **Purpose:** Verify that all Phase 1 foundation components work together before sports modules begin.
- **Architecture sources:** [../10-review/](../10-review/) (entire Phase 0.13 corpus, as the review methodology template).
- **Work packages:** WP-15-01 through WP-15-12.
- **Dependencies:** All prior epics (EPIC-01 through EPIC-14).
- **Completion outcome:** A Phase 1 Foundation Sign-Off Package modeled on [../10-review/phase-0-final-architecture-signoff.md](../10-review/phase-0-final-architecture-signoff.md) — evidence-gated, no fabricated approval.
- **Deferred items:** Sports-module readiness assessment (a future phase's responsibility, not this epic's).
- **Risks:** RISK-EPIC15-01 (declaring foundation "Complete" without genuine evidence would repeat the exact failure mode Phase 0.13 was created to prevent).
