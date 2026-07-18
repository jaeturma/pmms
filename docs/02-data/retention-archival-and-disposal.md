# PMMS Retention, Archival, and Disposal

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [high-integrity-data-model.md](high-integrity-data-model.md) · [../00-product/open-decisions.md, OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements) · [../01-architecture/domain-open-decisions.md, DD-23](../01-architecture/domain-open-decisions.md#dd-23--document-retention-ownership)

This document defines retention categories, archival processes, and meet-closure historical preservation. **No retention period is invented** — every numeric placeholder below is explicitly marked as pending DepEd records-management/legal authority, consistent with [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements).

---

## 1. Retention Categories

| Category | Business Purpose | Retention Authority (TBD) | Minimum Retention | Maximum Retention | Archival Requirement | Deletion Method | Legal/Operational Hold | Access After Archival | Review Owner | Validation Status |
|---|---|---|---|---|---|---|---|---|---|---|
| Temporary uploads | Staging before permanent placement | Software architect | N/A | Short (cleanup window) | No | Automated cleanup job | No | N/A | ICT Committee | Recommended |
| Draft records | Pre-submission working data | Owning context | N/A | Until submission or abandonment | No | Soft-delete / cleanup | No | N/A | Owning context | Recommended |
| Operational records (logistics, ICT, media) | Day-to-day meet operations | To be identified | **Placeholder** | **Placeholder** | Optional (per meet closure) | Standard | Rare | Standard | Committee heads | Requires DepEd input |
| Official meet records (results, medal tally, accreditation) | Institutional/competitive record | DepEd Leadership | **Placeholder — likely long/permanent** | **Placeholder** | Yes, mandatory | N/A (effectively permanent) | Possible (disputes) | Full, per authorization | To be identified | **Blocking — OD-24** |
| Financial records | Institutional accountability | DepEd Finance / Leadership | **Placeholder** | **Placeholder** | Yes | Per approved schedule | Possible (audit) | Restricted | Finance Committee | Requires DepEd input |
| Medical records | Health/legal accountability | Data Privacy and Legal Stakeholders | **Placeholder** | **Placeholder** | Yes | Per approved, legally-informed schedule | Possible (legal) | Highly Restricted | Medical Team lead | **Blocking — OD-15** |
| Security records | Incident accountability | Security Administrator | **Placeholder** | **Placeholder** | Yes | Per approved schedule | Possible (investigation) | Restricted | Security Committee | Requires DepEd input |
| Audit records | Institutional accountability, the longest-lived category | DepEd Leadership | **Placeholder — likely the longest of any category** | **Placeholder** | Yes | N/A (effectively permanent) | Rare, but possible | Highly Restricted | Auditors | **Blocking — OD-24** |
| Public records (published, post-archival) | Public transparency, historical | DepEd Leadership | **Placeholder** | **Placeholder** | Yes | N/A | Rare | Public (as originally published) | To be identified | Requires DepEd input |
| Generated reports | Operational and institutional reference | Reporting owner | **Placeholder** | **Placeholder** | Optional | Standard | Rare | Standard | Reporting and Analytics owner | Requires DepEd input |
| Backups | Disaster recovery | ICT Committee | Per [backup-restore-and-data-recovery.md](backup-restore-and-data-recovery.md) | Per that document | N/A | Automated rotation | Possible | Restricted | ICT Committee | Requires policy input |
| Import files | Migration/reconciliation evidence | Import owner | **Placeholder** | **Placeholder** | Optional | Standard | Rare | Restricted | Secretariat / ICT | Requires DepEd input |
| Export files | Time-boxed deliverables | Export requester's context | Short (expiry-based, per [import-export-and-data-exchange.md](import-export-and-data-exchange.md)) | Short | No | Automated expiry | Rare | N/A after expiry | Owning context | Recommended |
| Logs (operational, non-audit) | Diagnostics | ICT Committee | **Placeholder** | **Placeholder** | No | Automated rotation | Rare | Restricted | ICT Committee | Requires policy input |
| Cache and transient data | Performance | N/A (Redis, never authoritative) | N/A | N/A (ephemeral by design) | No | N/A | No | N/A | N/A | N/A |

**Every "Placeholder" above is a genuine gap requiring DepEd records-management and, for Medical/Financial/Audit categories, legal input before Phase 0.6 can finalize retention-driven physical schema decisions (e.g., partitioning strategy, per [indexing-performance-and-capacity.md](indexing-performance-and-capacity.md)).**

## 2. Archiving

- **Meet closure archive package** — Section 3 below.
- **Historical read-only mode** — an archived meet's data remains queryable but is no longer a target for ordinary write operations; any correction to archived data follows the exceptional data-repair path in [high-integrity-data-model.md, "Data Correction Architecture"](high-integrity-data-model.md#data-correction-architecture).
- **Database archival** — high-volume, aged operational tables (e.g., old Access Scans, old Notifications) are candidates for partitioning/cold-storage treatment once volume justifies it, per [indexing-performance-and-capacity.md](indexing-performance-and-capacity.md) — not implemented now.
- **Document archival** — per [object-metadata-and-file-lifecycle.md](object-metadata-and-file-lifecycle.md), a `DocumentRecord`'s `archived_status` flag, with the underlying MinIO object optionally moved to a lower-cost storage class.
- **Projection archival** — public/reporting projections for a closed meet are frozen (no further recalculation), consistent with "historical reports must remain reproducible even if master data changes later" (Section 3).
- **Media archival** — per document archival, applied to media assets specifically.
- **Audit preservation** — never archived out of active retrievability in the same way operational data might be; audit records remain fully queryable for their entire (likely very long) retention period.
- **Data disposal approval** — any actual deletion (as opposed to archival) of a retention-expired record requires an explicit approval step, logged as its own audit event.
- **Deletion evidence** — when a record's retention period genuinely expires and deletion is approved, the fact of deletion (what was deleted, when, under what authority) is itself retained as an audit record, even though the deleted content is gone.
- **Backup-expiry considerations** — per [backup-restore-and-data-recovery.md](backup-restore-and-data-recovery.md).
- **Orphan cleanup** — per [object-metadata-and-file-lifecycle.md, Section 4](object-metadata-and-file-lifecycle.md#4-reconciliation-process-conceptual).
- **Revoked-account handling** — a revoked User Account's historical attribution (who did what) is preserved per [../01-architecture/access-review-and-revocation.md, Section 13](../01-architecture/access-review-and-revocation.md#13-record-ownership-continuity) — revocation never triggers deletion of the account's historical footprint.
- **De-identified analytical data** — future cross-meet analytics (per [Phase 0.1 product-scope.md](../00-product/product-scope.md#6-future-scope-capabilities)) may use de-identified aggregate extracts rather than raw historical PII, evaluated when that capability is actually pursued.
- **Legal and operational holds** — a hold flag (per [object-metadata-and-file-lifecycle.md](object-metadata-and-file-lifecycle.md)) overrides any retention-driven deletion schedule; a record under hold is never deleted regardless of its category's normal retention expiry.
- **Rehydration requirements** — an archived meet's data can be "rehydrated" (returned to normal query performance/visibility) if a legitimate need arises (e.g., a dispute resurfacing years later) — archival is a performance/organization optimization, never a barrier to legitimate future access.

## 3. Meet Closure and Historical Preservation

At meet closure (`MeetClosed`/`MeetArchived`, per [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md)), the following are preserved as a coherent, permanent record:

Meet configuration · organization and delegation snapshots · participant participation records · eligibility decisions · competition entries · tournament structures · final schedules · official assignments · scores and corrections · certified results · protest decisions · medal awards and certified tally · accreditation issuance · access records where retained · medical summaries subject to privacy rules · committee reports · financial summaries · official documents · publication history · audit events · application and rule-set versions where material.

**Historical reports must remain reproducible even if master data changes later.** This is the persistence-layer requirement behind every versioning rule in [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md) — a report generated from the 2026 Provincial Meet's data must produce the same figures whether run in 2026 or 2036, even if a participant's name is later corrected, a sport definition is later revised, or an organization is later renamed. This is achieved by every historical reference pointing at a specific *version* (per Section 4 of that document), never a live "current value" lookup.

## 4. Open Questions

Every "Placeholder" retention period in Section 1 is an open question requiring DepEd records-management and (for Medical/Financial/Audit) legal input — collectively tracked in [data-open-decisions.md](data-open-decisions.md), cross-referencing [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements).
