# EPIC-12 — Flutter Application Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release E
**Status:** Planned — Not Started

## Purpose

Prepare the Flutter application for later field, device, offline, and QR workflows. The repository currently has **no `mobile/` directory at all** — this epic creates the Flutter project from scratch, unlike other epics that extend an existing starter kit.

## Architecture Sources

[../../../../01-architecture/flutter-architecture.md](../../../../01-architecture/flutter-architecture.md), [../../../../06-design/](../../../../06-design/) (cross-platform sections), ADR-0009.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-12-01](WP-12-01-flutter-project-baseline-verification.md) | Flutter Project Baseline Verification | Medium | P1 |
| [WP-12-02](WP-12-02-flutter-architecture-and-feature-folder-foundation.md) | Flutter Architecture and Feature Folder Foundation | Medium | P1 |
| [WP-12-03](WP-12-03-flutter-environment-and-api-configuration-foundation.md) | Flutter Environment and API Configuration Foundation | Medium | P1 |
| [WP-12-04](WP-12-04-secure-storage-foundation.md) | Secure Storage Foundation | Medium | P1 |
| [WP-12-05](WP-12-05-authentication-shell-foundation.md) | Authentication Shell Foundation | Medium | P1 |
| [WP-12-06](WP-12-06-assignment-aware-mobile-home-skeleton.md) | Assignment-Aware Mobile Home Skeleton | Medium | P2 |
| [WP-12-07](WP-12-07-pmms-arena-flutter-theme-foundation.md) | PMMS Arena Flutter Theme Foundation | Medium | P1 |
| [WP-12-08](WP-12-08-mobile-network-and-connectivity-state-foundation.md) | Mobile Network and Connectivity State Foundation | Medium | P1 |
| [WP-12-09](WP-12-09-offline-data-store-readiness.md) | Offline Data Store Readiness | Large | P2 |
| [WP-12-10](WP-12-10-sync-queue-architectural-skeleton.md) | Sync Queue Architectural Skeleton | Large | P2 |
| [WP-12-11](WP-12-11-flutter-logging-and-error-handling.md) | Flutter Logging and Error Handling | Small | P1 |
| [WP-12-12](WP-12-12-flutter-test-foundation.md) | Flutter Test Foundation | Medium | P2 |

## Dependencies

WP-01-05 (Hard), WP-03-02, WP-10-01, WP-11-01 (Hard).

## Completion Outcome

A Flutter project (currently absent) with architecture/feature-folder conventions, environment/API configuration, secure storage, an authentication shell, an assignment-aware home skeleton, a PMMS Arena theme, connectivity-state handling, offline-store and sync-queue architectural skeletons, and logging/error handling.

## Deferred Items

Full mobile operational modules (scanning, accreditation, scoring field capture) — skeleton only.

## Risks

RISK-EPIC12-01 — `mobile/` does not exist in the repository, so WP-12-01 must create the project from scratch, a materially larger effort than "verification" for the other EPIC-01/EPIC-12 baseline work packages.
