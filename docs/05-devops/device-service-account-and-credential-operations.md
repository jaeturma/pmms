# PMMS Device, Service-Account, and Credential Operations

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md) · [../03-security/mobile-device-and-offline-security.md](../03-security/mobile-device-and-offline-security.md) · [../03-security/cryptography-key-and-secret-management.md, Section 3](../03-security/cryptography-key-and-secret-management.md#3-secret-management)

This document operationalizes device-fleet management, service-account lifecycle, and credential-rotation operations. **No device is provisioned and no credential is issued by this document.**

---

## 1. Device Operations

| Element | Direction |
|---|---|
| Procurement or approved inventory | Devices are sourced from an approved inventory list, not ad hoc personal-device use for scanning/scoring roles |
| Registration | Per [../01-architecture/device-and-service-identity-model.md, Section 2](../01-architecture/device-and-service-identity-model.md#2-per-device-identity-fields-conceptual) |
| Naming | A consistent naming convention (e.g., "Gate 1 Scanner," per the existing example in Phase 0.3) supporting operational clarity |
| Credential issuance | Per [../03-security/mobile-device-and-offline-security.md, Section 2](../03-security/mobile-device-and-offline-security.md#2-device-and-scanner-security) |
| Assignment | Meet + venue + purpose, restated from Phase 0.3 |
| Configuration | Device-specific settings (venue assignment, operational mode) set at provisioning |
| Update | Software/firmware updates follow [patch-vulnerability-and-dependency-operations.md](patch-vulnerability-and-dependency-operations.md) |
| Health | Monitored per [observability-logging-metrics-tracing-and-alerting.md, Section 10](observability-logging-metrics-tracing-and-alerting.md#10-component-specific-monitoring) |
| Monitoring | Same |
| Spare pool | A reserve of pre-provisioned, ready-to-deploy replacement devices for meet-day use, per [meet-day-venue-and-offline-operations.md, Section 2](meet-day-venue-and-offline-operations.md#2-venue-operations) |
| Revocation | Immediate, server-side, per [../01-architecture/access-review-and-revocation.md, Section 7](../01-architecture/access-review-and-revocation.md#7-what-can-be-revoked) |
| Loss | Per [../03-security/mobile-device-and-offline-security.md, Section 2](../03-security/mobile-device-and-offline-security.md#2-device-and-scanner-security) |
| Return | A defined process for returning a device from meet-day/venue duty to inventory |
| Wipe | Device data is cleared before reassignment or disposal |
| Disposal | A device reaching end-of-life is disposed of after confirmed wipe, with the disposal itself recorded |
| Audit | Every lifecycle event (registration, assignment, revocation, wipe, disposal) is audit-relevant |

## 2. Service-Account Operations

Extends [../01-architecture/device-and-service-identity-model.md, Sections 5–8](../01-architecture/device-and-service-identity-model.md#5-service-identity-categories) and [../03-security/infrastructure-runtime-and-network-security.md, Section 4](../03-security/infrastructure-runtime-and-network-security.md#4-service-account-security) into an operational lifecycle:

Ownership (a named owning role for every service account) · purpose (documented, specific) · scope (narrowly, per its actual function) · creation (a reviewed, approved action, never ad hoc) · credential issuance (per Section 3) · rotation · review (periodic, per [../01-architecture/access-review-and-revocation.md, Section 1](../01-architecture/access-review-and-revocation.md#1-review-types), "Service-account review") · expiry · revocation · compromise response (per [../01-architecture/device-and-service-identity-model.md, Section 8](../01-architecture/device-and-service-identity-model.md#8-service-compromise)) · environment isolation (restated absolutely — a service account never crosses environment boundaries) · audit.

## 3. Credential Rotation Operations

| Credential Type | Rotation Consideration |
|---|---|
| Device credentials | Rotated on loss/compromise immediately; a scheduled rotation cadence is a candidate future control, not yet fixed |
| Service-account credentials | Rotated on a defined (not-yet-fixed) cadence and immediately on suspected compromise |
| Application secrets | Per [configuration-feature-flag-and-secret-management.md, Section 3](configuration-feature-flag-and-secret-management.md#3-secret-management) |
| Encryption keys | Per [../03-security/cryptography-key-and-secret-management.md, Section 2](../03-security/cryptography-key-and-secret-management.md#2-key-management) |
| Database/Redis/MinIO credentials | Rotatable without extended downtime, per [mysql-redis-minio-and-stateful-service-operations.md](mysql-redis-minio-and-stateful-service-operations.md) |

Every rotation is: planned (not improvised, except in a genuine emergency-rotation scenario) · low-disruption (sequenced to avoid an availability gap — e.g., the old and new credential briefly coexist during a rotation window where the mechanism supports it) · verified (the rotated credential is confirmed working before the old one is fully retired) · audited.

## 4. User Provisioning Operations

Restated from [../03-security/access-review-production-and-support-controls.md](../03-security/access-review-production-and-support-controls.md) and [../01-architecture/assignment-model.md](../01-architecture/assignment-model.md) — account creation, role assignment, and scope/assignment activation follow the approved assignment-model lifecycle, never a direct database edit. Provisioning for a specific meet cycle (committee staff, technical officials) is a recurring operational activity, distinct from one-time platform-administrator provisioning, and is planned ahead of each meet per [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md)'s readiness checklist.

## 5. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably device/service-account credential-rotation cadence (mirrors [../01-architecture/device-and-service-identity-model.md, "Open Questions"](../01-architecture/device-and-service-identity-model.md#9-open-questions)) and spare-device-pool sizing.
