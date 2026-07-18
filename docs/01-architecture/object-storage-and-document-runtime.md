# PMMS Object Storage and Document Runtime

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [bounded-context-catalog.md, BC-30](bounded-context-catalog.md#bc-30--document-and-records) · [high-integrity-access-controls.md](high-integrity-access-controls.md) · [runtime-security-architecture.md](runtime-security-architecture.md)

This document defines MinIO's role and the document lifecycle. **No storage disk configuration, bucket policy, or file-handling code is created here.** The repository's `config/filesystems.php` already defines a `local`, `public`, and `s3` disk (the `s3` disk configuration is MinIO-compatible via its `endpoint`/`use_path_style_endpoint` options, but is not yet populated with MinIO credentials) — this document defines how that disk is used once configured, not the configuration itself.

---

## 1. MinIO Responsibilities

Eligibility documents, accreditation assets, athlete/official profile images, committee documents, medical attachments (where approved), financial supporting documents, official result documents (generated), generated reports, media assets, import files, export files, archives.

**MinIO is infrastructure, not a bounded context** — per [bounded-context-catalog.md, BC-30](bounded-context-catalog.md#bc-30--document-and-records), Document and Records owns file *metadata*, classification, retention, and access authorization; MinIO is the storage engine that metadata points to.

## 2. Document Flow

1. **Upload authorization** — the Application layer authorizes the upload attempt (per [authorization-decision-model.md](authorization-decision-model.md)) before any bytes move — e.g., only a role with `document.upload` in the relevant context/scope may initiate.
2. **Metadata creation** — a Document and Records (BC-30) metadata record is created first, in a `Pending` state, establishing ownership before the object exists.
3. **Temporary object upload** — the file is uploaded to a temporary/staging location (a distinct MinIO prefix or bucket from permanent storage), never directly to its final classified location.
4. **File validation** — type, size, and structural validation against the expected document category.
5. **Malware or content scanning where available** — an evaluated, not assumed, capability (see [runtime-open-decisions.md](runtime-open-decisions.md) for whether a scanning service is in scope).
6. **Classification** — the document is assigned a data classification (Public/Internal/Confidential/Restricted/Highly Restricted, per [phase-0.3-access-and-assignment-architecture.md, Section 21](phase-0.3-access-and-assignment-architecture.md#21-data-classification-model)), inherited from its category (e.g., eligibility evidence is always Restricted).
7. **Permanent object placement** — moved from staging to its permanent, classification-appropriate storage location.
8. **Versioning** — a replacement upload creates a new version, never overwrites the prior object in place (mirrors the "correction instead of destructive overwrite" principle from [high-integrity-domain-rules.md](high-integrity-domain-rules.md)).
9. **Access policy assignment** — the metadata record's classification and owning context determine who may subsequently read it.
10. **Retention assignment** — per [domain-open-decisions.md, DD-23](domain-open-decisions.md#dd-23--document-retention-ownership), BC-30 enforces retention mechanics against policy parameters it does not itself author.
11. **Audit recording** — upload is logged (actor, timestamp, document category) at the audit level appropriate to its classification.
12. **Download authorization** — every subsequent read re-evaluates authorization; a document's classification does not become "already seen, therefore free to re-access without a check."
13. **Expiry or archival** — governed by retention policy (Section 10 above), executed as a scheduled maintenance process (per `maintenance` queue category in [event-and-queue-architecture.md](event-and-queue-architecture.md)).

## 3. Rules

- **Database metadata remains authoritative for ownership and access** — MinIO itself has no concept of PMMS's role/scope/assignment model; every access decision is made by the Laravel application before a signed URL or proxied download is issued.
- **Object keys must not expose sensitive personal data** — a key is an opaque identifier (e.g., a UUID-based path), never a participant's name or eligibility case detail embedded in the storage path.
- **Direct permanent public buckets are avoided for protected files** — only genuinely Public-classified assets (e.g., an approved public announcement image) may live in a bucket/prefix with any form of direct public access; everything else requires authorization on every access.
- **Signed temporary URLs may be used where appropriate** — for time-boxed, authorization-checked download access, particularly for larger files where proxying through the Laravel application itself would be wasteful; the signing step itself only occurs after the authorization check in Section 2, Step 12.
- **Upload completion must be verified server-side** — the Application layer confirms the object actually landed in MinIO (e.g., via a completion callback or explicit verification call) before marking the metadata record `Complete`; a client-reported "done" is never trusted alone.
- **Document replacement preserves version history** (Section 2, Step 8) — never a destructive overwrite.
- **Sensitive downloads are audited** — any Restricted/Highly Restricted document download is logged at Critical/Elevated audit level, consistent with [permission-catalog.md](permission-catalog.md).
- **Public media and restricted evidence use separate policies** — a public announcement's image asset and an eligibility case's supporting document are never stored under the same access-policy umbrella, even if both technically live in MinIO.
- **Deleted business records must not automatically destroy required retained files** — e.g., if a participant's eligibility case is superseded, the underlying evidence document is retained per policy (Section 10), not deleted merely because the case that referenced it moved to a new state.

## 4. Relationship to High-Integrity Domains

Documents feeding Eligibility (BC-09) evidence, Official Results (BC-16) certificates, Medical Operations (BC-21) attachments, and Finance Operations (BC-26) supporting records all carry the same integrity discipline as their owning domain — a document upload is not a lower-trust side channel around the safeguards in [high-integrity-access-controls.md](high-integrity-access-controls.md); it is subject to the same access, audit, and correction-not-overwrite rules.

## 5. Open Questions

- Whether a malware/content-scanning capability is included in the initial implementation, or deferred (Section 2, Step 5).
- Signed-URL vs. proxied-download default for each document category — an implementation-phase performance/security tradeoff.
- Specific staging-to-permanent transition timing (immediate vs. batch-processed) — implementation-phase detail.

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md).
