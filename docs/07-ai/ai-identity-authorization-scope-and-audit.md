# PMMS AI Identity, Authorization, Scope, and Audit

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../01-architecture/device-and-service-identity-model.md, Section 7](../01-architecture/device-and-service-identity-model.md#7-ai-service-identity-cross-reference) · [../03-security/audit-and-security-event-architecture.md, Section 7](../03-security/audit-and-security-event-architecture.md#7-ai-assistance-auditing)

This document defines AI service identity, the AI data-access boundary, and AI-specific audit events. **No service identity is created and no database access is granted by this document.**

---

## 1. Requesting User and Service Identity

Every AI request carries two distinct identities, never blended: the **requesting user** (whose authority the request is bound to) and the **AI service identity** (the non-human execution identity the request runs under) — restated absolutely from [../01-architecture/device-and-service-identity-model.md, Section 7](../01-architecture/device-and-service-identity-model.md#7-ai-service-identity-cross-reference): "an AI feature's effective permissions are the *intersection* of what the AI service is technically capable of and what the requesting user is authorized to see/do, never a union."

## 2. AI Service Identity

Dedicated service identity (per capability, or per capability-family) · capability-specific permissions (never broader than the specific use case requires) · **no human role** — restated absolutely, an AI service identity is never granted a role designed for human assignment · **no database administrator access** — restated absolutely · **no unrestricted object-storage access** — restated absolutely · environment separation (an AI service identity never crosses Local/Staging/Production boundaries) · credential rotation · revocation · audit · ownership (a named owning role, per [ai-vision-principles-and-governance.md, Section 8](ai-vision-principles-and-governance.md#8-feature-ownership)).

This restates and extends [../01-architecture/device-and-service-identity-model.md, Sections 5–8](../01-architecture/device-and-service-identity-model.md#5-service-identity-categories) and [../03-security/infrastructure-runtime-and-network-security.md, Section 4](../03-security/infrastructure-runtime-and-network-security.md#4-service-account-security) — not redefined here, only made AI-specific.

## 3. AI Data Access

AI requests use: explicit user identity · effective role · assignment · organization scope · meet scope · committee, delegation, sport, venue, or event scope · data classification · purpose · feature permission.

**AI services receive only the necessary records or approved projections** — restated absolutely per this phase's own governing instruction; an AI capability queries through the same scoped, authorization-checked data-access path as any other application feature, never a broad or unscoped database credential.

## 4. AI Permissions and Scopes

Extending [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md) and [../01-architecture/scope-model.md](../01-architecture/scope-model.md): every AI capability's data access is expressed as a specific, named permission (e.g., `ai.eligibility-review.assist`), scoped exactly like any human-facing permission — never a blanket "AI can read everything" grant. AI retrieval respects organization, meet, role, assignment, scope, and classification — restated absolutely per working rule 21.

## 5. Impersonation Restrictions

An AI capability never impersonates a user — restated absolutely; every AI request is clearly attributed to both the requesting user and the AI service identity (Section 1), never presented as if the AI itself were a human user with independent standing authority.

## 6. Public AI

A public-facing AI capability (per [mobile-offline-and-public-ai-boundaries.md, "Public AI"](mobile-offline-and-public-ai-boundaries.md#3-public-ai)) uses a dedicated, narrowly-scoped service identity limited to Public-tier data and knowledge sources only — never the same service identity used for an internal, authenticated-user-facing capability.

## 7. Queue Execution

Where an AI request is processed asynchronously (e.g., a background narrative-generation job), the queued job re-validates the initiating user's authorization at execution time — restated from [../01-architecture/event-and-queue-architecture.md, Section 2](../01-architecture/event-and-queue-architecture.md#2-job-rules), never trusting a payload's embedded authorization state as still current.

## 8. AI Audit Events

Recorded conceptually for every AI request: requesting user · effective role and scope · capability · purpose · model · prompt version · knowledge-source versions · data categories accessed · input hash or safe reference (never the full sensitive prompt, per [ai-security-privacy-and-data-minimization.md, Section 8](ai-security-privacy-and-data-minimization.md#8-ai-data-retention-prompt-and-response-retention)) · output reference · confidence category · reviewer · accepted/edited/rejected/escalated disposition · resulting authorized action · cost · latency · error · safety event.

This extends, and does not redefine, [../03-security/audit-and-security-event-architecture.md, Section 7](../03-security/audit-and-security-event-architecture.md#7-ai-assistance-auditing) — every AI-assisted action still records the requesting user (whose authority it's bound to), the AI service identity, model/prompt version, and the ordinary Command/Application-layer audit trail of whatever action resulted.

## 9. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably whether each of the 13 use cases receives its own dedicated service identity or a shared identity scoped per-request, and the specific input-hash mechanism for safe audit reference.
