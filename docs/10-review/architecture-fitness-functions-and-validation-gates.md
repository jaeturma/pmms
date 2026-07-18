# PMMS Architecture Fitness Functions and Validation Gates

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [implementation-readiness-assessment.md](implementation-readiness-assessment.md)

Candidate automated checks for future implementation, restated and consolidated from every phase's own boundary rules. **No executable test code is created here** — every item below is a specification for a future fitness function, not a script.

---

## 1. Domain Boundaries

- Bounded contexts do not write each other's tables (no cross-context ORM relationship or direct SQL write).
- Every table has exactly one owning bounded context, matching [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md).

## 2. Authorization

- Every protected action's authorization check includes role, permission, scope, AND assignment validity — never role alone.
- Tenant-owned records have explicit tenant ownership present in every query touching them.
- No frontend-only authorization check exists without an equivalent server-side enforcement.

## 3. Tenant Isolation

- Tenant context is present in job payloads, event metadata, cache keys, and object-storage keys for every tenant-owned record.
- No cross-tenant data is returned from any query without explicit, audited cross-tenant authorization.

## 4. Data Ownership and Integrity

- High-integrity transitions (per [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md)) generate an audit-evidence record.
- Certified records are never silently edited — every mutation to a certified/published record is a new version, correction, or supersession, never an in-place update.
- No high-integrity table has a `deleted_at` (soft-delete) column.

## 5. Public Data

- Public queries use only approved projections (BC-29), never operational tables directly.
- No protected data (medical, eligibility, finance, audit, authentication) enters a public cache or CDN edge cache.

## 6. Redis and Queue Reliability

- Redis loss does not lose authoritative state (verified by confirming no table's sole copy of data lives in Redis).
- Queue jobs are idempotent (verified by a duplicate-dispatch test producing the same end state).
- Reverb clients recover authoritative state via ordinary query after reconnect, never relying on broadcast replay.

## 7. AI Authority

- AI cannot write an authoritative decision directly — every AI-touching code path routes through the ordinary Command/Application-layer path with human confirmation.
- No AI service identity holds a standing, unrestricted database credential.

## 8. Offline Authority

- Offline submissions require server-side validation before being treated as final.
- None of the eight never-final-offline actions (eligibility approval, result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, high-risk overrides) can complete while a client is offline.

## 9. Object Storage

- Protected MinIO objects require metadata-layer authorization before any signed URL is issued.
- No direct, permanent MinIO URL is ever exposed to a client for Restricted-or-above content.

## 10. Privacy

- No minor-athlete personal data appears in application logs.
- No authentication credential (password, token, MFA secret) is ever sent to an AI service.

## 11. Accessibility

- Every interactive component has a keyboard-accessible equivalent (no hover-only interaction).
- Every status/state indicator uses more than color alone to convey meaning.

## 12. Backup and Recovery

- A scheduled backup-verification job confirms the most recent backup is restorable (once backups exist).
- A DR failover event, if it occurs, is itself an auditable operational event.

## 13. Performance (Readiness Only)

- Public traffic and critical-workload capacity are isolated at the queue/worker-pool level (verified structurally, not by load test, until load-test infrastructure exists).

## 14. Release Gates

- No workflow, automation entry, or AI capability reaches production without passing its full release-gate checklist (per [../08-workflows/workflow-incident-change-and-release-governance.md, Section 3](../08-workflows/workflow-incident-change-and-release-governance.md#3-workflow-and-automation-release-gates) and [../07-ai/ai-incident-response-change-and-release-governance.md, Section 3](../07-ai/ai-incident-response-change-and-release-governance.md#3-ai-release-gates)).

## 15. Adoption Sequencing

These fitness functions are candidates for Phase 0.14's CI pipeline, prioritized in the order: Sections 4, 2, 3 (data integrity, authorization, tenant isolation — the highest-consequence checks) before Sections 5–14 (lower-consequence or infrastructure-dependent checks).

## 16. Open Questions

Tooling selection for automated architectural-boundary enforcement (e.g., deptrac, PHPStan custom rules, or a similar static-analysis approach for Laravel) remains undecided — tracked as a new Phase 0.14 preparation item, not a Phase 0.13 open decision.
