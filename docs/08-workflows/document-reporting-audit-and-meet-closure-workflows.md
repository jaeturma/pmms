# PMMS Document, Reporting, Audit, Media, and Meet-Closure Workflows

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) (WF-22, WF-23) · [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md)

---

## 1. Document Workflow (BC-30)

Upload → scan-status gating → classification → version → access authorization → retrieval → archival.

- Follows the existing 15-stage, security-checkpointed file-upload lifecycle unchanged, per [../03-security/file-object-storage-and-malware-security.md](../03-security/file-object-storage-and-malware-security.md) — a file the malware scanner has not cleared is never presented as downloadable.
- Document metadata (ownership, access, classification) is always authoritative in MySQL, never MinIO directly, restated from [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md).
- A document event (upload, classification change, access grant) is audited identically to any other workflow transition, per [workflow-audit-observability-metrics-and-support.md, Section 1](workflow-audit-observability-metrics-and-support.md#1-workflow-audit).

## 2. Media and Publication Workflow (BC-28 → BC-29)

Draft content → review → privacy check → approval → schedule → publish → correct → unpublish.

- `AnnouncementPublished` is the catalog's existing notification-worthy event.
- Distinguished from the general Publication Workflow (Section, [result-protest-medal-and-publication-workflows.md, Section 4](result-protest-medal-and-publication-workflows.md#4-publication-workflow-cross-cutting)) only in its content type (media/communications content rather than a certified sports record) — the same publication discipline applies: no provisional/held/restricted content is ever published, restated absolutely per working rule 36 (Phase 0.9).
- Privacy check specifically screens for minor-athlete photo/data exposure, per [../03-security/minor-athlete-and-guardian-data-governance.md](../03-security/minor-athlete-and-guardian-data-governance.md) — no automatic public display merely because an image exists, restated from [../06-design/typography-iconography-and-content-style.md](../06-design/typography-iconography-and-content-style.md).
- SOD-09 applies: a Medical role must never combine with Media Coordinator/Public Information Publisher.
- Unpublish/correct follow the same correction-supersedes-never-destructively-overwrites discipline as any other high-integrity workflow.

## 3. Reporting Workflow (BC-33)

Report request → query plan or read-model selection → data retrieval → privacy filter → generation → delivery → audit.

- Reporting and Analytics (BC-33) is never a transactional source of truth, restated from [../01-architecture/reporting-and-read-model-boundaries.md](../01-architecture/reporting-and-read-model-boundaries.md) — a report reflects a point-in-time read projection, never an authoritative record itself.
- Bulk/scheduled report generation uses the `analytics`/`exports` queue categories, per [queue-routing-priority-retry-and-failure-architecture.md, Section 1](queue-routing-priority-retry-and-failure-architecture.md#1-queue-architecture-validated-against-phase-04).
- Export classification is shown before export, with reason capture and server-side redaction, restated from [../06-design/search-filter-import-export-and-file-experience.md, Section 4](../06-design/search-filter-import-export-and-file-experience.md#4-export-experience).
- This workflow is the deterministic counterpart to Phase 0.10's candidate Natural-Language Report Generation capability ([../07-ai/narrative-summary-and-natural-language-reporting.md](../07-ai/narrative-summary-and-natural-language-reporting.md)) — AI, if ever approved, assists query-intent resolution and summary drafting; it never generates arbitrary SQL or accesses an unapproved table.

## 4. Audit Workflow (BC-32)

Audit-event generation → retention → review → export → (no correction — restated absolutely).

**No context, including administrative roles, has the ability to delete or edit an existing audit event through normal operation** — restated absolutely from [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md)'s "Audit History" domain: "this is the one domain where 'no silent mutation' has no exceptions." SOD-08 applies: Audit Viewer must be distinct from Platform/Security/Support Administrator.

## 5. Meet-Closure Workflow (WF-23, BC-04, coordinating BC-30)

Closure readiness check → dependent-workflow completion verification → assignment expiration → credential expiration → archival → audit.

Meet-Closure-to-Assignment-and-Credential-Expiration is a named process manager (per [orchestration-choreography-and-process-manager-architecture.md, Section 1](orchestration-choreography-and-process-manager-architecture.md#1-process-managers)) — a wide fan-out requiring explicit process state, never a single unmonitored event cascade.

| Element | Direction |
|---|---|
| Closure checks | Verifies no open protest, unresolved eligibility case, or pending finance approval remains, before allowing `MeetClosed` |
| Assignment expiration | Uses candidate automation AU-02, per [responsible-automation-and-authority-boundaries.md, Section 3](responsible-automation-and-authority-boundaries.md#3-automation-authority-model) |
| Credential expiration | Uses candidate automation AU-01, same section |
| Reopen rules | Reopening a closed meet is High Risk, restated from [meet-registration-eligibility-and-entry-workflows.md, Section 1](meet-registration-eligibility-and-entry-workflows.md#1-meet-lifecycle-workflow-wf-01-bc-04) |
| Audit | `MeetClosed` is a notification-worthy, fully-audited event per the existing catalog |

## 6. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-30 (whether meet-closure readiness checks are enforced structurally or only advisory-warned in the initial implementation).
