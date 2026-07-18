# PMMS Search, Filter, Import, Export, and File Experience

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../02-data/import-export-and-data-exchange.md](../02-data/import-export-and-data-exchange.md) · [../03-security/data-sharing-export-and-public-disclosure-controls.md](../03-security/data-sharing-export-and-public-disclosure-controls.md) · [../02-data/public-reporting-and-projection-data.md, Section 3](../02-data/public-reporting-and-projection-data.md#3-search-indexes)

This document defines search, filter, import, export, and file-handling interface patterns. **No search implementation, import/export tooling, or file-storage component is created here.**

---

## 1. Search Experience

Global search · context search · participant search · athlete search · schedule search · result search · document search · public search.

### Requirements

Scope-aware results (a search never returns results outside the searcher's authorized scope) · privacy-aware results (restated absolutely — a search result never reveals the existence of a Restricted/Highly Restricted record to an unauthorized searcher, not even its existence) · typo tolerance readiness (a candidate future capability, per the staged search-index approach in [../02-data/public-reporting-and-projection-data.md, Section 3](../02-data/public-reporting-and-projection-data.md#3-search-indexes)) · filters (Section 2) · recent searches · keyboard access · empty state (per [status-feedback-error-offline-and-sync-patterns.md, Section 3](status-feedback-error-offline-and-sync-patterns.md#3-empty-states)) · loading state · **no leakage through suggestions** — restated absolutely, autocomplete/suggestion behavior never surfaces a record the searcher isn't authorized to see, even partially · clear result type and context (a search result always indicates what kind of record it is and where it lives in the information architecture).

## 2. Filter Experience

Visible active filters · clear all · saved views (Section 5 below) · URL persistence where appropriate (a filtered view is bookmarkable/shareable within the viewer's own authorized scope) · role-specific defaults · meet context (filters always respect the active meet/organization scope) · date ranges · status · sport · venue · delegation · committee.

**Avoid hidden filters that cause unexplained missing data** — restated absolutely; a user who can't find an expected record because of an active filter always sees that filter's effect clearly, never silently.

## 3. Import Experience

Template selection · template version (per [../02-data/test-seed-and-reference-data-strategy.md, Section 1](../02-data/test-seed-and-reference-data-strategy.md#1-reference-and-seed-data-classification)) · file upload · validation · preview · error summary · row-level errors · duplicate warnings (per [../02-data/identity-resolution-and-duplicate-management.md](../02-data/identity-resolution-and-duplicate-management.md)) · approval · commit · progress · result summary · downloadable error report · audit reference — restated and given interface expression from [../02-data/import-export-and-data-exchange.md, Section 1](../02-data/import-export-and-data-exchange.md#1-import-architecture)'s 14-stage lifecycle.

## 4. Export Experience

Export purpose · format · filters · data classification (visibly shown before the export proceeds, per [privacy-security-and-sensitive-data-experience.md](privacy-security-and-sensitive-data-experience.md)) · reason (captured where required) · approval where needed · estimated size · background generation (per [status-feedback-error-offline-and-sync-patterns.md, Section 4](status-feedback-error-offline-and-sync-patterns.md#4-loading-states-and-skeletons)) · progress · expiry (per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md)) · download · audit · redaction (applied server-side before the file is generated, never a client-side afterthought) · watermarking readiness (a candidate future control, per [../03-security/data-sharing-export-and-public-disclosure-controls.md, "Export Controls"](../03-security/data-sharing-export-and-public-disclosure-controls.md#export-controls), not committed to a specific implementation).

## 5. File Experience

Upload · progress · scan status (per [../03-security/file-object-storage-and-malware-security.md, Section 3](../03-security/file-object-storage-and-malware-security.md#3-malware-scanning-architecture) — a file the malware scanner hasn't cleared is never presented as available for download) · classification · version · preview · download · replace · archive · retention · access restrictions · missing object (per [../02-data/object-metadata-and-file-lifecycle.md, Section 4](../02-data/object-metadata-and-file-lifecycle.md#4-reconciliation-process-conceptual)) · failed scan · rejected file.

**Never expose raw MinIO object paths** — restated absolutely per [../03-security/infrastructure-runtime-and-network-security.md, Section 3](../03-security/infrastructure-runtime-and-network-security.md#3-minio-and-object-storage-security); every file interaction goes through a signed, time-boxed, server-mediated URL, never a direct object reference visible to the user.

## 6. Saved Views and Filter Persistence

A saved view (a named, reusable combination of filters/sort/columns) is a personalization layer on top of the shared filter architecture (Section 2) — per-user, never altering what data another user with the same view name would see, since authorization scope always applies independently of any saved view.

## 7. Pagination

Every list/table view is paginated (restated from [dashboard-table-chart-and-data-visualization-standards.md, Section 1](dashboard-table-chart-and-data-visualization-standards.md#1-table-and-data-grid-standards)) — no unbounded result set is ever rendered at once, both for performance and for comprehension.

## 8. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably search-index technology/typo-tolerance adoption timing (mirrors the Phase 0.4/0.5 staged-search-approach) and watermarking-mechanism selection.
