# EPIC-08 — File, Document, and Object Storage Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release C
**Status:** Planned — Not Started

## Purpose

Establish safe file handling and MinIO-compatible object storage (S3-compatible, per the repository's existing `filesystems.php` `s3` disk) without implementing all domain document workflows.

## Architecture Sources

[../../../../02-data/](../../../../02-data/) (object-metadata rules), [../../../../03-security/](../../../../03-security/) (file-safety rules), ADR-0005, ADR-0006.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-08-01](WP-08-01-storage-configuration-abstraction.md) | Storage Configuration Abstraction | Medium | P1 |
| [WP-08-02](WP-08-02-file-metadata-model-foundation.md) | File Metadata Model Foundation | Medium | P1 |
| [WP-08-03](WP-08-03-temporary-upload-foundation.md) | Temporary Upload Foundation | Medium | P1 |
| [WP-08-04](WP-08-04-file-validation-and-classification.md) | File Validation and Classification | Medium | P1 |
| [WP-08-05](WP-08-05-object-key-and-tenant-ready-ownership-conventions.md) | Object Key and Tenant-Ready Ownership Conventions | Medium | P1 |
| [WP-08-06](WP-08-06-authorized-download-and-signed-access.md) | Authorized Download and Signed Access | Medium | P1 |
| [WP-08-07](WP-08-07-file-version-and-replacement-foundation.md) | File Version and Replacement Foundation | Medium | P2 |
| [WP-08-08](WP-08-08-quarantine-and-malware-scan-readiness.md) | Quarantine and Malware-Scan Readiness | Medium | P2 |
| [WP-08-09](WP-08-09-missing-and-orphan-object-reconciliation-readiness.md) | Missing and Orphan Object Reconciliation Readiness | Small | P2 |
| [WP-08-10](WP-08-10-file-audit-events.md) | File Audit Events | Small | P1 |
| [WP-08-11](WP-08-11-file-security-and-integration-tests.md) | File Security and Integration Tests | Medium | P2 |

## Dependencies

WP-01-06 (Hard), WP-02-05 (Hard), WP-05-07 (Hard, for WP-08-06 only).

## Completion Outcome

Storage-configuration abstraction (local/S3/MinIO-compatible), file-metadata model, temporary upload, validation/classification, tenant-ready object-key conventions, authorized/signed download, versioning, quarantine readiness, and reconciliation readiness.

## Deferred Items

Selection and integration of a specific malware scanner (WP-08-08 is readiness only).

## Risks

RISK-EPIC08-01 — the MinIO object-content vs. MySQL-metadata ownership rule being violated by treating object storage as authoritative for anything but content.
