# PMMS Object Metadata and File Lifecycle

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [../01-architecture/object-storage-and-document-runtime.md](../01-architecture/object-storage-and-document-runtime.md) · [information-classification-and-privacy.md](information-classification-and-privacy.md) · [retention-archival-and-disposal.md](retention-archival-and-disposal.md)

This document defines the authoritative MySQL metadata model for MinIO-stored objects, extending [object-storage-and-document-runtime.md](../01-architecture/object-storage-and-document-runtime.md) (Phase 0.4 runtime flow) with persistence-specific detail. **No physical table is created here.**

---

## 1. Authoritative Metadata Concepts

| Concept | Purpose |
|---|---|
| Object identifier | The `DocumentRecord`'s own public ID (per [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md)) |
| Storage provider | Which object-storage backend holds the content (MinIO, for all current cases) |
| Bucket or storage class | Which bucket/prefix, informed by classification (Section 3 of [../01-architecture/object-storage-and-document-runtime.md](../01-architecture/object-storage-and-document-runtime.md)) |
| Object key | The opaque storage-layer key — never containing sensitive personal data, per [../01-architecture/object-storage-and-document-runtime.md, Section 3](../01-architecture/object-storage-and-document-runtime.md#3-rules) |
| Original filename | As uploaded, retained for display/download purposes |
| Stored filename | The internal, opaque name actually used in storage — may differ from the original for collision-safety |
| MIME type | Validated at upload |
| Size | In bytes, validated against category-appropriate limits |
| Checksum | For integrity verification and duplicate/orphan detection |
| Classification | One of the five tiers, per [information-classification-and-privacy.md](information-classification-and-privacy.md) — inherited from the document category, not independently chosen per upload |
| Owning context | Which bounded context's workflow produced this document |
| Owning aggregate type | The specific aggregate type this document evidences (e.g., `EligibilityCase`, `OfficialResult`) — an enumerated, versioned value per [identifier-and-reference-strategy.md, Section 5](identifier-and-reference-strategy.md#5-foreign-keys-vs-application-validated-references), never an open string |
| Owning aggregate ID | The specific record's public ID |
| Uploaded by | The actor (User Account) who performed the upload |
| Uploaded at | UTC timestamp |
| Source device | Where relevant (mobile-originated uploads) |
| Version | Per [temporal-history-and-versioning-model.md, Section 4](temporal-history-and-versioning-model.md#4-versioning-and-supersession) |
| Retention category | Per [retention-archival-and-disposal.md](retention-archival-and-disposal.md) |
| Legal hold | A boolean/flag preventing deletion even if retention policy would otherwise permit it |
| Scan status | Whether malware/content scanning (per [../01-architecture/object-storage-and-document-runtime.md, Section 2](../01-architecture/object-storage-and-document-runtime.md#2-document-flow), Step 5) has completed and its result — if this capability is included, per [../01-architecture/runtime-open-decisions.md, RD-03](../01-architecture/runtime-open-decisions.md#rd-03--malwarecontent-scanning-for-uploads) |
| Encryption status | Whether the object is encrypted at rest |
| Public status | Whether this specific document has been approved for public exposure (default: no) |
| Archived status | Whether the document has moved to archival storage (per [retention-archival-and-disposal.md](retention-archival-and-disposal.md)) |
| Deleted status | Distinguished from archived — a document may be soft-deletable if its category permits (Section 2) |
| Access policy | Which roles/scopes may access this specific document — usually inherited from classification + owning context, occasionally overridden |
| Last verified time | When metadata-to-object reconciliation (Section 4) last confirmed this object still exists and matches its recorded checksum |

## 2. Deletion Behavior by Document Category

Most document categories follow the same discipline as their owning high-integrity aggregate (per [temporal-history-and-versioning-model.md, Section 2](temporal-history-and-versioning-model.md#2-soft-deletion)):

| Category | Deletion Behavior |
|---|---|
| Eligibility evidence | Never deleted while the case or any retention/legal-hold requirement is active; archived per policy |
| Official result documents | Never deleted |
| Medical attachments | Never deleted while retention/legal-hold active; Highly Restricted access always |
| Financial supporting documents | Never deleted while retention/legal-hold active |
| Public media (approved) | May be replaced (new version), rarely hard-deleted |
| Temporary uploads (pre-finalization) | Deletable — a draft upload abandoned before its owning workflow completes is cleaned up by a scheduled `maintenance` job (per [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md)) |
| Import/export files | Deletable per their own retention window (Section, [retention-archival-and-disposal.md](retention-archival-and-disposal.md)) |

## 3. Rules (Restated and Extended from Phase 0.4)

- Object keys never contain sensitive personal data.
- File ownership is controlled in MySQL, not MinIO — the object store has no concept of PMMS's authorization model.
- Direct URLs are never authoritative — any URL a client receives is either a signed, time-boxed URL or a proxied download, both gated by the authorization check at generation time (per [../01-architecture/object-storage-and-document-runtime.md, Section 3](../01-architecture/object-storage-and-document-runtime.md#3-rules)).
- Signed URLs are temporary — a URL's validity window is short and does not survive a subsequent authorization revocation for the requester.
- Replaced documents preserve history — a version chain, not an overwrite (Section 1, "Version").
- Object deletion respects retention — a document metadata record's deletion is never processed without first checking its retention category and legal-hold flag.
- **Metadata and object reconciliation is supported** — a scheduled process (per the `maintenance` queue category) periodically verifies that every `DocumentRecord` metadata row has a corresponding, checksum-matching object in MinIO.
- **Orphan-object detection is required** — an object present in MinIO with no corresponding metadata row (e.g., from an interrupted upload) is flagged for cleanup, never silently ignored or silently trusted.
- **Missing-object detection is required** — a metadata row whose corresponding object cannot be found in MinIO is flagged as a data-integrity incident, escalated per [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md), not silently served as a broken link.

## 4. Reconciliation Process (Conceptual)

1. Enumerate `DocumentRecord` rows not yet verified within the expected verification window.
2. For each, confirm the corresponding MinIO object exists and its checksum matches the recorded value.
3. Update `last_verified_at` on success.
4. Flag discrepancies (missing object, checksum mismatch) for review — never auto-correct a checksum mismatch by silently re-trusting the object's current state, since that could mask tampering or corruption.
5. Separately, enumerate MinIO objects (within managed prefixes) with no matching metadata row and flag as orphans for scheduled cleanup after a grace period.

This is a conceptual process description, not an implementation — the specific job/schedule design is a Phase 0.6+ concern.

## 5. Open Questions

- Reconciliation frequency and orphan-cleanup grace period — implementation-phase tuning.
- Whether checksum verification uses a cryptographic hash (e.g., SHA-256) or a faster non-cryptographic checksum, given the integrity-vs-performance tradeoff at scale.

Tracked in [data-open-decisions.md](data-open-decisions.md).
